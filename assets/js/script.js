(function( $ ) {
  'use strict';

  if ( window.acf ) {
      window.acf.addAction( 'ready_field/type=flexible_content', function( field ) {
          var get_hidden_layouts = function ( field_name ) {
              var hidden_layouts = [];
              $.each( window.acf_hide_layout_options.hidden_layouts, function( key, layouts ) {
                  if ( -1 !== field_name.indexOf( key ) ) {
                      hidden_layouts = layouts;
                      return false;
                  }
              });

              return hidden_layouts;
          };

          // for each layout in the flexible field
          field.$el.find( '.layout' ).each(function( i, element ) {
              var $el = $( element ),
                  $controls = $el.find( '.acf-fc-layout-controls' ).first(),
                  $input = $el.find( 'input[type="hidden"]' ).first(),
                  index = $el.attr( 'data-id' ),
                  name = $input.attr( 'name' ).replace( 'acf_fc_layout', 'acf_hide_layout' ),
                  hidden_layouts = get_hidden_layouts( name ),
                  in_array = -1 !== $.inArray( index, hidden_layouts ),
                  is_hidden = in_array && 'acfcloneindex' !== index;

              if ($el.data('has-acf-hide-layout')) {
                  return;
              }

              $el.attr('data-has-acf-hide-layout', true)

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
          $layout = $el.closest( '.layout' ),
          $input = $el.find( '.acf-hide-layout' ),
          value = $input.val(),
          newValue = value === '1' ? '0' : '1';

      $input.val(newValue);
      $layout.toggleClass( 'acf-layout-hidden', newValue );
  });

})( jQuery );
