/**
 * CiviCRM Profile Sync "BuddyPress xProfile Field" Javascript.
 *
 * Implements functionality on BuddyPress xProfile Field.
 *
 * @package WordPress
 * @subpackage CiviCRM_Profile_Sync
 */



/**
 * Create BuddyPress xProfile Field object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.5
 */
var CWPS_BP_Field = CWPS_BP_Field || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.5
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 0.5
	 */
	CWPS_BP_Field.settings = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * @since 0.5
		 */
		this.init = function() {
			me.init_localisation();
			me.init_settings();
		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * @since 0.5
		 */
		this.dom_ready = function() {
		};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.5
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CWPS_BP_Field_Vars ) {
				me.localisation = CWPS_BP_Field_Vars.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.5
		 *
		 * @param {String} identifier The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.5
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CWPS_BP_Field_Vars ) {
				me.settings = CWPS_BP_Field_Vars.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.5
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		// Init BuddyPress Field Type.
		me.field_type = '';

		/**
		 * Getter for retrieving the current BuddyPress Field Type.
		 *
		 * @since 0.5
		 */
		this.get_field_type = function() {
			return me.field_type;
		};

		/**
		 * Sets the current BuddyPress Field Type.
		 *
		 * @since 0.5
		 */
		this.set_field_type = function( field_type ) {
			me.field_type = field_type;
		};

		/**
		 * Getter for retrieving the array of Options for a given Contact Type.
		 *
		 * This relies on a BuddyPress Field Type being set.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} The numeric ID of the Contact Type.
		 * @return {Array} The array of data for the Options.
		 */
		this.get_options_for_contact_type = function( contact_type_id ) {
			var options = me.get_setting('options');
			if ( options[me.field_type] ) {
				if ( options[me.field_type][contact_type_id] ) {
					return options[me.field_type][contact_type_id];
				}
			}
			return [];
		};

	};

	/**
	 * Create Field Singleton.
	 *
	 * @since 0.5
	 */
	CWPS_BP_Field.field = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise.
		 *
		 * @since 0.5
		 */
		this.init = function() {
		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * @since 0.5
		 */
		this.dom_ready = function() {

			var field_type = $('#fieldtype').val(),
				civicrm_field = $('#cwps_civicrm_field').val();

			// Store the initial BuddyPress Field Type.
			CWPS_BP_Field.settings.set_field_type( field_type );

			// Maybe disable the CiviCRM Field.
			if ( ! civicrm_field ) {
				$('#cwps_civicrm_field').prop( 'disabled', true );
			} else {

				// Maybe show the reminder.
				if ( field_type == 'selectbox' || field_type == 'radio' || field_type == 'checkbox' ) {
					$( '.cwps-reminder' ).show();
				}

			}

			// Add listeners.
			me.listeners();

		};

		/**
		 * Set up Event Listeners.
		 *
		 * @since 0.5
		 */
		this.listeners = function() {

			/**
			 * Hook into BuddyPress Field Type switch trigger.
			 *
			 * @since 0.5
			 *
			 * @param {Object} event The jQuery event.
			 * @param {String} forWhat The value of the Field to show options for.
			 */
			$(document).on( 'bp-xprofile-show-options', function( event, forWhat ) {

				// Store Field Type.
				CWPS_BP_Field.settings.set_field_type( forWhat );

				// Reset.
				me.reset();

				// Show by default.
				$( '#cwps-bp-civicrm-field' ).show();

				// Never show for WordPress sync Fields.
				if ( forWhat === 'wp-biography' || forWhat === 'wp-textbox' ) {
					$( '#cwps-bp-civicrm-field' ).hide();
				}

				// Never show for Checkbox Acceptance Field.
				if ( forWhat === 'checkbox_acceptance' ) {
					$( '#cwps-bp-civicrm-field' ).hide();
				}

				// Show reminder text for some Fields.
				if ( forWhat === 'selectbox' || forWhat === 'checkbox' || forWhat === 'radio' ) {
					$( '.cwps-reminder' ).show();
				}

			});

			/**
			 * Listen for changes to the Contact Type.
			 *
			 * @since 0.5
			 */
			$('#cwps_civicrm_contact_type').on( 'change', function() {
				$('#cwps_civicrm_contact_subtype').val( '' );
			});

			/**
			 * Listen for changes to the Contact Type and Contact Sub-type.
			 *
			 * @since 0.5
			 */
			$('#cwps_civicrm_contact_type, #cwps_civicrm_contact_subtype').on( 'change', function() {

				var contact_type, contact_subtype, new_options = [], field_type, placeholder;

				// Get both values.
				contact_type = $('#cwps_civicrm_contact_type').val();
				contact_subtype = $('#cwps_civicrm_contact_subtype').val();

				// Remove existing CiviCRM Field options.
				me.civicrm_field_reset();

				// Disable CiviCRM Field select.
				$('#cwps_civicrm_field').prop( 'disabled', true );

				// Bail if no Contact Type.
				if ( ! contact_type ) {
					$('#cwps_civicrm_contact_subtype').val( '' );
					me.civicrm_field_add_placeholder();
					return;
				}

				// Get the ID of the chosen Contact Type.
				contact_type_id = contact_type;
				if ( contact_subtype ) {
					contact_type_id = contact_subtype;
				}
				console.log( 'contact_type_id', contact_type_id );

				// Get Options for this of Contact Type.
				data = CWPS_BP_Field.settings.get_options_for_contact_type( contact_type_id );
				if ( ! data.length ) {
					me.civicrm_field_add_placeholder();
					return;
				}

				// Repopulate the options.
				placeholder = CWPS_BP_Field.settings.get_localisation( 'placeholder' );
				new_options.push( new Option( placeholder, '', false, false ) );
				for ( group of data ) {
					optgroup = document.createElement( 'optgroup' );
					optgroup.label = group.label;
					options = group.options;
					for ( option of options ) {
						optgroup.appendChild( new Option( option.label, option.value, false, false ) );
					}
					new_options.push( optgroup );
				}
				$('#cwps_civicrm_field').append( new_options );

				// Enable CiviCRM Field select.
				$('#cwps_civicrm_field').prop( 'disabled', false );

			});

		};

		/**
		 * Resets all selects.
		 *
		 * @since 0.5
		 */
		this.reset = function() {

			// Clear Contact selects.
			$('#cwps_civicrm_contact_type').val( '' );
			$('#cwps_civicrm_contact_subtype').val( '' );

			// Clear CiviCRM Field select.
			me.civicrm_field_reset();

			// Re-add Contact Field placeholder and disable.
			me.civicrm_field_add_placeholder();
			$('#cwps_civicrm_field').prop( 'disabled', true );

			// Hide the reminder box.
			$( '.cwps-reminder' ).hide();

		};

		/**
		 * Resets CiviCRM Field select.
		 *
		 * @since 0.5
		 */
		this.civicrm_field_reset = function() {
			$('#cwps_civicrm_field').children( 'option' ).remove();
			$('#cwps_civicrm_field').children( 'optgroup' ).remove();
		};

		/**
		 * Add CiviCRM Field placeholder.
		 *
		 * @since 0.5
		 */
		this.civicrm_field_add_placeholder = function() {
			placeholder = CWPS_BP_Field.settings.get_localisation( 'placeholder' );
			$('#cwps_civicrm_field').append( new Option( placeholder, '', false, false ) );
		};

	};

	// Init singletons.
	CWPS_BP_Field.settings.init();
	CWPS_BP_Field.field.init();

} )( jQuery );

/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.5
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CWPS_BP_Field.settings.dom_ready();
	CWPS_BP_Field.field.dom_ready();

}); // End document.ready()
