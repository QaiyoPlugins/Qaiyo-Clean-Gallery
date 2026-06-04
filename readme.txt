=== Qaiyo Clean Gallery ===
Contributors: pixeldesigns
Tags: gallery, masonry, lightbox, images, happyfiles
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.6.1
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, professional gallery plugin with Grid, Masonry, Lightbox, filtering, lazy loading and HappyFiles compatibility. No bloat, no dependencies.

== Description ==

Qaiyo Clean Gallery is a focused, dependency-free WordPress gallery plugin built for performance and clean markup. Each gallery is a custom post type with its own settings, fully usable via Gutenberg block or shortcode.

= Key features =

* Grid and Masonry layouts
* Built-in accessible lightbox (keyboard + swipe friendly)
* Category filter, sort, paging (load more) and column switcher on the all-galleries view
* Automatic image compression on upload (>1.5 MB → <1 MB)
* Native browser lazy loading
* SEO: schema.org/ImageGallery and ItemList structured data
* HappyFiles plugin integration — bulk import images from a HappyFiles folder
* Zero JavaScript dependencies on the frontend
* Translations included: English, Hungarian, German, French, Spanish

= Shortcodes =

* `[clean_gallery id="42"]` — single gallery
* `[clean_gallery_all card_columns="3" filter="true"]` — all galleries with paging, filter and sort

= Brand =

Qaiyo Clean Gallery is part of the Qaiyo plugin family by **Qaiyo by PixelDesigns** — https://qaiyo-plugins.com — support: info@qaiyo-plugins.com

== Installation ==

1. Upload the `qaiyo-clean-gallery` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Create your first gallery under **Clean Gallery → Add New**.
4. Embed it with the block or shortcode.

== Frequently Asked Questions ==

= Does it work with HappyFiles? =

Yes. If HappyFiles (free or Pro) is active, an "Import from HappyFiles" dropdown appears on the gallery editor.

= Can I override the single gallery template? =

Yes. Copy `templates/single-clean_gallery.php` into your theme root.

== Changelog ==

= 0.6.1 =
* Brand: official domain is now **qaiyo-plugins.com**, official email **info@qaiyo-plugins.com**, author is **Qaiyo by PixelDesigns**. Plugin URI, Author URI, admin footer and translations updated.

= 0.6.0 =
* New: **Deep linking** — opening an image in the lightbox updates the URL with `#qcg={id}&img={index}`. Sharing a link opens the gallery at the exact image. Toggle in *Info & Help → Lightbox & Protection*.
* New: **Social share buttons** inside the lightbox (Facebook, X, Pinterest, copy link). Pinterest pins the actual image; Facebook/X share the deep-linked page URL.
* New: **Right-click protection** — disables right-click and image drag on gallery images. Casual download protection, opt-in.

= 0.5.4 =
* Fixed: Qaiyo brand menu separator now uses the chip-style SVG label shared with all Qaiyo plugins. The old "QAIYO PLUGINS" fake menu item that broke onto two lines is removed.
* Shared with the other Qaiyo plugins: top + bottom separators are injected at admin_menu priority 999 by scanning the live $menu.

= 0.5.3 =
* New: Three additional gallery layouts — **Standard** (uniform squares), **Justified** (equal-height rows with varying widths), **Bento** (asymmetric multi-sized grid with hero cells).
* Filter: `qcg_justified_row_height` for tuning the Justified row target height (default 240).

= 0.5.2 =
* Fixed: Sort dropdown and 3/4-column switcher always render on the frontend, even when the category filter is disabled or no categories exist.

= 0.5.1 =
* New: Shortcode meta box on the gallery editor (right column, below Publish).
* New: HappyFiles **live link** mode — pick a folder and the gallery auto-syncs with no import step.
* Improved: "Gallery Images" meta box is always the first one in the normal column (above SEO plugins).
* UI: Read-only preview grid when the gallery is live-linked to a HappyFiles folder.

= 0.5.0 =
* First public Qaiyo release.
* Full WP.org Plugin Check compliance audit.
* Qaiyo brand UI (admin menu group, design tokens).
* 5-language i18n: EN / HU / DE / FR / ES.
* HappyFiles plugin compatibility (folder-based bulk import).
* SEO: schema.org markup on galleries and items.
