=== Media on Autopilot ===
Contributors: stormrockwell
Tags: focal point, alt text, image optimization, cdn, media library
Tested up to: 7.0.1
Requires at least: 7.0
Requires PHP: 8.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.1.0

AI alt text and tagging, focal-point cropping that sticks, and image CDN delivery through your own accounts.

== Description ==
Media on Autopilot takes the friction out of images in WordPress. For every image, AI writes alt text, adds tags, and picks the ideal focal point, so you get an accessible, searchable library where every crop stays well-framed. The focal point holds across your CDN's optimizations, and everything is served through your own BunnyCDN or Cloudflare account, with no reseller in between.

**What you get**

* **Focal points that stick:** The most important part of each image stays in frame across every crop. AI sets the focal point on upload, or you can place it by hand.
* **AI alt text and tags:** Every image gets descriptive alt text and searchable tags, for a library that is accessible and easy to find things in. Generate them per image, automatically on upload, or backfill your whole library at once.
* **Image CDN on your own account:** Serve optimized, responsive images through your own BunnyCDN or Cloudflare account, with automatic format and quality tuning. No reseller, no lock-in, and your originals are never deleted.

**Works with what you have**

Focal points work with no AI or CDN at all. It never overwrites your alt text unless you ask it to, tags are always merged rather than replaced, and deactivating the plugin reverts image URLs to your site with no leftover data. We do our best to be compatible with other major plugins. If you face a conflict, let us know and we may be able to help resolve it.

Setup steps for AI, BunnyCDN, and Cloudflare Images are on the Installation tab.

== How it works ==

= Focal points =

A focal point marks the main subject of an image so themes and CDN providers can crop around it instead of the center. New uploads get a focal point automatically when AI auto-tagging is enabled: the vision model marks the subject in the same call that writes alt text and tags. With auto-tagging off, set the focal point by hand with the picker on the edit-media screen. Focal-point cropping is on by default and can be turned off in Settings.

= AI alt text and tags =

Alt text and tags are generated with the native WordPress AI Client, so you connect your own provider (WordPress → Settings → Connectors). Alt text is written to the standard alt field; tags are stored in a dedicated media-tag taxonomy and are searchable in the media library. To keep costs down, images are downscaled before being sent, and the plugin prioritizes small, low-cost vision models first. In practice that keeps tagging to a few cents per hundred images; see the FAQ for a real-world example. You can change the model currently via a PHP hook.

There are three ways to generate them:

* **Per-image button:** "Generate alt text & tags" in the attachment editor. Overwrites existing alt text and merges new tags in alongside any existing ones.
* **Auto-tag on upload:** an opt-in setting (off by default) that fills empty alt text and tags automatically when a new image is uploaded.
* **Retag existing media:** a Settings tool that backfills the whole library in the background. Pick any of alt text, tags, and focal points, then either fill missing only or overwrite existing (with a confirmation). A progress bar survives page refreshes and can be cancelled mid-run.

The fill-empty options never clobber alt text you have written, and tags always merge. If no AI provider is configured, the AI fields are hidden, the Retag tool is disabled, and auto-upload does nothing.

= CDN delivery =

CDN delivery is opt-in. Choose a provider in Settings → Media on Autopilot → CDN: None (default), BunnyCDN, or Cloudflare Images. Only one can be active at a time. Selecting a provider lets you save credentials, and a separate "Serve images through CDN" toggle controls whether front-end URLs are actually rewritten.

The integration is front-end only: the admin, REST API, block editor, and feeds are never rewritten. Every option is reversible. Disable the provider and URLs revert to your origin with no stored state, and your original files are never deleted.

**BunnyCDN** is a pull zone. Enter your pull-zone hostname and Bunny becomes the resizing engine: front-end image URLs are rewritten to the pull zone, a responsive image set is built from the original, and hard crops are anchored to the stored focal point. Bunny pulls each image from your origin on first request and caches it at the edge, so there are no uploads. Choose a format strategy (auto WebP/AVIF, force WebP/AVIF, or off) and a quality setting.

**Cloudflare Images** offloads copies of your originals to Cloudflare and serves them from imagedelivery.net. New images are offloaded in the background after upload, and an "Offload existing media" tool backfills your existing library. Offloading is additive: your local originals are always kept, and if an image has not been offloaded yet, its front-end URL falls back to the local file so nothing breaks.

== For developers ==

Media on Autopilot uses WordPress 7.0's native AI Client, so you bring your own provider. Filters let you adjust the taxonomy slug, prompt, target tag count, image resize width, AI connector, vision-model preference order (`moap_ai_tagging_model_preference`), and alt-text application strategy, plus the CDN behavior:

* `moap_cdn_image_transform`: modify the transform spec before a URL is built.
* `moap_cdn_srcset_widths`: override the responsive candidate widths.
* `moap_cdn_max_width`: hard pixel-width ceiling.
* `moap_cloudflare_delivery_base`: serve Cloudflare Images through a custom delivery domain.
* `moap_focal_point_cache_bust`: suppress the focal-point cache-bust on CDN URLs (disabled automatically when the active provider encodes the focal point in its URL).

== Installation ==

1. Install and activate Media on Autopilot. Focal-point cropping works right away, with no configuration.
2. Follow the sections below to turn on AI features or CDN delivery.

= Set up AI alt text and tags =

1. Connect an AI provider in WordPress → Settings → Connectors. This plugin does not include a provider; usage is billed by whichever one you connect.
2. Once a provider is connected, the "Generate alt text & tags" button appears in the attachment editor.
3. To generate automatically, enable auto-tag on upload in Settings → Media on Autopilot.
4. To backfill your existing library, use the "Retag existing media" tool in Settings → Media on Autopilot.

= Connect BunnyCDN =

1. In Settings → Media on Autopilot → CDN, select BunnyCDN.
2. Enter your pull-zone hostname (e.g. `yourzone.b-cdn.net`).
3. Choose a format strategy and quality.
4. Turn on "Serve images through CDN".

= Connect Cloudflare Images =

1. In your Cloudflare dashboard, go to Images and enable the service for your account.
2. Create an API token with the Images:Edit permission.
3. In Cloudflare Images settings, enable Flexible variants. This is required for the width, quality, and format URL parameters to work.
4. Make sure your WordPress site has a publicly accessible origin URL so Cloudflare can fetch images over HTTP. Localhost or private-network URLs will not work.
5. In Settings → Media on Autopilot → CDN, select Cloudflare Images, then enter your Account ID, API token, and Account Hash (the hash segment from your `imagedelivery.net/<hash>` delivery URL). Choose a quality and format.
6. Turn on "Serve images through CDN". New uploads offload automatically; use the "Offload existing media" tool to backfill.

== Frequently Asked Questions ==

= How do I get started? =

Install and activate the plugin. Focal-point cropping works immediately, with nothing to configure. To turn on AI alt text and tags, connect an AI provider under WordPress → Settings → Connectors. To serve images through a CDN, add a BunnyCDN pull zone or a Cloudflare Images account. Full steps are on the Installation tab.

= Is Media on Autopilot free? =

Yes. The plugin is free and licensed under the GPL. There is no paid tier inside the plugin.

= Which features need a paid third-party service? =

Some features rely on services you bring yourself:

* **Free, no account needed:** the manual focal-point picker and focal-point cropping at every image size.
* **Needs an AI provider:** AI alt text, AI tagging, and automatic focal-point detection require an AI provider configured in WordPress → Settings → Connectors. You supply the credentials; usage is billed by that provider.
* **Needs a CDN account:** CDN delivery requires either a BunnyCDN pull zone or a Cloudflare Images account.

= Do I need to pay for AI, and how expensive is it? =

You configure your own AI provider, and any usage is billed directly by that provider, never by this plugin. Images are downscaled before being sent to keep token cost low. In our own testing, using OpenAI as the provider with gpt-5.4-mini, generating alt text and tags for 100 images cost roughly 8 cents at current OpenAI rates. Pricing is set by your provider and can change, so treat this as a rough guide.

= Which CDN should I use? =

BunnyCDN is a pull zone: it resizes images on the fly and pulls from your origin, with no uploads. Cloudflare Images offloads copies of your originals and serves them from imagedelivery.net. Only one provider can be active at a time, and your local originals are always kept.

= Will this delete my original images? =

No. The CDN rewrite and Cloudflare offload are both additive: your original files are never deleted by Media on Autopilot.

= Does it work without AI configured? =

Yes. Focal points set by hand and focal-point cropping work without AI. AI fields are hidden and auto-tagging does nothing until a provider is configured.

= What happens if I deactivate the plugin? =

Front-end image URLs immediately revert to your origin. There is no stored CDN state to clean up and no data loss.

== External services ==

Media on Autopilot only contacts an external service when you turn on a feature that needs one. Nothing is sent until you configure and enable that feature.

= Cloudflare Images =

Used only when you set the CDN provider to "Cloudflare Images" in Settings → Media on Autopilot → CDN. The plugin uploads copies of your image files to Cloudflare Images (api.cloudflare.com) so they can be served, resized, and cropped from Cloudflare's delivery network (imagedelivery.net). It also calls the Cloudflare API to verify your credentials, to delete an image when you delete the attachment, and to run the built-in delivery test. What is sent: your image files, plus the Cloudflare Account ID and API token you enter. When: when you offload media (on upload or via the "Offload existing media" tool), when an image is deleted, and when you run a connection or delivery test.

Cloudflare [terms of service](https://www.cloudflare.com/website-terms/) and [privacy policy](https://www.cloudflare.com/privacypolicy/).

= bunny.net (BunnyCDN) =

Used only when you set the CDN provider to "BunnyCDN" and enable "Serve images through CDN". The plugin rewrites front-end image URLs to your BunnyCDN pull-zone hostname; BunnyCDN fetches ("pulls") each image from your site's origin the first time it is requested and caches it at the edge. The built-in CDN test makes one request to your pull zone. What is sent: image requests (URLs) to your pull-zone host, which then fetches the corresponding images from your origin. When: on front-end page loads once serving is enabled, and when you run the CDN test.

bunny.net [terms of service](https://bunny.net/tos/) and [privacy policy](https://bunny.net/privacy/).

= AI provider (via the WordPress AI Client) =

Used only when you use AI alt text, AI tagging, or automatic focal-point detection. These features send a downscaled copy of the image (maximum 512px on the longest edge) together with a text prompt to the AI provider you configure in WordPress → Settings → Connectors, using WordPress's built-in AI Client. This plugin does not include or hardcode an AI provider; the destination is whichever provider you connect. What is sent: a downscaled copy of the image and the tagging prompt. When: when you click "Generate alt text & tags", when a new image is uploaded with auto-tagging enabled, or when you run the "Retag existing media" tool.

Because the AI provider is the one you choose and connect, please review that provider's own terms of service and privacy policy.

== Screenshots ==

1. The Media on Autopilot settings dashboard with focal point, AI tagging, and CDN status at a glance, plus quick links to report a bug or request a feature.
2. Searching the media library by keyword, with the focal point selector alongside.
3. CDN settings: choose BunnyCDN or Cloudflare Images, with quality and format options.
4. The built-in end-to-end CDN delivery test, confirming upload, resize, and delivery.
5. The "Retag existing media" tool mid-run, backfilling the library in the background.

== Changelog ==
= 1.1.0 =
* Lowered the minimum PHP requirement from 8.3 to 8.2, so the plugin now installs on hosts still running PHP 8.2. No functional changes.

= 1.0.0 =
* First stable release of Media on Autopilot.
