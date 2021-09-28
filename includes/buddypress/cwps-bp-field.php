<?php
/**
 * BuddyPress xProfile Field Class.
 *
 * Handles BuddyPress xProfile Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress xProfile Field Class.
 *
 * A class that encapsulates BuddyPress xProfile Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_xProfile_Field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $bp_loader The BuddyPress Loader object.
	 */
	public $bp_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * CiviCRM Contact Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $contact_field The CiviCRM Contact Field object.
	 */
	public $contact_field;

	/**
	 * CiviCRM Custom Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $custom_field The CiviCRM Custom Field object.
	 */
	public $custom_field;

	/**
	 * Settings Field Top Level Contact Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $contact_type_id The Settings Field Top Level Contact Type ID.
	 */
	public $contact_type_id = 'cwps_civicrm_contact_type';

	/**
	 * Settings Field Contact Sub-type ID.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $contact_subtype_id The Settings Field Contact Sub-type ID.
	 */
	public $contact_subtype_id = 'cwps_civicrm_contact_subtype';

	/**
	 * Settings Field value name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $name The Settings Field name.
	 */
	public $name = 'cwps_civicrm_field';



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $bp_loader The BuddyPress Loader object.
	 */
	public function __construct( $bp_loader ) {

		// Store references to objects.
		$this->plugin = $bp_loader->plugin;
		$this->bp_loader = $bp_loader;
		$this->civicrm = $bp_loader->plugin->civicrm;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/buddypress/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwps/buddypress/field/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-contact-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-custom-field.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init objects.
		$this->contact_field = new CiviCRM_Profile_Sync_BP_CiviCRM_Contact_Field( $this );
		$this->custom_field = new CiviCRM_Profile_Sync_BP_CiviCRM_Custom_Field( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Modify the xProfile Field display and its User values.

		// Filter the output of an xProfile Field.
		//add_action( 'xprofile_get_field_data', [ $this, 'data_get' ], 10, 3 );

		// Filter the xProfile Field options when displaying the Field.
		add_action( 'bp_xprofile_field_get_children', [ $this, 'get_children' ], 10, 3 );
		//add_action( 'xprofile_field_after_save', [ $this, 'after_save' ], 10, 2 );

		// BuddyPress uses the "name/label" as the "value" when saving. Needs to be the "ID" instead.
		add_filter( 'bp_get_the_profile_field_options_checkbox', [ $this, 'options_checkbox' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_select', [ $this, 'options_select' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_multiselect', [ $this, 'options_multiselect' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_radio', [ $this, 'options_radio' ], 10, 5 );

		// This means "tricking" BuddyPress into validating these Fields.
		add_filter( 'bp_xprofile_set_field_data_pre_validate', [ $this, 'pre_validate' ], 10, 3 );

		// Modify the xProfile Field setup.

		// xProfile admin template hooks.
		add_action( 'xprofile_field_after_contentbox', [ $this, 'metabox_render' ], 10 );

		// Add Javascript after BuddyPress does.
		add_action( 'bp_admin_enqueue_scripts', [ $this, 'enqueue_js' ], 10 );

		// Modify the xProfile Field when it is saved.
		add_action( 'xprofile_field_options_before_save', [ $this, 'options_before_save' ], 10, 2 );
		//add_action( 'xprofile_field_default_before_save', [ $this, 'default_before_save' ], 10, 2 );

		// Capture our metadata when the xProfile Field when it is saved.
		add_action( 'xprofile_fields_saved_field', [ $this, 'saved_field' ], 10 );

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Contact updates.
		add_action( 'cwps/mapper/bp_xprofile_edited', [ $this, 'fields_edited' ], 50 );
		//add_action( 'cwps/mapper/bp_field_edited', [ $this, 'field_edited' ], 50 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/mapper/bp_xprofile_edited', [ $this, 'fields_edited' ], 50 );
		//remove_action( 'cwps/mapper/bp_field_edited', [ $this, 'field_edited' ], 50 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires when a BuddyPress xProfile "Profile Group" has been updated.
	 *
	 * This callback is hooked in after the "core" methods of this plugin have
	 * done their thing - so a Contact will definitely exist by the time this
	 * method is called.
	 *
	 * The "core" methods will have handled the Fields that map to the built-in
	 * WordPress User Fields - so all that is left are the xProfile Fields that
	 * have been specifically mapped in their settings.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress params.
	 */
	public function fields_edited( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			'civicrm_ref' => $this->civicrm_ref,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no CiviCRM References.
		if ( empty( $this->civicrm_ref ) ) {
			return;
		}

		// Bail if this User doesn't have a Contact.
		$contact = $this->plugin->mapper->ufmatch->contact_get_by_user_id( $args['user_id'] );
		if ( $contact === false ) {
			return;
		}

		// Prepare the CiviCRM Contact data.
		$contact_data = $this->prepare_from_fields( $this->civicrm_ref );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			'civicrm_ref' => $this->civicrm_ref,
			'contact_data' => $contact_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Add the Contact ID.
		$contact_data['id'] = $contact['id'];

		// Update the Contact.
		$contact = $this->plugin->civicrm->contact->update( $contact_data );

		// Add our data to the params.
		$args['contact_id'] = $contact_data['id'];
		$args['contact'] = $contact;

		/**
		 * Broadcast that a Contact has been updated when a set of BuddyPress Fields were saved.
		 *
		 * Used internally by:
		 *
		 * - Phone
		 * - Instant Messenger
		 * - Address
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/bp/contact/bp_fields_saved', $args );

	}



	/**
	 * Prepares the CiviCRM Contact data from an array of BuddyPress Field data.
	 *
	 * This method combines all Contact Fields that the CiviCRM API accepts as
	 * params for ( 'Contact', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Contact', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field_data The array of BuddyPress Field data.
	 * @return array $contact_data The CiviCRM Contact data.
	 */
	public function prepare_from_fields( $field_data ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field_data' => $field_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Init data for fields.
		$contact_data = [];

		// Handle the data for each Field.
		foreach ( $field_data as $data ) {

			// Get metadata for this xProfile Field.
			$args = $this->get_metadata_all( $data['field_id'] );
			if ( empty( $args ) ) {
				continue;
			}

			// Get the CiviCRM Custom Field and Contact Field.
			$custom_field_id = $this->custom_field->id_get( $args['value'] );
			$contact_field_name = $this->contact_field->name_get( $args['value'] );

			// Do we have a synced Custom Field or Contact Field?
			if ( ! empty( $custom_field_id ) || ! empty( $contact_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . (string) $custom_field_id;

				} elseif ( ! empty( $contact_field_name ) ) {

					// The Contact Field code is the setting.
					$code = $contact_field_name;

					/*
					// Skip if it's a Field that requires special handling.
					if ( in_array( $code, $fields_handled ) ) {
						continue;
					}
					*/

				}

				// Build args for value conversion.
				$args = [
					'custom_field_id' => $custom_field_id,
					'contact_field_name' => $contact_field_name,
				];

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					//'contact_fields' => $contact_fields,
					//'field_type' => $field_type,
					'data' => $data,
					'args' => $args,
					//'backtrace' => $trace,
				], true ) );
				*/

				// Parse value by Field Type.
				$value = $this->value_get_for_civicrm( $data['value'], $data['field_type'], $args );

				// Add it to the field data.
				$contact_data[$code] = $value;

			}

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_data' => $contact_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $contact_data;

	}



	/**
	 * Fires when a BuddyPress xProfile Field has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress params.
	 */
	public function field_edited( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			'civicrm_ref' => $this->civicrm_ref,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the value of a BuddyPress Field formatted for CiviCRM.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The BuddyPress Field value.
	 * @param string $field_type The BuddyPress Field type.
	 * @param array $args Any additional arguments.
	 * @return mixed $value The value formatted for CiviCRM.
	 */
	public function value_get_for_civicrm( $value = 0, $field_type, $args = [] ) {

		// Set appropriate value per Field type.
		switch( $field_type ) {

	 		// Parse the value of a "Date" Field.
			case 'datebox' :
				$value = $this->date_value_get_for_civicrm( $value, $args );
				break;

			// Other Field types may require parsing - add them here.

		}

		// --<
		return $value;

	}



	/**
	 * Get the value of a "Date" Field formatted for CiviCRM.
	 *
	 * @since 0.5
	 *
	 * @param string $value The existing Field value.
	 * @param array $args Any additional arguments.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function date_value_get_for_civicrm( $value = '', $args ) {

		// Init format.
		$format = '';

		// BuddyPress saves in Y-m-d H:i:s format.
		$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );

		// Check if it's a Contact Field date.
		if ( ! empty( $args['contact_field_name'] ) ) {
			$format = $this->contact_field->date_format_get_from_civicrm( $args['contact_field_name'] );
		}

		// Check if it's a Custom Field date.
		if ( ! empty( $args['custom_field_id'] ) ) {
			$format = $this->custom_field->date_format_get_from_civicrm( $args['custom_field_id'] );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			'field_type' => $field_type,
			'args' => $args,
			'format' => $format,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Convert to CiviCRM format.
		$value = $datetime->format( $format );

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Filters the field data value for a specific field for the user.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value saved for the Field.
	 * @param integer $field_id The ID of the Field being displayed.
	 * @param integer $user_id The ID of the User being displayed.
	 */
	public function data_get( $value, $field_id, $user_id ) {

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Filters the HTML output for an xProfile Field options checkbox button.
	 *
	 * @since 0.5
	 *
	 * @param string $new_html Label and checkbox input field.
	 * @param object $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string $selected The current selected value.
	 * @param string $k The current index in the foreach loop.
	 */
	public function options_checkbox( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'new_html' => $new_html,
			'value' => $value,
			'id' => $id,
			'selected' => $selected,
			'k' => $k,
			//'backtrace' => $trace,
		], true ) );
		*/

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = sprintf( '<label for="%3$s" class="option-label"><input %1$s type="checkbox" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
			$selected,
			esc_attr( bp_get_the_profile_field_input_name() . '[]' ),
			esc_attr( "option_{$value->id}" ),
			esc_attr( stripslashes( $value->civicrm_value ) ),
			esc_html( stripslashes( $value->name ) )
		);

		// --<
		return $new_html;

	}



	/**
	 * Filters the HTML output for an xProfile Field options select button.
	 *
	 * @since 0.5
	 *
	 * @param string $new_html Label and select input field.
	 * @param object $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string $selected The current selected value.
	 * @param string $k The current index in the foreach loop.
	 */
	public function options_select( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'new_html' => $new_html,
			'value' => $value,
			'id' => $id,
			'selected' => $selected,
			'k' => $k,
			//'backtrace' => $trace,
		], true ) );
		*/

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = '<option' . $selected . ' value="' . esc_attr( stripslashes( $value->civicrm_value ) ) . '">' . esc_html( stripslashes( $value->name ) ) . '</option>';

		// --<
		return $new_html;

	}



	/**
	 * Filters the HTML output for an xProfile Field options multiselect button.
	 *
	 * @since 0.5
	 *
	 * @param string $new_html Label and multiselect input field.
	 * @param object $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string $selected The current selected value.
	 * @param string $k The current index in the foreach loop.
	 */
	public function options_multiselect( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'new_html' => $new_html,
			'value' => $value,
			'id' => $id,
			'selected' => $selected,
			'k' => $k,
			//'backtrace' => $trace,
		], true ) );
		*/

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = '<option' . $selected . ' value="' . esc_attr( stripslashes( $value->civicrm_value ) ) . '">' . esc_html( stripslashes( $value->name ) ) . '</option>';

		// --<
		return $new_html;

	}



	/**
	 * Filters the HTML output for an xProfile Field options radio button.
	 *
	 * @since 0.5
	 *
	 * @param string $new_html Label and radio input field.
	 * @param object $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string $selected The current selected value.
	 * @param string $k The current index in the foreach loop.
	 */
	public function options_radio( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'new_html' => $new_html,
			'value' => $value,
			'id' => $id,
			'selected' => $selected,
			'k' => $k,
			//'backtrace' => $trace,
		], true ) );
		*/

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = sprintf( '<label for="%3$s" class="option-label"><input %1$s type="radio" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
			$selected,
			esc_attr( bp_get_the_profile_field_input_name() ),
			esc_attr( "option_{$value->id}" ),
			esc_attr( stripslashes( $value->civicrm_value ) ),
			esc_html( stripslashes( $value->name ) )
		);

		// --<
		return $new_html;

	}



	// -------------------------------------------------------------------------



	/**
	 * Filter the raw submitted Profile Field value.
	 *
	 * We use this filter to modify the values submitted by users before
	 * doing field-type-specific validation.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field $field The Field object.
	 * @param BP_XProfile_Field_Type $field_type_obj The Field Type object.
	 * @return mixed $value The Field value.
	 */
	public function pre_validate( $value, $field, $field_type_obj ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			'field' => $field,
			'field_type_obj' => $field_type_obj,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get metadata for this xProfile Field.
		$args = $this->get_metadata_all( $field );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there is none.
		if ( empty( $args ) ) {
			return $value;
		}

		// Bail if there is no value.
		if ( empty( $args['value'] ) ) {
			return $value;
		}

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field->type, $args );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'options' => $options,
			'value' => $value,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there are no Options.
		if ( empty( $options ) ) {
			// It is mapped, so add value.
			$field->civicrm_value = $value;
			$this->civicrm_ref[] = [
				'field_id' => $field->id,
				'field_type' => $field->type,
				'value' => $value
			];
			return $value;
		}

		// Overwrite "value" to pass BuddyPress validation.
		if ( is_array( $value ) ) {
			$value_for_bp = [];
			$value_for_civicrm = [];
			foreach ( $value as $item ) {
				if ( array_key_exists( $item, $options ) ) {
					$value_for_bp[] = $options[$item];
					$value_for_civicrm[] = $item;
				}
			}
		} else {
			$value_for_bp = 0;
			$value_for_civicrm = 0;
			if ( array_key_exists( $value, $options ) ) {
				$value_for_bp = $options[$value];
				$value_for_civicrm = $value;
			}
		}

		// Always save the "real" CiviCRM value for later.
		$field->civicrm_value = $value_for_civicrm;
		$this->civicrm_ref[] = [
			'field_id' => $field->id,
			'field_type' => $field->type,
			'value' => $value,
		];

		// Now maybe overwrite the return.
		if ( ! empty( $value_for_bp ) ) {
			$value = $value_for_bp;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			'field' => $field,
			'civicrm_value' => $field->civicrm_value,
			'civicrm_ref' => $this->civicrm_ref,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires when the children of a BuddyPress xProfile Field are read.
	 *
	 * @since 0.5
	 *
	 * @param object $children Found children for a field.
	 * @param bool $for_editing Whether or not the field is for editing.
	 * @param BP_XProfile_Field $field The xProfile Field object.
	 */
	public function get_children( $children, $for_editing, $field ) {

		// We only want to filter them on the Edit Field screen.
		if ( ! $for_editing ) {
			//return $children;
		}

		// Get metadata for this xProfile Field.
		$args = $this->get_metadata_all( $field );

		// Bail if there are none.
		if ( empty( $args ) ) {
			return $children;
		}

		// Bail if there is no value.
		if ( empty( $args['value'] ) ) {
			return $children;
		}

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field->type, $args );

		// Bail if there are no Options.
		if ( empty( $options ) ) {
			return $children;
		}

		// Add in the CiviCRM values.
		foreach ( $options as $id => $option ) {
			foreach ( $children as $child ) {
				if ( $child->name == $option ) {
					$child->civicrm_value = $id;
				}
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'children' => $children,
			//'for_editing' => $for_editing,
			//'field' => $field,
			'options' => $options,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $children;

	}



	/**
	 * Fires when the options of a BuddyPress xProfile Field are filtered.
	 *
	 * @since 0.5
	 *
	 * @param array $post_option The submitted options array. (Need to check!)
	 * @param string $field_type The type of xProfile Field.
	 */
	public function options_before_save( $post_option, $field_type ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'post_option' => $post_option,
			'field_type' => $field_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Extract the Contact Type ID from our metabox.
		$contact_type_id = '';
		if ( isset( $_POST[$this->contact_type_id] ) && $_POST[$this->contact_type_id] ) {
			$contact_type_id = wp_unslash( $_POST[$this->contact_type_id] );
		}

		// Bail if we don't have a Contact Type.
		if ( empty( $contact_type_id ) ) {
			return $post_option;
		}

		// Extract the Contact Subtype ID from our metabox.
		$contact_subtype_id = '';
		if ( isset( $_POST[$this->contact_subtype_id] ) && $_POST[$this->contact_subtype_id] ) {
			$contact_subtype_id = wp_unslash( $_POST[$this->contact_subtype_id] );
		}

		// Extract the value from our metabox.
		$value = '';
		if ( isset( $_POST[$this->name] ) && $_POST[$this->name] ) {
			$value = wp_unslash( $_POST[$this->name] );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_type_id' => $contact_type_id,
			'contact_subtype_id' => $contact_subtype_id,
			'value' => $value,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if we don't have a value.
		if ( empty( $value ) ) {
			return $post_option;
		}

		// Let's make an array of the args.
		$args = [
			'contact_type_id' => $contact_type_id,
			'contact_subtype_id' => $contact_subtype_id,
			'value' => $value,
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field_type, $args );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'options' => $options,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Maybe overwrite.
		if ( ! empty( $options ) ) {
			$post_option = $options;
		}

		// --<
		return $post_option;

	}



	/**
	 * Fires when a BuddyPress xProfile Field has been saved.
	 *
	 * @since 0.5
	 *
	 * @param BP_XProfile_Field $field The current xProfile Field object.
	 */
	public function saved_field( $field ) {

		// Extract the Contact Type ID from our metabox.
		$contact_type_id = '';
		if ( isset( $_POST[$this->contact_type_id] ) && $_POST[$this->contact_type_id] ) {
			$contact_type_id = wp_unslash( $_POST[$this->contact_type_id] );
		}

		// Extract the Contact Subtype ID from our metabox.
		$contact_subtype_id = '';
		if ( isset( $_POST[$this->contact_subtype_id] ) && $_POST[$this->contact_subtype_id] ) {
			$contact_subtype_id = wp_unslash( $_POST[$this->contact_subtype_id] );
		}

		// Extract the value from our metabox.
		$value = '';
		if ( isset( $_POST[$this->name] ) && $_POST[$this->name] ) {
			$value = wp_unslash( $_POST[$this->name] );
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field' => $field,
			//'POST' => $_POST,
			'contact_type_id' => $contact_type_id,
			'contact_subtype_id' => $contact_subtype_id,
			'value' => $value,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Save setting(s).
		bp_xprofile_update_field_meta( $field->id, $this->contact_type_id, $contact_type_id );
		bp_xprofile_update_field_meta( $field->id, $this->contact_subtype_id, $contact_subtype_id );
		if ( ! empty( $value ) ) {
			bp_xprofile_update_field_meta( $field->id, $this->name, $value );
		} else {
			bp_xprofile_update_field_meta( $field->id, $this->name, '' );
		}

		// Bundle our data into an array.
		$args = [
			'contact_type_id' => $contact_type_id,
			'contact_subtype_id' => $contact_subtype_id,
			'value' => $value,
		];

		/**
		 * Broadcast our data when a BuddyPress xProfile Field has been saved.
		 *
		 * @since 0.5
		 *
		 * @param BP_XProfile_Field $field The current xProfile Field object.
		 * @param array $args The array of CiviCRM data.
		 */
		do_action( 'cwps/buddypress/field/saved', $field, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Output a metabox below the xProfile Field Type metabox in the main column.
	 *
	 * @since 0.5
	 *
	 * @param BP_XProfile_Field $field The current XProfile Field.
	 */
	public function metabox_render( $field ) {

		// Get our Field settings.
		$top_level_type = bp_xprofile_get_meta( $field->id, 'field', $this->contact_type_id );
		$sub_type = bp_xprofile_get_meta( $field->id, 'field', $this->contact_subtype_id );
		$civicrm_field = bp_xprofile_get_meta( $field->id, 'field', $this->name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'top_level_type' => $top_level_type,
			'sub_type' => $sub_type,
			'civicrm_field' => $civicrm_field,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Set the lowest-level Contact Type ID that we can.
		$contact_type_id = 0;
		if ( ! empty( $top_level_type ) ) {
			$contact_type_id = $top_level_type;
			if ( ! empty( $sub_type ) ) {
				$contact_type_id = $sub_type;
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_type_id' => $contact_type_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Init arrays.
		$contact_type_data = [];
		$top_level_types = [];
		$sub_types = [];

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// If there are some.
		if ( ! empty( $contact_types ) ) {

			// Add entries for top level Contact Types.
			foreach ( $contact_types as $contact_type ) {
				$top_level_types[$contact_type['id']] = $contact_type['label'];
				if ( $contact_type['id'] == $contact_type_id ) {
					$contact_type_data = $contact_type;
				}
			}

			// Add entries for CiviCRM Contact Sub-types.
			foreach ( $contact_types as $contact_type ) {
				if ( empty( $contact_type['children'] ) ) {
					continue;
				}
				foreach ( $contact_type['children'] as $contact_subtype ) {
					$sub_types[$contact_type['name']][$contact_subtype['id']] = $contact_subtype['label'];
					if ( $contact_subtype['id'] == $contact_type_id ) {
						$contact_type_data = $contact_subtype;
					}
				}
			}

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field->type' => $field->type,
			'contact_type_data' => $contact_type_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Request the choices for a Setting Field from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Setting Field choices array.
		 * @param string $field_type The BuddyPress xProfile Field Type.
		 * @param array $contact_type_data The array of Contact Type data.
		 */
		$choices = apply_filters( 'cwps/bp/field/query_setting_choices', [], $field->type, $contact_type_data );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'choices' => $choices,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Include the Setting Field template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/buddypress/metaboxes/metabox-bp-field-content.php';

	}



	/**
	 * Enqueue the Javascript for our xProfile Field metabox.
	 *
	 * @since 0.5
	 */
	public function enqueue_js() {

		// Same check as BuddyPress.
		if ( ! empty( $_GET['page'] ) && strpos( $_GET['page'], 'bp-profile-setup' ) !== false ) {

			// Enqueue our JavaScript.
			wp_enqueue_script(
				'cwps-xprofile-admin-js',
				plugins_url( 'assets/js/buddypress/xprofile/cwps-bp-civicrm-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
				[ 'xprofile-admin-js' ],
				CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
			);

			// Init options.
			$options = [];

			// Get all Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->types_get_all();

			// Get the Field mappings for all BuddyPress Field Types and Contact Types.
			foreach ( bp_xprofile_get_field_types() as $field_type => $field_type_class ) {
				foreach ( $contact_types as $contact_type ) {

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'field_type' => $field_type,
						'contact_type' => $contact_type,
						//'field_type_class' => $field_type_class,
						//'backtrace' => $trace,
					], true ) );
					*/

					/**
					 * Request the choices for a Setting Field from Entity classes.
					 *
					 * @since 0.5
					 *
					 * @param array The empty default Setting Field choices array.
					 * @param string $field_type The BuddyPress xProfile Field Type.
					 * @param array $contact_type The array of Contact Type data.
					 */
					$choices = apply_filters( 'cwps/bp/field/query_setting_choices', [], $field_type, $contact_type );

					// Skip if we get no choices.
					if ( empty( $choices ) ) {
						continue;
					}

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'choices' => $choices,
						//'backtrace' => $trace,
					], true ) );
					*/

					// Add to options.
					$data = [];
					foreach ( $choices as $optgroup => $choice ) {
						$opts = [];
						foreach ( $choice as $value => $label ) {
							$opts[] = [
								'value' => $value,
								'label' => $label,
							];
						}
						$data[] = [
							'label' => $optgroup,
							'options' => $opts,
						];
					}

					$options[$field_type][$contact_type['id']] = $data;

				}
			}

			// Build data array.
			$vars = [
				'localisation' => [
					'placeholder' => __( '- Select Field -', 'civicrm-wp-profile-sync' ),
				],
				'settings' => [
					'options' => $options,
				],
			];

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'options[textbox]' => $options['textbox'],
				//'options[datebox]' => $options['datebox'],
				//'backtrace' => $trace,
			], true ) );
			*/

			// Localise our script.
			wp_localize_script(
				'cwps-xprofile-admin-js',
				'CWPS_BP_Field_Vars',
				$vars
			);

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets all of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @param array $data The array of our Field metadata.
	 */
	public function get_metadata_all( $field ) {

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		// Only do this once per Field ID.
		static $pseudocache;
		if ( isset( $pseudocache[$field_id] ) ) {
			//return $pseudocache[$field_id];
		}

		// Grab the metadata.
		$contact_type_id = bp_xprofile_get_meta( $field_id, 'field', $this->contact_type_id );
		$contact_subtype_id = bp_xprofile_get_meta( $field_id, 'field', $this->contact_subtype_id );
		$value = bp_xprofile_get_meta( $field_id, 'field', $this->name );

		// Build data array.
		$data = [];
		if ( ! empty( $contact_type_id ) ) {
			$data['contact_type_id'] = $contact_type_id;
		}
		if ( ! empty( $contact_subtype_id ) ) {
			$data['contact_subtype_id'] = $contact_subtype_id;
		}
		if ( ! empty( $value ) ) {
			$data['value'] = $value;
		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$field_id] ) ) {
			$pseudocache[$field_id] = $data;
		}

		// --<
		return $data;

	}



	/**
	 * Gets an item of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @param string $setting The xProfile Field setting.
	 */
	public function get_metadata( $field, $setting = 'value' ) {

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		switch ( $setting ) {
			case 'value' :
				$key = $this->name;
				break;
			case 'contact_type_id' :
				$key = $this->contact_type_id;
				break;
			case 'contact_subtype_id' :
				$key = $this->contact_subtype_id;
				break;
		}

		// --<
		return bp_xprofile_get_meta( $field_id, 'field', $key );

	}



} // Class ends.




