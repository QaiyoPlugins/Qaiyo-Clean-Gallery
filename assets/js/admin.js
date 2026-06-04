/**
 * Qaiyo Clean Gallery – Admin JS
 * - WP Media picker, drag-and-drop sort, remove
 * - Large-file warning
 * - HappyFiles folder import
 */
( function ( $ ) {
	'use strict';

	const cfg       = window.qcgAdmin || {};
	const i18n      = cfg.i18n || {};
	const THRESHOLD = cfg.thresholdBytes || 1572864;
	const $sortable = $( '#qcg-sortable' );
	const $idsInput = $( '#qcg-image-ids' );

	function humanSize( bytes ) {
		if ( bytes >= 1048576 ) return ( bytes / 1048576 ).toFixed( 1 ) + ' MB';
		return ( bytes / 1024 ).toFixed( 0 ) + ' KB';
	}

	function showNotice( message, type ) {
		$( '.qcg-upload-notice' ).remove();

		const cls = type === 'warning'
			? 'qcg-upload-notice is-warning'
			: type === 'success'
				? 'qcg-upload-notice is-success'
				: 'qcg-upload-notice';

		const $notice = $( '<div>' )
			.addClass( cls )
			.text( message );

		$( '#qcg-image-manager' ).prepend( $notice );

		setTimeout( function () {
			$notice.fadeOut( 400, function () { $notice.remove(); } );
		}, 8000 );
	}

	function addThumbs( attachments ) {
		attachments.forEach( function ( att ) {
			const id = parseInt( att.id || att.get( 'id' ), 10 );
			if ( ! id ) return;
			if ( $sortable.find( '[data-id="' + id + '"]' ).length ) return;

			let thumbUrl;
			if ( att.url ) {
				thumbUrl = att.url;
			} else {
				const sizes = att.get( 'sizes' );
				thumbUrl = ( sizes && sizes.thumbnail ) ? sizes.thumbnail.url : att.get( 'url' );
			}

			const $item = $( '<div class="qcg-thumb-item">' )
				.attr( 'data-id', id )
				.append(
					$( '<img>' ).attr( { src: thumbUrl, alt: '' } ),
					$( '<span class="qcg-remove-thumb dashicons dashicons-no-alt">' ).attr( 'title', i18n.remove || 'Remove' ),
					$( '<span class="qcg-drag-handle dashicons dashicons-move">' )
				);

			$sortable.append( $item );
		} );

		syncIds();
	}

	function syncIds() {
		const ids = [];
		$sortable.find( '.qcg-thumb-item' ).each( function () {
			ids.push( parseInt( $( this ).attr( 'data-id' ), 10 ) );
		} );
		$idsInput.val( ids.join( ',' ) );
	}

	// --- Media Library Frame ---
	let frame;
	function openFrame() {
		if ( frame ) { frame.open(); return; }

		frame = wp.media( {
			title:    i18n.title  || 'Select Gallery Images',
			button:   { text: i18n.button || 'Add to Gallery' },
			multiple: true,
			library:  { type: 'image' },
		} );

		frame.on( 'select', function () {
			const selection = frame.state().get( 'selection' );
			const all   = [];
			const large = [];

			selection.each( function ( attachment ) {
				all.push( attachment );
				if ( ( attachment.get( 'filesizeInBytes' ) || 0 ) > THRESHOLD ) {
					large.push( attachment );
				}
			} );

			if ( large.length === 1 ) {
				const att     = large[0];
				const name    = att.get( 'filename' ) || att.get( 'title' ) || '?';
				const sizeStr = humanSize( att.get( 'filesizeInBytes' ) || 0 );
				const tpl     = i18n.warnSingle || 'Large image: "%1$s" (%2$s).';
				showNotice( tpl.replace( '%1$s', name ).replace( '%2$s', sizeStr ), 'warning' );
			} else if ( large.length > 1 ) {
				const tpl = i18n.warnMultiple || '%d large image(s) detected.';
				showNotice( tpl.replace( '%d', large.length ), 'warning' );
			}

			addThumbs( all );
		} );

		frame.open();
	}

	$( '#qcg-add-images' ).on( 'click', openFrame );

	$( '#qcg-clear-images' ).on( 'click', function () {
		if ( ! window.confirm( i18n.confirmClear || 'Remove all images?' ) ) return;
		$sortable.empty();
		$idsInput.val( '' );
	} );

	$sortable.on( 'click', '.qcg-remove-thumb', function ( e ) {
		e.stopPropagation();
		$( this ).closest( '.qcg-thumb-item' ).remove();
		syncIds();
	} );

	if ( $.fn.sortable ) {
		$sortable.sortable( {
			items:       '.qcg-thumb-item',
			cursor:      'grabbing',
			placeholder: 'qcg-thumb-item ui-sortable-placeholder',
			tolerance:   'pointer',
			update:      syncIds,
		} );
	}

	// --- Shortcode copy button ---
	$( document ).on( 'click', '.qcg-copy-btn', function () {
		const $btn = $( this );
		const sel  = $btn.attr( 'data-clipboard-target' );
		const $tgt = sel ? $( sel ).first() : $btn.prev( 'input' );
		if ( ! $tgt.length ) return;

		const val = $tgt.val();
		const done = function () {
			const orig = $btn.html();
			$btn.addClass( 'is-copied' )
				.html( '<span class="dashicons dashicons-yes"></span>' );
			setTimeout( function () {
				$btn.removeClass( 'is-copied' ).html( orig );
			}, 1400 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( val ).then( done, function () {
				$tgt[0].select();
				document.execCommand( 'copy' );
				done();
			} );
		} else {
			$tgt[0].select();
			document.execCommand( 'copy' );
			done();
		}
	} );

} )( jQuery );
