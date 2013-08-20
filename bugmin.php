<?php
/**
 * bugmin.php
 *
 * Copyright (c) www.itthinx.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Karim Rahimpur
 * @package bugmin
 * @since bugmin 1.0.0
 *
 * Plugin Name: Bugmin
 * Plugin URI: http://www.itthinx.com/plugins/
 * Description: Show stuff in admin sections for debugging purposes.
 * Version: 1.0.0
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 * Donate-Link: http://www.itthinx.com
 * License: GPLv3
 */

define( 'BUGMIN_PLUGIN_DOMAIN', 'bugmin' );

/**
 * Bugmin class - admin notices.
 */
class Bugmin {

	/**
	 * Registers our admin_notices action.
	 */
	public static function init() {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
	}

	/**
	 * Prints the screen id and invokes actions hooked on bugmin_screen.
	 */
	public static function admin_notices() {

		$screen = get_current_screen();
		if ( !empty( $screen->id ) ) {
			echo '<div style="padding:1em;">';
			echo '<pre>';
			echo sprintf( 'Bugmin - on the <code>%s</code> screen.', $screen->id, true );
			echo '</pre>';
			echo '</div>';
		}

		do_action( 'bugmin_screen', $screen );

		if ( isset( $screen->id ) && $screen->id == 'settings_page_bugmin' && isset( $_POST['action'] ) && ( $_POST['action'] == 'save' ) && wp_verify_nonce( $_POST['bugmin'], 'admin' ) ) {
			echo '<div>';
			echo '<pre>';
			echo __( 'Skipped evaluation after saving settings.', BUGMIN_PLUGIN_DOMAIN );
			echo '</pre>';
			echo '</div>';
		}

		if ( get_option( 'bugmin-eval-enabled', 'no' ) === 'yes' ) {
			
			$eval = stripslashes( get_option( 'bugmin-eval', '' ) );
			$eval = trim( apply_filters( 'bugmin_eval', $eval, $screen ) );
			if ( strpos( $eval, '<?php' ) === 0 ) {
				$eval = substr( $eval, 5 );
			}
			if ( strpos( $eval, '<?=' ) === 0 ) {
				$eval = substr( $eval, 3 );
			}
			if ( strpos( $eval, '<?' ) === 0 ) {
				$eval = substr( $eval, 2 );
			}
			if ( strrpos( $eval, '?>' ) === strlen( $eval ) - 2 ) {
				$eval = substr($eval, 0, strlen( $eval ) - 2 );
			}
			do_action( 'bugmin_before_eval', $eval, $screen );
	
			if ( !empty( $eval ) ) {
				echo '<div>';
				echo '<pre>';
				echo __( 'Evaluating ...', BUGMIN_PLUGIN_DOMAIN );
				echo '</pre>';
				echo '</div>';
				$result = eval( $eval );
				if ( $result === false ) {
					delete_option( 'bugmin-eval-enabled' );
					add_option( 'bugmin-eval-enabled', 'no', '', 'no' );
					echo '<div>';
					echo '<pre>';
					echo __( '... the code is causing <strong>parse errors</strong>. Evaluation has been disabled.', BUGMIN_PLUGIN_DOMAIN );
					echo '</pre>';
					echo '</div>';
				} else {
					echo '<div>';
					echo '<pre>';
					echo __( '... done.', BUGMIN_PLUGIN_DOMAIN );
					echo '</pre>';
					echo '</div>';
				}
			}
			do_action( 'bugmin_after_eval', $eval, $screen );
		}
	}

	/**
	 * Add the Settings > Bugmin section.
	 */
	public static function admin_menu() {
		add_options_page(
			'Bugmin',
			'Bugmin',
			'manage_options',
			'bugmin',
			array( __CLASS__, 'settings' )
		);
	}

	/**
	 * Bugmin admin settings.
	 */
	public static function settings() {

		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Access denied.', BUGMIN_PLUGIN_DOMAIN ) );
		}

		if ( isset( $_POST['action'] ) && ( $_POST['action'] == 'save' ) && wp_verify_nonce( $_POST['bugmin'], 'admin' ) ) {
			delete_option( 'bugmin-eval' );
			add_option( 'bugmin-eval', $_POST['bugmin_eval'], '', 'no' );
			delete_option( 'bugmin-eval-enabled' );
			add_option( 'bugmin-eval-enabled', !empty( $_POST['bugmin_eval_enabled'] ) ? 'yes' : 'no', '', 'no' );
			echo
			'<p class="info">' .
			__( 'The settings have been saved.', BUGMIN_PLUGIN_DOMAIN ) .
			'</p>';
		}

		$bugmin_eval    = get_option( 'bugmin-eval', '' );
		$bugmin_eval_enabled = ( get_option( 'bugmin-eval-enabled', 'no' ) == 'yes' );

		echo '<h1>';
		echo __( 'Bugmin', BUGMIN_PLUGIN_DOMAIN );
		echo '</h1>';
		
		echo '<p style="text-align:center;padding:1em;">' . __( '<span style="color:#900;font-weight:bold;font-size:3em;">STOP!</span>', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p style="text-align:center;padding:1em;">' . __( '<span style="color:#900;font-weight:bold;font-size:1.2em;">Make a <strong>BACKUP</strong> of your entire site and database before you proceed.</span>', BUGMIN_PLUGIN_DOMAIN ) . '</p>';

		echo '<div class="settings" style="margin-right:1em;">';
		echo '<form name="settings" method="post" action="">';
		echo '<div>';
		
		echo '<h2>';
		echo __( 'Status', BUGMIN_PLUGIN_DOMAIN );
		echo '</h2>';
		echo '<label>';
		printf( '<input type="checkbox" name="bugmin_eval_enabled" %s />', $bugmin_eval_enabled == 'yes' ? ' checked="checked" ' : '' );
		echo __( 'Enable code execution with eval. Parse errors will disable this - WSOD may still happen though so check your code before testing it.', BUGMIN_PLUGIN_DOMAIN );
		echo '</label>';

		echo '<h2>' . sprintf( __( '<a href="%s">eval</a>', BUGMIN_PLUGIN_DOMAIN ), 'http://php.net/manual/en/function.eval.php' ) . '</h2>';

		echo '<p>' . __( '<code>eval</code> can <strong>destroy</strong> your World - yes, that includes your site.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Bugmin uses <code>eval</code> to evaluate the hopefully useful and unevil PHP code you are going to put into the field below.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Bugmin will <code>eval</code> this every time the <code>admin_notices</code> WordPress action is evoked.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Do not use opening or closing PHP tags.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Bugmin uses two options to store the code for eval and enabling its evaluation: <code>bugmin-eval</code> and <code>bugmin-eval-enabled</code>. You can try to delete these if you WSOD your site. Deleting the plugin folder entirely is another option.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'If all this sounds odd to you, do not put anything there, deactivate and delete the plugin.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Do NOT USE THIS PLUGIN unless you are an experienced PHP developer!', BUGMIN_PLUGIN_DOMAIN ) . '</p>';
		echo '<p>' . __( 'Use of this plugin is AT YOUR OWN RISK and YOU and ONLY YOU are responsible for any damage that might be caused by the use of this plugin or the use of this plugin by authorized or unauthorized persons or entities.', BUGMIN_PLUGIN_DOMAIN ) . '</p>';

		echo '<p>';
		echo '<label>';
		echo __( 'PHP Code - <strong>READ THE ABOVE NOTICES</strong>', BUGMIN_PLUGIN_DOMAIN );
		echo '<br/>';
		printf( '<textarea style="width:100%%;min-height:50em;" name="bugmin_eval">%s</textarea>', htmlentities( stripslashes( $bugmin_eval ) ) );
		echo '<br/>';
		echo __( 'You have been warned! Only proceed at <strong>YOUR OWN RISK!</strong>', BUGMIN_PLUGIN_DOMAIN );
		echo '</label>';
		echo '</p>';
		
		echo '<p style="color:#900;">' . __( 'This will take IMMEDIATE effect.', BUGMIN_PLUGIN_DOMAIN ). '</p>';

		wp_nonce_field( 'admin', 'bugmin', true, true );

		echo '<br/>';

		echo '<div class="buttons">';
		echo sprintf( '<input class="save button" type="submit" name="submit" value="%s" />', __( 'Save', BUGMIN_PLUGIN_DOMAIN ) );
		echo '<input type="hidden" name="action" value="save" />';
		echo '</div>';

		echo '</div>';
		echo '</form>';
		echo '</div>';
	}

}
Bugmin::init();
