@extends('layouts.app')
@section('title', 'Terms of Service & Privacy Policy')
@section('description', 'Terms of use and privacy policy for UUIDTools.com.')
@section('content')
<section class="hero"><h1>Terms of Service &amp; Privacy Policy</h1>
<p class="lead"><a href="#terms">Terms of Use</a> &middot; <a href="#privacy">Privacy Policy</a></p></section>
<div class="prose">

				<h2 id="terms">Terms of Use - {{ config('app.name') }}</h2>
				<p>
					Thank you for using {{ config('app.name') }}. This Terms of Use Agreement ("Agreement") sets forth the legally binding terms for your use of the {{ config('app.name') }}. By using the {{ config('app.name') }} Services, you agree to be bound by this Agreement, whether you are a "Visitor" (which means that you simply browse the {{ config('app.name') }} Website) or you are a "Member" (which means that you have registered with {{ config('app.name') }}). The term "User" refers to a Visitor or a Member.
				</p>
				<p>
					You are only authorized to use {{ config('app.name') }} (regardless of whether your access or use is intended) if you agree to abide by all applicable laws and to this Agreement. Please read this Agreement carefully and save it. If you do not agree with it, you should leave the {{ config('app.name') }} Website and discontinue use of the {{ config('app.name') }} Services immediately. By using the {{ config('app.name') }} Services, you acknowledge your acceptance of this Agreement in its entirety.
				</p>
				<h3>Indemnification of Responsibility</h3>
				<p>
					While we make every effort to maintain true and up-to-date information on the website, we cannot be held responsible for incorrect information or the results of using any information on the website.
				</p>
				<p>
					You agree to indemnify and hold us harmless, our subsidiaries, affiliates, related parties, employees, and related parties from any claim or demand, including reasonable attorney's fees, that may be made by any third party, that is due to or arising out of your conduct or connection with this web site or service, your provision of Content, your violation of this Terms of Use or any other violation of the rights of another person or party.
				</p>
				<h3>Disclaimer of Warranties</h3>
				<p>
					You understand and agree that the information on this site is provided "AS-IS", and does not constitute legal or financial advice. Our information comes with no actual or implied warranty, and is provided to you at your own risk. We expressly disclaim all warranties of any kind, implied or express, including but not limited to the warranties of merchantability, fitness for a particular purpose, and non-infringement.
				</p>
				<p>
					{{ config('app.name') }} cannot replace a licensed professional or lawyer, and we can make no claims as to the accuracy of the data and tools we provide. The information provided by {{ config('app.name') }} is not intended as professional advice, and you understand that any information or advice provided by the {{ config('app.name') }} is for use solely at your own risk and without liability or warranty of any kind.
				</p>
				<h3>Limitation of Liability</h3>
				<p>
					YOU EXPRESSLY UNDERSTAND AND AGREE THAT WE SHALL NOT BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL, INCIDENTAL, CONSEQUENTIAL OR EXEMPLARY DAMAGES, INCLUDING BUT NOT LIMITED TO, DAMAGES FOR LOSS OF PROFITS, GOODWILL, USE, DATA OR OTHER INTANGIBLE LOSS (EVEN IF WE HAVE BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES), RESULTING FROM OR ARISING OUT OF:
				</p>
				<ol type=I>
					<li>THE USE OF OR THE INABILITY TO USE THE SERVICE</li>
					<li>THE COST TO OBTAIN SUBSTITUTE GOODS AND/OR SERVICES RESULTING FROM ANY TRANSACTION ENTERED INTO ON THROUGH THE SERVICE</li>
					<li>UNAUTHORIZED ACCESS TO OR ALTERATION OF YOUR DATA TRANSMISSIONS</li>
					<li>STATEMENTS OR CONDUCT OF ANY THIRD PARTY ON THE SERVICE</li>
					<li>ANY OTHER MATTER RELATING TO THE SERVICE</li>
				</ol>
				<h3>External Websites</h3>
				<p>
					We may provide organic or advertiser's links to content on external web sites that are not controlled or influenced by {{ config('app.name') }}. We cannot be held responsible for any of the content or information contained on these external sites, and we do not necessarily endorse these sites or the views they present.
				</p>
				<h3>Copyrighted Material</h3>
				<p>
					All original material on the website is copyrighted by {{ config('app.name') }} unless explicitly stated otherwise. You agree that you will not copy, retransmit, or modify any of the content provided by the website without obtaining explicit permission from {{ config('app.name') }}. Permissions can be obtained by contacting us directly.
				</p>
				<p>
					If you believe information on {{ config('app.name') }} is in violation of a copyright you hold, please <a href="/contact">contact us</a> directly. Any confirmed infringing material will be removed as soon as possible.
				</p>
				<hr />
				
				<h2 id="privacy">Privacy Policy - {{ config('app.name') }}</h2>
				<p>
					In this policy, we'll spell out what information our site collects, and how it is used. Please note that this policy may change at any time without notice.
				<ul>
					<li><strong>Web Analytics and Tracking - </strong>{{ config('app.name') }} uses web analytics to track anonymous use data including (but not limited to) IP address, browser information, usage history, etc. No personally identifiable information is collected. We may share this aggregate, anonymized data with third party partners, or when required by law.</li>
				</ul>
				<ul>
					<li><strong>Registration for Services - </strong>We may collect personally identifiable information from you during the process of registering for services provided by {{ config('app.name') }}. This information will be used for processing your request for services, and may be shared with trusted third parties in order to best provide you with the information or services requested.</li>
				</ul>
				<ul>
					<li><strong>Cookies - </strong>{{ config('app.name') }} may use cookies directly to improve your experience by remembering key settings or preferences. You may disable this at any time through your browser privacy settings. Third-party partners may also use cookies, as described below.</li>
				</ul>
				<ul>
					<li><strong>Third-Party Analytics - </strong>Certain third-party plugins used across the website may include additional analytics or tracking codes or cookies. Advertisers like Google Adsense, plugins like the and other third-party plugins have their own privacy policies and opt-out mechanisms that will enable you to manage your preferences.</li>
				</ul>
				</p>
				<h3>Disclosure and Use of Your Information</h3>
				<p>
					{{ config('app.name') }} may use the information collected from you as described above for a variety of purposes, including improving our website and contacting you in reference to the products or services you explicitly registered for.
				</p>
				<p>
					{{ config('app.name') }} may disclose aggregate, anonymous summaries of personal data to third parties. Personally identifiable information may also be released as required by applicable law to qualified law enforcement. In the event that the ownership or structure of {{ config('app.name') }} changes in the future, any personal information collected may be exchanged with the rest of the {{ config('app.name') }} Data.
				</p>
				<h3>Comments and Notice</h3>
				<p>
					This policy may be changed at any time without notice, so please check to make sure you are aware of the latest version. Please feel free to <a href="/contact">contact us</a> with any questions.
				</p>


</div>
@endsection
