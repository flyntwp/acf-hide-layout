(function( $ ) {
  'use strict';

  if ( window.acf && window.acf_hide_layout_options.supports_disabled_layouts === 'false' ) {
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

  $( document ).on( 'click', '.acf-hide-layout-migration-notice .notice-dismiss', function() {
      $.ajax( {
          url: window.acf_hide_layout_options.ajax_url,
          type: 'POST',
          data: {
              action: 'acf_hide_layout_dismiss_notice',
              nonce: window.acf_hide_layout_options.nonce,
          },
      });
  });

  var $modal = $( '#acf-hide-layout-modal' )

  if ( $modal.length ) {
      var $progressBar = $modal.find( '.acf-hide-layout-modal__progress-bar' )
      var $progressFill = $modal.find( '.acf-hide-layout-modal__progress-fill' )
      var $progressPercentage = $modal.find( '.acf-hide-layout-modal__progress-percentage' )
      var $migrateButton = $modal.find( '.acf-hide-layout-modal__migrate-button' )
      var $message = $modal.find( '.acf-hide-layout-modal__message' )
      var $closeButton = $modal.find( '.acf-hide-layout-modal__close' )
      var isMigrating = false

      $( document ).on( 'click', '[data-acf-hide-layout-open-modal]', function( e ) {
          $modal.attr( 'aria-hidden', 'false' ).addClass( 'is-open' )
      });

      $( document ).on( 'click', '[data-acf-hide-layout-modal-close]', function( e ) {
          if ( isMigrating ) {
              e.preventDefault()
              e.stopPropagation()
              return false
          }
          $modal.attr( 'aria-hidden', 'true' ).removeClass( 'is-open' )
      });

      $( document ).on( 'keydown', function( e ) {
          if ( e.key === 'Escape' && $modal.hasClass( 'is-open' ) ) {
              if ( isMigrating ) {
                  e.preventDefault()
                  return false
              }
              $modal.attr( 'aria-hidden', 'true' ).removeClass( 'is-open' )
          }
      });

      $( document ).on( 'click', '[data-acf-hide-layout-migrate]', function( e ) {
          e.preventDefault()
          var $button = $( this )
          var types = [ 'postmeta', 'termmeta', 'usermeta', 'options' ]
          var currentTypeIndex = 0
          var totalMigrated = 0
          var totalNotMigrated = 0

          // Disable closing modal during migration
          isMigrating = true
          $closeButton.prop( 'disabled', true )

          // Reset message state
          $message.empty().removeClass( 'acf-hide-layout-modal__message--warning acf-hide-layout-modal__message--success acf-hide-layout-modal__message--error' ).hide()

          // Show percentage and reset progress to 0
          $progressPercentage.show()
          $progressFill.css( 'width', '0%' )
          $progressBar.attr( 'aria-valuenow', '0' )
          $progressPercentage.text( '0%' )

          // Disable clicked button immediately
          $button.prop( 'disabled', true )

          // Check if clicked button is the migrate button or try again
          var isTryAgain = ! $button.hasClass( 'acf-hide-layout-modal__migrate-button' )
          if ( isTryAgain ) {
              // Keep try again visible but disabled, hide migrate button
              $migrateButton.hide()
          } else {
              // Hide any existing try again button
              $modal.find( '[data-acf-hide-layout-migrate]:not(.acf-hide-layout-modal__migrate-button)' ).remove()
          }

          function updateProgress( progress ) {
              var roundedProgress = Math.round( progress )
              $progressFill.css( 'width', progress + '%' )
              $progressBar.attr( 'aria-valuenow', roundedProgress )
              $progressPercentage.text( roundedProgress + '%' )
          }

          function noop( noopObj, count ) {
              return ( count === 1 ? noopObj.singular : noopObj.plural ).replace( '%s', count )
          }

          function onMigrationComplete() {
              updateProgress( 100 )
              // Show success message and hide button
              var messageText
              var messageClass
              var i18n = window.acf_hide_layout_options.i18n

              if ( totalNotMigrated > 0 ) {
                  messageText = noop( i18n.migrated, totalMigrated ) + ' ' + noop( i18n.not_migrated, totalNotMigrated )
                  messageClass = 'acf-hide-layout-modal__message--warning'
              } else {
                  messageText = noop( i18n.migration_success, totalMigrated )
                  messageClass = 'acf-hide-layout-modal__message--success'
              }
              $message
                  .addClass( messageClass )
                  .append( '<span class="dashicons dashicons-' + ( totalNotMigrated > 0 ? 'warning' : 'yes-alt' ) + '" aria-hidden="true"></span>' )
                  .append( $( '<p>' ).text( messageText ) )
                  .show()

              // Show/enable try again button if some layouts couldn't be migrated
              if ( totalNotMigrated > 0 ) {
                  var $existingTryAgain = $modal.find( '[data-acf-hide-layout-migrate]:not(.acf-hide-layout-modal__migrate-button)' )
                  if ( $existingTryAgain.length ) {
                      // Re-enable existing try again button
                      $existingTryAgain.prop( 'disabled', false )
                  } else {
                      // Create new try again button
                      var $tryAgainButton = $( '<button>', {
                          type: 'button',
                          class: 'button button-secondary',
                          text: i18n.try_again,
                          'data-acf-hide-layout-migrate': ''
                      } )
                      $modal.find( '.acf-hide-layout-modal__actions' ).append( $tryAgainButton )
                  }
                  $migrateButton.hide()
              } else {
                  // Success - hide all buttons and dismiss notice permanently
                  $modal.find( '[data-acf-hide-layout-migrate]' ).hide()
                  $.ajax( {
                      url: window.acf_hide_layout_options.ajax_url,
                      type: 'POST',
                      data: {
                          action: 'acf_hide_layout_dismiss_notice',
                          nonce: window.acf_hide_layout_options.nonce
                      }
                  } )
              }

              // Hide WordPress admin notice
              $( '.acf-hide-layout-migration-notice' ).fadeOut()
              // Re-enable closing modal after migration completes
              isMigrating = false
              $closeButton.prop( 'disabled', false )
          }

          function onMigrationError( error ) {
              // Show error message and hide button
              var errorText = error && error.message ? error.message : error
              $message
                  .addClass( 'acf-hide-layout-modal__message--error' )
                  .append( '<span class="dashicons dashicons-warning" aria-hidden="true"></span>' )
                  .append( $( '<p>' ).text( errorText ) )
                  .show()
              $migrateButton.prop( 'disabled', true )
              // Re-enable closing modal
              isMigrating = false
              $closeButton.prop( 'disabled', false )
              console.error( 'Migration error:', error )
          }

          function migrateHiddenLayouts( type, lastId ) {
              $.ajax( {
                  url: window.acf_hide_layout_options.ajax_url,
                  type: 'POST',
                  data: {
                      action: 'acf_hide_layout_migrate_hidden_layouts',
                      nonce: window.acf_hide_layout_options.nonce,
                      type: type,
                      last_id: lastId
                  },
                  success: function( response ) {
                      if ( response.success ) {
                          var data = response.data

                          // Count migrated fields
                          if ( data.total_migrated ) {
                              totalMigrated += data.total_migrated
                          }
                          if ( data.not_migrated ) {
                              totalNotMigrated += data.not_migrated
                          }

                          // Update progress based on types completed
                          var baseProgress = ( currentTypeIndex / types.length ) * 100
                          var typeProgress = data.has_results ? 5 : 25 // Small increment if continuing, larger if moving to next type
                          updateProgress( Math.min( baseProgress + typeProgress, 99 ) )

                          if ( data.has_results ) {
                              // Continue fetching same type with new last_id
                              migrateHiddenLayouts( type, data.last_id )
                          } else {
                              // Move to next type
                              currentTypeIndex++
                              if ( currentTypeIndex < types.length ) {
                                  migrateHiddenLayouts( types[ currentTypeIndex ], 0 )
                              } else {
                                  // All types completed
                                  onMigrationComplete()
                              }
                          }
                      } else {
                          onMigrationError( response.data )
                      }
                  },
                  error: function( xhr, status, error ) {
                      onMigrationError( error )
                  }
              } )
          }

          // Start migration with first type
          migrateHiddenLayouts( types[ currentTypeIndex ], 0 )
      });
  }

})( jQuery );
