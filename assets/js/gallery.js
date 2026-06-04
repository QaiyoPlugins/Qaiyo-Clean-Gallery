/**
 * Qaiyo Clean Gallery – Frontend JS
 * 1. GalleryAll   — [clean_gallery_all] card grid + filter + sort + paging
 * 2. GallerySingle — [clean_gallery id="X"] lightbox
 */
( function () {
	'use strict';

	const I18N = window.qcgI18n || {};

	// Plugin-level settings injected by Qcg_Settings::print_frontend_css_vars().
	const SETTINGS = ( function () {
		try {
			const el = document.getElementById( 'qcg-settings' );
			return el ? JSON.parse( el.textContent ) : {};
		} catch ( e ) { return {}; }
	} )();

	function t( key, fallback ) {
		return Object.prototype.hasOwnProperty.call( I18N, key ) ? I18N[ key ] : fallback;
	}
	function format( str, args ) {
		let out = str;
		Object.keys( args ).forEach( function ( k ) {
			out = out.replace( '%' + k + '$d', args[ k ] ).replace( '%' + k + '$s', args[ k ] );
		} );
		return out;
	}

	/* ── GALLERY ALL ─────────────────────────────────────────────────── */
	class GalleryAll {
		constructor( wrap ) {
			this.wrap     = wrap;
			this.grid     = wrap.querySelector( '.qcg-card-grid' );
			this.moreBtn  = wrap.querySelector( '.qcg-load-more-btn' );
			this.moreCnt  = wrap.querySelector( '.qcg-load-more-count' );
			this.perPage  = parseInt( wrap.dataset.perPage, 10 ) || 12;
			this.cardCols = parseInt( wrap.dataset.cardCols, 10 ) || 3;

			const dataEl = wrap.querySelector( '.qcg-all-data' );
			this.allItems    = dataEl ? JSON.parse( dataEl.textContent ) : [];
			this.filtered    = this.allItems.slice();
			this.shown       = 0;
			this.activeFilter = '*';
			this.sortMode    = 'date-desc';

			this._bindControls();
			this._render( true );
		}

		_bindControls() {
			this.wrap.querySelectorAll( '.qcg-filter-btn' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					this.wrap.querySelectorAll( '.qcg-filter-btn' ).forEach( ( b ) => b.classList.remove( 'is-active' ) );
					btn.classList.add( 'is-active' );
					this.activeFilter = btn.dataset.filter;
					this._applyFilterAndSort();
					this._render( true );
				} );
			} );

			const sortSel = this.wrap.querySelector( '.qcg-sort-select' );
			if ( sortSel ) {
				sortSel.addEventListener( 'change', () => {
					this.sortMode = sortSel.value;
					this._applyFilterAndSort();
					this._render( true );
				} );
			}

			this.wrap.querySelectorAll( '.qcg-col-btn' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', () => {
					this.wrap.querySelectorAll( '.qcg-col-btn' ).forEach( ( b ) => b.classList.remove( 'is-active' ) );
					btn.classList.add( 'is-active' );
					this.cardCols = parseInt( btn.dataset.cols, 10 );
					this.grid.style.setProperty( '--qcg-card-cols', this.cardCols );
				} );
			} );

			if ( this.moreBtn ) {
				this.moreBtn.addEventListener( 'click', () => this._render( false ) );
			}
		}

		_applyFilterAndSort() {
			this.filtered = this.activeFilter === '*'
				? this.allItems.slice()
				: this.allItems.filter( ( g ) => {
					const cats = ( g.cats || '' ).split( ' ' ).filter( Boolean );
					return cats.includes( this.activeFilter );
				} );

			const parts = this.sortMode.split( '-' );
			const field = parts[ 0 ];
			const dir   = parts[ 1 ];

			this.filtered.sort( ( a, b ) => {
				let va, vb;
				if ( field === 'date' ) { va = a.date_ts; vb = b.date_ts; }
				else { va = a.title.toLowerCase(); vb = b.title.toLowerCase(); }
				if ( va < vb ) return dir === 'asc' ? -1 :  1;
				if ( va > vb ) return dir === 'asc' ?  1 : -1;
				return 0;
			} );
		}

		_render( reset ) {
			if ( reset ) {
				this.shown = 0;
				this.grid.innerHTML = '';
			}

			const batch = this.filtered.slice( this.shown, this.shown + this.perPage );
			batch.forEach( ( g ) => this.grid.insertAdjacentHTML( 'beforeend', this._cardHtml( g ) ) );
			this.shown += batch.length;

			const remaining = this.filtered.length - this.shown;
			if ( this.moreBtn ) {
				if ( remaining > 0 ) {
					this.moreBtn.hidden = false;
					const next = Math.min( remaining, this.perPage );
					const tpl  = t( 'remaining', '(%1$d / %2$d remaining)' );
					this.moreCnt.textContent = ' ' + format( tpl, { 1: next, 2: remaining } );
				} else {
					this.moreBtn.hidden = true;
				}
			}
		}

		_cardHtml( g ) {
			const imagesLabel = t( 'images', 'images' );
			const openLabel   = t( 'openGallery', 'Open gallery' );

			const thumb = g.thumb
				? `<img src="${this._esc(g.thumb)}" alt="${this._esc(g.title)}" width="${g.thumb_w}" height="${g.thumb_h}" loading="lazy">`
				: `<div class="qcg-card-no-thumb"></div>`;

			return `
			<a class="qcg-card" href="${this._esc(g.url)}" aria-label="${this._esc(openLabel + ': ' + g.title)}"
			   itemprop="itemListElement" itemscope itemtype="https://schema.org/ImageGallery">
				<meta itemprop="name" content="${this._esc(g.title)}">
				<div class="qcg-card-thumb">
					${thumb}
					<div class="qcg-card-count">${g.count} ${this._esc(imagesLabel)}</div>
				</div>
				<div class="qcg-card-body">
					<h3 class="qcg-card-title">${this._esc(g.title)}</h3>
					<time class="qcg-card-date">${this._esc(g.date)}</time>
				</div>
			</a>`;
		}

		_esc( str ) {
			return String( str )
				.replace( /&/g, '&amp;' )
				.replace( /</g, '&lt;' )
				.replace( />/g, '&gt;' )
				.replace( /"/g, '&quot;' );
		}
	}

	/* ── GALLERY SINGLE (lightbox + deep link + share + right-click) ── */
	class GallerySingle {
		constructor( wrap ) {
			this.wrap      = wrap;
			this.lb        = wrap.querySelector( '.qcg-lightbox' );
			this.galleryId = wrap.dataset.galleryId || '0';
			this.items     = [];
			this.cur       = 0;
			if ( this.lb ) this._initLightbox();
			if ( SETTINGS.rightClickProtect ) this._initRightClickProtect();
		}

		/* ── Lightbox core ─────────────────────────────────── */
		_initLightbox() {
			const items = this.wrap.querySelectorAll( '.qcg-item[data-full]' );
			items.forEach( ( el, i ) => {
				this.items.push( { full: el.dataset.full, caption: el.dataset.caption || '' } );
				el.addEventListener( 'click', () => this._open( i ) );
				el.style.cursor = 'pointer';
			} );

			this.lb.querySelector( '.qcg-lb-close' ).addEventListener( 'click', () => this._close() );
			this.lb.querySelector( '.qcg-lb-prev' ).addEventListener( 'click', () => this._go( -1 ) );
			this.lb.querySelector( '.qcg-lb-next' ).addEventListener( 'click', () => this._go( 1 ) );
			this.lb.addEventListener( 'click', ( e ) => { if ( e.target === this.lb ) this._close(); } );

			document.addEventListener( 'keydown', ( e ) => {
				if ( this.lb.hidden ) return;
				if ( e.key === 'Escape' )     this._close();
				if ( e.key === 'ArrowLeft' )  this._go( -1 );
				if ( e.key === 'ArrowRight' ) this._go( 1 );
			} );

			// Social share buttons
			if ( SETTINGS.socialShare ) {
				this._initShare();
			}
		}

		_open( i ) {
			this.cur = i;
			this._show();
			this.lb.hidden = false;
			document.body.style.overflow = 'hidden';
			if ( SETTINGS.deepLinking ) this._pushHash();
		}

		_close() {
			this.lb.hidden = true;
			document.body.style.overflow = '';
			if ( SETTINGS.deepLinking ) this._clearHash();
		}

		_go( dir ) {
			this.cur = ( this.cur + dir + this.items.length ) % this.items.length;
			this._show();
			if ( SETTINGS.deepLinking ) this._pushHash();
		}

		_show() {
			const item = this.items[ this.cur ];
			const img  = this.lb.querySelector( '.qcg-lb-img' );
			const cap  = this.lb.querySelector( '.qcg-lb-caption' );
			const cnt  = this.lb.querySelector( '.qcg-lb-counter' );
			img.src = item.full;
			img.alt = item.caption;
			cap.textContent = item.caption;
			if ( cnt ) cnt.textContent = ( this.cur + 1 ) + ' / ' + this.items.length;
		}

		/* ── Deep linking (URL hash) ───────────────────────── */
		_buildHash() {
			return '#qcg=' + this.galleryId + '&img=' + this.cur;
		}

		_pushHash() {
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', this._buildHash() );
			} else {
				window.location.hash = this._buildHash();
			}
		}

		_clearHash() {
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', window.location.pathname + window.location.search );
			} else {
				window.location.hash = '';
			}
		}

		/** Called once on DOMContentLoaded to open from URL hash. */
		openFromHash() {
			const hash = window.location.hash;
			if ( ! hash ) return;
			const m = hash.match( /qcg=(\d+)/ );
			const mi = hash.match( /img=(\d+)/ );
			if ( ! m ) return;
			if ( m[1] !== this.galleryId && m[1] !== '0' ) return;
			const idx = mi ? parseInt( mi[1], 10 ) : 0;
			if ( idx >= 0 && idx < this.items.length ) {
				this._open( idx );
			}
		}

		/* ── Social share ──────────────────────────────────── */
		_initShare() {
			const shareWrap = this.lb.querySelector( '.qcg-lb-share' );
			if ( ! shareWrap ) return;
			shareWrap.hidden = false;

			shareWrap.querySelectorAll( '.qcg-share-btn' ).forEach( ( btn ) => {
				btn.addEventListener( 'click', ( e ) => {
					e.stopPropagation();
					const net = btn.dataset.network;
					this._share( net, btn );
				} );
			} );
		}

		_share( network, btn ) {
			const item    = this.items[ this.cur ];
			const pageUrl = window.location.href.split( '#' )[0] + this._buildHash();
			const imgUrl  = item.full;
			const text    = item.caption || document.title;
			let url;

			switch ( network ) {
				case 'facebook':
					url = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent( pageUrl );
					break;
				case 'x':
					url = 'https://x.com/intent/tweet?url=' + encodeURIComponent( pageUrl ) + '&text=' + encodeURIComponent( text );
					break;
				case 'pinterest':
					url = 'https://pinterest.com/pin/create/button/?url=' + encodeURIComponent( pageUrl ) + '&media=' + encodeURIComponent( imgUrl ) + '&description=' + encodeURIComponent( text );
					break;
				case 'copy':
					if ( navigator.clipboard && navigator.clipboard.writeText ) {
						navigator.clipboard.writeText( pageUrl );
					} else {
						const ta = document.createElement( 'textarea' );
						ta.value = pageUrl;
						document.body.appendChild( ta );
						ta.select();
						document.execCommand( 'copy' );
						document.body.removeChild( ta );
					}
					btn.classList.add( 'is-copied' );
					setTimeout( () => btn.classList.remove( 'is-copied' ), 1400 );
					return;
			}

			if ( url ) {
				window.open( url, 'qcg_share', 'width=600,height=450,menubar=no,toolbar=no' );
			}
		}

		/* ── Right-click protection ────────────────────────── */
		_initRightClickProtect() {
			// Prevent context menu on all gallery images.
			this.wrap.addEventListener( 'contextmenu', ( e ) => {
				if ( e.target.closest( '.qcg-thumb-wrap' ) || e.target.closest( '.qcg-lb-stage' ) ) {
					e.preventDefault();
				}
			} );
			// Prevent drag on images.
			this.wrap.addEventListener( 'dragstart', ( e ) => {
				if ( e.target.tagName === 'IMG' ) {
					e.preventDefault();
				}
			} );
		}
	}

	/* ── JUSTIFIED LAYOUT ─────────────────────────────────────────────
	   Rows of equal height with images sized by their aspect ratio
	   so each row fills the container width exactly. */
	function qcgJustified( gallery ) {
		const grid = gallery.querySelector( '.qcg-grid' );
		if ( ! grid ) return;

		const items = Array.from( grid.querySelectorAll( '.qcg-item' ) );
		if ( ! items.length ) return;

		const cs           = getComputedStyle( gallery );
		const gap          = parseFloat( cs.getPropertyValue( '--qcg-gap' ) ) || 12;
		const targetHeight = parseFloat( gallery.dataset.rowHeight ) || 240;
		const containerW   = grid.clientWidth;
		if ( containerW <= 0 ) return;

		// Collect aspect ratios from width/height attrs (no need to wait for load).
		const ars = items.map( ( item ) => {
			const img = item.querySelector( 'img' );
			let w = parseFloat( img.getAttribute( 'width' ) );
			let h = parseFloat( img.getAttribute( 'height' ) );
			if ( ! w || ! h ) { w = img.naturalWidth || 4; h = img.naturalHeight || 3; }
			return w / h;
		} );

		let row    = [];
		let rowARs = [];
		let i      = 0;

		const flushRow = ( fillRow ) => {
			if ( ! row.length ) return;
			const totalAR = rowARs.reduce( ( s, a ) => s + a, 0 );
			const avail   = containerW - gap * ( row.length - 1 );
			let h;
			if ( fillRow ) {
				h = avail / totalAR;
			} else {
				// last row: cap at target height (don't blow it up).
				h = Math.min( targetHeight, avail / totalAR );
			}
			row.forEach( ( item, idx ) => {
				const w = rowARs[ idx ] * h;
				item.style.width  = w + 'px';
				item.style.height = h + 'px';
			} );
			row    = [];
			rowARs = [];
		};

		while ( i < items.length ) {
			row.push( items[ i ] );
			rowARs.push( ars[ i ] );

			const totalAR     = rowARs.reduce( ( s, a ) => s + a, 0 );
			const rowWidthAtT = totalAR * targetHeight + gap * ( row.length - 1 );

			if ( rowWidthAtT >= containerW ) {
				flushRow( true );
			}
			i++;
		}
		// Last (possibly partial) row.
		flushRow( false );
	}

	function initJustified() {
		document.querySelectorAll( '.qcg-layout-justified' ).forEach( ( g ) => qcgJustified( g ) );
	}

	let _resizeT;
	window.addEventListener( 'resize', () => {
		clearTimeout( _resizeT );
		_resizeT = setTimeout( initJustified, 120 );
	} );

	document.addEventListener( 'DOMContentLoaded', () => {
		document.querySelectorAll( '.qcg-all-wrap' ).forEach( ( el ) => new GalleryAll( el ) );

		const galleries = [];
		document.querySelectorAll( '.clean-gallery.qcg-has-lightbox' ).forEach( ( el ) => {
			const g = new GallerySingle( el );
			galleries.push( g );
		} );

		// Lightbox nélküli galériák — right-click protection alkalmazása
		if ( SETTINGS.rightClickProtect ) {
			document.querySelectorAll( '.clean-gallery:not(.qcg-has-lightbox)' ).forEach( ( el ) => {
				el.addEventListener( 'contextmenu', ( e ) => {
					if ( e.target.closest( '.qcg-thumb-wrap' ) ) e.preventDefault();
				} );
				el.addEventListener( 'dragstart', ( e ) => {
					if ( e.target.tagName === 'IMG' ) e.preventDefault();
				} );
			} );
		}

		// Deep link: open lightbox from URL hash on page load.
		if ( SETTINGS.deepLinking && galleries.length ) {
			galleries.forEach( ( g ) => g.openFromHash() );
			window.addEventListener( 'hashchange', () => {
				galleries.forEach( ( g ) => g.openFromHash() );
			} );
		}

		initJustified();
	} );

} )();
