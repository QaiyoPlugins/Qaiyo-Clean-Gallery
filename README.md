# Qaiyo Clean Gallery

> A lightweight, professional WordPress gallery plugin — Grid, Masonry, Standard, Justified, Bento layouts with built-in lightbox, filtering, deep linking, social share, image compression and HappyFiles live-link. No bloat, zero JavaScript dependencies.

![License](https://img.shields.io/badge/license-GPL--2.0--or--later-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4)
![Version](https://img.shields.io/badge/version-0.6.1-6c5ce7)

---

## Why Qaiyo Clean Gallery?

Most WordPress gallery plugins ship with megabytes of vendor code, jQuery dependencies, premium upsells inside every settings page, and tracking scripts you didn't ask for.

**Qaiyo Clean Gallery is the opposite.** A single CPT, a single shortcode, a single block. The frontend ships **zero JavaScript dependencies** — no jQuery, no Swiper, no PhotoSwipe. Just ~7 KB of plain modern JS that runs in every browser.

If you want a gallery plugin that **renders fast, looks sharp, and doesn't fight your theme**, this is it.

---

## ✨ Features

### Gallery layouts (all included, free)

- **Grid** — classic 4:3 column grid
- **Masonry** — Pinterest-style variable height columns
- **Standard** — uniform 1:1 squares for e-commerce / portfolio
- **Justified** — equal-height rows with varying widths (Flickr style)
- **Bento** — asymmetric multi-sized grid with hero cells

### Lightbox

- Keyboard navigation (←/→/Esc)
- Touch swipe friendly
- Image counter, captions, mobile responsive
- Configurable per gallery

### Filter & sort (on the all-galleries view)

- Category filter buttons
- Sort by date (newest/oldest) or title (A→Z / Z→A)
- 3 / 4 column switcher with customizable icon colors
- Load more pagination

### Power features other plugins charge for

- **Deep linking** — `/page/#qcg=42&img=3` opens the gallery at the exact image. Shareable, bookmarkable.
- **Social share buttons** — Facebook, X, Pinterest, copy-link directly inside the lightbox
- **Right-click protection** — opt-in casual download protection
- **Automatic image compression** — uploads over 1.5 MB are compressed to under 1 MB (Imagick or GD)
- **HappyFiles live-link** — pick a HappyFiles folder once, images stay in sync forever. No re-importing

### SEO & accessibility

- Schema.org `ImageGallery`, `ImageObject` and `ItemList` markup
- Native browser lazy loading (`loading="lazy"`)
- ARIA labels and keyboard navigation throughout
- Theme-overridable single template

### Internationalization

Ships with translations in:

- 🇬🇧 English (en_US)
- 🇭🇺 Hungarian (hu_HU)
- 🇩🇪 German (de_DE)
- 🇫🇷 French (fr_FR)
- 🇪🇸 Spanish (es_ES)

Locale variants (de_CH, fr_BE, es_MX, …) automatically map to the base locale.

---

## 📦 Installation

### From the WordPress admin

1. Download the latest release ZIP
2. **Plugins → Add New → Upload Plugin**
3. Choose `qaiyo-clean-gallery.zip` and click **Install Now**
4. **Activate**

### Manual

```bash
cd wp-content/plugins
git clone https://github.com/qaiyo-plugins/qaiyo-clean-gallery.git
```

Then activate from **Plugins**.

---

## 🚀 Usage

### Create your first gallery

1. **Clean Gallery → Add New**
2. Title it (e.g. „Summer 2026")
3. Click **Add / Insert Images** and pick from your Media Library
4. Drag-and-drop to reorder
5. Configure layout, columns, lightbox, etc. in the **Gallery Settings** box
6. **Publish**
7. Copy the shortcode from the **Shortcode** meta box on the right

### Embed in a post / page

**Shortcode:**

```
[clean_gallery id="42"]
```

**Gutenberg block:**

Search for **„Qaiyo Clean Gallery"** in the block inserter.

### Show all galleries as a paged card grid

```
[clean_gallery_all]
```

With every option:

```
[clean_gallery_all
    card_columns="3"
    gallery_columns="3"
    filter="true"
    orderby="date"
    order="DESC"
    per_page="12"]
```

### Shortcode parameters — `[clean_gallery]`

| Parameter  | Values                | Default       | Description |
|------------|-----------------------|---------------|-------------|
| `id`       | integer               | 0             | Gallery CPT post ID |
| `ids`      | `"101,102,103"`       | —             | Direct attachment IDs (when there's no CPT post) |
| `layout`   | `grid` / `masonry` / `standard` / `justified` / `bento` | CPT setting | Layout type |
| `columns`  | `2` – `5`             | CPT setting   | Number of columns |
| `lightbox` | `true` / `false`      | CPT setting   | Enable lightbox |
| `captions` | `true` / `false`      | CPT setting   | Show captions |
| `lazy`     | `true` / `false`      | CPT setting   | Native lazy loading |
| `gap`      | `0` – `60` (px)       | CPT setting   | Gap between images |

---

## 🔗 HappyFiles integration

If you have the [HappyFiles](https://wordpress.org/plugins/happyfiles/) plugin installed (free or Pro), Qaiyo Clean Gallery detects it automatically and adds a **HappyFiles folder (live link)** dropdown to the gallery editor.

**Pick a folder → save → done.** The gallery now mirrors that folder live. Drop new images into the HappyFiles folder via the Media Library and they appear in the gallery instantly. No re-importing, no syncing.

This makes Qaiyo Clean Gallery a perfect fit for sites that already organize media in HappyFiles.

---

## ⚙️ Settings

**Clean Gallery → Info & Help** in the admin sidebar.

### Column switcher icon colors

Pick custom colors for the 3 / 4 column switcher on the `[clean_gallery_all]` view:

- Default color (idle state)
- Hover color
- Active color

Live preview included.

### Lightbox & Protection

- **Deep linking** — adds `#qcg={id}&img={index}` to the URL when navigating the lightbox
- **Social share buttons** — Facebook / X / Pinterest / copy-link inside the lightbox
- **Right-click protection** — disables right-click + image drag (opt-in)

---

## 🛠 Developer hooks

### Filters

```php
// Justified layout target row height (default: 240 px).
add_filter( 'qcg_justified_row_height', function ( $h, $gallery_id, $settings ) {
    return 320;
}, 10, 3 );

// HappyFiles taxonomy override (default auto-detected).
add_filter( 'qcg_happyfiles_taxonomy', function () {
    return 'happyfiles_category';
} );

// HappyFiles folder image order.
add_filter( 'qcg_hf_orderby', fn() => 'date' );
add_filter( 'qcg_hf_order',   fn() => 'DESC' );

// Image compression thresholds.
add_filter( 'qcg_compress_threshold',     fn() => 2 * 1024 * 1024 ); // 2 MB
add_filter( 'qcg_compress_max_bytes',     fn() => 800 * 1024 );      // 800 KB target
add_filter( 'qcg_compress_quality_step',  fn() => 5 );
add_filter( 'qcg_compress_quality_floor', fn() => 30 );

// Single gallery URL slug (default: 'galeria').
add_filter( 'qcg_cpt_slug', fn() => 'gallery' );
add_filter( 'qcg_tax_slug', fn() => 'gallery-category' );
```

### Template override

The single gallery template can be overridden in your theme by copying:

```
qaiyo-clean-gallery/templates/single-clean_gallery.php
```

into your theme root as:

```
single-clean_gallery.php
```

---

## 📁 Project structure

```
qaiyo-clean-gallery/
├── qaiyo-clean-gallery.php       # Bootstrap
├── readme.txt                    # WordPress.org readme
├── README.md                     # This file
├── uninstall.php                 # Cleanup on uninstall
├── includes/
│   ├── class-qcg-i18n.php
│   ├── class-qcg-brand-menu.php
│   ├── class-qcg-cpt.php
│   ├── class-qcg-meta-boxes.php
│   ├── class-qcg-shortcode.php
│   ├── class-qcg-single-template.php
│   ├── class-qcg-assets.php
│   ├── class-qcg-block.php
│   ├── class-qcg-image-optimizer.php
│   ├── class-qcg-happyfiles.php
│   └── class-qcg-settings.php
├── assets/
│   ├── css/{gallery,admin}.css
│   └── js/{gallery,admin,block,settings}.js
├── languages/
│   ├── qaiyo-clean-gallery.pot
│   ├── *.po + *.mo (en, hu, de, fr, es)
│   └── build-mo.py               # Translation builder
└── templates/
    └── single-clean_gallery.php  # Theme-overridable
```

---

## 🧪 Requirements

| Dependency | Minimum |
|---|---|
| WordPress | 5.8 |
| PHP       | 7.4 |
| Image lib | Imagick (preferred) or GD |
| Browser   | Last 2 versions of Chrome, Firefox, Safari, Edge |

No external runtime dependencies. No jQuery on the frontend.

---

## 🌍 Translations

Translations live in `languages/`. To regenerate the `.po`/`.mo` files after editing the source strings:

```bash
cd languages
python3 build-mo.py
```

This regenerates the `.pot` template and all five language `.po` + `.mo` files in one pass.

Pull requests adding new languages are welcome — see `build-mo.py` for the dictionary format.

---

## 🤝 Contributing

Issues and pull requests welcome.

Before submitting:

1. Match the existing code style (WordPress coding standards, tab indentation)
2. Run through the PHPCS WordPress ruleset locally if you can
3. Don't introduce new frontend dependencies (no jQuery, no NPM packages)
4. Test on the latest WordPress version

For bigger features, open an issue first to discuss the scope.

---

## 📜 License

GPL-2.0-or-later — see the included `LICENSE` file or [gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html).

You're free to use this on as many sites as you want, modify it, fork it, redistribute it — as long as derivative work stays under GPL.

---

## 👤 Author

**Qaiyo by PixelDesigns**

🌐 [qaiyo-plugins.com](https://qaiyo-plugins.com)
✉️ info@qaiyo-plugins.com

Part of the Qaiyo plugin family for WordPress.

---

## 🙏 Credits

- HappyFiles integration is based on the public HappyFiles taxonomy API — no affiliation
- Schema.org markup follows the official ImageGallery / ImageObject specifications
- Inspired by clean-code gallery plugins that respect both developers and users
