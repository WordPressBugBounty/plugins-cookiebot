<?php

namespace cybot\cookiebot\settings\pages;

use cybot\cookiebot\addons\controller\addons\Base_Cookiebot_Addon;
use cybot\cookiebot\addons\Cookiebot_Addons;
use cybot\cookiebot\lib\Consent_API_Helper;
use cybot\cookiebot\lib\Cookiebot_Frame;
use cybot\cookiebot\lib\Cookiebot_Javascript_Helper;
use cybot\cookiebot\lib\Settings_Service_Interface;
use cybot\cookiebot\lib\Cookiebot_WP;
use cybot\cookiebot\shortcode\Cookiebot_Declaration_Shortcode;

use InvalidArgumentException;
use function cybot\cookiebot\lib\asset_url;
use function cybot\cookiebot\lib\include_view;
use Exception;

class Support_Page implements Settings_Page_Interface {


	const ADMIN_SLUG = 'cookiebot_support';

	public function menu() {
		add_submenu_page(
			'cookiebot',
			__( 'Cookiebot Support', 'cookiebot' ),
			__( 'Support', 'cookiebot' ),
			'manage_options',
			self::ADMIN_SLUG,
			array( $this, 'display' ),
			20
		);
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function display() {
		$scripts = array(
			array( 'cookiebot-support-page-js', 'js/backend/support-page.js' ),
		);

		foreach ( $scripts as $script ) {
			wp_enqueue_script(
				$script[0],
				asset_url( $script[1] ),
				null,
				Cookiebot_WP::COOKIEBOT_PLUGIN_VERSION,
				true
			);
		}

		$style_sheets = array(
			array( 'cookiebot-support-css', 'css/backend/support_page.css' ),
		);

		foreach ( $style_sheets as $style ) {
			wp_enqueue_style(
				$style[0],
				asset_url( $style[1] ),
				null,
				Cookiebot_WP::COOKIEBOT_PLUGIN_VERSION
			);
		}

		$debug_output = $this->prepare_debug_data();
		include_view( Cookiebot_Frame::get_view_path() . 'support-page.php', array( 'debug_output' => $debug_output ) );
	}

	private function get_ignored_scripts() {
		$ignored_scripts = get_option( 'cookiebot-ignore-scripts' );

		$ignored_scripts = array_map(
			function ( $ignore_tag ) {
				return trim( $ignore_tag );
			},
			explode( PHP_EOL, $ignored_scripts )
		);

		$ignored_scripts = apply_filters( 'cybot_cookiebot_ignore_scripts', $ignored_scripts );

		return implode( ', ', $ignored_scripts );
	}

	/**
	 * @throws InvalidArgumentException
	 */
	public function prepare_debug_data() {
		global $wpdb;

		$cookiebot_javascript_helper = new Cookiebot_Javascript_Helper();

		$debug_output = '';
		// phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
		$debug_output .= '##### Debug Information for ' . get_site_url() . ' generated at ' . date( 'c' ) . " #####\n\n";
		$debug_output .= 'WordPress Version: ' . get_bloginfo( 'version' ) . "\n";
		$debug_output .= 'WordPress Language: ' . get_bloginfo( 'language' ) . "\n";
		$debug_output .= 'PHP Version: ' . phpversion() . "\n";
		$debug_output .= 'MySQL Version: ' . $wpdb->db_version() . "\n";
		$debug_output .= "\n--- Cookiebot Information ---\n";
		$debug_output .= 'Plugin Version: ' . Cookiebot_WP::COOKIEBOT_PLUGIN_VERSION . "\n";
		$debug_output .= 'Settings ID: ' . Cookiebot_WP::get_cbid() . "\n";
		$debug_output .= 'Blocking mode: ' . get_option( 'cookiebot-cookie-blocking-mode' ) . "\n";
		if ( Cookiebot_Frame::is_cb_frame_type() !== false ) {
			$debug_output .= 'Language: ' . get_option( 'cookiebot-language' ) . "\n";
			$debug_output .= 'Frontend Language: ' . $this->print_option_enabled( 'cookiebot-front-language' ) . "\n";
		}
		$debug_output .= 'IAB: ' . $this->print_option_enabled( 'cookiebot-iab' ) . "\n";
		if ( Cookiebot_Frame::is_cb_frame_type() !== false ) {
			$debug_output .= 'TCF version: ' . $this->print_tcf_version() . "\n";
			$debug_output .= 'TCF tag: ' . $cookiebot_javascript_helper->include_publisher_restrictions_js( true ) . "\n";
			$debug_output .= 'Multiple banners: ' . $this->print_option_enabled( 'cookiebot-multiple-config' ) . "\n";
			$debug_output .= $this->print_multiple_configuration_banners();
			$debug_output .= 'Add async/defer to banner tag: ' . $this->print_option_if_not_empty( 'cookiebot-script-tag-uc-attribute' ) . "\n";
			$debug_output .= 'Add async/defer to declaration tag: ' . $this->print_option_if_not_empty( 'cookiebot-script-tag-cd-attribute' ) . "\n";
		}
		$debug_output .= 'Auto update: ' . $this->print_option_enabled( 'cookiebot-autoupdate' ) . "\n";
		$debug_output .= 'Show banner on site: ' . $this->print_option_active( 'cookiebot-banner-enabled' ) . "\n";
		$debug_output .= 'Hide Cookie Popup: ' . $this->print_option_active( 'cookiebot-nooutput' ) . "\n";
		$debug_output .= 'Enable Cookiebot on front end while logged in: ' . $this->print_option_active( 'cookiebot-output-logged-in' ) . "\n";
		if ( Cookiebot_Frame::is_cb_frame_type() !== false ) {
			$debug_output .= 'List of ignored javascript files: ' . $this->get_ignored_scripts() . "\n";
			$debug_output .= 'Banner tag: ' . "\n" . $cookiebot_javascript_helper->include_cookiebot_js( true ) . "\n";
			$debug_output .= 'Declaration tag: ' . Cookiebot_Declaration_Shortcode::show_declaration() . "\n";
		} else {
			$debug_output .= 'Banner tag: ' . "\n" . $cookiebot_javascript_helper->include_uc_cmp_js( true ) . "\n";
		}

		if ( get_option( 'cookiebot-gtm' ) !== false ) {
			$debug_output .= 'GTM tag: ' . $cookiebot_javascript_helper->include_google_tag_manager_js( true ) . "\n";
		}

		if ( get_option( 'cookiebot-gcm' ) === '1' ) {
			$debug_output .= 'GCM tag: ' . $cookiebot_javascript_helper->include_google_consent_mode_js( true ) . "\n";
		}

		if ( is_multisite() ) {
			$debug_output .= $this->print_multisite_network_settings();
		}

		$debug_output .= $this->print_wp_consent_level_api_mapping();
		$debug_output .= $this->print_activated_addons();
		$debug_output .= $this->print_activated_plugins();

		$debug_output .= "\n##### Debug Information END #####";

		return $debug_output;
	}

	/**
	 * Print the value of the option if it's not empty.
	 *
	 * @param string $option_name Name of the option to print.
	 *
	 * @return string
	 */
	private function print_option_if_not_empty( $option_name, $is_multisite = false ) {
		$option_value = $is_multisite ? get_site_option( $option_name ) : get_option( $option_name );
		return $option_value !== '' ? $option_value : 'None';
	}

	/**
	 * Print "Enabled" or "Not enabled" depending on the option value. Option value should be "1" or "0".
	 *
	 * @param string $option_name Name of the option to check.
	 * @param bool   $is_multisite Is multisite option.
	 *
	 * @return string
	 */
	private function print_option_enabled( $option_name, $is_multisite = false ) {
		return $this->print_option_active( $option_name, $is_multisite, 'Enabled', 'Not enabled' );
	}

	/**
	 * Print "Yes" or "No" depending on the option value. Option value should be "1" or "0". If <b>$active_text</b> or
	 * <b>$disabled_text</b> is set, it will be used instead of default values "Yes" or "No".
	 *
	 * @param string $option_name Name of the option to check.
	 * @param bool   $is_multisite Is multisite option.
	 * @param string $active_text (Optional) Text to print if option is active. Default is "Yes".
	 * @param string $disabled_text (Optional) Text to print if option is disabled. Default is "No".
	 *
	 * @return string
	 */
	private function print_option_active( $option_name, $is_multisite = false, $active_text = 'Yes', $disabled_text = 'No' ) {
		if ( $is_multisite ) {
			return get_site_option( $option_name ) === '1' ? $active_text : $disabled_text;
		}
		return get_option( $option_name ) === '1' ? $active_text : $disabled_text;
	}

	/**
	 * Render debug information about WP Consent Level API mapping.
	 *
	 * @return string
	 */
	private function print_wp_consent_level_api_mapping() {
		$output = '';

		$consent_api_helper = new Consent_API_Helper();

		if ( $consent_api_helper->is_wp_consent_api_active() ) {
			$output .= "\n--- WP Consent API Mapping ---\n";
			$map     = $consent_api_helper->get_wp_consent_api_mapping();
			if ( Cookiebot_Frame::is_cb_frame_type() !== false ) {
				$output .= 'F = Functional, N = Necessary, P = Preferences, M = Marketing, S = Statistics, SA = Statistics Anonymous' . "\n";
				foreach ( $map as $key => $value ) {
					$output .= strtoupper( str_replace( ';', ', ', $key ) ) . '   =>   ';
					$output .= 'F=1, ';
					$output .= 'P=' . $value['preferences'] . ', ';
					$output .= 'M=' . $value['marketing'] . ', ';
					$output .= 'S=' . $value['statistics'] . ', ';
					$output .= 'SA=' . $value['statistics-anonymous'] . "\n";
				}
			} else {
				foreach ( $map as $key => $value ) {
					$output .= $key . ' => ' . $value . "\n";
				}
			}
		}

		return $output;
	}

	private function print_multiple_configuration_banners() {
		$secondary_id      = get_option( 'cookiebot-second-banner-id' );
		$secondary_regions = get_option( 'cookiebot-second-banner-regions' );
		$banners           = get_option( 'cookiebot-multiple-banners' );
		$output            = '';

		if ( ! empty( $banners ) ) {
			$counter = 1;
			$output .= "\n--- Multiple Configuration Banners ---\n";
			if ( ! empty( $secondary_id ) ) {
				$output .= '-Banner: ' . $counter . " -\n";
				$output .= 'Id: ' . $secondary_id . " \n";
				$output .= 'Regions: ' . $secondary_regions . " \n\n";
				++$counter;
			}
			foreach ( $banners as $banner ) {
				$output .= '-Banner: ' . $counter . " -\n";
				$output .= 'Id: ' . $banner['group'] . " \n";
				$output .= 'Regions: ' . $banner['region'] . " \n\n";
				++$counter;
			}
		}

		return $output;
	}

	private function print_tcf_version() {
		$version = get_option( 'cookiebot-tcf-version' );
		return ( empty( $version ) || $version === 'IAB' ) ? '2.0' : $version;
	}

	/**
	 * Print information about activated cookiebot addons.
	 *
	 * @return string
	 */
	private function print_activated_addons() {
		$output = '';

		if ( Cookiebot_Frame::is_cb_frame_type() === false ) {
			return $output;
		}

		try {
			$cookiebot_addons = new Cookiebot_Addons();
			/** @var Settings_Service_Interface $settings_service */
			$settings_service = $cookiebot_addons->container->get( 'Settings_Service_Interface' );
			$addons           = $settings_service->get_active_addons();
			$output          .= "\n--- Activated Cookiebot Addons ---\n";
			/** @var Base_Cookiebot_Addon $addon */
			foreach ( $addons as $addon ) {
				$output .= $addon::ADDON_NAME . ' (' . implode( ', ', $addon->get_cookie_types() ) . ")\n";
			}
		} catch ( Exception $exception ) {
			$output .= PHP_EOL . '--- Cookiebot Addons could not be activated ---' . PHP_EOL;
			$output .= $exception->getMessage() . PHP_EOL;
		}

		return $output;
	}

	/**
	 * Print information about activated plugins
	 *
	 * @return string
	 */
	private function print_activated_plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugins        = get_plugins();
		$active_plugins = get_option( 'active_plugins' );

		$output = "\n--- Activated Plugins ---\n";

		foreach ( $active_plugins as $plugin_key ) {
			if ( $plugin_key !== 'cookiebot/cookiebot.php' ) {
				$output .= $plugins[ $plugin_key ]['Name'] . ' (Version: ' . $plugins[ $plugin_key ]['Version'] . ")\n";
			}
		}

		return $output;
	}

	/**
	 * Print information about activated plugins
	 *
	 * @return string
	 */
	private function print_multisite_network_settings() {
		$output  = "\n--- Cookiebot Multisite Information ---\n";
		$output .= 'Cookiebot Network ID: ' . $this->print_option_if_not_empty( 'cookiebot-cbid', true ) . "\n";
		$output .= 'Network Blocking mode: ' . get_site_option( 'cookiebot-cookie-blocking-mode' ) . "\n";
		$output .= 'Network Add async/defer to banner tag: ' . $this->print_option_if_not_empty( 'cookiebot-script-tag-uc-attribute', true ) . "\n";
		$output .= 'Network Add async/defer to declaration tag: ' . $this->print_option_if_not_empty( 'cookiebot-script-tag-cd-attribute', true ) . "\n";
		$output .= 'Network Auto update: ' . $this->print_option_enabled( 'cookiebot-autoupdate', true ) . "\n";
		$output .= 'Network Hide Cookie Popup: ' . $this->print_option_enabled( 'cookiebot-nooutput', true ) . "\n";
		$output .= 'Network Disable Cookiebot in WP Admin: ' . $this->print_option_active( 'cookiebot-nooutput-admin', true ) . "\n";

		return $output;
	}
}
