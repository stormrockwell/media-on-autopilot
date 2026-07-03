=== Media on Autopilot ===
Contributors: stormrockwell
Tags: focal point, alt text, image optimization, cdn, media library
Tested up to: 7.0
Requires at least: 7.0
Requires PHP: 8.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.0.0

Take the friction out of WordPress images: AI alt text and tagging, plus focal points that hold from crop to CDN to srcset.

== Description ==
Media on Autopilot takes the friction out of images in WordPress. AI writes alt text and tags every image for an accessible, searchable library, while a focal point set once holds across every crop, your CDN's optimizations, and the responsive `srcset`. Everything is served through your own BunnyCDN or Cloudflare account, with no reseller in between.

**Focal point** — set a focal point per image that themes and CDN providers can use for smart cropping. Focal-point cropping is on by default and can be turned off in Settings. New uploads get a focal point automatically when AI auto-tagging is enabled — the vision model marks the main subject, generated alongside alt text and tags in one call. With auto-tagging off, set the focal point by hand with the picker, which appears on the edit-media screen while focal-point cropping is enabled.

**AI alt text + tagging** — generates descriptive alt text and searchable tags for image attachments using the native WordPress AI Client (requires an AI provider configured in WordPress → Settings → Connectors). Alt text is written to the standard alt field; tags are stored in a dedicated `moap_media_tag` taxonomy and are searchable in the media library.

Two ways to generate alt text and tags:

* **Per-image button** — "Generate alt text & tags" in the attachment editor. Always overwrites existing alt text and replaces tags.
* **Auto-tag on upload** — opt-in setting (off by default) that runs the fill-empty behaviour automatically when a new image is uploaded.

**Retag existing media** — a Settings → Media on Autopilot tool that backfills the whole library in the background. Pick any of alt text, tags, and focal points, then either **fill missing only** or **overwrite existing** (destructive, with a browser confirmation). Each image is enriched with a single combined AI call to keep token cost low, and a refresh-survivable progress bar with a Cancel button tracks the run. The tool is disabled until an AI provider is configured.

All AI operations are non-destructive by default: the fill-empty variants never clobber human-written alt text, and tags always merge rather than replace. If no AI provider is configured, the per-image AI Tagging field is hidden, the bulk Retag tool is disabled, and the auto-upload no-ops silently.

Six developer filters let you adjust the taxonomy slug, prompt, target tag count, image resize width, AI connector, and alt-text application strategy.

**CDN delivery** — opt-in: serve images through a CDN provider with on-the-fly optimization. Select a provider in Settings → Media on Autopilot → CDN. Options: **None** (default), **BunnyCDN**, or **Cloudflare Images**. Only one provider may be active at a time. Selecting a provider lets you save credentials independently; a separate "Serve images through CDN" toggle controls whether front-end URLs are actually rewritten.

The CDN integration is front-end only — the admin, REST API, block editor, and feeds are never rewritten. All variants are fully reversible: disable the provider and URLs immediately revert to your origin with no stored state. Your original files are never deleted by the CDN rewrite itself.

**BunnyCDN** — connect a BunnyCDN pull zone. Select BunnyCDN as your CDN provider and enter the pull-zone hostname (e.g. `yourzone.b-cdn.net`); that is all it takes to switch it on. Bunny becomes the image resizing engine: every front-end image URL is rewritten to the pull zone and served from the original file (WordPress's local intermediate sizes are bypassed, never deleted), a responsive `srcset` is built from the original, and hard-crop sizes are anchored to the stored focal point via Bunny's `focus_crop`. Choose a format strategy (auto WebP/AVIF, force WebP/AVIF, or off) and quality setting. No uploads — Bunny pulls each image from your origin on first request and caches it at the edge.

**Cloudflare Images** — upload ("offload") originals to Cloudflare Images and serve them via `imagedelivery.net`. Requires a Cloudflare account with Images enabled.

Setup:

1. In your Cloudflare dashboard, go to **Images** and enable the service for your account.
2. Create an API token with the **Images:Edit** permission.
3. In Cloudflare Images settings, enable **Flexible variants** — this is required for Media on Autopilot's width/quality/format URL parameters to work.
4. Your WordPress site must have a publicly accessible origin URL so Cloudflare can ingest images by fetching them over HTTP. Localhost or private-network URLs will not work with URL ingest.
5. In Settings → Media on Autopilot → CDN, select **Cloudflare Images**, then enter your Account ID, API token, and Account Hash (the hash segment from your `imagedelivery.net/<hash>` delivery URL). Choose a quality and format.

Uploads and bulk offload:

* New images are offloaded to Cloudflare in the background automatically after upload (when credentials are set).
* Use the **Offload existing media** tool in Settings → Media on Autopilot to backfill your existing library.
* Offloading is purely additive: your local originals are always kept. Cloudflare Images is used for delivery, never as your only copy.

When an attachment has not yet been offloaded (or offload is pending), front-end URLs fall back to the local WordPress uploads URL automatically — no broken images.

Developer filters for the CDN integration: `moap_cdn_image_transform` (modify the transform spec before a URL is built), `moap_cdn_srcset_widths` (override srcset candidate widths), `moap_cdn_max_width` (hard pixel-width ceiling), and `moap_focal_point_cache_bust` (suppress the focal-point query-string cache-bust on CDN URLs — disabled automatically when the active provider encodes the focal point in its URL).

== Frequently Asked Questions ==

= Is Media on Autopilot free? =

Yes. The plugin is free and licensed under the GPL. There is no paid tier inside the plugin.

= Which features need a paid third-party service? =

Some features rely on services you bring yourself:

* **Free, no account needed:** the manual focal-point picker, focal-point cropping at every image size, and tag-based media search.
* **Needs an AI provider:** AI alt text, AI tagging, and automatic focal-point detection require an AI provider configured in WordPress → Settings → Connectors. You supply the credentials; usage is billed by that provider.
* **Needs a CDN account:** CDN delivery requires either a BunnyCDN pull zone or a Cloudflare Images account.

= Do I need to pay for AI? =

You configure your own AI provider, and any usage is billed directly by that provider, never by this plugin. Images are downscaled before being sent to keep token cost low.

= Which CDN should I use? =

BunnyCDN is a pull zone: it resizes images on the fly and pulls from your origin, with no uploads. Cloudflare Images offloads copies of your originals and serves them from imagedelivery.net. Only one provider can be active at a time, and your local originals are always kept.

= Will this delete my original images? =

No. The CDN rewrite and Cloudflare offload are both additive: your original files are never deleted by Media on Autopilot.

= Does it work without AI configured? =

Yes. Focal points (set by hand), focal-point cropping, and tag-based search all work without AI. AI fields are hidden and auto-tagging no-ops until a provider is configured.

= What happens if I deactivate the plugin? =

Front-end image URLs immediately revert to your origin. There is no stored CDN state to clean up and no data loss.

== External services ==

Media on Autopilot only contacts an external service when you turn on a feature that needs one. Nothing is sent until you configure and enable that feature.

= Cloudflare Images =

Used only when you set the CDN provider to "Cloudflare Images" in Settings → Media on Autopilot → CDN. The plugin uploads copies of your image files to Cloudflare Images (api.cloudflare.com) so they can be served, resized, and cropped from Cloudflare's delivery network (imagedelivery.net). It also calls the Cloudflare API to verify your credentials, to delete an image when you delete the attachment, and to run the built-in delivery test. What is sent: your image files, plus the Cloudflare Account ID and API token you enter. When: when you offload media (on upload or via the "Offload existing media" tool), when an image is deleted, and when you run a connection or delivery test.

Cloudflare terms of service: https://www.cloudflare.com/website-terms/
Cloudflare privacy policy: https://www.cloudflare.com/privacypolicy/

= bunny.net (BunnyCDN) =

Used only when you set the CDN provider to "BunnyCDN" and enable "Serve images through CDN". The plugin rewrites front-end image URLs to your BunnyCDN pull-zone hostname; BunnyCDN fetches ("pulls") each image from your site's origin the first time it is requested and caches it at the edge. The built-in CDN test makes one request to your pull zone. What is sent: image requests (URLs) to your pull-zone host, which then fetches the corresponding images from your origin. When: on front-end page loads once serving is enabled, and when you run the CDN test.

bunny.net terms of service: https://bunny.net/tos/
bunny.net privacy policy: https://bunny.net/privacy/

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
= 1.0.0 =
* First stable release of Media on Autopilot.
