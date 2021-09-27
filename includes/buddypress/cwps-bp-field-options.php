<?php
/**
 * BuddyPress xProfile Field Options Class.
 *
 * Handles BuddyPress xProfile Field Options functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress xProfile Field Options Class.
 *
 * A class that encapsulates BuddyPress xProfile Field Options functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_xProfile_Field_Options {

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

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	// -------------------------------------------------------------------------



} // Class ends.



