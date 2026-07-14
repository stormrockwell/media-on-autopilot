# Media on Autopilot

[![CI](https://github.com/stormrockwell/media-on-autopilot/actions/workflows/ci.yml/badge.svg)](https://github.com/stormrockwell/media-on-autopilot/actions/workflows/ci.yml)

Media on Autopilot takes the friction out of working with images in WordPress, from upload to delivery. AI generates the alt text and tags every image as it lands, so your library is accessible, SEO-friendly, and actually searchable. A focal point set once carries all the way through: every generated crop, your CDN's optimizations, and the responsive `srcset`, so subjects stay in frame everywhere. Best of all, it runs through your own BunnyCDN or Cloudflare account, served straight to visitors with no reseller in between. Built for developers tired of fighting the same media problems, and simple enough for the whole community.

## Features

- **Focal points**: set a focal point per image so every crop size keeps the subject in frame. Detected automatically on upload (when AI tagging is on) or set by hand with the picker. No more cropped-off heads.
- **AI alt text**: auto-generate descriptive alt text for accessibility and SEO using WordPress 7.0's native AI Client.
- **AI tagging & search**: generate searchable tags so the media library is actually findable.
- **CDN delivery**: serve resized, optimized images on the fly through BunnyCDN or Cloudflare Images, with no middleman service. Fully reversible and additive, so your originals are never deleted.

## Requirements

- WordPress 7.0+
- PHP 8.2+
- For AI features: an AI provider configured in WordPress → Settings → Connectors
- For CDN delivery: a BunnyCDN pull zone or a Cloudflare Images account

## Installation

**From WordPress.org:** search for "Media on Autopilot" under Plugins → Add New, or download from the [plugin directory](https://wordpress.org/plugins/media-on-autopilot/).

**From source:**

```bash
git clone https://github.com/stormrockwell/media-on-autopilot.git
cd media-on-autopilot
composer install
npm ci && npm run build
```

Place the directory in `wp-content/plugins/` and activate.

## Development

| Task | Command |
| --- | --- |
| PHP unit tests | `composer test:unit` |
| PHP integration tests | `composer install` then `composer test:integration` |
| PHP lint (PHPCS) | `composer lint` |
| JS build | `npm run build` |
| JS tests (Jest) | `npm run test:js` |
| JS lint (ESLint) | `npm run lint:js` |
| Build distributable zip | `composer build:zip` |

The integration suite boots WordPress via `wp-phpunit` against a throwaway SQLite database, with no MySQL and no extra environment variables required.

## Architecture

See [CLAUDE.md](CLAUDE.md) for the module pattern, feature layout, and conventions.

## Contributing

Contributions are welcome; see [CONTRIBUTING.md](CONTRIBUTING.md). Report bugs and request features via [GitHub issues](https://github.com/stormrockwell/media-on-autopilot/issues).

## License

[GPL-2.0-or-later](LICENSE).
