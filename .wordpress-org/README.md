# WordPress.org assets

Final art committed here is published to the plugin's WordPress.org `assets/` directory by two
workflows (both via `10up/action-wordpress-plugin-asset-update`):

- `.github/workflows/update-assets.yml` — on every push to `main` that touches `readme.txt` or
  `.wordpress-org/**`, syncs the readme and these assets to WordPress.org **without a release**.
- `.github/workflows/deploy.yml` — re-syncs them at release time alongside the versioned deploy.

These files are NOT shipped in the plugin zip (`.distignore` excludes `.wordpress-org`).

Expected files (see `docs/assets-brief.md` for full briefs):

- `icon-128x128.png`, `icon-256x256.png`
- `banner-772x250.png`, `banner-1544x500.png`
- `screenshot-1.png` … `screenshot-5.png`
