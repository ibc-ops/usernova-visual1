<?php
/**
 * Main plugin class for Organic Glassmorphism.
 *
 * @package   Organic_Glassmorphism
 * @author    Jules
 * @version   1.0.0
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

	/**
	 * The single instance of the class.
	 * @var OG_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Main instance. Ensures only one instance of the class is loaded.
	 * @return OG_Plugin
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
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

	/**
	 * Loads the plugin text domain for translation.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'organic-glassmorphism',
			false,
			dirname( plugin_basename( __DIR__ ) ) . '/languages/'
		);
	}

	/**
	 * Gets the CSS selectors for the main content wrapper.
	 * @return string A comma-separated string of CSS selectors.
	 */
	public function get_content_selectors() {
		// A more conservative and modern-theme-focused list of selectors.
		// `body > .wp-site-blocks` is often the main wrapper in block themes like Twenty Twenty-Four.
		$default_selectors = array(
			'body > .wp-site-blocks',
			'#page',
			'#main',
			'main',
			'.site-main',
		);
		$selectors = apply_filters( 'organic_glassmorphism_content_selectors', $default_selectors );
		// We return an array now, to be handled by JavaScript.
		return array_unique( array_filter( $selectors ) );
	}

	/**
	 * Enqueues scripts and styles, and generates a dynamic inline stylesheet
	 * to override CSS variables with user-defined settings.
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'organic-glassmorphism-core', ORGANIC_GLASSMORPHISM_URL . 'assets/css/style.css', array(), ORGANIC_GLASSMORPHISM_VERSION );

		// Define the complete set of default values for a more subtle, modern aesthetic.
		$defaults = array(
			'og_bg_color'           => '#f9f9f9',
			'og_base_text_color'    => '#333333',
			'og_glass_bg_color'     => 'rgba(255, 255, 255, 0.5)',
			'og_glass_border_color' => 'rgba(0, 0, 0, 0.05)',
			'og_shadow_color'       => 'rgba(0, 0, 0, 0.05)',
			'og_backdrop_blur'      => '10',
			'og_border_radius'      => '12',
		);
		$settings = wp_parse_args( get_option( 'og_settings', $defaults ), $defaults );

		// Create a more opaque fallback color from the user's chosen glass background color.
		$rgba = array();
		// Default to a safe value if preg_match fails
		$fallback_rgba = 'rgba(255, 253, 240, 0.85)';
		if ( preg_match( '/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d(?:\.\d+)?))?\)$/', $settings['og_glass_bg_color'], $rgba ) ) {
			// Construct fallback with a higher alpha, capped at 1.
			$alpha = isset($rgba[4]) ? floatval($rgba[4]) : 1;
			$fallback_alpha = min(1, $alpha + 0.5);
			$fallback_rgba = 'rgba(' . $rgba[1] . ',' . $rgba[2] . ',' . $rgba[3] . ', ' . $fallback_alpha . ')';
		}

		// Generate the dynamic CSS variables.
		$dynamic_css = "
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

		wp_add_inline_style( 'organic-glassmorphism-core', str_replace( array( "\r", "\n", "\t" ), '', $dynamic_css ) );

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

		wp_enqueue_script( 'organic-glassmorphism-main-script', ORGANIC_GLASSMORPHISM_URL . 'assets/js/script.js', array(), ORGANIC_GLASSMORPHISM_VERSION, true );
		wp_add_inline_script( 'organic-glassmorphism-main-script', str_replace( array( "\r", "\n", "\t" ), '', $js_code ) );
	}

	/**
	 * Injects the HTML for the theme toggle switch into the footer.
	 */
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

	/**
	 * Injects SVG filters into the page footer.
	 */
	public function inject_svg_filters() {
		?>
		<svg xmlns="http://www.w3.org/2000/svg" version="1.1" style="position: absolute; width: 0; height: 0; overflow: hidden;">
			<defs><filter id="organic-liquid-distortion"><feTurbulence type="fractalNoise" baseFrequency="0.005 0.015" numOctaves="1" result="turbulence"/><feDisplacementMap in2="turbulence" in="SourceGraphic" scale="4" xChannelSelector="R" yChannelSelector="G"/></filter></defs>
		</svg>
		<?php
	}

	// --- Admin Methods ---

	/**
	 * Enqueues scripts and styles for the admin settings page.
	 */
	public function admin_enqueue_assets( $hook ) {
		// Only load on our plugin's settings page
		if ( 'settings_page_organic_glassmorphism' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'og-admin-script', ORGANIC_GLASSMORPHISM_URL . 'assets/js/admin-script.js', array( 'wp-color-picker' ), ORGANIC_GLASSMORPHISM_VERSION, true );
	}

	/**
	 * Adds the admin menu item.
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Organic Glassmorphism Settings', 'organic-glassmorphism' ),
			__( 'Organic Glassmorphism', 'organic-glassmorphism' ),
			'manage_options',
			'organic_glassmorphism',
			array( $this, 'settings_page_html' )
		);
	}

	/**
	 * Renders the HTML for the settings page.
	 */
	public function settings_page_html() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'organic-glassmorphism' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p><?php esc_html_e( 'Customize the appearance of the Organic Glassmorphism effects across your site.', 'organic-glassmorphism' ); ?></p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'organic_glassmorphism_options' );
				do_settings_sections( 'organic_glassmorphism' );
				submit_button( __( 'Save Changes', 'organic-glassmorphism' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Registers all settings, sections, and fields for the admin panel.
	 */
	public function register_settings() {
		register_setting( 'organic_glassmorphism_options', 'og_settings', array( $this, 'sanitize_settings' ) );

		// Section 1: Colors
		add_settings_section( 'og_colors_section', __( 'Color Palette', 'organic-glassmorphism' ), null, 'organic_glassmorphism' );
		add_settings_field( 'og_bg_color', __( 'Page Background', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_bg_color', 'default' => '#fffdd0'] );
		add_settings_field( 'og_base_text_color', __( 'Base Text Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_base_text_color', 'default' => '#000000'] );
		add_settings_field( 'og_glass_border_color', __( 'Glass Border Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_glass_border_color', 'default' => 'rgba(255, 222, 173, 0.4)'] );
		add_settings_field( 'og_shadow_color', __( 'Glass Shadow Color', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_shadow_color', 'default' => 'rgba(139, 69, 19, 0.15)'] );

		add_settings_field( 'og_glass_bg_color', __( 'Glass Background', 'organic-glassmorphism' ), array( $this, 'color_field_cb' ), 'organic_glassmorphism', 'og_colors_section', ['id' => 'og_glass_bg_color', 'default' => 'rgba(255, 253, 240, 0.35)'] );

		// Section 2: Effects
		add_settings_section( 'og_effects_section', __( 'Glass Effects', 'organic-glassmorphism' ), null, 'organic_glassmorphism' );
		add_settings_field( 'og_backdrop_blur', __( 'Blur Radius (px)', 'organic-glassmorphism' ), array( $this, 'number_field_cb' ), 'organic_glassmorphism', 'og_effects_section', ['id' => 'og_backdrop_blur', 'default' => '12', 'min' => '0', 'max' => '50', 'step' => '1', 'desc' => 'The intensity of the backdrop blur effect.'] );
		add_settings_field( 'og_border_radius', __( 'Border Radius (px)', 'organic-glassmorphism' ), array( $this, 'number_field_cb' ), 'organic_glassmorphism', 'og_effects_section', ['id' => 'og_border_radius', 'default' => '16', 'min' => '0', 'max' => '100', 'step' => '1', 'desc' => 'The roundness of the corners.'] );
	}

	/**
	 * Sanitizes all settings before saving to the database.
	 */
	public function sanitize_settings( $input ) {
		$settings = get_option( 'og_settings', array() );
		$output = array_merge( $settings, $input );

		// Sanitize Colors
		if ( isset( $input['og_bg_color'] ) ) $output['og_bg_color'] = sanitize_text_field( $input['og_bg_color'] );
		if ( isset( $input['og_base_text_color'] ) ) $output['og_base_text_color'] = sanitize_text_field( $input['og_base_text_color'] );
		if ( isset( $input['og_glass_border_color'] ) ) $output['og_glass_border_color'] = sanitize_text_field( $input['og_glass_border_color'] );
		if ( isset( $input['og_shadow_color'] ) ) $output['og_shadow_color'] = sanitize_text_field( $input['og_shadow_color'] );
		if ( isset( $input['og_glass_bg_color'] ) ) $output['og_glass_bg_color'] = sanitize_text_field( $input['og_glass_bg_color'] );

		// Sanitize Effects
		if ( isset( $input['og_backdrop_blur'] ) ) $output['og_backdrop_blur'] = absint( $input['og_backdrop_blur'] );
		if ( isset( $input['og_border_radius'] ) ) $output['og_border_radius'] = absint( $input['og_border_radius'] );

		return $output;
	}

	// --- Field Callbacks ---
	public function color_field_cb( $args ) {
		$settings = get_option( 'og_settings' );
		$value = isset( $settings[$args['id']] ) ? $settings[$args['id']] : $args['default'];
		echo '<input type="text" name="og_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" class="og-color-picker" data-alpha-enabled="true" data-default-color="' . esc_attr( $args['default'] ) . '">';
	}

	public function number_field_cb( $args ) {
		$settings = get_option( 'og_settings' );
		$value = isset( $settings[$args['id']] ) ? $settings[$args['id']] : $args['default'];
		echo '<input type="number" name="og_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" class="small-text" min="' . esc_attr( $args['min'] ) . '" max="' . esc_attr( $args['max'] ) . '" step="' . esc_attr( $args['step'] ) . '">';
		if ( ! empty( $args['desc'] ) ) {
			echo '<p class="description">' . esc_html( $args['desc'] ) . '</p>';
		}
	}
}
