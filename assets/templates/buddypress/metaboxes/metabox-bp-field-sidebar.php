<?php
/**
 * BuddyPress xProfile Field sidebar metabox template.
 *
 * Handles markup for the BuddyPress xProfile Field sidebar metabox.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

?><!-- assets/templates/buddypress/metaboxes/metabox-bp-field-sidebar.php -->
<div class="postbox" id="field-type-blah-metabox">
	<h2><?php esc_html_e( 'Blah', 'buddypress' ); ?></h2>
	<div class="inside">
		<p class="description"><?php esc_html_e( 'CiviCRM Field', 'civicrm-wp-profile-sync' ); ?></p>

		<p>
			<label for="do-blah" class="screen-reader-text"><?php esc_html_e( 'CiviCRM Field', 'civicrm-wp-profile-sync' ); ?></label>
			<select name="do_blah" id="do-blah">
				<option value="on"><?php esc_html_e( 'Enabled', 'civicrm-wp-profile-sync' ); ?></option>
				<option value=""><?php esc_html_e( 'Disabled', 'civicrm-wp-profile-sync' ); ?></option>
			</select>
		</p>
	</div>
</div>
