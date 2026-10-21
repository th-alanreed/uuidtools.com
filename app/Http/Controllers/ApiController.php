<?php

namespace App\Http\Controllers;

use App\Uuid\UuidFormatter;
use App\Uuid\UuidGenerator;
use App\Uuid\UuidInspector;
use App\Uuid\UuidVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use InvalidArgumentException;

class ApiController extends Controller
{
    public function __construct(
        private readonly UuidGenerator $generator,
        private readonly UuidInspector $inspector,
    ) {}

    /**
     * GET /api/generate/{version}[/count/{count}]?count=&namespace=&name=&format=&upper=…
     */
    public function generate(Request $request, string $version, ?string $count = null): Response|JsonResponse
    {
        $uuidVersion = UuidVersion::tryFrom(strtolower($version));

        if ($uuidVersion === null) {
            return $this->error('version', 'Unknown version. Use one of: '.implode(', ', array_column(UuidVersion::cases(), 'value')).'.', 404);
        }

        if ($uuidVersion->isNameBased()) {
            return $this->nameBased($request, $uuidVersion, $request->query('namespace'), $request->query('name'));
        }

        $count ??= $request->query('count', '1');
        $max = config('uuidtools.max_count');

        if (! is_string($count) || ! ctype_digit($count) || (int) $count < 1 || (int) $count > $max) {
            return $this->error('count', "The count must be a whole number between 1 and {$max}.");
        }

        return $this->respond($request, $this->generator->generate($uuidVersion, (int) $count));
    }

    /**
     * GET /api/generate/{v3|v5}/namespace/{namespace}/name/{name}
     *
     * A name prefixed with "base64:" is base64-decoded first (issue #30), which lets
     * clients send any bytes, e.g. UTF-8 text containing "/" or "?".
     */
    public function nameBasedPath(Request $request, string $version, string $namespace, string $name): Response|JsonResponse
    {
        $uuidVersion = UuidVersion::tryFrom(strtolower($version));

        if ($uuidVersion === null || ! $uuidVersion->isNameBased()) {
            return $this->error('version', 'Only v3 and v5 UUIDs take a namespace and name.', 404);
        }

        return $this->nameBased($request, $uuidVersion, $namespace, $name);
    }

    /**
     * GET /api/decode/{uuid}
     */
    public function decode(string $uuid): JsonResponse
    {
        $result = $this->inspector->inspect($uuid);

        if ($result === null) {
            return response()->json([
                'valid' => false,
                'message' => 'The value is not a valid UUID.',
                'errors' => ['uuid' => ['The value is not a valid UUID. Expected 32 hex digits, optionally hyphenated (8-4-4-4-12), in braces, or prefixed with urn:uuid:.']],
            ], 400);
        }

        return response()->json($result);
    }

    /**
     * GET /api/validate/{uuid} - always 200, with a boolean "valid".
     */
    public function validate(string $uuid): JsonResponse
    {
        $result = $this->inspector->inspect($uuid);

        return response()->json($result === null ? ['valid' => false] : [
            'valid' => true,
            'uuid' => $result['uuid'],
            'version' => $result['version'],
            'variant' => $result['variant'],
            'is_rfc9562' => $result['variant_bits'] === '10' || $result['is_nil'] || $result['is_max'],
        ]);
    }

    private function nameBased(Request $request, UuidVersion $version, mixed $namespace, mixed $name): Response|JsonResponse
    {
        if (! is_string($namespace) || $namespace === '') {
            return $this->error('namespace', 'A namespace is required: a UUID or one of ns:DNS, ns:URL, ns:OID, ns:X500.');
        }

        if (! is_string($name) || $name === '') {
            return $this->error('name', 'A name is required.');
        }

        if (str_starts_with(strtolower($name), 'base64:')) {
            $decoded = base64_decode(strtr(substr($name, 7), '-_ ', '+/+'), true);

            if ($decoded === false) {
                return $this->error('name', 'The base64-encoded name is not valid base64.');
            }

            $name = $decoded;
        }

        try {
            $uuid = $this->generator->nameBased($version, $namespace, $name);
        } catch (InvalidArgumentException $e) {
            return $this->error('namespace', $e->getMessage());
        }

        return $this->respond($request, [$uuid]);
    }

    /**
     * @param  list<string>  $uuids
     */
    private function respond(Request $request, array $uuids): Response|JsonResponse
    {
        $formatter = UuidFormatter::fromArray($request->query());

        if ($this->wantsText($request)) {
            return response($formatter->join($uuids)."\n", 200, ['Content-Type' => 'text/plain; charset=utf-8']);
        }

        return response()->json($formatter->formatAll($uuids));
    }

    private function wantsText(Request $request): bool
    {
        $format = strtolower((string) $request->query('format', ''));

        if ($format !== '') {
            return in_array($format, ['text', 'txt', 'plain'], true);
        }

        $accept = (string) $request->header('Accept', '');

        return str_contains($accept, 'text/plain') && ! str_contains($accept, 'application/json');
    }

    private function error(string $field, string $message, int $status = 400): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => [$field => [$message]]], $status);
    }
}
