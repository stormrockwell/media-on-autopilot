## Summary

<!-- What does this PR change and why? -->

## Testing

<!-- Commands run and their results. -->

- [ ] `composer lint` and `composer test:unit` pass
- [ ] `npm run lint:js` and `npm run test:js` pass
- [ ] `composer test:integration` passes (if PHP behavior changed)

## Release checklist (user-facing changes only)

- [ ] Version bumped identically in `media-on-autopilot.php` header, `const VERSION`, and `readme.txt` `Stable tag`
- [ ] New `= x.y.z =` entry added to the top of the `readme.txt` changelog
- [ ] `php bin/check-version.php` passes
