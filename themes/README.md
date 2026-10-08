# Themes

A public theme is a folder next to `default`. It overlays Twig, layout JSON, and CSS. It does not run PHP.

`default` is the Kingdoms parent. `dragon-gate` is a full public child: vertical desktop navigation, a data-driven hero, and a dashboard home in lacquer night, lantern gold, and jade. `hanji` is a light public child: ink-on-paper surfaces, a Metin stone hero, and a scroll carousel on the home. `starter` is the minimal child example: night/cobalt colors and the Discord widget removed. `admin` is the panel (`"public": false`) — do not pick it for the site.

## Add one

1. Copy `starter` to `themes/my-theme` (letters, digits, `_`, `-`).
2. Set `"name": "my-theme"` in `theme.json`. Keep `"parent": "default"`.
3. Edit `assets/css/tokens.css`. Keep the `@font-face` blocks.
4. **Admin → Settings → Themes** → activate `my-theme`. Or set `THEME=my-theme` in `.env`.

Full guide (what data templates can show, widgets, safety): [docs/add-theme.md](https://github.com/dev-brunoreis/metin2-website/blob/main/docs/add-theme.md) in the source repo. This `docs/` tree is not in the release tarball.

Keep the footer credit link if you override the footer.
