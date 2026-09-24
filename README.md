# Qaiyo Clean Gallery

> WordPress gallery plugin with zero frontend dependencies — six layouts picked from visual mock-ups, a built-in lightbox, live-linked HappyFiles folders, and a one-click ZIP export that carries the actual image files to another site.

[![WordPress 5.8+](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org/)
[![PHP 7.4+](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![Version](https://img.shields.io/badge/version-1.4.1-6c5ce7.svg)](#)

This repository hosts the **free** Qaiyo Clean Gallery plugin — live on
[WordPress.org](https://wordpress.org/plugins/qaiyo-clean-gallery/). The optional
[Qaiyo Clean Gallery Pro](https://qaiyo-plugins.com/qaiyo-clean-gallery) add-on adds the studio and
agency side: bulk transfer and site-to-site sync, login-free client delivery links, password
protection and watermarking, image rights and consent records, WooCommerce sources and shoppable
hotspots, a REST API with signed webhooks, white-label, and the Zigzag layout.

- **Website:** [qaiyo-plugins.com](https://qaiyo-plugins.com)
- **Support:** info@qaiyo-plugins.com
- **Issues:** [GitHub Issues](../../issues)

---

## Why this plugin

Most WordPress gallery plugins ship megabytes of vendor code, a jQuery dependency, an upsell inside
every settings screen, and a tracking script nobody asked for. This one is built the other way
round: one custom post type, one shortcode, one block, and a frontend with **no JavaScript
dependencies at all** — no jQuery, no Swiper, no PhotoSwipe, just a few kilobytes of plain modern JS.

Two things it does that gallery plugins generally do not:

- **A gallery is portable.** Export it — with the real image files, not just URLs — into a single
  ZIP and import it on another site. Images are de-duplicated by SHA-1, and a HappyFiles folder
  link is re-established on the far side.
- **Nothing leaves your site.** No phone-home, no CDN in front of your images, no external
  service. The view counter stores one number next to the gallery; that is the whole of it.

The plugin is designed to be:

- **Layout-first** — you pick a layout from drawn mock-ups above the images, not from a dropdown in
  a narrow sidebar, and only the settings that layout actually uses stay on screen.
- **Extensible without forking** — layouts, image sources, the access gate, the manifest and the
  admin surfaces are all documented hooks (see [HOOKS.md](HOOKS.md)). Pro uses exactly those and
  nothing else.
- **Honest about free** — there is no locked code in the free plugin, no trial and no limit. Pro is
  a separate plugin with its own code.
- **Translation-ready** — eleven languages bundled, Polylang/WPML/TranslatePress compatible, with
  locale-variant fallback (`de_CH → de_DE`, `pt_BR → pt_PT`).

---

## Features

### Layouts

Six layouts, all free, all chosen from a visual mock-up picker.

| Layout | What it is |
|---|---|
| **Grid** | Classic 4:3 column grid. |
| **Masonry** | Real variable-height packing (JS grid-span measured from the actual images), with the reading order still running left to right. |
| **Standard** | Uniform 1:1 squares, for product and portfolio sets. |
| **Justified** | Equal-height rows of varying width, Flickr style, with a configurable row height. |
| **Bento** | Asymmetric multi-size grid with hero cells. |
| **Showcase** | Groups of three — one large photo beside two stacked ones — mirrored every second group, so a set reads like a magazine spread. |
| **Zigzag** *(Pro)* | Portrait tiles stepping up and down across the row, with an optional title laid over each image. |

Layouts are a registry, not a hard-coded list: `qaiyo_clean_gallery_layouts` registers one, and the
editor picker, the Gutenberg block and the shortcode all read from the same place.

### Viewing

| Feature | What it does |
|---|---|
| **Lightbox** | Keyboard navigation (←/→/Esc), touch-swipe friendly, image counter, captions, configurable per gallery. |
| **Deep linking** | `/page/#qcg=42&img=3` opens the gallery at that exact image — shareable and bookmarkable. |
| **Social share** | Facebook, X, Pinterest and copy-link, inside the lightbox. |
| **Right-click protection** | Opt-in casual download protection. |
| **All-galleries view** | `[qaiyo_clean_gallery_all]` renders every gallery as a paged card grid with category filters, sorting and a 3/4 column switcher. |

### Media workflow

| Feature | What it does |
|---|---|
| **HappyFiles live-link** | Pick a folder once; the gallery mirrors it forever. Drop images into the folder and they appear — no re-import, no sync step. |
| **ZIP export / import** | A whole gallery — manifest, settings, category path and every image file — in one portable ZIP. See [ZIP export and import](#zip-export-and-import) below. |
| **Duplicate finder** | Finds byte-identical images in the Media Library by SHA-1 fingerprint. |
| **Layout suggestion** | Analyses the aspect ratios actually present in a gallery and proposes the layout that fits them. |
| **Placeholder images** | Neutral sample tiles in the real layout while you wait for the client's photos — shipped with the plugin, nothing added to the Media Library, no external service. They disappear by themselves the moment a real image arrives. |
| **Image compression** | Uploads over 1.5 MB are brought under 1 MB with Imagick or GD, with configurable thresholds. |

### Insight

| Feature | What it does |
|---|---|
| **View counter** | A private per-gallery count: sortable admin column, editor sidebar with a reset, Overview tile. Counted once per visitor session, when the gallery genuinely comes into view. Logged-in editors and known bots are excluded, so the numbers are not inflated while you build the page. No cookies, no IP addresses, nothing sent anywhere. Switch it off any time. |
| **Overview screen** | Galleries, images, views and the largest gallery at a glance, with the Pro cards alongside. |
| **Qaiyo dashboard card** | Reports a small "Galleries / Media stats" summary into the shared Qaiyo ecosystem widget, when one of the sibling plugins hosts it. |

### SEO and accessibility

Schema.org `ImageGallery`, `ImageObject` and `ItemList` markup; native `loading="lazy"`; ARIA
labels and full keyboard navigation; a theme-overridable single template; captions taken from the
Media Library so they stay in one place.

---

### ZIP export and import

A deep dive, because this is the part other gallery plugins do not have:

"Export" in most gallery plugins means a JSON file full of attachment IDs and absolute URLs. Import
it on another site and you get a gallery of broken images, because neither the IDs nor the URLs
mean anything there.

Qaiyo Clean Gallery exports a **self-contained package**: a manifest plus every image file inside
one ZIP. On import it:

1. Rebuilds the gallery, its settings and its category path — creating the terms if they do not exist.
2. De-duplicates by SHA-1, so an image already in the destination Media Library is reused instead
   of uploaded again.
3. Re-establishes a HappyFiles folder link on the destination site when the source gallery had one,
   recreating the folder path if needed.
4. Never needs the source site to stay online — the package carries the files, not references to them.

The package format is a documented, public API (`Qaiyo_Clean_Gallery_Package`), so Pro's bulk
transfer and site-to-site sync are built on the same format rather than a private one.

---

## Qaiyo Clean Gallery Pro

A separate plugin, sold at [qaiyo-plugins.com](https://qaiyo-plugins.com/qaiyo-clean-gallery). It
needs the free plugin (1.4.0 or newer) and does nothing without it. Requires WordPress 6.8, because
delivery tokens are stored with `wp_fast_hash()`, which arrived in that release.

### Move galleries between sites

| Feature | What it does |
|---|---|
| **Bulk export and import** | Every gallery, or the ones you pick, in one ZIP. On the way back in you always get a preview: what will be created, replaced, or skipped. Nothing is written until you approve the plan. |
| **Site-to-site sync** | Pair staging and live once with a WordPress application password, then push or pull only what actually changed. Both sides compare by content, so an untouched gallery is never moved twice. Only paired sites can be contacted, over HTTPS, and two syncs cannot run at once. |
| **REST API** | Read and write galleries from your own tooling. Off until you switch it on; reading needs an editing account, importing an administrator. |
| **Webhooks** | A signed POST whenever a gallery is created, updated or deleted, so another system can keep up. |
| **HappyFiles bulk import** | Turn a whole folder tree into live-linked galleries in one step. Folders that already have one are left alone, so it is safe to run again. |
| **White-label** | Your own agency name on the tool your clients use. The licence page always stays reachable. |

### Deliver photos to clients

| Feature | What it does |
|---|---|
| **Private delivery links** | The client needs no account. They browse, mark favourites, and download the set as a ZIP. Links expire, can be revoked at any time, and every download is counted. |
| **WooCommerce photo delivery** | A completed order creates a draft gallery for itself. Upload the photos, then send the customer their link with one click. |
| **Password-protected galleries** | Until the password is entered the images are not rendered into the page at all — there is no image URL in the source to find. |
| **Watermarking** | Public galleries serve a watermarked copy while the original file stays untouched in the Media Library, ready to hand over once the client has paid. |

### Rights and consent

| Feature | What it does |
|---|---|
| **Image rights tracker** | Where an image came from, what licence it carries, when that licence runs out — with advance warning on what is about to expire. |
| **Consent tracking** | A per-image record of model and photo consent, including withdrawals. |
| **"Used in" index** | For any image, which galleries still use it. When consent is withdrawn or a licence lapses, that is the first question you have. |

This is a record-keeping tool. It helps you track licences and consent; it does not decide what is
lawful, and it is not legal advice.

### Commerce

| Feature | What it does |
|---|---|
| **WooCommerce category galleries** | Point a gallery at a product category and it fills itself, keeping up as products come and go. |
| **Shoppable hotspots** | Put a marker on a photo and attach a product. Visitors tap it and get the price and an add-to-cart button without leaving the gallery. |
| **Customer photo galleries** | Built from photos customers attached to their reviews — approved ones from verified buyers only. Works with Qaiyo WooCommerce Custom Product Reviews. |

### How Pro attaches

Pro patches nothing and adds no code to the free plugin. It uses the same public hooks your own
code can use:

| Seam | Used for |
|---|---|
| `qaiyo_clean_gallery_layouts` / `_render_layout` | registering Zigzag and loading its stylesheet only when it is drawn |
| `qaiyo_clean_gallery_sources` / `_resolve_source` | the WooCommerce category and customer-photo sources |
| `qaiyo_clean_gallery_gallery_access` | the password gate and the delivery-link notice |
| `qaiyo_clean_gallery_image_src` | swapping in the watermarked copy |
| `qaiyo_clean_gallery_settings_fields` / `_save_gallery` | the per-gallery Pro settings |
| `Qaiyo_Clean_Gallery_Package` | bulk transfer and sync reuse the free ZIP format |
| `qaiyo_clean_gallery_overview_cards` | the Pro cards on the Overview screen |

---

## Installation

### From a ZIP file

1. Install directly from **Plugins → Add New** (search "Qaiyo Clean Gallery"), or download a
   [release ZIP](../../releases) and use **Plugins → Add New → Upload Plugin**.
2. Activate the plugin.
3. Go to **Clean Gallery → Add New**, pick a layout from the mock-up picker, add images, publish,
   and copy the shortcode from the sidebar.

### From source (developers)

```bash
git clone https://github.com/qaiyo/qaiyo-clean-gallery.git
cd qaiyo-clean-gallery
# Symlink or copy the folder into wp-content/plugins/
ln -s "$(pwd)" /path/to/wordpress/wp-content/plugins/qaiyo-clean-gallery
```

**Requirements:** WordPress 5.8+, PHP 7.4+, and Imagick or GD for image compression.

### Embedding a gallery

```
[qaiyo_clean_gallery id="42"]
[qaiyo_clean_gallery_all]
```

Or search for **Qaiyo Clean Gallery** in the block inserter.

| `[qaiyo_clean_gallery]` | Values | Default |
|---|---|---|
| `id` | gallery post ID | `0` |
| `ids` | `"101,102,103"` — attachment IDs directly | — |
| `layout` | `grid` · `masonry` · `standard` · `justified` · `bento` · `showcase` (· `zigzag` with Pro) | gallery setting |
| `columns` | `2`–`5` | gallery setting |
| `row_height` | px, for Justified and Showcase | gallery setting |
| `gap` | `0`–`60` | gallery setting |
| `lightbox` · `captions` · `lazy` | `true` / `false` | gallery setting |

| `[qaiyo_clean_gallery_all]` | Values | Default |
|---|---|---|
| `card_columns` / `gallery_columns` | `3` or `4` | `3` |
| `filter` | `true` / `false` | `true` |
| `orderby` / `order` | `date` · `title` / `ASC` · `DESC` | `date` / `DESC` |
| `category` | category slug | — |
| `per_page` | integer | `12` |
| `lightbox` · `captions` · `lazy` · `gap` | as above | inherited |

---

## Developer API

The Pro add-on and sibling Qaiyo plugins extend Clean Gallery only through these documented hooks —
your own code can do the same. Full details, parameters and examples are in [HOOKS.md](HOOKS.md).

### Filters

| Filter | Purpose |
|---|---|
| `qaiyo_clean_gallery_layouts` | Register a layout: label, description, `supports` list and an SVG mock-up for the picker. |
| `qaiyo_clean_gallery_sources` | Register an image source that appears in the editor's source dropdown. |
| `qaiyo_clean_gallery_resolve_source` | Return the attachment IDs for your source. |
| `qaiyo_clean_gallery_gallery_access` | Decide who may see a gallery: `true`, `false`, or replacement markup (filtered through a `wp_kses` allowlist). |
| `qaiyo_clean_gallery_image_src` | Swap the URL served for an image — this is how watermarking works. |
| `qaiyo_clean_gallery_manifest` | Add your own data to an exported package. |
| `qaiyo_clean_gallery_view_counter_enabled` | Turn the view counter off programmatically. |
| `qaiyo_clean_gallery_pro_catalog` / `_pro_active` / `_pro_unlocked_modules` / `_pro_upgrade_url` | The freemium teaser: the catalog lives in the free plugin, Pro only signals what is unlocked. |
| `qaiyo_clean_gallery_cpt_slug` / `_tax_slug` | The gallery and category URL prefixes (default `galeria`). |
| `qaiyo_clean_gallery_happyfiles_taxonomy` | Override the detected HappyFiles taxonomy. |
| `qaiyo_clean_gallery_hf_orderby` / `_hf_order` | Ordering of images inside a live-linked folder. |
| `qaiyo_clean_gallery_justified_row_height` | Row height for the Justified layout. |
| `qaiyo_clean_gallery_compress_threshold` / `_compress_max_bytes` / `_compress_quality_step` / `_compress_quality_floor` | Image-compression tuning. |

### Actions

| Action | Fires |
|---|---|
| `qaiyo_clean_gallery_render_layout` | Just before a layout is drawn — where an add-on enqueues its own stylesheet. |
| `qaiyo_clean_gallery_source_controls` | In the editor, so a custom source can render its own controls. |
| `qaiyo_clean_gallery_settings_fields` / `qaiyo_clean_gallery_save_gallery` | Add and persist your own per-gallery settings. |
| `qaiyo_clean_gallery_imported_gallery` | After a gallery is imported from a package. |
| `qaiyo_clean_gallery_overview_cards` / `qaiyo_clean_gallery_export_import_page` | Add cards to the Overview and Export/Import screens. |

### Template override

Copy `templates/single-qaiyo_clean_gallery.php` into your theme root as
`single-qaiyo_clean_gallery.php`.

---

## Translations

The plugin ships with eleven languages in `/languages/`:

```
qaiyo-clean-gallery.pot
qaiyo-clean-gallery-hu_HU.po + .mo    qaiyo-clean-gallery-it_IT.po + .mo
qaiyo-clean-gallery-de_DE.po + .mo    qaiyo-clean-gallery-ru_RU.po + .mo
qaiyo-clean-gallery-fr_FR.po + .mo    qaiyo-clean-gallery-tr_TR.po + .mo
qaiyo-clean-gallery-es_ES.po + .mo    qaiyo-clean-gallery-pl_PL.po + .mo
qaiyo-clean-gallery-ja.po + .mo       qaiyo-clean-gallery-pt_PT.po + .mo
```

Each locale uses its own plural rules (Russian and Polish three-form, Japanese single-form), and
locale variants fall back to the base locale. Note that Japanese is `ja`, not `ja_JP` — WordPress
ships it under the bare code, and a `ja_JP` file loads for nobody.

New source strings go into a per-language JSON dictionary and are merged into every catalog by a
single script (see *Development* below), which validates printf placeholders and plural forms
rather than trusting them.

The plugin uses `load_textdomain()` directly (not `load_plugin_textdomain()`) to avoid the
WordPress.org Plugin Check warning about discouraged functions.

---

## Standards & security

The codebase follows the WordPress Coding Standards and the WordPress.org Plugin Check rules:

- Class prefix `Qaiyo_Clean_Gallery_`, constants `QAIYO_CLEAN_GALLERY_*`, CSS and JS prefix `qcg`.
- `$_POST`/`$_GET` data is always unslashed and sanitized before use, and every state-changing
  request is capability- and nonce-checked.
- **Markup arriving from another plugin is not trusted.** The access-gate filter may return a
  replacement screen — a password form, a delivery notice — and that markup passes through a
  `wp_kses` allowlist (forms, inputs, labels, the usual text elements; no `script`, `iframe` or
  `style`) before it reaches the page.
- The public view-counter route carries **no nonce, deliberately**: a nonce printed into a cached
  page goes stale and the counter would fail silently. Instead the route validates the gallery
  itself, and editors never receive the beacon attribute in the first place.
- Templates are rendered through a single guarded renderer: the template name must match a narrow
  pattern and the resolved path must sit inside the plugin's own template folder, checked with
  `realpath()`.
- Every dynamic output uses `esc_html`, `esc_attr`, `esc_url` or `wp_kses` — verified with the
  escaping sniff run under `--ignore-annotations`, because a `phpcs:ignore` comment is not proof.
- Multisite-aware uninstall: settings and caches are removed on every site of a network, while
  your galleries are deliberately kept, so reinstalling picks up where you left off.

See [SECURITY.md](SECURITY.md) for the vulnerability disclosure policy.

---

## Development

### Repository layout

```
qaiyo-clean-gallery.php         Main plugin file (header, constants, bootstrap, activation migration)
uninstall.php                   Multisite-aware cleanup — settings and caches go, galleries stay
includes/
  class-qcg-i18n.php            Translation loading (load_textdomain + locale fallback)
  class-qcg-cpt.php             Post type + taxonomy
  class-qcg-layouts.php         Layout registry, `supports` lists and the SVG mock-ups
  class-qcg-view.php            Template renderer (name pattern + realpath containment)
  class-qcg-meta-boxes.php      Save, sanitize, and the public data API other plugins call
  class-qcg-editor.php          The editor screen: box registration, assets, view models
  class-qcg-shortcode.php       Both shortcodes and the access gate
  class-qcg-block.php           Gutenberg block
  class-qcg-assets.php          Frontend stylesheet and script
  class-qcg-single-template.php Theme-overridable single view
  class-qcg-package.php         The ZIP manifest: build, read, validate, import
  class-qcg-export-import.php   The Export / Import screen
  class-qcg-happyfiles.php      Live folder link
  class-qcg-views.php           View counter (public REST route, atomic increment)
  class-qcg-placeholders.php    Sample tiles while the photos are missing
  class-qcg-duplicate-finder.php  SHA-1 fingerprint duplicate search
  class-qcg-layout-advisor.php  Aspect-ratio analysis behind "Suggest a layout"
  class-qcg-image-optimizer.php Upload compression (Imagick or GD)
  class-qcg-settings.php        Info & Help
  class-qcg-overview.php        Overview screen
  class-qcg-ecosystem.php       Summary card for the shared Qaiyo dashboard widget
  class-qcg-pro-catalog.php     Pro catalog — teaser copy only, no locked code
  class-qcg-pro-teaser.php      Lock icon and upsell UI
  class-qcg-brand-menu.php      "QAIYO PLUGINOK" admin menu separator chip
  class-qcg-more-plugins.php    "Discover Qaiyo" panel (WordPress.org API, no phone-home)
templates/
  single-qaiyo_clean_gallery.php  Theme-overridable single template
  admin/                          Display-only templates for the editor boxes, rendered by Qaiyo_Clean_Gallery_View
assets/                         css/, js/ and the bundled placeholder SVGs
languages/                      Eleven bundled translations (see above)
HOOKS.md                        Full developer hook reference
SECURITY.md                     Vulnerability disclosure policy
readme.txt                      WordPress.org readme
```

Everything in this folder is what runs on a site — it is copied verbatim to the WordPress.org SVN.
The test suite, static analysis config, build scripts and the translation generator live in the
sibling `qaiyo-clean-gallery-dev-tools/` folder instead:

```
../qaiyo-clean-gallery-dev-tools/
  composer.json                 PHPUnit 9 + Brain Monkey + PHPStan (dev only)
  phpunit.xml.dist              Test suite + coverage scope
  phpstan.neon.dist             Level 5, WordPress stubs, no worker-memory trap
  stubs/happyfiles.php          The optional third-party API, stubbed rather than ignored
  tests/                        Unit tests (Unit/) + fixtures (Support/)
  build-zips.sh                 Release ZIP builder (full + WordPress.org variant)
  languages/                    build-mo.py + the per-language JSON dictionaries
```

Pro has the same split: `qaiyo-clean-gallery-pro/` ships, `qaiyo-clean-gallery-pro-dev-tools/` does not.

### Quality gates

Run from `../qaiyo-clean-gallery-dev-tools/` before every release:

```bash
composer install
vendor/bin/phpunit                                    # PHPUnit 9 + Brain Monkey, no WordPress install needed
vendor/bin/phpstan analyse --memory-limit=4G          # level 5 — must be zero errors
python3 languages/build-mo.py                         # rebuild and validate every catalog
```

From the workspace root:

```bash
phpcs --standard=WordPress --sniffs=WordPress.Security.EscapeOutput,\
WordPress.Security.NonceVerification,WordPress.Security.ValidatedSanitizedInput,\
WordPress.DB.PreparedSQL --ignore-annotations qaiyo-clean-gallery
phpcs --standard=PHPCompatibilityWP --runtime-set testVersion 7.4- qaiyo-clean-gallery
wp plugin check qaiyo-clean-gallery --include-experimental
```

`--ignore-annotations` matters: a `phpcs:ignore` comment is not proof, and a WordPress.org reviewer
runs the sniff without honouring it. Plugin Check must run under the real folder name, or every
string reports a TextDomainMismatch. Expected: 0 errors.

### Translations

After adding or changing a source string:

```bash
wp i18n make-pot . languages/qaiyo-clean-gallery.pot --exclude=vendor \
    --domain=qaiyo-clean-gallery --skip-audit

cd ../qaiyo-clean-gallery-dev-tools
python3 languages/build-mo.py --resync    # align the dictionaries to the fresh .pot
python3 languages/build-mo.py             # write the .po and .mo files
```

The dictionaries match the `.pot` **by position** and carry a checksum, so the build fails loudly
rather than silently pairing the wrong translation. Moving a string to another file changes the
`make-pot` order — that is what `--resync` is for.

### Building a release ZIP

```bash
cd ../qaiyo-clean-gallery-dev-tools
./build-zips.sh
```

Produces `../qaiyo-clean-gallery.zip` (full, with translations — for self-hosted installs) and
`../qaiyo-clean-gallery-wporg.zip` (no `.po`/`.mo`, since translate.wordpress.org serves those once
the plugin is listed). For WordPress.org itself the plugin folder is copied to SVN directly — the
ZIPs are for everything else.

### Structure rule (no god objects)

A class is either wiring, logic, or display — never all three. Display lives in a view model plus a
template under `templates/admin/`, rendered by `Qaiyo_Clean_Gallery_View`. Before a refactor,
capture the rendered HTML and compare it character for character afterwards.

---

## Contributing

Bug reports and pull requests are welcome via [GitHub Issues](../../issues).

Please follow the existing coding style (tab indentation, WPCS-compliant, PHPDoc on public
methods), add tests for new logic in `qaiyo-clean-gallery-dev-tools/tests/Unit/`, do not introduce a
frontend dependency, and add an entry to the `readme.txt` changelog under the next version. If you
touch a user-facing string, regenerate the `.pot` and run the translation build.

---

## License

GPL-2.0-or-later. See <https://www.gnu.org/licenses/gpl-2.0.html>.

---

## Credits

Made by **[Qaiyo](https://qaiyo-plugins.com)**.
Part of the Qaiyo plugin family — a set of WordPress plugins that share a brand, a design system,
and a coordinated admin experience.

HappyFiles integration uses the public HappyFiles taxonomy; no affiliation.

Contact: info@qaiyo-plugins.com
