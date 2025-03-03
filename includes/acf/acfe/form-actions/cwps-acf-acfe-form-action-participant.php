<?php
/**
 * "Participant" ACFE Form Action Class.
 *
 * Handles the "Participant" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "Participant" ACFE Form Action Class.
 *
 * A class that handles the "Participant" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Participant extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $form The ACFE Form object.
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = 'cwps_participant';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_participant_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_participant_action_';

	/**
	 * Public Participant Fields to add.
	 *
	 * These are not mapped for Post Type Sync, so need to be added.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_add The Public Participant Fields to add.
	 */
	public $fields_to_add = [
		//'must_wait' => 'select',
	];

	/**
	 * Public Participant Fields to ignore.
	 *
	 * These are mapped for Post Type Sync, but need special handling.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_ignore The Public Participant Fields to ignore.
	 */
	public $fields_to_ignore = [
		'contact_id' => 'civicrm_contact',
		'event_id' => 'civicrm_event',
		'status_id' => 'select',
		'register_date' => 'date_time_picker',
	];

	/**
	 * Participant Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $contact_fields The Participant Contact Fields.
	 */
	public $contact_fields = [
		'contact_id' => 'civicrm_contact',
	];

	/**
	 * Participant Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $participant_fields The Participant Fields.
	 */
	public $participant_fields = [
		'registered_by_id' => 'civicrm_participant',
	];

	/**
	 * Participant Event Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $event_fields The Participant Event Fields.
	 */
	public $event_fields = [
		'event_id' => 'civicrm_event',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acfe = $parent->acfe;
		$this->form = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->action_label = __( 'CiviCRM Participant action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Participant', 'civicrm-wp-profile-sync' );

		// Register hooks.
		$this->register_hooks();

		// Init parent.
		parent::__construct();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

		// Get the public Participant Fields for all Participants.
		$this->public_participant_fields = $this->civicrm->participant_field->get_public_fields();

		// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
		if ( ! empty( $this->fields_to_add ) ) {
			foreach ( $this->fields_to_add as $name => $field_type ) {
				array_unshift( $this->public_participant_fields, $this->civicrm->participant_field->get_by_name( $name ) );
			}
		}

		// Populate public mapping Fields.
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Handle Contact Fields.
		foreach ( $this->contact_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->participant_field->get_by_name( $name );
			$this->mapping_field_filters_add( $field['name'] );

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_contacts[] = $field;

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Handle Participant Fields.
		foreach ( $this->participant_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->participant_field->get_by_name( $name );
			$this->mapping_field_filters_add( $field['name'] );

			// Add +articipant Action Reference Field to ACF Model.
			$this->js_model_participant_reference_field_add( $this->field_name . 'ref_' . $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_participants[] = $field;

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Handle Event Fields.
		foreach ( $this->event_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->participant_field->get_by_name( $name );
			$this->mapping_field_filters_add( $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_events[] = $field;

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Get the Custom Groups and Fields for all Participants.
		$this->custom_fields = $this->plugin->civicrm->custom_group->get_for_participants();
		$this->custom_field_ids = [];

		// Populate mapping Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
					// Also build Custom Field IDs.
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

		// Participant Conditional Field.
		$this->mapping_field_filters_add( 'participant_conditional' );

	}



	/**
	 * Pre-load mapping Fields with "Generic" choices.
	 *
	 * Not used but leaving this here for future use.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing array of Field data.
	 * @param array $field The modified array of Field data.
	 */
	public function prepare_choices( $field ) {

		// --<
		return $field;

	}



	/**
	 * Performs validation when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		/*
		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );
		//acfe_add_validation_error( $selector, $message );
		*/

	}



	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Populate Participant and Custom Field data arrays.
		$participant = $this->form_participant_data( $form, $current_post_id, $action );
		$custom_fields = $this->form_custom_data( $form, $current_post_id, $action );

		// Save the Participant with the data from the Form.
		$participant = $this->form_participant_save( $participant, $custom_fields );

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $participant );

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Define Field.
		$participant_roles_field = [
			'key' => $this->field_key . 'participant_roles',
			'label' => __( 'Participant Role', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'participant_roles',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->participant_role->choices_get(),
		];

		// Define Status Field.
		$participant_status_field = [
			'key' => $this->field_key . 'participant_status_id',
			'label' => __( 'Participant Status', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'participant_status_id',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->participant_field->options_get( 'status_id' ),
		];

		// Init Fields.
		$fields = [
			$participant_roles_field,
			$participant_status_field,
		];

		// Add Campaign Field if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {

			$fields[] = [
				'key' => $this->field_key . 'participant_campaign_id',
				'label' => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'participant_campaign_id',
				'type' => 'select',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => '',
				'allow_null' => 1,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => $this->civicrm->campaign->choices_get(),
			];

		}

		// Add Conditional Field.
		$code = 'participant_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To add the Participant only when a Form Field is populated (e.g. "Email") link this to the Form Field. To add the Participant only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
		$fields[] = $conditional;

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_contacts_accordion = $this->tab_mapping_accordion_contacts_add();

		// Build Participants Accordion.
		$mapping_participants_accordion = $this->tab_mapping_accordion_participants_add();

		// Build Event Accordion.
		$mapping_event_accordion = $this->tab_mapping_accordion_event_add();

		// Build Participant Details Accordion.
		$mapping_participant_accordion = $this->tab_mapping_accordion_participant_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_participants_accordion,
			$mapping_event_accordion,
			$mapping_participant_accordion,
			$mapping_custom_accordion
		);

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Contacts" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_contacts_add() {

		// Init return.
		$fields = [];

		// "Contact References" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_open',
			'label' => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key' => $this->field_key . 'contact_group_' . $field['name'],
				'label' => $field['title'],
				'name' => $this->field_name . 'contact_group_' . $field['name'],
				'type' => 'group',
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'required' => 0,
				'layout' => 'block',
			];

			// Define Contact Action Reference Field.
			$contact_group_field['sub_fields'][] = [
				'key' => $this->field_key . 'ref_' . $field['name'],
				'label' => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'ref_' . $field['name'],
				'type' => 'cwps_acfe_contact_action_ref',
				'instructions' => __( 'Select a Contact Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'cid_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$cid_field = [
				'key' => $this->field_key . 'cid_' . $field['name'],
				'label' => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'cid_' . $field['name'],
				'type' => 'civicrm_contact',
				'instructions' => __( 'Select a CiviCRM Contact ID from the database.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'ref_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

			// Define Custom Contact Reference Field.
			$title = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions'] = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $this->field_key . 'ref_' . $field['name'],
						'operator' => '==empty',
					],
					[
						'field' => $this->field_key . 'cid_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Contact Reference Field.
			$contact_group_field['sub_fields'][] = $mapping_field;

			// Add Contact Reference Group.
			$fields[] = $contact_group_field;

		}

		// "Contact References" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_close',
			'label' => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Participants" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_participants_add() {

		// Init return.
		$fields = [];

		// "Participant References" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_participants_open',
			'label' => __( 'Participant References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add Participant Reference Fields.
		foreach ( $this->fields_for_participants as $field ) {

			// Bundle them into a container group.
			$participant_group_field = [
				'key' => $this->field_key . 'participant_group_' . $field['name'],
				'label' => $field['title'],
				'name' => $this->field_name . 'participant_group_' . $field['name'],
				'type' => 'group',
				'instructions' => sprintf( __( 'If the Participant is not the Submitter, use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'required' => 0,
				'layout' => 'block',
			];

			// Define Participant Action Reference Field.
			$participant_group_field['sub_fields'][] = [
				'key' => $this->field_key . 'ref_' . $field['name'],
				'label' => __( 'CiviCRM Participant Action', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'ref_' . $field['name'],
				'type' => 'cwps_acfe_participant_action_ref',
				'instructions' => __( 'Select a Participant Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'cid_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Participant ID Field.
			$pid_field = [
				'key' => $this->field_key . 'cid_' . $field['name'],
				'label' => __( 'CiviCRM Participant ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'cid_' . $field['name'],
				'type' => 'civicrm_participant',
				'instructions' => __( 'Select a CiviCRM Participant ID from the database.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'ref_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Participant ID Field.
			//$participant_group_field['sub_fields'][] = $pid_field;

			// Define Custom Participant Reference Field.
			$title = sprintf( __( 'Custom Participant Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions'] = __( 'Define a custom Participant Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $this->field_key . 'ref_' . $field['name'],
						'operator' => '==empty',
					],
					[
						'field' => $this->field_key . 'cid_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Participant Reference Field.
			$participant_group_field['sub_fields'][] = $mapping_field;

			// Add Participant Reference Group.
			$fields[] = $participant_group_field;

		}

		// "Participant References" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_participants_close',
			'label' => __( 'Participant References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Event" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_event_add() {

		// Init return.
		$fields = [];

		// "Event References" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_events_open',
			'label' => __( 'Event References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add Event Reference Fields.
		foreach ( $this->fields_for_events as $field ) {

			// Define the Group key.
			$group_key = $this->field_key . 'event_group';

			// Bundle them into a container group.
			$event_group_field = [
				'key' => $group_key,
				'label' => $field['title'],
				'name' => $this->field_name . 'event_group',
				'type' => 'group',
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'required' => 0,
				'layout' => 'block',
			];

			// Define Event ID Field.
			$event_id_field = [
				'key' => $group_key . '_' . $field['name'],
				'label' => __( 'CiviCRM Event ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'event_id_' . $field['name'],
				'type' => 'civicrm_event',
				'instructions' => __( 'Select a CiviCRM Event ID from the database.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Event ID Field.
			$event_group_field['sub_fields'][] = $event_id_field;

			// Define Custom Event Reference Field.
			$title = sprintf( __( 'Custom Event Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions'] = __( 'Define a custom Event Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $group_key . '_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Event Reference Field.
			$event_group_field['sub_fields'][] = $mapping_field;

			// Add Event ID Reference Group.
			$fields[] = $event_group_field;

			// Define Event Type Field.
			$event_type_field = [
				'key' => $this->field_key . 'event_type',
				'label' => __( 'Event Type', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'event_type',
				'type' => 'select',
				'instructions' => __( 'Choose the Event Type to show its Custom Fields below.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field',
				],
				'default_value' => '',
				'choices' => $this->acf_loader->civicrm->event->types_get_options(),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
			];

			// Add Event Type Field.
			$fields[] = $event_type_field;

		}

		// "Event References" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_events_close',
			'label' => __( 'Event References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Participant Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_participant_add() {

		// Init return.
		$fields = [];

		// "Participant Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_participant_open',
			'label' => __( 'Participant Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Participant Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_participant_close',
			'label' => __( 'Participant Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Custom Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_custom_add() {

		// Init return.
		$fields = [];

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Init conditional logic.
			$conditional_logic = [];

			/*
			 * For Participant: "extends_entity_column_id" means:
			 *
			 * * Missing "extends_entity_column_id": All Participants.
			 * * Missing "extends_entity_column_value": All Participants.
			 *
			 * 1: The VALUE of the 'participant_role'
			 * 2: The ID of the CiviCRM Event
			 * 3: The VALUE of the 'event_type'
			 */

			// Missing "extends_entity_column_id" means All Participants.
			if ( ! empty( $custom_group['extends_entity_column_id'] ) ) {

				// Set conditions for Fields that only show for Participant Roles.
				if ( $custom_group['extends_entity_column_id'] == 1 ) {

					// Get the Participant Role IDs.
					$participant_role_ids = $custom_group['extends_entity_column_value'];

					// Add Roles as OR conditionals if present.
					if ( ! empty( $participant_role_ids ) ) {
						foreach ( $participant_role_ids as $participant_role_id ) {

							$participant_role = [
								'field' => $this->field_key . 'participant_roles',
								'operator' => '==contains',
								'value' => $participant_role_id,
							];

							$conditional_logic[] = [
								$participant_role,
							];

						}
					}

				}

				// Set conditions for Fields that only show for specific Events.
				if ( $custom_group['extends_entity_column_id'] == 2 ) {

					// Get the Event IDs.
					$event_ids = $custom_group['extends_entity_column_value'];

					// Add Events as OR conditionals if present.
					if ( ! empty( $event_ids ) ) {
						foreach ( $event_ids as $event_id ) {

							$event_ref = [
								'field' => $this->field_key . 'map_event_id',
								'operator' => '==',
								'value' => $event_id,
							];

							$event = [
								'field' => $this->field_key . 'event_group_event_id',
								'operator' => '==',
								'value' => $event_id,
							];

							$conditional_logic[] = [
								$event_ref,
							];

							$conditional_logic[] = [
								$event,
							];

						}
					}

				}

				// Set conditions for Fields that only show for specific Event Types.
				if ( $custom_group['extends_entity_column_id'] == 3 ) {

					// Get the Event Type IDs.
					$event_type_ids = $custom_group['extends_entity_column_value'];

					// Add Event Types as OR conditionals if present.
					if ( ! empty( $event_type_ids ) ) {
						foreach ( $event_type_ids as $event_type_id ) {

							$event = [
								'field' => $this->field_key . 'event_type',
								'operator' => '==',
								'value' => $event_type_id,
							];

							$conditional_logic[] = [
								$event,
							];

						}
					}

				}

			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key' => $this->field_key . 'custom_group_' . $custom_group['id'],
				'label' => $custom_group['title'],
				'name' => $this->field_name . 'custom_group_' . $custom_group['id'],
				'type' => 'group',
				'instructions' => '',
				'instruction_placement' => 'field',
				'required' => 0,
				'layout' => 'block',
				'conditional_logic' => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
		];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Participant data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Participant data.
	 */
	public function form_participant_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[ $field['name'] ] = get_sub_field( $this->field_key . 'map_' . $field['name'] );
			}
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the Participant Role & Status.
		$data['participant_role_id'] = get_sub_field( $this->field_key . 'participant_roles' );
		$data['status_id'] = get_sub_field( $this->field_key . 'participant_status_id' );

		// Get the Participant Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = get_sub_field( $this->field_key . 'contact_group_' . $field['name'] );

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ] ) ) {
				$action_name = $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ];
				$contact_id = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ] ) ) {
					$contact_id = $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ];
				}
			}

			// Check mapped Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ) ) {
					$reference = [ $field['name'] => $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $field['name'] ] ) && is_numeric( $reference[ $field['name'] ] ) ) {
						$contact_id = $reference[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data[ $field['name'] ] = $contact_id;
			}

		}

		// Get the Participant Participants.
		foreach ( $this->fields_for_participants as $field ) {

			// Get Group Field.
			$participant_group_field = get_sub_field( $this->field_key . 'participant_group_' . $field['name'] );

			// Check Action Reference Field.
			$participant_id = false;
			if ( ! empty( $participant_group_field[ $this->field_name . 'ref_' . $field['name'] ] ) ) {
				$action_name = $participant_group_field[ $this->field_name . 'ref_' . $field['name'] ];
				$participant_id = $this->form_participant_id_get_mapped( $action_name );
			}

			/*
			// Check Participant ID Field.
			if ( $participant_id === false ) {
				if ( ! empty( $participant_group_field[ $this->field_name . 'cid_' . $field['name'] ] ) ) {
					$participant_id = $participant_group_field[ $this->field_name . 'cid_' . $field['name'] ];
				}
			}
			*/

			// Check mapped Field.
			if ( $participant_id === false ) {
				if ( ! empty( $participant_group_field[ $this->field_name . 'map_' . $field['name'] ] ) ) {
					$reference = [ $field['name'] => $participant_group_field[ $this->field_name . 'map_' . $field['name'] ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $field['name'] ] ) && is_numeric( $reference[ $field['name'] ] ) ) {
						$participant_id = $reference[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $participant_id ) && is_numeric( $participant_id ) ) {
				$data[ $field['name'] ] = $participant_id;
			}

		}

		// Get the Event.
		foreach ( $this->fields_for_events as $field ) {

			// Get Group Field.
			$event_group_field = get_sub_field( $this->field_key . 'event_group' );

			// Check Event ID Field.
			$event_id = false;
			if ( ! empty( $event_group_field[ $this->field_name . 'event_id_' . $field['name'] ] ) ) {
				$event_id = $event_group_field[ $this->field_name . 'event_id_' . $field['name'] ];
			}

			// Check mapped Field.
			if ( $event_id === false ) {
				if ( ! empty( $event_group_field[ $this->field_name . 'map_' . $field['name'] ] ) ) {
					$reference = [ $field['name'] => $event_group_field[ $this->field_name . 'map_' . $field['name'] ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $field['name'] ] ) && is_numeric( $reference[ $field['name'] ] ) ) {
						$event_id = $reference[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $event_id ) && is_numeric( $event_id ) ) {
				$data[ $field['name'] ] = $event_id;
			}

		}

		// Add the Campaign if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$data['campaign_id'] = get_sub_field( $this->field_key . 'participant_campaign_id' );
		}

		// Get Participant Conditional Reference.
		$data['participant_conditional_ref'] = get_sub_field( $this->field_key . 'map_participant_conditional' );
		$conditionals = [ $data['participant_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Participant Conditional.
		$data['participant_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}



	/**
	 * Saves the CiviCRM Participant given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $participant_data The array of Participant data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array|bool $participant The Participant data array, or false on failure.
	 */
	public function form_participant_save( $participant_data, $custom_data ) {

		// Init return.
		$participant = false;

		// Skip if the Participant Conditional Reference Field has a value.
		if ( ! empty( $participant_data['participant_conditional_ref'] ) ) {
			// And the Participant Conditional Field has no value.
			if ( empty( $participant_data['participant_conditional'] ) ) {
				return $participant;
			}
		}

		// Add Custom Field data if present.
		if ( ! empty ( $custom_data ) ) {
			$participant_data += $custom_data;
		}

		// Unset Participant Conditionals.
		if ( isset( $participant_data['participant_conditional'] ) ) {
			unset( $participant_data['participant_conditional'] );
		}
		if ( isset( $participant_data['participant_conditional_ref'] ) ) {
			unset( $participant_data['participant_conditional_ref'] );
		}

		// Strip out empty Fields.
		$participant_data = $this->form_data_prepare( $participant_data );

		// Create the Participant.
		$result = $this->civicrm->participant->create( $participant_data );

		// Bail on failure.
		if ( $result === false ) {
			return $participant;
		}

		// Get the full Participant data.
		$participant = $this->civicrm->participant->get_by_id( $result['id'] );

		// --<
		return $participant;

	}



	/**
	 * Finds the linked Contact ID when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_mapped( $action_name ) {

		// Init return.
		$contact_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $contact_id;
		}

		// Get the Contact data for that Action.
		$related_contact = acfe_form_get_action( $action_name, 'contact' );
		if ( empty( $related_contact['id'] ) ) {
			return $contact_id;
		}

		// Assign return.
		$contact_id = (int) $related_contact['id'];

		// --<
		return $contact_id;

	}



	/**
	 * Finds the linked Participant ID when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $participant_id The numeric ID of the Participant, or false if not found.
	 */
	public function form_participant_id_get_mapped( $action_name ) {

		// Init return.
		$participant_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $participant_id;
		}

		// Get the Participant data for that Action.
		$related_participant_id = acfe_form_get_action( $action_name, 'id' );
		if ( empty( $related_participant_id ) ) {
			return $participant_id;
		}

		// Assign return.
		$participant_id = (int) $related_participant_id;

		// --<
		return $participant_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Build data array.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Fresh Fields array.
			$fields = [];

			// Get Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );
			foreach ( $custom_group_field as $field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];
				}

			}

			// Populate data array with values of mapped Fields.
			$data += acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// --<
		return $data;

	}



} // Class ends.



