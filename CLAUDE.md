# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

Media on Autopilot is a WordPress plugin (PHP 8.3+, WP 7.0+) that improves the media library: focal-point picker with AI detection on upload, AI alt text + tagging, and CDN delivery (BunnyCDN pull-zone or Cloudflare Images) with on-the-fly image optimization.

This plugin lives inside a WordPress Studio site. The site root (`../../..`) holds `STUDIO.md` / `AGENTS.md` — **all `wp` CLI commands must be prefixed with `studio`** (e.g. `studio wp eval '...'`). `wp shell` is unsupported; use `studio wp eval` instead. The database is SQLite, not MySQL.

## Commands

PHP (run from the plugin directory):
- `composer test:unit` — pure unit tests (no WordPress), `tests/Unit/`
- `composer test:integration` — real-WordPress tests against a throwaway SQLite DB via `wp-phpunit`; needs `composer install` first. No MySQL or env vars required; expects the plugin at `wp-content/plugins/media-on-autopilot/` with the SQLite drop-in present (standard for Studio).
- `composer lint` — PHPCS (`WordPress` + `WordPress-Extra` + `PHPCompatibilityWP`)
- Single test: `vendor/bin/phpunit --testsuite unit --filter TestName` or `vendor/bin/phpunit -c phpunit-integration.xml.dist --filter TestName`

JS (from the plugin directory):
- `npm run build` / `npm run start` — bundle `assets/src/index.js` + `assets/src/ai-tagging.js` into `build/` via `@wordpress/scripts`
- `npm run test:js` — Jest (config at `assets/test/jest.config.js`)
- `npm run lint:js` — ESLint over `assets/src` and `assets/test`

CI and deployment use three workflows in `.github/workflows/`:

- `ci.yml` (push to `main` and PRs) runs three jobs: `php` (lint + unit), `js` (lint + jest), and `integration` (reconstructs the Studio layout, downloads WP 7.0 core + SQLite integration, builds assets, then runs the integration suite). Integration tests require `npm run build` to have produced `build/` assets.
- `update-assets.yml` (push to `main` touching `readme.txt` or `.wordpress-org/**`, plus manual dispatch) syncs `readme.txt` and the `.wordpress-org/` assets to WordPress.org via `10up/action-wordpress-plugin-asset-update`, **without cutting a release**. The action parses the `Stable tag` and commits to both trunk and that tag folder, so the public plugin page updates with the version unchanged. This is the path for readme or asset edits between releases (readme-only display fixes do not need a version bump).
- `deploy.yml` (on GitHub release published) verifies version consistency, builds a production zip, deploys the new version to WordPress.org, and re-syncs the readme and assets. Runs in the `main` GitHub environment, which holds the `SVN_USERNAME` and `SVN_PASSWORD` secrets (so `update-assets.yml` sets `environment: main` for the same reason).

## Architecture

**Module pattern.** `media-on-autopilot.php` boots on `plugins_loaded` by constructing `Plugin` with one module per feature and calling `boot()`, which calls `register()` on each. Every feature is a `Module` (`src/Module.php`: a single `register(): void`). A feature's `*Module` class is the composition root — it instantiates the feature's collaborators and wires their hooks. There is no DI container or service locator; dependencies are constructor-injected by hand inside `register()`.

Three features under `src/Features/`:
- **FocalPoint** — focal X/Y meta drives server-side cropping (`CropHandler`, `Regenerator`) and front-end `object-position` (`Frontend`). The manual JS picker lives in `assets/src/focal-point/`; focal detection on upload is produced server-side by AiTagging's combined vision call (no browser smartcrop). The `moap_focal_point_enabled` option (default `1`) gates all crop hooks — when off, the FocalPoint feature registers no hooks.
- **AiTagging** — generates alt text, taxonomy tags, and focal points. Vision calls go through the `VisionClient` interface; production uses `NativeVisionClient`, which wraps WP 7.0's native `wp_ai_client_prompt()` (no custom AI/provider abstraction — rely on the native AI Client). Images are downscaled by `ImageResizer` before sending to keep token cost low. REST + background tagging supported. Bulk library enrichment is a settings-only "Retag existing media" tool — there are no media-library bulk actions.
- **Cdn** — one `CdnModule` with a `moap_cdn_provider` option (values: `none` / `bunny` / `cloudflare`, mutually exclusive) and a separate `moap_cdn_serve` option (boolean, default `0`). Selecting a provider saves and validates credentials; `moap_cdn_serve = 1` is required for front-end URLs to be rewritten. Sites upgrading from a version before 0.8.0 that already had a provider configured have serving enabled automatically on first load via a one-time migration (guarded by the `moap_cdn_serve_migrated` option flag); fresh installs default to off. `CdnModule::wire()` selects the active provider and — when `ImageProvider::encodes_focal_in_url()` returns `true` — gates the focal cache-bust via `add_filter( 'moap_focal_point_cache_bust', '__return_false' )`. Shared abstractions live at `src/Features/Cdn/`: `ImageProvider` (interface), `ImageTransform` (value object), `ImageFrontend` (hooks: `image_downsize`, srcset, attachment URL, content img tag), `WidthLadder`. All provider sections render server-side (so every field is submitted and persists — the typed sanitizers never receive `null`); each provider section is wrapped via `add_settings_section`'s `section_class` (`moap-cdn-provider--bunny` / `--cloudflare`), and `build/cdn-settings.js` (enqueued by `CdnSettings::enqueue_admin` on the options page) shows only the section matching the `moap_cdn_provider` dropdown. CDN and AI connection tests are triggered manually via dedicated REST endpoints (not run automatically on page load).
  - **Bunny** (`src/Features/Cdn/Providers/Bunny/`) — pull-zone delivery. Active when `moap_cdn_provider = bunny`, `moap_cdn_serve = 1`, and the pull-zone hostname is set.
  - **Cloudflare** (`src/Features/Cdn/Providers/Cloudflare/`) — Cloudflare Images storage offload. Active when `moap_cdn_provider = cloudflare`, `moap_cdn_serve = 1`, and account credentials are set. Upload is URL-based ingest (`ImagesApiClient`); background offload runs via `CloudflareOffloader` (action scheduler slug `cloudflare_offload`); bulk "Offload existing media" tool available in settings (`OffloadTool`). Offload is purely additive — local originals are always kept. When an attachment has no stored Cloudflare image ID, `CloudflareProvider::build_url()` returns the original local URL (graceful fallback). Delete-sync removes the CF image when an attachment is deleted.

**Filterable CDN hooks** (all renamed from old `mme_bunnycdn_*`):
- `moap_cdn_image_transform` — modify the `ImageTransform` before the provider builds a URL.
- `moap_cdn_srcset_widths` — override the srcset candidate width array.
- `moap_cdn_max_width` — hard maximum pixel width (default `WidthLadder::DEFAULT_MAX_WIDTH`).
- `moap_cloudflare_delivery_base` — override the Cloudflare Images delivery base URL (default `https://imagedelivery.net/<hash>`) to serve through a custom Images delivery domain. Applied in `CloudflareProvider::build_url()`; receives `(string $base, string $account_hash)`.

**Cross-feature focal contract:** whichever provider returns `true` from `encodes_focal_in_url()` (both Bunny and Cloudflare do) disables FocalPoint's query-string cache-bust. Keep that relationship intact when touching either feature.

**Shared settings.** `Support\Settings` owns one options page (`Settings::MENU_SLUG = 'media-on-autopilot'`) under Settings. Features register their own sections/fields against that slug via the Settings API rather than creating separate pages.

## Conventions

- Strict types everywhere: `declare( strict_types=1 );` and `defined( 'ABSPATH' ) || exit;` at the top of every PHP file.
- PSR-4 (`MediaOnAutopilot\` → `src/`), so files are PascalCase — WP's hyphenated-filename PHPCS rules are disabled in `phpcs.xml.dist`.
- All global symbols (hooks, options, functions) use the `moap_` prefix; namespaced code uses `MediaOnAutopilot`. Both are registered as PHPCS prefixes.
- snake_case everywhere: all PHP identifiers — methods, properties, variables — use snake_case, including value objects (`FocalPoint::from_array`, `->x_percent()`, `BunnyConfig::is_active()`). This is the single project-wide standard, enforced by PHPCS (the WordPress ruleset, with no naming exclusions). The one exception is **string keys** that form a JS/JSON contract (e.g. the `wp_localize_script` data passed to the admin JS, like `'startedAt'` / `'targetTagCount'`, and JSON Schema keys like `'additionalProperties'`) — those stay camelCase because they're consumed by JavaScript, and PHPCS does not lint string array keys.
- Comments explain non-obvious behavior only; never add comments that justify a decision. Filters get docblocks.
- Don't add `Co-Authored-By: Claude` or Claude attribution to commits/PRs.

## Releasing

Any user-facing change (new/removed feature, setting, or behavior — not pure refactors, tests, or internal docs) MUST, in the same branch/PR:

1. Bump the version in **both** places, kept identical: the `Version:` header in `media-on-autopilot.php` and `Stable tag:` in `readme.txt`. Semver: patch for fixes, minor for features/removed settings, major for breaking changes.
2. Add a matching `= x.y.z =` entry at the top of the `== Changelog ==` block in `readme.txt` describing the change in user terms. Never edit older changelog entries.
3. Update the relevant current-behavior prose in `readme.txt` and this file if the change alters how a feature works.

The plugin header `Version:`, the readme `Stable tag:`, and the newest changelog entry must always be the same version. Check this before opening a PR.
