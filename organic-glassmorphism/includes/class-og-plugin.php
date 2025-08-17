<?php
/**
 * Main plugin class for Organic Glassmorphism.
 *
 * @package   Organic_Glassmorphism
 * @author    Jules
 * @version   1.1.0
 * @since     1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The main plugin class.
 */
final class OG_Plugin {

	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'add_theme_toggle_html' ), 100 );
		add_action( 'wp_footer', array( $this, 'inject_svg_filters' ) );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_init', array( $this, 'register_settings' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_assets' ) );
		}
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'organic-glassmorphism', false, dirname( plugin_basename( __DIR__ ) ) . '/languages/' );
	}

	public function get_content_selectors() {
		$default_selectors = array(
			'body > .wp-site-blocks', '#page', '#main', 'main', '.site-main',
		);
		$selectors = apply_filters( 'organic_glassmorphism_content_selectors', $default_selectors );
		return array_unique( array_filter( $selectors ) );
	}

	public function enqueue_assets() {
		// Enqueue the main static stylesheet.
		wp_enqueue_style( 'organic-glassmorphism-core', ORGANIC_GLASSMORPHISM_URL . 'assets/css/style.css', array(), ORGANIC_GLASSMORPHISM_VERSION );

		// Enqueue the dynamic stylesheet if it exists, using a timestamp for cache busting.
		$dynamic_css_path = $this->get_upload_dir_path() . '/dynamic.css';
		if ( file_exists( $dynamic_css_path ) ) {
			wp_enqueue_style(
				'organic-glassmorphism-dynamic',
				$this->get_upload_dir_url() . '/dynamic.css',
				array( 'organic-glassmorphism-core' ),
				get_option( 'og_styles_timestamp' )
			);
		}

		// Enqueue the frontend JavaScript for the theme toggle.
		wp_enqueue_script( 'organic-glassmorphism-main-script', ORGANIC_GLASSMORPHISM_URL . 'assets/js/script.js', array(), ORGANIC_GLASSMORPHISM_VERSION, true );

		// JavaScript to apply the main container class dynamically.
		$selectors = $this->get_content_selectors();
		$js_code = "
			document.addEventListener('DOMContentLoaded', function() {
				const selectors = " . json_encode( $selectors ) . ";
				for ( const selector of selectors ) {
					const el = document.querySelector( selector );
					if ( el ) {
						el.classList.add( 'og-sitewide-container-effect' );
						break;
					}
				}
			});
		";
		wp_add_inline_script( 'organic-glassmorphism-main-script', str_replace( array( "\r", "\n", "\t" ), '', $js_code ) );
	}

	public function add_theme_toggle_html() {
		?>
		<div class="og-theme-toggle-container" role="presentation">
			<label for="og-theme-toggle-checkbox" class="og-toggle-label" aria-label="<?php esc_attr_e( 'Toggle Dark Mode', 'organic-glassmorphism' ); ?>">
				<input type="checkbox" id="og-theme-toggle-checkbox" class="og-theme-toggle-checkbox">
				<div class="og-theme-toggle-switch"></div>
			</label>
		</div>
		<?php
	}

	public function inject_svg_filters() {
		?>
		<svg xmlns="http://www.w3.org/2000/svg" version="1.1" style="position: absolute; width: 0; height: 0; overflow: hidden;">
			<defs><filter id="organic-liquid-distortion"><feTurbulence type="fractalNoise" baseFrequency="0.005 0.015" numOctaves="1" result="turbulence"/><feDisplacementMap in2="turbulence" in="SourceGraphic" scale="4" xChannelSelector="R" yChannelSelector="G"/></filter></defs>
		</svg>
		<?php
	}

	// --- Admin Methods ---

	public function admin_enqueue_assets( $hook ) {
		if ( 'settings_page_organic_glassmorphism' !== $hook ) return;

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'og-admin-script', ORGANIC_GLASSMORPHISM_URL . 'assets/js/admin-script.js', array( 'wp-color-picker', 'jquery' ), ORGANIC_GLASSMORPHISM_VERSION, true );

		// Pass preset data to the admin script.
		wp_localize_script( 'og-admin-script', 'ogPresetData', $this->get_design_presets() );

		$admin_css = "
			.og-presets-container { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 1rem; }
			.og-preset-radio { display: none; }
			.og-preset-label { background: #f0f0f1; border: 1px solid #dcdcde; padding: 8px 16px; border-radius: 4px; cursor: pointer; transition: all 0.2s ease-in-out; }
			.og-preset-radio:checked + .og-preset-label, .og-preset-label:hover { background-color: #0073aa; color: #fff; border-color: #0073aa; }
		";
		wp_add_inline_style( 'wp-color-picker', $admin_css );
	}

	public function add_admin_menu() {
		add_options_page( __( 'Organic Glassmorphism Settings', 'organic-glassmorphism' ), __( 'Organic Glassmorphism', 'organic-glassmorphism' ), 'manage_options', 'organic_glassmorphism', array( $this, 'settings_page_html' ) );
	}

	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'organic-glassmorphism' ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Customize the appearance of the Organic Glassmorphism effects across your site.', 'organic-glassmorphism' ); ?></p>
			<form action="options.php" method="post">
				<?php settings_fields( 'organic_glassmorphism_options' ); ?>
				<?php do_settings_sections( 'organic_glassmorphism' ); ?>
				<?php submit_button( __( 'Save Changes', 'organic-glassmorphism' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function register_settings() {
		register_setting( 'organic_glassmorphism_options', 'og_settings', array( $this, 'sanitize_settings' ) );

		add_settings_section( 'og_presets_section', __( 'Design Presets', 'organic-glassmorphism' ), null, 'organic_glassmorphism' );
		add_settings_field( 'og_design_preset', __( 'Choose a Preset', 'organic-glassmorphism' ), array( $this, 'presets_field_cb' ), 'organic_glassmorphism', 'og_presets_section' );

		add_settings_section( 'og_colors_section', __( 'Color Palette', 'organic-glassmorphism' ), null, 'organic_glassmorphism' );
		add_settings_field( 'og_bg_color', __( 'Page Background', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_bg_color'] );
		add_settings_field( 'og_base_text_color', __( 'Base Text Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_base_text_color'] );
		add_settings_field( 'og_glass_bg_color', __( 'Glass Background', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_glass_bg_color'] );
		add_settings_field( 'og_glass_border_color', __( 'Glass Border Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_glass_border_color'] );
		add_settings_field( 'og_shadow_color', __( 'Glass Shadow Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_shadow_color'] );

		add_settings_section( 'og_effects_section', __( 'Glass Effects', 'organic-glassmorphism' ), null, 'organic_glassmorphism' );
		add_settings_field( 'og_backdrop_blur', __( 'Blur Radius (px)', 'organic-glassmorphism' ), array( $this, 'number_field_cb' ), 'organic_glassmorphism', 'og_effects_section', ['id' => 'og_backdrop_blur', 'desc' => 'The intensity of the backdrop blur effect.'] );
		add_settings_field( 'og_border_radius', __( 'Border Radius (px)', 'organic-glassmorphism' ), array( $this, 'number_field_cb' ), 'organic_glassmorphism', 'og_effects_section', ['id' => 'og_border_radius', 'desc' => 'The roundness of the corners.'] );
	}

	public function sanitize_settings( $input ) {
		$settings = get_option( 'og_settings', array() );
		$output = array_merge( $settings, $input );
		$color_keys = ['og_bg_color', 'og_base_text_color', 'og_glass_bg_color', 'og_glass_border_color', 'og_shadow_color'];
		foreach($color_keys as $key) {
			if (isset($input[$key])) $output[$key] = sanitize_text_field($input[$key]);
		}
		$int_keys = ['og_backdrop_blur', 'og_border_radius'];
		foreach($int_keys as $key) {
			if (isset($input[$key])) $output[$key] = absint($input[$key]);
		}

		// After sanitizing, save the new settings to the dynamic stylesheet.
		$this->save_dynamic_stylesheet( $output );

		return $output;
	}

	/**
	 * Gets the path to the custom uploads directory.
	 * @return string
	 */
	private function get_upload_dir_path() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['basedir'] ) . 'organic-glassmorphism';
	}

	/**
	 * Gets the URL to the custom uploads directory.
	 * @return string
	 */
	private function get_upload_dir_url() {
		$upload_dir = wp_upload_dir();
		return trailingslashit( $upload_dir['baseurl'] ) . 'organic-glassmorphism';
	}

	/**
	 * Generates and saves the dynamic CSS file.
	 * @param array $settings The sanitized settings array.
	 * @return bool True on success, false on failure.
	 */
	public function save_dynamic_stylesheet( $settings ) {
		if ( empty( $settings ) ) {
			return false;
		}

		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$upload_path = $this->get_upload_dir_path();

		if ( ! $wp_filesystem->is_dir( $upload_path ) ) {
			$wp_filesystem->mkdir( $upload_path, 0755 );
		}

		$defaults = $this->get_default_settings();
		$settings = wp_parse_args( $settings, $defaults );

		$rgba = array();
		$fallback_rgba = 'rgba(255, 255, 255, 0.9)';
		if ( preg_match( '/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d(?:\.\d+)?))?\)$/', $settings['og_glass_bg_color'], $rgba ) ) {
			$alpha = isset($rgba[4]) ? floatval($rgba[4]) : 1;
			$fallback_alpha = min(1, $alpha + 0.5);
			$fallback_rgba = 'rgba(' . $rgba[1] . ',' . $rgba[2] . ',' . $rgba[3] . ', ' . $fallback_alpha . ')';
		}

		$css_content = "
			:root {
				--og-bg-color: " . esc_html( $settings['og_bg_color'] ) . ";
				--og-base-text-color: " . esc_html( $settings['og_base_text_color'] ) . ";
				--og-glass-bg: " . esc_html( $settings['og_glass_bg_color'] ) . ";
				--og-glass-bg-fallback: " . esc_html( $fallback_rgba ) . ";
				--og-glass-border-color: " . esc_html( $settings['og_glass_border_color'] ) . ";
				--og-shadow-color: " . esc_html( $settings['og_shadow_color'] ) . ";
				--og-backdrop-blur: " . absint( $settings['og_backdrop_blur'] ) . "px;
				--og-border-radius: " . absint( $settings['og_border_radius'] ) . "px;
			}
		";

		$file_path = trailingslashit( $upload_path ) . 'dynamic.css';
		$result = $wp_filesystem->put_contents( $file_path, str_replace( array( "\r", "\n", "\t" ), '', $css_content ) );

		if ( $result ) {
			update_option( 'og_styles_timestamp', time(), false );
			return true;
		}

		return false;
	}

	public function get_default_settings() {
		return $this->get_design_presets()['default'];
	}

	/**
	 * Defines the settings for all design presets.
	 * @return array
	 */
	public function get_design_presets() {
		return array(
			'default' => array(
				'og_bg_color'           => '#f9f9f9',
				'og_base_text_color'    => '#333333',
				'og_glass_bg_color'     => 'rgba(255, 255, 255, 0.5)',
				'og_glass_border_color' => 'rgba(0, 0, 0, 0.05)',
				'og_shadow_color'       => 'rgba(0, 0, 0, 0.05)',
				'og_backdrop_blur'      => '10',
				'og_border_radius'      => '12',
			),
			'futuristic_ui' => array(
				'og_bg_color'           => '#2C2A4A',
				'og_base_text_color'    => '#F0F0F5',
				'og_glass_bg_color'     => 'rgba(75, 70, 110, 0.4)',
				'og_glass_border_color' => 'rgba(150, 100, 255, 0.2)',
				'og_shadow_color'       => 'rgba(0, 0, 0, 0)',
				'og_backdrop_blur'      => '8',
				'og_border_radius'      => '10',
			),
			'saas_dashboard' => array(
				'og_bg_color'           => '#1f2128',
				'og_base_text_color'    => '#ffffff',
				'og_glass_bg_color'     => 'rgba(44, 47, 57, 1)',
				'og_glass_border_color' => '#8A2BE2',
				'og_shadow_color'       => 'rgba(0, 0, 0, 0)',
				'og_backdrop_blur'      => '0',
				'og_border_radius'      => '8',
			),
		);
	}

	// --- Field Callbacks ---
	public function presets_field_cb() {
		?>
		<div class="og-presets-container">
			<label>
				<input type="radio" name="og_settings[og_design_preset]" value="default" class="og-preset-radio" data-preset="default" checked>
				<span class="og-preset-label">Default</span>
			</label>
			<label>
				<input type="radio" name="og_settings[og_design_preset]" value="futuristic_ui" class="og-preset-radio" data-preset="futuristic_ui">
				<span class="og-preset-label">Futuristic UI</span>
			</label>
			<label>
				<input type="radio" name="og_settings[og_design_preset]" value="saas_dashboard" class="og-preset-radio" data-preset="saas_dashboard">
				<span class="og-preset-label">SaaS Dashboard</span>
			</label>
		</div>
		<p class="description"> <?php esc_html_e( 'Select a preset to apply a pre-configured style. This will update the fields below.', 'organic-glassmorphism' ); ?> </p>
		<?php
	}

	public function color_field_cb( $args ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( 'og_settings' );
		$value = isset( $settings[$args['id']] ) ? $settings[$args['id']] : $defaults[$args['id']];
		echo '<input type="text" id="' . esc_attr($args['id']) . '" name="og_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" class="og-color-picker" data-alpha-enabled="true" data-default-color="' . esc_attr( $defaults[$args['id']] ) . '">';
	}

	public function number_field_cb( $args ) {
		$defaults = $this->get_default_settings();
		$settings = get_option( 'og_settings' );
		$value = isset( $settings[$args['id']] ) ? $settings[$args['id']] : $defaults[$args['id']];
		echo '<input type="number" id="' . esc_attr($args['id']) . '" name="og_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" class="small-text">';
		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}

	/**
	 * Cleans up plugin data on deactivation.
	 */
	public function on_deactivation() {
		// Delete the timestamp option
		delete_option( 'og_styles_timestamp' );
		// Note: We don't delete the main 'og_settings' on deactivation, only on uninstall.

		// Delete the dynamic stylesheet and its directory
		global $wp_filesystem;
		if ( ! $wp_filesystem ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$upload_path = $this->get_upload_dir_path();
		if ( $wp_filesystem->is_dir( $upload_path ) ) {
			$wp_filesystem->rmdir( $upload_path, true ); // true for recursive
		}
	}
}
