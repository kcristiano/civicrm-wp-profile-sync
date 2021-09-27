<?php
/**
 * BuddyPress Member Type Class.
 *
 * Handles BuddyPress Member Type compatibility.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress Member Type Class.
 *
 * A class that encapsulates BuddyPress Member Type functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_Member_Type {

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

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/buddypress/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwps/buddypress/member_type/loaded' );

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



