<?php
/**
 * BuddyPress CiviCRM Phone Class.
 *
 * Handles BuddyPress CiviCRM Phone functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Phone Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Phone functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Phone {

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
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * BuddyPress Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The BuddyPress Field object.
	 */
	public $field;

	/**
	 * "CiviCRM Field" field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Phone Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $phone_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $phone_field_prefix = 'cwps_phone_';

	/**
	 * Public Phone Fields.
	 *
	 * Mapped to their corresponding BuddyPress Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $phone_fields The array of public Phone Fields.
	 */
	public $phone_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'phone' => 'textbox',
		'phone_ext' => 'textbox',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $field The BuddyPress Field object.
	 */
	public function __construct( $field ) {

		// Store references to objects.
		$this->plugin = $field->bp_loader->plugin;
		$this->bp_loader = $field->bp_loader;
		$this->civicrm = $this->plugin->civicrm;
		$this->field = $field;

		// Init when the BuddyPress Field object is loaded.
		add_action( 'cwps/buddypress/field/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Listen for queries from the BuddyPress Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 30, 4 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'true_false_settings_modify' ], 10, 3 );

		// Append "True/False" mappings to the "Checkbox" xProfile Field Type.
		add_filter( 'cwps/bp/civicrm/phone_field/get_for_bp_field', [ $this, 'true_false_fields_append' ], 10, 2 );

		// Listen for when BuddyPress Profile Fields have been saved.
		add_filter( 'cwps/bp/contact/bp_fields_saved', [ $this, 'bp_fields_saved' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Save Phone(s) when BuddyPress Profile Fields have been saved.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress and CiviCRM params.
	 */
	public function bp_fields_saved( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there is no Field data.
		if ( empty( $args['field_data'] ) ) {
			return;
		}

		// Filter the Fields to include only Phone data.
		$phone_fields = [];
		foreach ( $args['field_data'] as $field ) {
			if ( empty( $field['meta']['entity_type'] ) || $field['meta']['entity_type'] !== 'Phone' ) {
				continue;
			}
			$phone_fields[] = $field;
		}

		// Bail if there are no Phone Fields.
		if ( empty( $phone_fields ) ) {
			return;
		}

		// Group Fields by Location.
		$phone_groups = [];
		foreach ( $phone_fields as $field ) {
			if ( empty( $field['meta']['entity_data']['location_type_id'] ) ) {
				continue;
			}
			if ( empty( $field['meta']['entity_data']['phone_type_id'] ) ) {
				continue;
			}
			$location_type_id = $field['meta']['entity_data']['location_type_id'];
			$phone_type_id = $field['meta']['entity_data']['phone_type_id'];
			$phone_groups[$location_type_id][$phone_type_id][] = $field;
		}

		// Bail if there are no Phone Groups.
		if ( empty( $phone_groups ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'phone_groups' => $phone_groups,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Save each Phone.
		foreach ( $phone_groups as $location_type_id => $phone_type ) {
			foreach ( $phone_type as $phone_type_id => $group ) {

				// Prepare the CiviCRM Phone data.
				$phone_data = $this->prepare_from_fields( $group );

				// Try and get the existing Phone record.
				$existing = $this->plugin->civicrm->phone->phones_get_by_type( $args['contact_id'], $location_type_id, $phone_type_id );

				// We can only handle exactly one, though CiviCRM allows many.
				if ( count( $existing ) > 1 ) {
					continue;
				}

				// Grab retrieved Phone data.
				$phone_record = array_pop( $existing );

				// Add its ID if present.
				if ( ! empty( $phone_record->id ) ) {
					$phone_data['id'] = $phone_record->id;
				}

				// Add the Location Type and Phone Type.
				$phone_data['location_type_id'] = $location_type_id;
				$phone_data['phone_type_id'] = $phone_type_id;

				///*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'phone_data' => $phone_data,
					//'backtrace' => $trace,
				], true ) );
				//*/

				// Okay, write the data to CiviCRM.
				$phone = $this->plugin->civicrm->phone->update( $args['contact_id'], $phone_data );

			}
		}

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
		$phone_data = [];

		// Handle the data for each Field.
		foreach ( $field_data as $data ) {

			// Get metadata for this xProfile Field.
			$args = $data['meta'];
			if ( empty( $args ) ) {
				continue;
			}

			// Get the CiviCRM Custom Field and Phone Field.
			$custom_field_id = $this->field->custom_field->id_get( $args['value'] );
			$phone_field_name = $this->name_get( $args['value'] );

			// Do we have a synced Custom Field or Phone Field?
			if ( ! empty( $custom_field_id ) || ! empty( $phone_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . (string) $custom_field_id;

				} elseif ( ! empty( $phone_field_name ) ) {

					// The Phone Field code is the setting.
					$code = $phone_field_name;

				}

				// Build args for value conversion.
				$args = [
					'custom_field_id' => $custom_field_id,
					'phone_field_name' => $phone_field_name,
				];

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					//'phone_fields' => $phone_fields,
					//'field_type' => $field_type,
					'data' => $data,
					'args' => $args,
					//'backtrace' => $trace,
				], true ) );
				*/

				// Parse value by Field Type.
				$value = $this->field->value_get_for_civicrm( $data['value'], $data['field_type'], $args );

				// Add it to the field data.
				$phone_data[$code] = $value;

			}

		}

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'phone_data' => $phone_data,
			//'backtrace' => $trace,
		], true ) );
		//*/

		// --<
		return $phone_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the Phone Field choices for a Setting Field from when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing array of choices for the Setting Field.
	 * @param string $field_type The BuddyPress Field Type.
	 * @param string $entity_type The CiviCRM Entity Type.
	 * @param array $entity_type_data The array of Entity Type data.
	 * @return array $choices The modified array of choices for the Setting Field.
	 */
	public function query_setting_choices( $choices, $field_type, $entity_type, $entity_type_data ) {

		// Bail if there's something amiss.
		if ( empty( $entity_type ) ||  empty( $field_type ) ) {
			return $choices;
		}

		// Bail if not the "Phone" Entity Type.
		if ( $entity_type !== 'Phone' ) {
			return $choices;
		}

		// Get the Phone Fields for this BuddyPress Field Type.
		$phone_fields = $this->get_for_bp_field_type( $field_type );

		// Build Phone Field choices array for dropdown.
		if ( ! empty( $phone_fields ) ) {
			$phone_fields_label = esc_attr__( 'Phone Fields', 'civicrm-wp-profile-sync' );
			foreach ( $phone_fields as $phone_field ) {
				$choices[$phone_fields_label][$this->phone_field_prefix . $phone_field['name']] = $phone_field['title'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/phone_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Get the CiviCRM Phone Fields for a BuddyPress Field Type.
	 *
	 * @since 0.5
	 *
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $phone_fields The array of Phone Fields.
	 */
	public function get_for_bp_field_type( $field_type ) {

		// Init return.
		$phone_fields = [];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field_type' => $field_type,
			'location_type' => $location_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public fields of this type.
		$phone_fields = $this->data_get( $field_type, 'public' );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'phone_fields' => $phone_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the Phone Fields.
		 *
		 * @since 0.5
		 *
		 * @param array $phone_fields The existing array of Phone Fields.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$phone_fields = apply_filters( 'cwps/bp/civicrm/phone_field/get_for_bp_field', $phone_fields, $field_type );

		// --<
		return $phone_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the core Fields for a CiviCRM Phone Type.
	 *
	 * @since 0.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function data_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter][$field_type] ) ) {
			return $pseudocache[$filter][$field_type];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Phone Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->phone_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $this->phone_fields[$value['name']] ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter][$field_type] ) ) {
			$pseudocache[$filter][$field_type] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Fields for CiviCRM Phones.
	 *
	 * @since 0.4
	 *
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function data_get_filtered( $filter = 'none' ) {

		// Only do this once per filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter] ) ) {
			return $pseudocache[$filter];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Phone Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->phone_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter] ) ) {
			$pseudocache[$filter] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the public Fields for CiviCRM Phones.
	 *
	 * @since 0.5
	 *
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields() {

		// Init return.
		$public_fields = [];

		// Get the public Fields for CiviCRM Phones.
		$public_fields = $this->data_get_filtered( 'public' );

		// --<
		return $public_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Phone Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter] ) ) {
			return $pseudocache[$filter];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Phone Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->phone_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter] ) ) {
			$pseudocache[$filter] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Phone Field options for a given Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the field.
	 * @return array $field The array of field data.
	 */
	public function get_by_name( $name ) {

		// Init return.
		$field = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'name' => $name,
			'action' => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set is the item.
		$field = $result['values'];

		// --<
		return $field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the BuddyPress Field Type for a Phone Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Phone Field.
	 * @return string $type The type of BuddyPress Field.
	 */
	public function get_bp_type( $name = '' ) {

		// Init return.
		$type = false;

		// if the key exists, return the value - which is the BuddyPress Type.
		if ( array_key_exists( $name, $this->phone_fields ) ) {
			$type = $this->phone_fields[$name];
		}

		// --<
		return $type;

	}



	/**
	 * Gets the mapped Phone Field name.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return string $name The mapped Contact Field name.
	 */
	public function name_get( $value ) {

		// Init return.
		$name = '';

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->phone_field_prefix ) ) {
			return $name;
		}

		// Get the mapped Contact Field name.
		$name = (string) str_replace( $this->phone_field_prefix, '', $value );

		// --<
		return $name;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function true_false_settings_modify( $options, $field_type, $args ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'options' => $options,
			'field_type' => $field_type,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the mapped Contact Field name.
		$field_name = $this->name_get( $args['value'] );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			//'value' => $value,
			'field_name' => $field_name,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $field_name ) ) {
			return $options;
		}

		// Bail if not a "True/False" Field Type.
		$civicrm_field_type = $this->get_bp_type( $field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field_type' => $civicrm_field_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( $civicrm_field_type !== 'true_false' ) {
			return $options;
		}

		// Get the full details for the CiviCRM Field.
		$civicrm_field = $this->get_by_name( $field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field' => $civicrm_field,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Use title for checkbox label.
		$options = [ 1 => $civicrm_field['title'] ];

		// --<
		return $options;

	}



	/**
	 * Filter the Phone Fields for a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $phone_fields The existing array of Phone Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $phone_fields The modified array of Phone Fields.
	 */
	public function true_false_fields_append( $phone_fields, $field_type ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $phone_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'phone_fields' => $phone_fields,
			'field_type' => $field_type,
			'name' => $name,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public fields of this type.
		$true_false_fields = $this->data_get( 'true_false', 'public' );
		if ( empty( $true_false_fields ) ) {
			return $phone_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'true_false_fields' => $true_false_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Merge with Phone Fields.
		$phone_fields = array_merge( $phone_fields, $true_false_fields );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'phone_fields-FINAL' => $phone_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $phone_fields;

	}



} // Class ends.



