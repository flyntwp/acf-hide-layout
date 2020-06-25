(function( $ ) {
    'use strict';

    if ( window.acf ) {
        window.acf.addAction( 'ready_field/type=flexible_content', function( field ) {
            var hidden_layouts = window.acf_hide_layout_options.hidden_layouts[field.data.key];

            // for each layout in the flexible field
            field.$el.find( '.layout' ).each(function( i, element ) {
                var $el = $( element ),
                    $controls = $el.find( '.acf-fc-layout-controls' ),
                    index = $el.attr( 'data-id' ),
                    name = 'acf[' + field.data.key + '][' + index + '][acf_hide_layout]',
                    in_array = -1 !== $.inArray( index, hidden_layouts ),
                    is_hidden = in_array && 'acfcloneindex' !== index;

                var $input = $( '<input>', {
                    type: 'hidden',
                    name: name,
                    class: 'acf-hide-layout',
                    value: is_hidden ? '1' : '0',
                });

                var $action = $( '<a>', {
                    'data-index': index,
                    'data-name': 'hide-layout',
                    href: '#',
                    title: window.acf_hide_layout_options.i18n.hide_layout,
                    class: 'acf-icon dashicons acf-hide-layout small light acf-js-tooltip',
                });

                $action.prepend( $input );
                $controls.prepend( $action );

                if ( is_hidden ) {
                    $el.addClass( 'acf-layout-hidden' );
                }
            });
        });
    }

    $( document ).on( 'click', '.acf-hide-layout', function() {
        var $el = $( this ),
            $layout = $el.parents( '.layout' ),
            $input = $el.find( '.acf-hide-layout' ),
            value = $input.val(),
            newValue = value === '1' ? '0' : '1';

        $input.val(newValue);
        $layout.toggleClass( 'acf-layout-hidden', newValue );
    });

})( jQuery );
