<?php
/**
 * BuddyPress CiviCRM Custom Field Class.
 *
 * Handles BuddyPress CiviCRM Custom Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Custom Field Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Custom Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Custom_Field {

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
	 * BuddyPress Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The BuddyPress Field object.
	 */
	public $field;

	/**
	 * CiviCRM Custom Field data types that can have "Select", "Radio" and
	 * "CheckBox" HTML subtypes.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $data_types The data types that can have "Select", "Radio"
	 *                        and "CheckBox" HTML subtypes.
	 */
	public $data_types = [
		'String',
		'Int',
		'Float',
		'Money',
		'Country',
		'StateProvince',
	];

	/**
	 * All CiviCRM Custom Fields that are of type "Select".
	 *
	 * @since 0.5
	 * @access public
	 * @var array $data_types The Custom Fields that are of type "Select".
	 */
	public $select_types = [
		'Select',
		'Multi-Select',
		'Autocomplete-Select',
		'Select Country',
		'Multi-Select Country',
		'Select State/Province',
		'Multi-Select State/Province',
	];

	/**
	 * "CiviCRM Field" field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Custom Fields from Contact Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $custom_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $custom_field_prefix = 'cwps_custom_';


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

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 100, 4 );

		// Filter the "CiviCRM Field" select to include only Custom Fields of the right type on the "Edit Field" sceen.
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'checkbox_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'select_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'multiselect_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'radio_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'date_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'text_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'textarea_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'url_settings_filter' ], 10, 3 );
		//add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'true_false_settings_filter' ], 10, 3 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'checkbox_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'select_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'multiselect_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'radio_settings_get' ], 10, 3 );

		// Filter the xProfile Field settings when saving on the "Edit Field" screen.
		//add_filter( 'cwps/bp/field/query_options', [ $this, 'date_settings_get' ], 10, 3 );
		//add_filter( 'cwps/bp/field/query_options', [ $this, 'text_settings_get' ], 10, 3 );

		return;




		// Intercept when the content of a set of CiviCRM Custom Fields has been updated.
		add_action( 'cwps/bp/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Intercept Post synced from Contact events.
		add_action( 'cwps/bp/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Intercept Post synced from Activity events.
		//add_action( 'cwps/bp/post/activity/sync', [ $this, 'activity_sync_to_post' ], 10 );

		// Intercept Post synced from Participant events.
		//add_action( 'cwps/bp/post/participant/sync', [ $this, 'participant_sync_to_post' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the Contact Field choices for a Setting Field from when found.
	 *
	 * Contact Fields only differ for the top level Contact Types.
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

		// Get Custom Fields for the "Contact" Entity Type.
		if ( $entity_type === 'Contact' ) {

			// We need Contact Type data.
			if ( empty( $entity_type_data ) ) {
				return $choices;
			}

			// Get the "name" of the Contact Type.
			$name = $entity_type_data['name'];
			$subtype_name = '';

			// Alter names if this is a Sub-type.
			if ( ! empty( $entity_type_data['parent_id'] ) ) {
				$parent_type = $this->civicrm->contact->type_get_by_id( $entity_type_data['parent_id'] );
				$name = $parent_type['name'];
				$subtype_name = $entity_type_data['name'];
			}

			// Get the Custom Fields for this Contact Type.
			$custom_fields = $this->plugin->civicrm->custom_field->get_for_contact_type( $name, $subtype_name );

		} else {

			// Get Custom Fields for other Entity Types.
			$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( $entity_type, '' );

		}

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$filtered_fields = apply_filters( 'cwps/bp/query_settings/custom_fields_filter', [], $custom_fields, $field_type );

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			foreach ( $filtered_fields as $custom_field_label => $custom_fields ) {
				foreach ( $custom_fields as $custom_field ) {
					$choices[$custom_field_label][$this->custom_field_prefix . $custom_field['id']] = $custom_field['label'];
				}
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			//'choices' => $choices,
			'field_type' => $field_type,
			'contact_type' => $contact_type,
			//'custom_fields' => $custom_fields,
			'filtered_fields' => $filtered_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/custom_field/choices', $choices );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'choices' => $choices,
		], true ) );
		*/

		// Return populated array.
		return $choices;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the mapped Custom Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return integer|bool $custom_field_id The mapped Custom Field ID or false if not present.
	 */
	public function id_get( $value ) {

		// Init return.
		$custom_field_id = false;

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->custom_field_prefix ) ) {
			return $custom_field_id;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = (int) str_replace( $this->custom_field_prefix, '', $value );

		// --<
		return $custom_field_id;

	}



	/**
	 * Get the mapped Custom Field ID for a given BuddyPress Field.
	 *
	 * @since 0.5
	 *
	 * @param object $field The xProfile Field object.
	 * @return integer|bool $custom_field_id The numeric ID of the Custom Field, or false if none.
	 */
	public function id_get_by_field( $field ) {

		// Init return.
		$custom_field_id = false;

		// Get the BuddyPress CiviCRM Field value.
		$bp_field_value = $this->field->get_mapping_data( $field, 'value' );

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $bp_field_value );

		/**
		 * Filter the Custom Field ID.
		 *
		 * @since 0.5
		 *
		 * @param integer $custom_field_id The existing Custom Field ID.
		 * @param object $field The xProfile Field object.
		 */
		$custom_field_id = apply_filters( 'cwps/bp/custom_field/id_get', $custom_field_id, $field );

		// --<
		return $custom_field_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the value of a Custom Field, formatted for BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The Custom Field value.
	 * @param array $field The Custom Field data.
	 * @param string $selector The BuddyPress Field selector.
	 * @param integer|string $post_id The BuddyPress "Post ID".
	 * @return mixed $value The formatted field value.
	 */
	public function value_get_for_bp( $value, $field, $selector, $post_id ) {

		// Bail if empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Convert CiviCRM value to BuddyPress value by field type.
		switch( $field->type ) {

			// Used by "CheckBox" and others.
			case 'String' :
			case 'Country' :
			case 'StateProvince' :

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}

				break;

			// Contact Reference fields may return the Contact's "sort_name".
			case 'ContactReference' :

				// Test for a numeric value.
				if ( ! is_numeric( $value ) ) {

					/*
					 * This definitely happens when Contact Reference fields are
					 * attached to Events - when retrieving the Event from the
					 * CiviCRM API, the Custom Field values are helpfully added
					 * to the returned data. However, the value in "custom_N" is
					 * the Contact's "sort_name". The numeric ID is also returned,
					 * but this is added under the key "custom_N_id" instead.
					 */

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'value' => $value,
						'field' => $field,
						'selector' => $selector,
						'post_id' => $post_id,
						//'backtrace' => $trace,
					], true ) );
					*/

				}

				break;

			// Used by "Date Select" and  "Date Time Select".
			case 'Timestamp' :

				// Get field setting.
				$bp_setting = get_field_object( $selector, $post_id );

				// Convert to BuddyPress format.
				$datetime = DateTime::createFromFormat( 'YmdHis', $value );
				if ( $bp_setting['type'] == 'date_picker' ) {
					$value = $datetime->format( 'Ymd' );
				} elseif ( $bp_setting['type'] == 'date_time_picker' ) {
					$value = $datetime->format( 'Y-m-d H:i:s' );
				}

				break;

		}

		// --<
		return $value;

	}



	/**
	 * Get the "date format" for a given CiviCRM Custom Field ID.
	 *
	 * @since 0.5
	 *
	 * @param array $custom_field_id The numeric ID of the Custom Field.
	 * @return string $format The date format.
	 */
	public function date_format_get_from_civicrm( $custom_field_id ) {

		// Init return.
		$format = '';

		// Bail if there is no Custom Field ID.
		if ( empty( $custom_field_id ) ) {
			return $format;
		}

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $format;
		}

		// Bail if it's not Date.
		if ( $field_data['data_type'] !== 'Date' ) {
			return $format;
		}

		// Bail if it's not "Select Date".
		if ( $field_data['html_type'] !== 'Select Date' ) {
			return $format;
		}

		// Bail if the "Time Format" is set.
		if ( isset( $field_data['time_format'] ) ) {
			return $format;
		}

		// Get the mappings.
		$mappings = $this->plugin->mapper->date_mappings;

		// Get the PHP format.
		$format = $mappings[ $field_data['date_format'] ];

		// --<
		return $format;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a BuddyPress "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function checkbox_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->checkbox_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function checkbox_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not "String".
		if ( $field_data['data_type'] !== 'String' ) {
			return $choices;
		}

		// Bail if it's not "CheckBox".
		if ( $field_data['html_type'] !== 'CheckBox' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "CheckBox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function checkbox_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only Boolean/Radio.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'String' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'CheckBox' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function select_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->select_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function select_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Select".
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not a type of "Select".
		if ( ! in_array( $field_data['html_type'], $this->select_types ) ) {
			return $choices;
		}

		// Populate with child options where possible.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// "Country" selects require special handling.
		$country_selects = [ 'Select Country', 'Multi-Select Country' ];
		if ( in_array( $field_data['html_type'], $country_selects ) ) {
			$choices = CRM_Core_PseudoConstant::country();
		}

		// "State/Province" selects also require special handling.
		$state_selects = [ 'Select State/Province', 'Multi-Select State/Province' ];
		if ( in_array( $field_data['html_type'], $state_selects ) ) {
			$choices = CRM_Core_PseudoConstant::stateProvince();
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function select_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $filtered_fields;
		}

		/*
		// BuddyPress has no "Autocomplete-Select".
		if ( $field['ui'] == 1 && $field['ajax'] == 1 ) {

			// Filter fields to include only Autocomplete-Select.
			$select_types = [ 'Autocomplete-Select' ];

		}
		*/

		// Filter fields to include only "Select" types.
		$select_types = [ 'Select', 'Select Country', 'Select State/Province' ];

		// Filter fields to include only those which are compatible.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && in_array( $custom_field['html_type'], $select_types ) ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Multi Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function multiselect_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'multiselectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->select_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Multi Select Box" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function multiselect_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'multiselectbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only "Multi-Select" types.
		$select_types = [ 'Multi-Select', 'Multi-Select Country', 'Multi-Select State/Province' ];

		// Filter fields to include only those which are compatible.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && in_array( $custom_field['html_type'], $select_types ) ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function radio_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->radio_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function radio_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Radio" sub-type.
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not "Radio".
		if ( $field_data['html_type'] !== 'Radio' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function radio_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only "Radio" HTML types.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Date" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function date_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'datebox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only Date/Select Date.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Date' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Select Date' ) {
						if ( ! isset( $custom_field['time_format'] ) OR $custom_field['time_format'] == '0' ) {
							$filtered_fields[$custom_group_name][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function text_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'textbox' !== $field_type ) {
			return $options;
		}

		// Bail if our prefix isn't there.
		if ( false === strpos( $args['value'], $this->custom_field_prefix ) ) {
			return $options;
		}

		// Get keyed array of settings.
		//$options = $this->radio_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function text_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'textbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only those of HTML type "Text".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Text' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Textarea" Field.
	 *
	 * Thisis actually a "Rich Text" Field in BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function textarea_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'textarea' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only Memo/RichTextEditor.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Memo' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'RichTextEditor' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "True/False" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function true_false_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'true_false' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only Boolean/Radio.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Boolean' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "URL" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function url_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'url' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter fields to include only "Link".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Link' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Link' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------
	public function _____divider() {}
	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------
	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated BuddyPress Fields mapped to Custom Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get the Custom Fields for this CiviCRM Contact.
		$custom_fields_for_contact = $this->plugin->civicrm->custom_field->get_for_contact( $args['objectRef'] );

		// Bail if we don't have any Custom Fields for this Contact.
		if ( empty( $custom_fields_for_contact ) ) {
			return;
		}

		// Get the Custom Field IDs for this Contact.
		$custom_field_ids = $this->ids_get_by_contact_id( $args['objectId'], $args['post_type'] );

		// Filter the Custom Fields array.
		$filtered = [];
		foreach ( $custom_field_ids as $selector => $custom_field_id ) {
			foreach ( $custom_fields_for_contact as $key => $custom_field_data ) {
				if ( $custom_field_data['id'] == $custom_field_id ) {
					$filtered[$selector] = $custom_field_data;
					break;
				}
			}
		}

		// Extract the Custom Field mappings.
		$custom_field_mappings = wp_list_pluck( $filtered, 'id' );

		// Get the Custom Field values for this Contact.
		$custom_field_values = $this->plugin->civicrm->custom_field->values_get_by_contact_id( $args['objectId'], $custom_field_mappings );

		// Build a final data array.
		$final = [];
		foreach ( $filtered as $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[$custom_field['id']];
			$custom_field['type'] = $custom_field['data_type'];
			$final[$key] = $custom_field;
		}

		// Let's populate each BuddyPress Field in turn.
		foreach ( $final as $selector => $field ) {

			// Modify values for BuddyPress prior to update.
			$value = $this->value_get_for_bp(
				$field['value'],
				$field,
				$selector,
				$args['post_id']
			);

			// Update the BuddyPress Field.
			$this->bp_loader->bp->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Get the Custom Field correspondences for a given Contact ID and Post Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param string $post_type The WordPress Post Type.
	 * @return array $custom_field_ids The array of found Custom Field IDs.
	 */
	public function ids_get_by_contact_id( $contact_id, $post_type ) {

		// Init return.
		$custom_field_ids = [];

		// Grab Contact.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
		if ( $contact === false ) {
			return $custom_field_ids;
		}

		// Get the Post ID that this Contact is mapped to.
		$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );
		if ( $post_id === false ) {
			return $custom_field_ids;
		}

		// Get all fields for the Post.
		$bp_fields = $this->bp_loader->bp->field->fields_get_for_post( $post_id );

		// Bail if we don't have any Custom Fields.
		if ( empty( $bp_fields['custom'] ) ) {
			return $custom_field_ids;
		}

		// Build the array of Custom Field IDs, keyed by BuddyPress selector.
		foreach ( $bp_fields['custom'] as $selector => $field ) {
			$custom_field_ids[$selector] = $field;
		}

		// --<
		return $custom_field_ids;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update BuddyPress Fields when a set of CiviCRM Custom Fields has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_edited( $args ) {

		// Init Post IDs.
		$post_ids = false;

		/**
		 * Query for the Post IDs that this set of Custom Fields are mapped to.
		 *
		 * This filter sends out a request for other classes to respond with a
		 * Post ID if they detect that the set of Custom Fields maps to an
		 * Entity Type that they are responsible for.
		 *
		 * When a Contact is created, however, the synced Post has not yet been
		 * created because the "civicrm_custom" hook fires before "civicrm_post"
		 * fires and so the Post ID will always be false.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_BP_CiviCRM_Contact::query_post_id()
		 * @see CiviCRM_Profile_Sync_BP_CiviCRM_Activity::query_post_id()
		 *
		 * @since 0.5
		 *
		 * @param bool $post_ids False, since we're asking for Post IDs.
		 * @param array $args The array of CiviCRM Custom Fields params.
		 * @return array|bool $post_ids The array of mapped Post IDs, or false if not mapped.
		 */
		$post_ids = apply_filters( 'cwps/bp/query_post_id', $post_ids, $args );

		// Process the Post IDs that we get.
		if ( $post_ids !== false ) {

			// Handle each Post ID in turn.
			foreach ( $post_ids as $post_id ) {

				// Get the BuddyPress Fields for this Post.
				$bp_fields = $this->bp_loader->bp->field->fields_get_for_post( $post_id );

				// Bail if we don't have any Custom Fields.
				if ( empty( $bp_fields['custom'] ) ) {
					continue;
				}

				// Build a reference array for Custom Fields.
				$custom_fields = [];
				foreach ( $args['custom_fields'] as $key => $field ) {
					$custom_fields[$key] = $field['custom_field_id'];
				}

				// Let's look at each BuddyPress Field in turn.
				foreach ( $bp_fields['custom'] as $selector => $custom_field_ref ) {

					// Skip if it isn't mapped to a Custom Field.
					if ( ! in_array( $custom_field_ref, $custom_fields ) ) {
						continue;
					}

					// Get the corresponding Custom Field.
					$args_key = array_search( $custom_field_ref, $custom_fields );
					$field = $args['custom_fields'][$args_key];

					// Modify values for BuddyPress prior to update.
					$value = $this->value_get_for_bp(
						$field['value'],
						$field,
						$selector,
						$post_id
					);

					// Update it.
					$this->bp_loader->bp->field->value_update( $selector, $value, $post_id );

				}

			}

		}

		/**
		 * Broadcast that a set of CiviCRM Custom Fields may have been updated.
		 *
		 * @since 0.5
		 *
		 * @param array|bool $post_ids The array of mapped Post IDs, or false if not mapped.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/bp/civicrm/custom_field/custom_edited', $post_ids, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Custom Fields for a BuddyPress Field.
	 *
	 * @since 0.5
	 *
	 * @param object $field The BuddyPress Field data object.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_bp_field( $field ) {

		// Init return.
		$custom_fields = [];

		// Get field group for this field's parent.
		$field_group = $this->bp_loader->bp->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $custom_fields;
		}

		/**
		 * Query for the Custom Fields that this BuddyPress Field can be mapped to.
		 *
		 * This filter sends out a request for other classes to respond with an
		 * array of Fields if they detect that the set of Custom Fields maps to
		 * an Entity Type that they are responsible for.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_BP_CiviCRM_Contact::query_custom_fields()
		 * @see CiviCRM_Profile_Sync_BP_CiviCRM_Activity::query_custom_fields()
		 * @see CiviCRM_Profile_Sync_BP_CiviCRM_Participant::query_custom_fields()
		 *
		 * @since 0.5
		 *
		 * @param array $custom_fields Empty by default.
		 * @param array $field_group The array of BuddyPress Field Group data.
		 * @param array $custom_fields The populated array of CiviCRM Custom Fields params.
		 */
		$custom_fields = apply_filters( 'cwps/bp/query_custom_fields', $custom_fields, $field_group );

		// --<
		return $custom_fields;

	}



} // Class ends.



