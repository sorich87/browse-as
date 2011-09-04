<?php
/**
 * @package Browse_As
 * @version 0.2
 */
/*
Plugin Name: Browse As
Plugin URI: http://pubpoet.com/plugins/
Description: Allow your site administrators, editors and other roles with the 'edit_users' capability to browser the site as a user they can edit.
Version: 0.2
Author: PubPoet
Author URI: http://pubpoet.com/
License: GPL2
*/
/*  Copyright 2011  Ulrich Sossou  (email : sorich87@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Main plugin class
 *
 * @since 0.1
 **/
class IS_BA_Browse_As {

	/**
	 * Class contructor
	 *
	 * @since 0.1
	 **/
	public function __construct() {
		add_filter( 'user_row_actions', array( $this, 'user_row_actions' ), 10, 2 );
		add_filter( 'init', array( $this, 'switch_user' ) );
		add_action( 'wp_footer', array( $this, 'notice' ), 1000 );
		add_action( 'admin_footer', array( $this, 'notice' ), 1000 );
		add_action( 'wp_head', array( $this, 'notice_css' ), 1000 );
		add_action( 'admin_head', array( $this, 'notice_css' ), 1000 );
	}

	/**
	 * Add action to users list.
	 *
	 * @since 0.1
	 */
	function user_row_actions( $actions, $user_object ) {
		if ( current_user_can( 'edit_user',  $user_object->ID ) && get_current_user_id() !== $user_object->ID ) {
			$actions['browse_as'] = '<a class="submitbrowseas" href="' . wp_nonce_url( "users.php?action=browse_as&amp;user=$user_object->ID", 'is-ba-switch-user' ) . '">' . __( 'Browse as' ) . '</a>';
		}

		return $actions;
	}

	function switch_user() {
		$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

		switch ( $action ) {
			case 'browse_as' :
				check_admin_referer( 'is-ba-switch-user' );

				$user = get_user_by( 'id', (int) $_GET['user'] );

				if ( ! current_user_can( 'edit_user',  $user->ID ) )
					wp_die( __( 'You do not have sufficient permissions to browse the site as this user.' ) );

				$original_user_id = get_current_user_id();

				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID, false );
				do_action( 'wp_login', $user->user_login );

				$secure = is_ssl();
				$secure = apply_filters( 'secure_auth_cookie', $secure, $user->ID );
				$secure_cookie = apply_filters( 'is_ba_secure_browse_as_cookie', false, $user->ID, $secure );
				setcookie( 'is_ba_original_user_' . COOKIEHASH, $original_user_id, 0, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_cookie, true );

				wp_safe_redirect( admin_url('profile.php') );
				exit;
			break;

			case 'browse_as_restore' :
				check_admin_referer( 'is-ba-restore-user' );

				if ( ! isset( $_COOKIE['is_ba_original_user_' . COOKIEHASH] ) && $_COOKIE['is_ba_original_user_' . COOKIEHASH] !== $_GET['user'] )
					die( __( 'Cheatin&#8217; uh?' ) );

				$user = get_user_by( 'id', (int) $_GET['user'] );

				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID, false );
				do_action( 'wp_login', $user->user_login );

				setcookie( 'is_ba_original_user_' . COOKIEHASH, ' ', time() - 31536000, SITECOOKIEPATH, COOKIE_DOMAIN );

				wp_safe_redirect( admin_url('users.php') );
				exit;
			break;

			default :
			break;
		}
	}

	function notice() {
		$current_user = wp_get_current_user();

		if ( ! isset( $_COOKIE['is_ba_original_user_' . COOKIEHASH] ) )
			return;

		$original_user_id = $_COOKIE['is_ba_original_user_' . COOKIEHASH];
		$original_user = get_user_by( 'id', $original_user_id );
		$back_url = wp_nonce_url( site_url( "?action=browse_as_restore&amp;user=$original_user_id" ), 'is-ba-restore-user' );

		echo "<div id='browseas-notice' class='updated'><p><strong>{$original_user->display_name}, you are browsing the site as {$current_user->display_name}. <a class='button' href='{$back_url}'>Back to your session.</a></strong></p></div>";
	}

	function notice_css() {
		$current_user = wp_get_current_user();

		if ( ! isset( $_COOKIE['is_ba_original_user_' . COOKIEHASH] ) )
			return;

		echo '<style type="text/css">
#browseas-notice {
	position: fixed;
	top: 50px;
	left: 50px;
	padding: 0 0.6em;
	margin: 5px 0 15px;
	border: 1px solid #e6db55;
	background-color: #ffffe0;
	color: #333;
	-moz-border-radius: 3px;
	-khtml-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-radius: 3px;
}
#browseas-notice p {
	margin: 0.5em 0;
	padding: 2px;
}
#browseas-notice a {
	text-decoration: none;
	padding-bottom: 2px;
}
</style>';
	}

}

new IS_BA_Browse_As;
