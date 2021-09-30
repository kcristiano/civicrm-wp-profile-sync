<?php
/**
 * BuddyPress CiviCRM Address Class.
 *
 * Handles BuddyPress CiviCRM Address functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Address Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Address functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Address {

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
	 * This distinguishes Address Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $address_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $address_field_prefix = 'cwps_address_';

	/**
	 * Public Address Fields.
	 *
	 * Mapped to their corresponding BuddyPress Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $address_fields The array of public Address Fields.
	 */
	public $address_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'street_address' => 'textbox',
		'supplemental_address_1' => 'textbox',
		'supplemental_address_2' => 'textbox',
		'supplemental_address_3' => 'textbox',
		'city' => 'textbox',
		'county_id' => 'selectbox',
		'state_province_id' => 'selectbox',
		'country_id' => 'selectbox',
		'postal_code' => 'textbox',
		'geo_code_1' => 'textbox',
		'geo_code_2' => 'textbox',
		//'name' => 'textbox',
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
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 20, 4 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'checkbox_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'true_false_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'select_settings_modify' ], 10, 3 );

		// Append "True/False" mappings to the "Checkbox" xProfile Field Type.
		add_filter( 'cwps/bp/civicrm/address_field/get_for_bp_field', [ $this, 'true_false_fields_append' ], 10, 2 );

		// Listen for when BuddyPress Profile Fields have been saved.
		add_filter( 'cwps/bp/contact/bp_fields_saved', [ $this, 'bp_fields_saved' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Save Address(es) when BuddyPress Profile Fields have been saved.
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

		// Filter the Fields to include only Address data.
		$address_fields = [];
		foreach ( $args['field_data'] as $field ) {
			if ( empty( $field['meta']['entity_type'] ) || $field['meta']['entity_type'] !== 'Address' ) {
				continue;
			}
			$address_fields[] = $field;
		}

		// Bail if there are no Address Fields.
		if ( empty( $address_fields ) ) {
			return;
		}

		// Group Fields by Location.
		$address_groups = [];
		foreach ( $address_fields as $field ) {
			if ( empty( $field['meta']['entity_data']['location_type_id'] ) ) {
				continue;
			}
			$location_type_id = $field['meta']['entity_data']['location_type_id'];
			$address_groups[$location_type_id][] = $field;
		}

		// Bail if there are no Address Groups.
		if ( empty( $address_groups ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_groups' => $address_groups,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Save each Address.
		foreach ( $address_groups as $location_type_id => $group ) {

			// Prepare the CiviCRM Address data.
			$address_data = $this->prepare_from_fields( $group );

			// Try and get the existing Address record.
			$existing = (array) $this->plugin->civicrm->address->address_get_by_location( $args['contact_id'], $location_type_id );

			// Add its ID if present.
			if ( ! empty( $existing['id'] ) ) {
				$address_data['id'] = $existing['id'];
			}

			// Add the Location Type.
			$address_data['location_type_id'] = $location_type_id;

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'address_data' => $address_data,
				//'backtrace' => $trace,
			], true ) );
			*/

			// Okay, write the data to CiviCRM.
			$address = $this->plugin->civicrm->address->update( $args['contact_id'], $address_data );

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
		$address_data = [];

		// Handle the data for each Field.
		foreach ( $field_data as $data ) {

			// Get metadata for this xProfile Field.
			$args = $data['meta'];
			if ( empty( $args ) ) {
				continue;
			}

			// Get the CiviCRM Custom Field and Address Field.
			$custom_field_id = $this->field->custom_field->id_get( $args['value'] );
			$address_field_name = $this->name_get( $args['value'] );

			// Do we have a synced Custom Field or Address Field?
			if ( ! empty( $custom_field_id ) || ! empty( $address_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . (string) $custom_field_id;

				} elseif ( ! empty( $address_field_name ) ) {

					// The Address Field code is the setting.
					$code = $address_field_name;

				}

				// Build args for value conversion.
				$args = [
					'custom_field_id' => $custom_field_id,
					'address_field_name' => $address_field_name,
				];

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					//'address_fields' => $address_fields,
					//'field_type' => $field_type,
					'data' => $data,
					'args' => $args,
					//'backtrace' => $trace,
				], true ) );
				*/

				// Parse value by Field Type.
				$value = $this->field->value_get_for_civicrm( $data['value'], $data['field_type'], $args );

				// Add it to the field data.
				$address_data[$code] = $value;

			}

		}

		///*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_data' => $address_data,
			//'backtrace' => $trace,
		], true ) );
		//*/

		// --<
		return $address_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the Address Field choices for a Setting Field from when found.
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

		// Bail if not the "Address" Entity Type.
		if ( $entity_type !== 'Address' ) {
			return $choices;
		}

		// Get the Address Fields for this BuddyPress Field Type.
		$address_fields = $this->get_for_bp_field_type( $field_type );

		// Build Address Field choices array for dropdown.
		if ( ! empty( $address_fields ) ) {
			$address_fields_label = esc_attr__( 'Address Fields', 'civicrm-wp-profile-sync' );
			foreach ( $address_fields as $address_field ) {
				$choices[$address_fields_label][$this->address_field_prefix . $address_field['name']] = $address_field['title'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/address_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Get the CiviCRM Address Fields for a BuddyPress Field Type.
	 *
	 * @since 0.5
	 *
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $address_fields The array of Address Fields.
	 */
	public function get_for_bp_field_type( $field_type ) {

		// Init return.
		$address_fields = [];

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
		$address_fields = $this->data_get( $field_type, 'public' );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields' => $address_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the Address Fields.
		 *
		 * @since 0.5
		 *
		 * @param array $address_fields The existing array of Address Fields.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$address_fields = apply_filters( 'cwps/bp/civicrm/address_field/get_for_bp_field', $address_fields, $field_type );

		// --<
		return $address_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the core Fields for a CiviCRM Address Type.
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
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Address Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $this->address_fields[$value['name']] ) {
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
	 * Get the Fields for CiviCRM Addresses.
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
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Address Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
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
	 * Get the public Fields for CiviCRM Addresses.
	 *
	 * @since 0.5
	 *
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields() {

		// Init return.
		$public_fields = [];

		// Get the public Fields for CiviCRM Addresses.
		$public_fields = $this->data_get_filtered( 'public' );

		// --<
		return $public_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Address Fields.
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
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Address Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
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
	 * Get the Address Field options for a given Field ID.
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
		$result = civicrm_api( 'Address', 'getfield', $params );

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
	 * Get the BuddyPress Field Type for an Address Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Address Field.
	 * @return string $type The type of BuddyPress Field.
	 */
	public function get_bp_type( $name = '' ) {

		// Init return.
		$type = false;

		// if the key exists, return the value - which is the BuddyPress Type.
		if ( array_key_exists( $name, $this->address_fields ) ) {
			$type = $this->address_fields[$name];
		}

		// --<
		return $type;

	}



	/**
	 * Gets the mapped Address Field name.
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
		if ( false === strpos( $value, $this->address_field_prefix ) ) {
			return $name;
		}

		// Get the mapped Contact Field name.
		$name = (string) str_replace( $this->address_field_prefix, '', $value );

		// --<
		return $name;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the BuddyPress "selectbox" options for a given CiviCRM Contact Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $options The array of xProfile Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $options;
		}

		// We only have a few to account for.

		// Counties.
		if ( $name == 'county_id' ) {
			$options = $this->plugin->civicrm->address->counties_get();
		}

		// States/Provinces.
		if ( $name == 'state_province_id' ) {
			$config = CRM_Core_Config::singleton();
			// Only get the list of States/Provinces if some are chosen.
			// BuddyPress becomes unresponsive when all are returned.
			if ( ! empty( $config->provinceLimit ) ) {
				$options = $this->plugin->civicrm->address->state_provinces_get();
			}
		}

		// Countries.
		if ( $name == 'country_id' ) {
			// Only get the list of Countries if some are chosen?
			$options = CRM_Core_PseudoConstant::country();
		}

		// --<
		return $options;

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
	 * Filter the Address Fields for a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $address_fields The existing array of Address Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $address_fields The modified array of Address Fields.
	 */
	public function true_false_fields_append( $address_fields, $field_type ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $address_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields' => $address_fields,
			'field_type' => $field_type,
			'name' => $name,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public fields of this type.
		$true_false_fields = $this->data_get( 'true_false', 'public' );
		if ( empty( $true_false_fields ) ) {
			return $address_fields;
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

		// Merge with Address Fields.
		$address_fields = array_merge( $address_fields, $true_false_fields );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields-FINAL' => $address_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $address_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a BuddyPress "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function select_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Field name.
		$field_name = $this->name_get( $args['value'] );
		if ( empty( $field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Field.
		$options = $this->options_get( $field_name );

		// --<
		return $options;

	}



	/**
	 * Modify the Options of a BuddyPress "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function checkbox_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Field name.
		$field_name = $this->name_get( $args['value'] );
		if ( empty( $field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Address Field.
		$options = $this->options_get( $field_name );

		// --<
		return $options;

	}



} // Class ends.



