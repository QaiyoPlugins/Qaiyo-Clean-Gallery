/**
 * Qaiyo Clean Gallery – Gutenberg block (server-side render).
 */
( function () {
	'use strict';

	const { registerBlockType }  = wp.blocks;
	const { InspectorControls }  = wp.blockEditor;
	const {
		PanelBody, SelectControl, RangeControl,
		ToggleControl,
	} = wp.components;
	const ServerSideRender = wp.serverSideRender || wp.components.ServerSideRender;
	const { __ } = wp.i18n;

	const galleries = ( window.qcgBlock && window.qcgBlock.galleries ) || [];

	registerBlockType( 'qaiyo-clean-gallery/gallery', {
		title:       __( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ),
		description: __( 'Display a gallery with grid or masonry layout, lightbox and filtering.', 'qaiyo-clean-gallery' ),
		category:    'media',
		icon:        'format-gallery',
		keywords:    [ 'gallery', 'images', 'masonry', 'lightbox', 'grid', 'qaiyo' ],

		edit: function ( { attributes, setAttributes } ) {
			const {
				galleryId, layout, columns, lightbox,
				captions, lazy, gap, showFilter,
			} = attributes;

			const galleryOptions = galleries.map( ( g ) => ( { value: g.value, label: g.label } ) );

			return wp.element.createElement(
				wp.element.Fragment,
				null,
				wp.element.createElement(
					InspectorControls,
					null,
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Gallery', 'qaiyo-clean-gallery' ), initialOpen: true },
						wp.element.createElement( SelectControl, {
							label:    __( 'Select Gallery', 'qaiyo-clean-gallery' ),
							value:    galleryId,
							options:  galleryOptions,
							onChange: ( v ) => setAttributes( { galleryId: parseInt( v, 10 ) } ),
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Layout', 'qaiyo-clean-gallery' ), initialOpen: true },
						wp.element.createElement( SelectControl, {
							label:    __( 'Layout', 'qaiyo-clean-gallery' ),
							value:    layout,
							options:  [
								{ value: 'grid',      label: __( 'Grid', 'qaiyo-clean-gallery' ) },
								{ value: 'masonry',   label: __( 'Masonry', 'qaiyo-clean-gallery' ) },
								{ value: 'standard',  label: __( 'Standard (uniform squares)', 'qaiyo-clean-gallery' ) },
								{ value: 'justified', label: __( 'Justified (equal-height rows)', 'qaiyo-clean-gallery' ) },
								{ value: 'bento',     label: __( 'Bento (asymmetric)', 'qaiyo-clean-gallery' ) },
							],
							onChange: ( v ) => setAttributes( { layout: v } ),
						} ),
						wp.element.createElement( RangeControl, {
							label:    __( 'Columns', 'qaiyo-clean-gallery' ),
							value:    columns,
							min:      2, max: 5,
							onChange: ( v ) => setAttributes( { columns: v } ),
						} ),
						wp.element.createElement( RangeControl, {
							label:    __( 'Gap (px)', 'qaiyo-clean-gallery' ),
							value:    gap,
							min:      0, max: 60, step: 2,
							onChange: ( v ) => setAttributes( { gap: v } ),
						} )
					),
					wp.element.createElement(
						PanelBody,
						{ title: __( 'Options', 'qaiyo-clean-gallery' ), initialOpen: false },
						wp.element.createElement( ToggleControl, {
							label:    __( 'Lightbox', 'qaiyo-clean-gallery' ),
							checked:  lightbox,
							onChange: ( v ) => setAttributes( { lightbox: v } ),
						} ),
						wp.element.createElement( ToggleControl, {
							label:    __( 'Captions', 'qaiyo-clean-gallery' ),
							checked:  captions,
							onChange: ( v ) => setAttributes( { captions: v } ),
						} ),
						wp.element.createElement( ToggleControl, {
							label:    __( 'Lazy loading', 'qaiyo-clean-gallery' ),
							checked:  lazy,
							onChange: ( v ) => setAttributes( { lazy: v } ),
						} ),
						wp.element.createElement( ToggleControl, {
							label:    __( 'Show filter bar', 'qaiyo-clean-gallery' ),
							checked:  showFilter,
							onChange: ( v ) => setAttributes( { showFilter: v } ),
						} )
					)
				),

				galleryId
					? wp.element.createElement( ServerSideRender, {
						block:      'qaiyo-clean-gallery/gallery',
						attributes: attributes,
					} )
					: wp.element.createElement(
						'div',
						{
							style: {
								padding:      '2rem',
								textAlign:    'center',
								background:   '#f8f6ff',
								border:       '2px dashed #6c5ce7',
								borderRadius: '4px',
								color:        '#4a3cc0',
							}
						},
						wp.element.createElement( 'p', null, __( 'Qaiyo Clean Gallery', 'qaiyo-clean-gallery' ) ),
						wp.element.createElement( 'p', { style: { fontSize: '.875rem', margin: 0 } },
							__( 'Select a gallery in the sidebar to show a preview.', 'qaiyo-clean-gallery' )
						)
					)
			);
		},

		save: function () { return null; },
	} );

} )();
