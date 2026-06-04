#!/usr/bin/env python3
"""
Qaiyo Clean Gallery – .po + .mo builder (Qaiyo i18n szabvány).

Egy futtatással regenerálja a .pot sablont és minden támogatott nyelv
.po + .mo fájlját. msgfmt-mentes (saját binary writer).

Futtatás:  python3 build-mo.py
"""

import os
import struct
from collections import OrderedDict

DOMAIN = "qaiyo-clean-gallery"
PLUGIN_VERSION = "0.6.1"
HERE = os.path.dirname(os.path.abspath(__file__))

# ---------------------------------------------------------------------------
# Source strings (msgid → English msgstr in .pot; per-locale overrides below)
# ---------------------------------------------------------------------------

SOURCE = [
    "Qaiyo Plugins",
    # CPT labels
    "Galleries", "Gallery", "Add New", "Add New Gallery", "Edit Gallery",
    "New Gallery", "View Gallery", "Search Galleries", "No galleries found.",
    "No galleries in Trash.", "Clean Gallery",
    # Taxonomy labels
    "Gallery Categories", "Gallery Category", "Search Categories",
    "All Categories", "Edit Category", "Update Category", "Add New Category",
    "New Category Name", "Categories",
    # Meta boxes
    "Gallery Images", "Gallery Settings",
    "Select Gallery Images", "Add to Gallery", "Remove",
    "No images yet. Click \"Add / Insert Images\" to get started.",
    "Add / Insert Images", "Remove All", "— HappyFiles folder —",
    "Import from HappyFiles", "Import from HappyFiles folder",
    "— Select a folder —", "Importing…",
    "%d image(s) imported from HappyFiles folder.",
    "This folder has no images.", "Could not load HappyFiles folders.",
    "Large image: \"%1$s\" (%2$s). Qaiyo Clean Gallery will automatically compress it to under 1 MB after upload.",
    "%d large image(s) detected (over 1.5 MB each). Qaiyo Clean Gallery will automatically compress them after upload.",
    # Settings rows
    "Layout", "Columns", "Lightbox", "Captions", "Lazy loading", "Gap (px)",
    "Grid", "Masonry",
    # Optimizer
    "Neither Imagick nor GD is available on this server.",
    "GD WebP support not compiled in.",
    "Unsupported image type for GD compression.",
    "GD WebP support not available.",
    "Unsupported image type.",
    "Optimized by Qaiyo Clean Gallery: %1$s → %2$s (saved %3$d%%, engine: %4$s)",
    "Qaiyo Clean Gallery",
    "Compression failed: ",
    # Block
    "Display a gallery with grid or masonry layout, lightbox and filtering.",
    "Select Gallery", "Gallery", "Options",
    "Show filter bar", "Select a gallery in the sidebar to show a preview.",
    "— Select gallery —",
    # Shortcode / frontend
    "No galleries available.", "Gallery filter", "All",
    "Sort order", "Date: newest first", "Date: oldest first",
    "A → Z", "Z → A", "3 columns", "4 columns",
    "Load more", "Close", "Previous", "Next",
    "images", "(%1$d / %2$d remaining)", "Open gallery",
    # Single template
    "Back to galleries", "%d image", "%d images",
    "No images uploaded for this gallery yet.",
    # HF AJAX
    "Insufficient permissions.", "HappyFiles plugin is not active.",
    "Invalid folder.",
    # Settings page
    "Info & Help",
    "Lightweight, professional gallery plugin — Grid, Masonry, Lightbox, filtering and lazy loading. No bloat, no dependencies.",
    "Getting started",
    "Create a new gallery under <a href=\"%s\">Clean Gallery → Add New</a>.",
    "Pick images from the Media Library, set layout and columns, then publish.",
    "Embed it with the <code>[clean_gallery id=\"X\"]</code> shortcode or the <strong>Qaiyo Clean Gallery</strong> Gutenberg block.",
    "Shortcodes",
    "single gallery view",
    "all galleries grid with paging, sorting and category filter",
    "HappyFiles integration", "ACTIVE", "NOT DETECTED",
    "HappyFiles is active. On the gallery editor screen you can pick a HappyFiles folder and bulk-import all its images.",
    "Install HappyFiles (free or Pro) to enable bulk-importing entire media folders into a gallery.",
    "Qaiyo Clean Gallery v%s — by Qaiyo by PixelDesigns · qaiyo-plugins.com",
    # Column switcher color settings
    "Column switcher icon colors",
    "These colors are used for the 3-/4-column switcher icons on the [clean_gallery_all] view.",
    "Default color", "Hover color", "Active color",
    "Preview", "Columns preview", "Save changes",
    # 0.6.0 — shortcode box + HF live link
    "Shortcode", "All galleries (paged)",
    "Save the gallery first to see its shortcode.",
    "Paste the shortcode into any post, page or widget to embed this gallery.",
    "Copy", "Copied!",
    "HappyFiles folder (live link)",
    "— None (manual gallery) —",
    "Select a folder to live-link this gallery. Images stay in sync with HappyFiles automatically — no import needed.",
    "Live-linked to HappyFiles folder %1$s (%2$d image(s)). Add or remove images directly in HappyFiles — this gallery updates automatically.",
    # 0.6.0 — new layouts
    "Standard (uniform squares)",
    "Justified (equal-height rows)",
    "Bento (asymmetric)",
    # v0.6.0 — deep linking, social share, right-click
    "Lightbox & Protection",
    "Deep linking",
    "Add #hash to the URL when navigating the lightbox, so individual images can be linked and shared directly.",
    "Social share buttons",
    "Show Facebook, X, Pinterest and copy-link buttons inside the lightbox.",
    "Right-click protection",
    "Disable right-click and drag on gallery images. Prevents casual downloading (not bulletproof).",
    "Copy link",
]

# Plural forms (used with _n() in PHP)
PLURALS = [
    ("%d image", "%d images"),
]

# ---------------------------------------------------------------------------
# Translations
# ---------------------------------------------------------------------------

HU = {
    "Qaiyo Plugins": "Qaiyo pluginok",
    "Galleries": "Galériák", "Gallery": "Galéria", "Add New": "Új hozzáadása",
    "Add New Gallery": "Új galéria", "Edit Gallery": "Galéria szerkesztése",
    "New Gallery": "Új galéria", "View Gallery": "Galéria megtekintése",
    "Search Galleries": "Galériák keresése",
    "No galleries found.": "Nem található galéria.",
    "No galleries in Trash.": "Nincs törölt galéria.",
    "Clean Gallery": "Clean Gallery",
    "Gallery Categories": "Galéria kategóriák", "Gallery Category": "Galéria kategória",
    "Search Categories": "Kategóriák keresése", "All Categories": "Összes kategória",
    "Edit Category": "Kategória szerkesztése", "Update Category": "Kategória frissítése",
    "Add New Category": "Új kategória", "New Category Name": "Új kategória neve",
    "Categories": "Kategóriák",
    "Gallery Images": "Galéria képek", "Gallery Settings": "Galéria beállítások",
    "Select Gallery Images": "Galéria képek kiválasztása",
    "Add to Gallery": "Hozzáadás a galériához", "Remove": "Eltávolítás",
    "No images yet. Click \"Add / Insert Images\" to get started.":
        "Még nincs kép. Kattints az „Új képek hozzáadása\" gombra a kezdéshez.",
    "Add / Insert Images": "Új képek hozzáadása", "Remove All": "Mind eltávolítása",
    "— HappyFiles folder —": "— HappyFiles mappa —",
    "Import from HappyFiles": "Importálás HappyFilesból",
    "Import from HappyFiles folder": "Importálás HappyFiles mappából",
    "— Select a folder —": "— Válassz mappát —", "Importing…": "Importálás…",
    "%d image(s) imported from HappyFiles folder.":
        "%d kép importálva a HappyFiles mappából.",
    "This folder has no images.": "Ebben a mappában nincs kép.",
    "Could not load HappyFiles folders.": "Nem sikerült betölteni a HappyFiles mappákat.",
    "Large image: \"%1$s\" (%2$s). Qaiyo Clean Gallery will automatically compress it to under 1 MB after upload.":
        "Nagy méretű kép: „%1$s\" (%2$s). A Qaiyo Clean Gallery feltöltés után automatikusan 1 MB alá tömöríti.",
    "%d large image(s) detected (over 1.5 MB each). Qaiyo Clean Gallery will automatically compress them after upload.":
        "%d nagy méretű kép észlelve (egyenként 1.5 MB felett). A Qaiyo Clean Gallery feltöltés után automatikusan tömöríti őket.",
    "Layout": "Elrendezés", "Columns": "Oszlopok", "Lightbox": "Lightbox",
    "Captions": "Feliratok", "Lazy loading": "Lazy loading", "Gap (px)": "Térköz (px)",
    "Grid": "Rács", "Masonry": "Masonry",
    "Neither Imagick nor GD is available on this server.":
        "Sem az Imagick, sem a GD nem érhető el ezen a szerveren.",
    "Optimized by Qaiyo Clean Gallery: %1$s → %2$s (saved %3$d%%, engine: %4$s)":
        "Optimalizálta a Qaiyo Clean Gallery: %1$s → %2$s (megtakarítás: %3$d%%, motor: %4$s)",
    "Qaiyo Clean Gallery": "Qaiyo Clean Gallery",
    "Compression failed: ": "A tömörítés sikertelen: ",
    "Display a gallery with grid or masonry layout, lightbox and filtering.":
        "Galéria megjelenítése rács vagy masonry elrendezéssel, lightboxszal és szűrővel.",
    "Select Gallery": "Galéria kiválasztása", "Options": "Beállítások",
    "Show filter bar": "Szűrősáv megjelenítése",
    "Select a gallery in the sidebar to show a preview.":
        "Válassz galériát az oldalsávban az előnézethez.",
    "— Select gallery —": "— Válassz galériát —",
    "No galleries available.": "Nincs megjeleníthető galéria.",
    "Gallery filter": "Galéria szűrő", "All": "Összes",
    "Sort order": "Rendezés",
    "Date: newest first": "Dátum: legújabb elöl",
    "Date: oldest first": "Dátum: legrégebbi elöl",
    "A → Z": "ABC: A → Z", "Z → A": "ABC: Z → A",
    "3 columns": "3 oszlop", "4 columns": "4 oszlop",
    "Load more": "Továbbiak betöltése",
    "Close": "Bezárás", "Previous": "Előző", "Next": "Következő",
    "images": "kép",
    "(%1$d / %2$d remaining)": "(%1$d / %2$d maradt)",
    "Open gallery": "Galéria megnyitása",
    "Back to galleries": "Vissza a galériákhoz",
    "No images uploaded for this gallery yet.":
        "Ehhez a galériához még nincsenek képek feltöltve.",
    "Insufficient permissions.": "Nincs megfelelő jogosultság.",
    "HappyFiles plugin is not active.": "A HappyFiles plugin nem aktív.",
    "Invalid folder.": "Érvénytelen mappa.",
    "Info & Help": "Info & Segítség",
    "Lightweight, professional gallery plugin — Grid, Masonry, Lightbox, filtering and lazy loading. No bloat, no dependencies.":
        "Letisztult, gyors, professzionális galéria plugin — Rács, Masonry, Lightbox, szűrés és lazy loading. Nulla függőség.",
    "Getting started": "Kezdő lépések",
    "Create a new gallery under <a href=\"%s\">Clean Gallery → Add New</a>.":
        "Hozz létre új galériát: <a href=\"%s\">Clean Gallery → Új hozzáadása</a>.",
    "Pick images from the Media Library, set layout and columns, then publish.":
        "Válassz képeket a Média könyvtárból, állítsd be az elrendezést és oszlopokat, majd publikáld.",
    "Embed it with the <code>[clean_gallery id=\"X\"]</code> shortcode or the <strong>Qaiyo Clean Gallery</strong> Gutenberg block.":
        "Ágyazd be a <code>[clean_gallery id=\"X\"]</code> shortcode-dal vagy a <strong>Qaiyo Clean Gallery</strong> Gutenberg blokkal.",
    "Shortcodes": "Shortcode-ok",
    "single gallery view": "egy galéria megjelenítése",
    "all galleries grid with paging, sorting and category filter":
        "összes galéria kártyás listája lapozással, rendezéssel és kategória szűrővel",
    "HappyFiles integration": "HappyFiles integráció",
    "ACTIVE": "AKTÍV", "NOT DETECTED": "NEM ÉSZLELVE",
    "HappyFiles is active. On the gallery editor screen you can pick a HappyFiles folder and bulk-import all its images.":
        "A HappyFiles aktív. A galéria szerkesztőben kiválaszthatsz egy HappyFiles mappát, és tömegesen importálhatod az összes képét.",
    "Install HappyFiles (free or Pro) to enable bulk-importing entire media folders into a gallery.":
        "Telepítsd a HappyFilest (ingyenes vagy Pro), hogy tömegesen importálhass teljes mappákat a galériába.",
    "Qaiyo Clean Gallery v%s — by Qaiyo by PixelDesigns · qaiyo-plugins.com":
        "Qaiyo Clean Gallery v%s — Készítette: Qaiyo by PixelDesigns · qaiyo-plugins.com",
    "Column switcher icon colors": "Oszlopváltó ikon színek",
    "These colors are used for the 3-/4-column switcher icons on the [clean_gallery_all] view.":
        "Ezek a színek vonatkoznak a [clean_gallery_all] nézet 3 / 4 oszlop kapcsoló ikonjaira.",
    "Default color": "Alap szín", "Hover color": "Hover szín", "Active color": "Aktív szín",
    "Preview": "Előnézet", "Columns preview": "Oszlopváltó előnézet",
    "Save changes": "Módosítások mentése",
    "Shortcode": "Shortcode",
    "All galleries (paged)": "Összes galéria (lapozható)",
    "Save the gallery first to see its shortcode.":
        "Mentsd el a galériát a shortcode megjelenítéséhez.",
    "Paste the shortcode into any post, page or widget to embed this gallery.":
        "Másold a shortcode-ot bármely bejegyzésbe, oldalba vagy widgetbe a galéria megjelenítéséhez.",
    "Copy": "Másolás", "Copied!": "Másolva!",
    "HappyFiles folder (live link)": "HappyFiles mappa (élő kapcsolat)",
    "— None (manual gallery) —": "— Nincs (kézi galéria) —",
    "Select a folder to live-link this gallery. Images stay in sync with HappyFiles automatically — no import needed.":
        "Válassz mappát az élő összekötéshez. A képek automatikusan szinkronban maradnak a HappyFilesszal — nincs szükség importra.",
    "Live-linked to HappyFiles folder %1$s (%2$d image(s)). Add or remove images directly in HappyFiles — this gallery updates automatically.":
        "Élő kapcsolat a %1$s HappyFiles mappával (%2$d kép). Adj hozzá vagy törölj képeket közvetlenül a HappyFilesban — ez a galéria automatikusan frissül.",
    "Standard (uniform squares)": "Standard (egyforma négyzetek)",
    "Justified (equal-height rows)": "Justified (egyenlő magas sorok)",
    "Bento (asymmetric)": "Bento (aszimmetrikus)",
    "Lightbox & Protection": "Lightbox és védelem",
    "Deep linking": "Deep linking",
    "Add #hash to the URL when navigating the lightbox, so individual images can be linked and shared directly.":
        "#hash hozzáadása az URL-hez a lightbox navigálásakor, így az egyes képek közvetlenül linkelhetők és megoszthatók.",
    "Social share buttons": "Közösségi megosztó gombok",
    "Show Facebook, X, Pinterest and copy-link buttons inside the lightbox.":
        "Facebook, X, Pinterest és link másolás gombok megjelenítése a lightboxban.",
    "Right-click protection": "Jobb klikk védelem",
    "Disable right-click and drag on gallery images. Prevents casual downloading (not bulletproof).":
        "Jobb klikk és húzás tiltása a galéria képein. Megakadályozza az egyszerű letöltést (nem teljesen áthatolhatatlan).",
    "Copy link": "Link másolása",
    # Plurals (n forms separated by \0 below in writer)
    "%d image": ("%d kép", "%d kép"),
}

DE = {
    "Qaiyo Plugins": "Qaiyo Plugins",
    "Galleries": "Galerien", "Gallery": "Galerie", "Add New": "Neu hinzufügen",
    "Add New Gallery": "Neue Galerie", "Edit Gallery": "Galerie bearbeiten",
    "New Gallery": "Neue Galerie", "View Gallery": "Galerie ansehen",
    "Search Galleries": "Galerien suchen",
    "No galleries found.": "Keine Galerien gefunden.",
    "No galleries in Trash.": "Keine Galerien im Papierkorb.",
    "Clean Gallery": "Clean Gallery",
    "Gallery Categories": "Galerie-Kategorien", "Gallery Category": "Galerie-Kategorie",
    "Categories": "Kategorien", "All Categories": "Alle Kategorien",
    "Gallery Images": "Galeriebilder", "Gallery Settings": "Galerie-Einstellungen",
    "Add / Insert Images": "Bilder hinzufügen", "Remove All": "Alle entfernen",
    "Remove": "Entfernen", "Add to Gallery": "Zur Galerie hinzufügen",
    "Select Gallery Images": "Galeriebilder auswählen",
    "Layout": "Layout", "Columns": "Spalten", "Lightbox": "Lightbox",
    "Captions": "Bildunterschriften", "Lazy loading": "Lazy Loading",
    "Gap (px)": "Abstand (px)", "Grid": "Raster", "Masonry": "Masonry",
    "— HappyFiles folder —": "— HappyFiles-Ordner —",
    "Import from HappyFiles": "Aus HappyFiles importieren",
    "All": "Alle", "Load more": "Mehr laden",
    "Close": "Schließen", "Previous": "Zurück", "Next": "Weiter",
    "images": "Bilder", "Open gallery": "Galerie öffnen",
    "Back to galleries": "Zurück zu den Galerien",
    "No images uploaded for this gallery yet.":
        "Für diese Galerie wurden noch keine Bilder hochgeladen.",
    "No galleries available.": "Keine Galerien verfügbar.",
    "Sort order": "Sortierung",
    "Date: newest first": "Datum: neueste zuerst",
    "Date: oldest first": "Datum: älteste zuerst",
    "Info & Help": "Info & Hilfe",
    "Getting started": "Erste Schritte",
    "Shortcodes": "Shortcodes",
    "HappyFiles integration": "HappyFiles-Integration",
    "ACTIVE": "AKTIV", "NOT DETECTED": "NICHT ERKANNT",
    "Column switcher icon colors": "Spaltenwechsler-Symbolfarben",
    "Default color": "Standardfarbe", "Hover color": "Hover-Farbe", "Active color": "Aktive Farbe",
    "Preview": "Vorschau", "Save changes": "Änderungen speichern",
    "Shortcode": "Shortcode",
    "Copy": "Kopieren", "Copied!": "Kopiert!",
    "HappyFiles folder (live link)": "HappyFiles-Ordner (Live-Verknüpfung)",
    "— None (manual gallery) —": "— Keiner (manuelle Galerie) —",
    "Standard (uniform squares)": "Standard (gleichmäßige Quadrate)",
    "Justified (equal-height rows)": "Justified (gleich hohe Reihen)",
    "Bento (asymmetric)": "Bento (asymmetrisch)",
    "Lightbox & Protection": "Lightbox & Schutz",
    "Deep linking": "Deep Linking",
    "Social share buttons": "Social-Share-Buttons",
    "Right-click protection": "Rechtsklick-Schutz",
    "Copy link": "Link kopieren",
    "%d image": ("%d Bild", "%d Bilder"),
}

FR = {
    "Qaiyo Plugins": "Plugins Qaiyo",
    "Galleries": "Galeries", "Gallery": "Galerie", "Add New": "Ajouter",
    "Add New Gallery": "Nouvelle galerie", "Edit Gallery": "Modifier la galerie",
    "New Gallery": "Nouvelle galerie", "View Gallery": "Voir la galerie",
    "Search Galleries": "Rechercher des galeries",
    "No galleries found.": "Aucune galerie trouvée.",
    "No galleries in Trash.": "Aucune galerie dans la corbeille.",
    "Clean Gallery": "Clean Gallery",
    "Gallery Categories": "Catégories de galeries", "Gallery Category": "Catégorie de galerie",
    "Categories": "Catégories", "All Categories": "Toutes les catégories",
    "Gallery Images": "Images de la galerie", "Gallery Settings": "Réglages de la galerie",
    "Add / Insert Images": "Ajouter des images", "Remove All": "Tout supprimer",
    "Remove": "Supprimer", "Add to Gallery": "Ajouter à la galerie",
    "Select Gallery Images": "Sélectionner les images",
    "Layout": "Disposition", "Columns": "Colonnes", "Lightbox": "Lightbox",
    "Captions": "Légendes", "Lazy loading": "Chargement différé",
    "Gap (px)": "Espacement (px)", "Grid": "Grille", "Masonry": "Masonry",
    "— HappyFiles folder —": "— Dossier HappyFiles —",
    "Import from HappyFiles": "Importer depuis HappyFiles",
    "All": "Toutes", "Load more": "Charger plus",
    "Close": "Fermer", "Previous": "Précédent", "Next": "Suivant",
    "images": "images", "Open gallery": "Ouvrir la galerie",
    "Back to galleries": "Retour aux galeries",
    "No images uploaded for this gallery yet.":
        "Aucune image n'a encore été téléversée pour cette galerie.",
    "No galleries available.": "Aucune galerie disponible.",
    "Sort order": "Tri",
    "Date: newest first": "Date : plus récent d'abord",
    "Date: oldest first": "Date : plus ancien d'abord",
    "Info & Help": "Info & Aide",
    "Getting started": "Premiers pas",
    "Shortcodes": "Shortcodes",
    "HappyFiles integration": "Intégration HappyFiles",
    "ACTIVE": "ACTIF", "NOT DETECTED": "NON DÉTECTÉ",
    "Column switcher icon colors": "Couleurs des icônes du sélecteur de colonnes",
    "Default color": "Couleur par défaut", "Hover color": "Couleur au survol", "Active color": "Couleur active",
    "Preview": "Aperçu", "Save changes": "Enregistrer les modifications",
    "Shortcode": "Shortcode",
    "Copy": "Copier", "Copied!": "Copié !",
    "HappyFiles folder (live link)": "Dossier HappyFiles (lien dynamique)",
    "— None (manual gallery) —": "— Aucun (galerie manuelle) —",
    "Standard (uniform squares)": "Standard (carrés uniformes)",
    "Justified (equal-height rows)": "Justifié (rangées de hauteur égale)",
    "Bento (asymmetric)": "Bento (asymétrique)",
    "Lightbox & Protection": "Lightbox et protection",
    "Deep linking": "Liens directs",
    "Social share buttons": "Boutons de partage social",
    "Right-click protection": "Protection clic droit",
    "Copy link": "Copier le lien",
    "%d image": ("%d image", "%d images"),
}

ES = {
    "Qaiyo Plugins": "Plugins Qaiyo",
    "Galleries": "Galerías", "Gallery": "Galería", "Add New": "Añadir nueva",
    "Add New Gallery": "Nueva galería", "Edit Gallery": "Editar galería",
    "New Gallery": "Nueva galería", "View Gallery": "Ver galería",
    "Search Galleries": "Buscar galerías",
    "No galleries found.": "No se encontraron galerías.",
    "No galleries in Trash.": "No hay galerías en la papelera.",
    "Clean Gallery": "Clean Gallery",
    "Gallery Categories": "Categorías de galería", "Gallery Category": "Categoría de galería",
    "Categories": "Categorías", "All Categories": "Todas las categorías",
    "Gallery Images": "Imágenes de la galería", "Gallery Settings": "Ajustes de la galería",
    "Add / Insert Images": "Añadir imágenes", "Remove All": "Eliminar todas",
    "Remove": "Eliminar", "Add to Gallery": "Añadir a la galería",
    "Select Gallery Images": "Seleccionar imágenes",
    "Layout": "Disposición", "Columns": "Columnas", "Lightbox": "Lightbox",
    "Captions": "Leyendas", "Lazy loading": "Carga diferida",
    "Gap (px)": "Separación (px)", "Grid": "Cuadrícula", "Masonry": "Mampostería",
    "— HappyFiles folder —": "— Carpeta de HappyFiles —",
    "Import from HappyFiles": "Importar desde HappyFiles",
    "All": "Todas", "Load more": "Cargar más",
    "Close": "Cerrar", "Previous": "Anterior", "Next": "Siguiente",
    "images": "imágenes", "Open gallery": "Abrir galería",
    "Back to galleries": "Volver a las galerías",
    "No images uploaded for this gallery yet.":
        "Aún no se han subido imágenes para esta galería.",
    "No galleries available.": "No hay galerías disponibles.",
    "Sort order": "Orden",
    "Date: newest first": "Fecha: más reciente primero",
    "Date: oldest first": "Fecha: más antiguo primero",
    "Info & Help": "Info y Ayuda",
    "Getting started": "Primeros pasos",
    "Shortcodes": "Shortcodes",
    "HappyFiles integration": "Integración con HappyFiles",
    "ACTIVE": "ACTIVO", "NOT DETECTED": "NO DETECTADO",
    "Column switcher icon colors": "Colores de iconos del selector de columnas",
    "Default color": "Color por defecto", "Hover color": "Color al pasar", "Active color": "Color activo",
    "Preview": "Vista previa", "Save changes": "Guardar cambios",
    "Shortcode": "Shortcode",
    "Copy": "Copiar", "Copied!": "¡Copiado!",
    "HappyFiles folder (live link)": "Carpeta de HappyFiles (enlace en vivo)",
    "— None (manual gallery) —": "— Ninguna (galería manual) —",
    "Standard (uniform squares)": "Estándar (cuadrados uniformes)",
    "Justified (equal-height rows)": "Justificado (filas de igual altura)",
    "Bento (asymmetric)": "Bento (asimétrico)",
    "Lightbox & Protection": "Lightbox y protección",
    "Deep linking": "Enlaces directos",
    "Social share buttons": "Botones para compartir",
    "Right-click protection": "Protección clic derecho",
    "Copy link": "Copiar enlace",
    "%d image": ("%d imagen", "%d imágenes"),
}

LOCALES = {
    "hu_HU": ("hu", "nplurals=2; plural=(n != 1);", HU),
    "de_DE": ("de", "nplurals=2; plural=(n != 1);", DE),
    "fr_FR": ("fr", "nplurals=2; plural=(n > 1);", FR),
    "es_ES": ("es", "nplurals=2; plural=(n != 1);", ES),
}

# ---------------------------------------------------------------------------
# Writers
# ---------------------------------------------------------------------------

def po_escape(s):
    return s.replace("\\", "\\\\").replace("\"", "\\\"").replace("\n", "\\n")


def write_po(path, lang_code, plural_forms, translations, header_lang):
    lines = []
    lines.append('msgid ""')
    lines.append('msgstr ""')
    lines.append('"Project-Id-Version: ' + DOMAIN + ' ' + PLUGIN_VERSION + '\\n"')
    lines.append('"Report-Msgid-Bugs-To: https://qaiyo-plugins.com\\n"')
    lines.append('"Language: ' + lang_code + '\\n"')
    lines.append('"MIME-Version: 1.0\\n"')
    lines.append('"Content-Type: text/plain; charset=UTF-8\\n"')
    lines.append('"Content-Transfer-Encoding: 8bit\\n"')
    lines.append('"Plural-Forms: ' + plural_forms + '\\n"')
    lines.append('"X-Generator: Qaiyo build-mo.py\\n"')
    lines.append('')

    seen = set()
    for src in SOURCE:
        if src in seen or any(src == p[0] for p in PLURALS):
            continue
        seen.add(src)
        trans = (translations or {}).get(src, "") if translations is not None else src
        if translations is None:
            trans = src  # .pot: msgstr is empty by convention; we keep msgid for ref
            trans = ""
        lines.append('msgid "' + po_escape(src) + '"')
        lines.append('msgstr "' + po_escape(trans) + '"')
        lines.append('')

    # Plurals
    for singular, plural in PLURALS:
        lines.append('msgid "' + po_escape(singular) + '"')
        lines.append('msgid_plural "' + po_escape(plural) + '"')
        if translations is None:
            lines.append('msgstr[0] ""')
            lines.append('msgstr[1] ""')
        else:
            t = translations.get(singular, ("", ""))
            if isinstance(t, str):
                t = (t, t)
            lines.append('msgstr[0] "' + po_escape(t[0] or singular) + '"')
            lines.append('msgstr[1] "' + po_escape(t[1] or plural) + '"')
        lines.append('')

    with open(path, "w", encoding="utf-8") as f:
        f.write("\n".join(lines))


def write_mo(path, lang_code, plural_forms, translations):
    """
    Minimal .mo writer (GNU format).
    """
    entries = OrderedDict()

    # Metadata "" entry
    meta = (
        "Project-Id-Version: " + DOMAIN + " " + PLUGIN_VERSION + "\n"
        "Language: " + lang_code + "\n"
        "MIME-Version: 1.0\n"
        "Content-Type: text/plain; charset=UTF-8\n"
        "Content-Transfer-Encoding: 8bit\n"
        "Plural-Forms: " + plural_forms + "\n"
    )
    entries[""] = meta

    seen = set()
    for src in SOURCE:
        if src in seen or any(src == p[0] for p in PLURALS):
            continue
        seen.add(src)
        t = translations.get(src) if translations else None
        if t:
            entries[src] = t

    # Plurals: msgid \0 plural -> msgstr0 \0 msgstr1
    for singular, plural in PLURALS:
        t = translations.get(singular) if translations else None
        if t:
            if isinstance(t, str):
                t = (t, t)
            key = singular + "\0" + plural
            entries[key] = (t[0] or singular) + "\0" + (t[1] or plural)

    keys = list(entries.keys())
    # GNU spec: sort by msgid
    keys.sort()

    encoded_keys = [k.encode("utf-8") for k in keys]
    encoded_vals = [entries[k].encode("utf-8") for k in keys]

    n = len(keys)
    offsets = []
    pos = 7 * 4  # header size
    keys_table_offset = pos
    pos += n * 8
    vals_table_offset = pos
    pos += n * 8

    key_offsets = []
    val_offsets = []

    for k in encoded_keys:
        key_offsets.append((len(k), pos))
        pos += len(k) + 1  # NUL
    for v in encoded_vals:
        val_offsets.append((len(v), pos))
        pos += len(v) + 1

    output = b""
    # Header: magic, version, n, keys_off, vals_off, hash_size, hash_off
    output += struct.pack("Iiiiiii", 0x950412DE, 0, n, keys_table_offset,
                          vals_table_offset, 0, 0)
    for length, offset in key_offsets:
        output += struct.pack("ii", length, offset)
    for length, offset in val_offsets:
        output += struct.pack("ii", length, offset)
    for k in encoded_keys:
        output += k + b"\x00"
    for v in encoded_vals:
        output += v + b"\x00"

    with open(path, "wb") as f:
        f.write(output)


# ---------------------------------------------------------------------------
# Main
# ---------------------------------------------------------------------------

def main():
    # .pot template
    pot_path = os.path.join(HERE, DOMAIN + ".pot")
    write_po(pot_path, "", "nplurals=2; plural=(n != 1);", None, "")
    print("✔ " + pot_path)

    for locale, (lang_code, plurals, trans) in LOCALES.items():
        po_path = os.path.join(HERE, DOMAIN + "-" + locale + ".po")
        mo_path = os.path.join(HERE, DOMAIN + "-" + locale + ".mo")
        write_po(po_path, lang_code, plurals, trans, locale)
        write_mo(mo_path, lang_code, plurals, trans)
        print("✔ " + po_path)
        print("✔ " + mo_path)


if __name__ == "__main__":
    main()
