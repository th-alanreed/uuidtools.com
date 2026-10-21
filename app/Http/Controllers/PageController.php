<?php

namespace App\Http\Controllers;

use App\Uuid\GeneratorPage;
use App\Uuid\UuidFormatter;
use App\Uuid\UuidGenerator;
use App\Uuid\UuidInspector;
use App\Uuid\UuidVersion;
use App\Uuid\VersionContent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly UuidGenerator $generator,
        private readonly UuidInspector $inspector,
    ) {}

    public function home(): View
    {
        return $this->generator(GeneratorPage::find('v4'), home: true);
    }

    public function generate(string $slug): View
    {
        $page = GeneratorPage::find(strtolower($slug)) ?? abort(404);

        return $this->generator($page);
    }

    public function decode(Request $request, ?string $uuid = null): View
    {
        $input = $uuid ?? $request->query('uuid');
        $input = is_string($input) ? trim($input) : '';

        return view('decode', [
            'input' => $input,
            'result' => $input === '' ? null : $this->inspector->inspect($input),
            'sampleUuid' => $this->generator->generate(UuidVersion::V7)[0],
        ]);
    }

    public function sitemapXml(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'."\n".view('sitemap-xml', ['urls' => $this->sitemapUrls()])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }

    public function sitemap(): View
    {
        return view('sitemap', ['pages' => GeneratorPage::all()]);
    }

    /**
     * @return list<string>
     */
    private function sitemapUrls(): array
    {
        $paths = ['/'];

        foreach (GeneratorPage::all() as $page) {
            if ($page->slug !== 'v4') {
                $paths[] = $page->path();
            }
        }

        array_push($paths, '/decode', '/docs', '/what-is-uuid', '/uuid-versions-explained', '/terms', '/contact', '/sitemap');

        return array_map(url(...), $paths);
    }

    private function generator(GeneratorPage $page, bool $home = false): View
    {
        $formatter = UuidFormatter::fromArray($page->preset);

        if ($page->version->isNameBased()) {
            $example = $this->generator->nameBased($page->version, 'ns:dns', 'example.com');
            $initial = [$example];
        } else {
            $initial = $this->generator->generate($page->version, $page->count);
        }

        return view('generator', [
            'page' => $page,
            'home' => $home,
            'formatter' => $formatter,
            'initial' => $initial,
            'initialText' => $formatter->join($initial),
            'content' => VersionContent::for($page->slug),
        ]);
    }
}
