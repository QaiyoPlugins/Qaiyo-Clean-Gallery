/**
 * Qaiyo Clean Gallery – Settings page JS
 * Init WP color picker + live preview update.
 */
( function ( $ ) {
	'use strict';

	const $preview = $( '.qcg-preview' );

	const map = {
		'col_icon_color':        '--qcg-col-icon',
		'col_icon_hover_color':  '--qcg-col-icon-hover',
		'col_icon_active_color': '--qcg-col-icon-active',
	};

	$( '.qcg-color-field' ).wpColorPicker( {
		change: function ( event, ui ) {
			const $input = $( event.target );
			const name   = $input.attr( 'name' ) || '';
			Object.keys( map ).forEach( function ( key ) {
				if ( name.indexOf( '[' + key + ']' ) !== -1 ) {
					$preview[0].style.setProperty( map[ key ], ui.color.toString() );
				}
			} );
		},
		clear: function () {
			// On clear, repaint preview from input defaults
			Object.keys( map ).forEach( function ( key ) {
				const $f = $( 'input[name$="[' + key + ']"]' );
				const def = $f.data( 'default-color' );
				if ( def ) {
					$preview[0].style.setProperty( map[ key ], def );
				}
			} );
		},
	} );

} )( jQuery );
