# Contributing to Media on Autopilot

Thanks for helping improve Media on Autopilot! Bug reports, feature ideas, and pull requests are all welcome.

## Reporting bugs & requesting features

Use the GitHub issue templates:

- [Report a bug](https://github.com/stormrockwell/media-on-autopilot/issues/new?template=bug_report.yml)
- [Request a feature](https://github.com/stormrockwell/media-on-autopilot/issues/new?template=feature_request.yml)

For usage questions, the [WordPress.org support forum](https://wordpress.org/support/plugin/media-on-autopilot/) is the best place.

## Development environment

Install dependencies:

```bash
composer install
npm ci
```

## Running the checks

| Task | Command |
| --- | --- |
| PHP unit tests | `composer test:unit` |
| PHP integration tests | `composer test:integration` |
| PHP lint | `composer lint` |
| JS build | `npm run build` |
| JS tests | `npm run test:js` |
| JS lint | `npm run lint:js` |

Run the full JS test and lint suites (not filtered subsets) before opening a PR. Integration tests require `npm run build` to have produced the `build/` assets.

## Coding standards

- Every PHP file starts with `declare( strict_types=1 );` and `defined( 'ABSPATH' ) || exit;`.
- PSR-4 autoloading (`MediaOnAutopilot\` → `src/`); files are PascalCase.
- Global symbols (hooks, options, functions) use the `moap_` prefix; namespaced code uses `MediaOnAutopilot`.
- Value objects are immutable with camelCase APIs.
- Sanitize input and escape output

## Pull requests

1. Branch from `main`.
2. Keep changes focused; follow existing patterns in the file you are editing.
3. Make sure lint and tests pass.
4. Fill in the pull request template, including the release checklist for user-facing changes.

## Releasing (maintainers)

Any user-facing change must, in the same PR:

1. Bump the version identically in three places: the `Version:` header and `const VERSION` in `media-on-autopilot.php`, and `Stable tag:` in `readme.txt`. Semver: patch for fixes, minor for features, major for breaking changes.
2. Add a matching `= x.y.z =` entry at the top of the `readme.txt` changelog.
3. Confirm `php bin/check-version.php` passes.
