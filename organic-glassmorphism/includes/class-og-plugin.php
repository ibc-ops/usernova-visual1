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
		$default_selectors = array(
			'#page', '.site-content', 'main', '#main', '.wp-site-blocks', '#primary',
			'.content-area', '#content', '.site-main', '.main-content',
		);
		$selectors = apply_filters( 'organic_glassmorphism_content_selectors', $default_selectors );
		return implode( ', ', array_unique( array_filter( $selectors ) ) );
	}

	/**
	 * Enqueues scripts and styles, and adds dynamic inline CSS.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'organic-glassmorphism-core',
			ORGANIC_GLASSMORPHISM_URL . 'assets/css/style.css',
			array(),
			ORGANIC_GLASSMORPHISM_VERSION,
			'all'
		);

		$selectors    = $this->get_content_selectors();
		$options      = get_option( 'organic_glassmorphism_options' );
		$transparency = isset( $options['og_transparency'] ) ? floatval( $options['og_transparency'] ) : 0.35;

		$glass_bg_color = "rgba(255, 253, 240, {$transparency})";
		$fallback_transparency = min( 1, $transparency + 0.5 );
		$fallback_bg_color     = "rgba(255, 253, 240, {$fallback_transparency})";

		$dynamic_css = "
			{$selectors} { position: relative; z-index: 0; background: transparent !important; padding: 2rem; margin: 2rem auto; max-width: 1200px; }
			{$selectors}::before { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: -1; background: {$fallback_bg_color}; border: 1px solid var(--og-glass-border-color); border-radius: var(--og-border-radius); box-shadow: 0 8px 32px 0 var(--og-shadow-color); transition: background-color 0.4s ease, box-shadow 0.4s ease; }
			@supports ((-webkit-backdrop-filter: none) or (backdrop-filter: none)) { {$selectors}::before { background: {$glass_bg_color}; -webkit-backdrop-filter: blur(var(--og-backdrop-blur)); backdrop-filter: blur(var(--og-backdrop-blur)); filter: url('#organic-liquid-distortion'); } }
			@media (max-width: 768px) { {$selectors} { padding: 1.5rem 1rem; margin: 1rem auto; } {$selectors}::before { border-radius: 0; } }
		";
		wp_add_inline_style( 'organic-glassmorphism-core', str_replace( array( "\r", "\n", "\t" ), '', $dynamic_css ) );

		wp_enqueue_script(
			'organic-glassmorphism-main-script',
			ORGANIC_GLASSMORPHISM_URL . 'assets/js/script.js',
			array(),
			ORGANIC_GLASSMORPHISM_VERSION,
			true
		);
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
			<p><?php esc_html_e( 'Fine-tune the appearance of the organic glassmorphism effects across your site.', 'organic-glassmorphism' ); ?></p>
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
	 * Registers settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'organic_glassmorphism_options',
			'organic_glassmorphism_options',
			array( $this, 'options_sanitize' )
		);

		add_settings_section(
			'og_main_settings_section',
			__( 'Core Appearance Settings', 'organic-glassmorphism' ),
			array( $this, 'main_settings_section_callback' ),
			'organic_glassmorphism'
		);

		add_settings_field(
			'og_transparency',
			__( 'Glass Transparency', 'organic-glassmorphism' ),
			array( $this, 'transparency_field_callback' ),
			'organic_glassmorphism',
			'og_main_settings_section'
		);
	}

	/**
	 * Sanitizes the options array.
	 * @param array $input The raw input.
	 * @return array The sanitized input.
	 */
	public function options_sanitize( $input ) {
		$sanitized_input = array();
		if ( isset( $input['og_transparency'] ) ) {
			$sanitized_input['og_transparency'] = max( 0, min( 1, floatval( $input['og_transparency'] ) ) );
		}
		return $sanitized_input;
	}

	/**
	 * Renders the description for the main settings section.
	 */
	public function main_settings_section_callback() {
		echo '<p>' . esc_html__( 'Adjust the core visual properties of the glassmorphism effect.', 'organic-glassmorphism' ) . '</p>';
	}

	/**
	 * Renders the input field for the Glass Transparency setting.
	 */
	public function transparency_field_callback() {
		$options      = get_option( 'organic_glassmorphism_options' );
		$transparency = isset( $options['og_transparency'] ) ? $options['og_transparency'] : 0.35;
		?>
		<input type="number" name="organic_glassmorphism_options[og_transparency]" value="<?php echo esc_attr( $transparency ); ?>" min="0" max="1" step="0.05" class="small-text">
		<p class="description">
			<?php esc_html_e( 'Controls the opacity of the glass effect. Enter a value between 0.0 (fully transparent) and 1.0 (fully opaque).', 'organic-glassmorphism' ); ?>
		</p>
		<?php
	}
}
