<?php
/**
 * Init Configuration
 *
 * @author Jegstudio
 * @package oigny-lite
 */

namespace Oigny_Lite;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Class
 *
 * @package oigny-lite
 */
class Init {

	/**
	 * Instance variable
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return Init
	 */
	public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Class constructor.
	 */
	private function __construct() {
		$this->init_instance();
		$this->load_hooks();
	}

	/**
	 * Load initial hooks.
	 */
	private function load_hooks() {
		add_action( 'init', array( $this, 'register_block_patterns' ), 9 );
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'dashboard_scripts' ) );

		add_action( 'wp_ajax_oigny-lite_set_admin_notice_viewed', array( $this, 'notice_closed' ) );
		add_action( 'admin_notices', array( $this, 'notice_install_plugin' ) );

		add_action( 'after_switch_theme', array( $this, 'update_global_styles_after_theme_switch' ) );
		add_filter( 'gutenverse_template_path', array( $this, 'template_path' ), null, 3 );
		add_filter( 'gutenverse_themes_template', array( $this, 'add_template' ), 10, 2 );
		add_filter( 'gutenverse_block_config', array( $this, 'default_font' ), 10 );
		add_filter( 'gutenverse_font_header', array( $this, 'default_header_font' ) );
		add_filter( 'gutenverse_global_css', array( $this, 'global_header_style' ) );

		add_filter( 'gutenverse_stylesheet_directory', array( $this, 'change_stylesheet_directory' ) );
		add_filter( 'gutenverse_themes_override_mechanism', '__return_true' );
	}

	/**
	 * Update Global Styles After Theme Switch
	 */
	public function update_global_styles_after_theme_switch() {
		// Get the path to the current theme's theme.json file
		$theme_json_path = get_template_directory() . '/theme.json';
		$theme_slug      = get_option( 'stylesheet' ); // Get the current theme's slug
		$args            = array(
			'post_type'      => 'wp_global_styles',
			'post_status'    => 'publish',
			'name'           => 'wp-global-styles-' . $theme_slug,
			'posts_per_page' => 1,
		);

		$global_styles_query = new WP_Query( $args );
		// Check if the theme.json file exists
		if ( file_exists( $theme_json_path ) && $global_styles_query->have_posts() ) {
			$global_styles_query->the_post();
			$global_styles_post_id = get_the_ID();
			// Step 2: Get the existing global styles (color palette)
			$global_styles_content = json_decode( get_post_field( 'post_content', $global_styles_post_id ), true );
			if ( isset( $global_styles_content['settings']['color']['palette']['theme'] ) ) {
				$existing_colors = $global_styles_content['settings']['color']['palette']['theme'];
			} else {
				$existing_colors = array();
			}

			// Step 3: Extract slugs from the existing colors
			$existing_slugs = array_column( $existing_colors, 'slug' );
			// Step 4:Read the contents of the theme.json file

			$theme_json_content = file_get_contents( $theme_json_path );
			$theme_json_data    = json_decode( $theme_json_content, true );

			// Access the color palette from the theme.json file
			if ( isset( $theme_json_data['settings']['color']['palette'] ) ) {

				$theme_colors = $theme_json_data['settings']['color']['palette'];

				// Step 5: Loop through theme.json colors and add them if they don't exist
				foreach ( $theme_colors as $theme_color ) {
					if ( ! in_array( $theme_color['slug'], $existing_slugs ) ) {
						$existing_colors[] = $theme_color; // Add new color to the existing palette
					}
				}
				foreach ( $theme_colors as $theme_color ) {
					$theme_slug = $theme_color['slug'];

					// Step 6: Use in_array to check if the slug already exists in the global palette
					if ( ! in_array( $theme_slug, $existing_slugs ) ) {
						// If the slug does not exist, add the theme color to the global palette
						$global_colors[] = $theme_color;
					}
				}
				// Step 6: Update the global styles content with the new colors
				$global_styles_content['settings']['color']['palette']['theme'] = $existing_colors;

				// Step 7: Save the updated global styles back to the post
				wp_update_post(
					array(
						'ID'           => $global_styles_post_id,
						'post_content' => wp_json_encode( $global_styles_content ),
					)
				);

			}
			wp_reset_postdata(); // Reset the query
		}
	}

	/**
	 * Change Stylesheet Directory.
	 *
	 * @return string
	 */
	public function change_stylesheet_directory() {
		return OIGNY_LITE_DIR . 'gutenverse-files';
	}

	/**
	 * Initialize Instance.
	 */
	public function init_instance() {
		new Asset_Enqueue();
		
	}

	/**
	 * Notice Closed
	 */
	public function notice_closed() {
		if ( isset( $_POST['nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'oigny-lite_admin_notice' ) ) {
			update_user_meta( get_current_user_id(), 'gutenverse_install_notice', 'true' );
		}
		die;
	}

	/**
	 * Show notification to install Gutenverse Plugin.
	 */
	public function notice_install_plugin() {
		// Skip if gutenverse block activated.
		if ( defined( 'GUTENVERSE' ) ) {
			return;
		}

		// Skip if gutenverse pro activated.
		if ( defined( 'GUTENVERSE_PRO' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( isset( $screen->parent_file ) && 'themes.php' === $screen->parent_file && 'appearance_page_oigny-lite-dashboard' === $screen->id ) {
			return;
		}

		if ( isset( $screen->parent_file ) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id ) {
			return;
		}

		if ( 'true' === get_user_meta( get_current_user_id(), 'gutenverse_install_notice', true ) ) {
			return;
		}

		$all_plugin = get_plugins();
		$plugins    = $this->theme_config()['plugins'];
		$actions    = array();

		foreach ( $plugins as $plugin ) {
			$slug   = $plugin['slug'];
			$path   = "$slug/$slug.php";
			$active = is_plugin_active( $path );

			if ( isset( $all_plugin[ $path ] ) ) {
				if ( $active ) {
					$actions[ $slug ] = 'active';
				} else {
					$actions[ $slug ] = 'inactive';
				}
			} else {
				$actions[ $slug ] = '';
			}
		}

		?>
		<style>
			.install-gutenverse-plugin-notice {
				border: 1px solid #E6E6EF;
				position: relative;
				overflow: hidden;
				padding: 0 !important;
				margin-bottom: 30px !important;
				background: url( <?php echo esc_url( OIGNY_LITE_URI . '/assets/img/background-banner.png' ); ?> );
				background-size: cover;
				background-position: center;
			}

			.install-gutenverse-plugin-notice .gutenverse-notice-content {
				display: flex;
				align-items: center;
				position: relative;
			}

			.gutenverse-notice-text, .gutenverse-notice-image {
				width: 50%;
			}

			.gutenverse-notice-text {
				padding: 40px 0 40px 40px;
				position: relative;
				z-index: 2;
			}

			.install-gutenverse-plugin-notice img {
				max-height: 100%;
				display: flex;
				position: absolute;
				top: 0;
				right: 0;
				bottom: 0;
			}

			.install-gutenverse-plugin-notice:after {
				content: "";
				position: absolute;
				left: 0;
				top: 0;
				height: 100%;
				width: 5px;
				display: block;
				background: linear-gradient(to bottom, #68E4F4, #4569FF, #F045FF);
			}

			.install-gutenverse-plugin-notice .notice-dismiss {
				top: 20px;
				right: 20px;
				padding: 0;
				background: white;
				border-radius: 6px;
			}

			.install-gutenverse-plugin-notice .notice-dismiss:before {
				content: "\f335";
				font-size: 17px;
				width: 25px;
				height: 25px;
				line-height: 25px;
				border: 1px solid #E6E6EF;
				border-radius: 3px;
			}

			.install-gutenverse-plugin-notice h3 {
				margin-top: 5px;
				margin-bottom: 15px;
				font-weight: 600;
				font-size: 25px;
				line-height: 1.4em;
			}

			.install-gutenverse-plugin-notice h3 span {
				font-weight: 700;
				background-clip: text !important;
				-webkit-text-fill-color: transparent;
				background: linear-gradient(80deg, rgba(208, 77, 255, 1) 0%,rgba(69, 105, 255, 1) 48.8%,rgba(104, 228, 244, 1) 100%);
			}

			.install-gutenverse-plugin-notice p {
				font-size: 13px;
				font-weight: 400;
				margin: 5px 100px 20px 0 !important;
			}

			.install-gutenverse-plugin-notice .gutenverse-bottom {
				display: flex;
				align-items: center;
				margin-top: 30px;
			}

			.install-gutenverse-plugin-notice a {
				text-decoration: none;
				margin-right: 20px;
			}

			.install-gutenverse-plugin-notice a.gutenverse-button {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", serif;
				text-decoration: none;
				cursor: pointer;
				font-size: 12px;
				line-height: 18px;
				border-radius: 5px;
				background: #3B57F7;
				color: #fff;
				padding: 10px 15px;
				font-weight: 500;
				background: linear-gradient(to left, #68E4F4, #4569FF, #F045FF);
				transition: transform 0.5s ease, color 0.5s ease;
			}

			.install-gutenverse-plugin-notice a.gutenverse-button:hover {
				color: hsla(0, 0%, 100%, .749);
				transform: scale(.94);
			}

			#gutenverse-install-plugin.loader:after {
				display: block;
				content: '';
				border: 5px solid white;
				border-radius: 50%;
				border-top: 5px solid rgba(255, 255, 255, 0);
				width: 10px;
				height: 10px;
				-webkit-animation: spin 2s linear infinite;
				animation: spin 2s linear infinite;
			}

			@-webkit-keyframes spin {
				0% {
					-webkit-transform: rotate(0deg);
				}
				100% {
					-webkit-transform: rotate(360deg);
				}
			}

			@keyframes spin {
				0% {
					transform: rotate(0deg);
				}
				100% {
					transform: rotate(360deg);
				}
			}

			@media screen and (max-width: 1024px) {
				.gutenverse-notice-text {
					width: 100%;
				}

				.gutenverse-notice-image {
					display: none;
				}
			}
		</style>
		<script>
		var promises = [];
		var actions = <?php echo wp_json_encode( $actions ); ?>;

		function sequenceInstall (plugins, index = 0) {
			if (plugins[index]) {
				var plugin = plugins[index];

				switch (actions[plugin?.slug]) {
					case 'active':
						break;
					case 'inactive':
						var path = plugin?.slug + '/' + plugin?.slug;
						promises.push(
							wp.apiFetch({
								path: 'wp/v2/plugins/plugin?plugin=' + path,									
								method: 'POST',
								data: {
									status: 'active'
								}
							}).then(() => {
								sequenceInstall(plugins, index + 1);
							}).catch((error) => {
							})
						);
						break;
					default:
						promises.push(
							wp.apiFetch({
								path: 'wp/v2/plugins',
								method: 'POST',
								data: {
									slug: plugin?.slug,
									status: 'active'
								}
							}).then(() => {
								sequenceInstall(plugins, index + 1);
							}).catch((error) => {
							})
						);
						break;
				}
			}

			return;
		};

		jQuery( function( $ ) {
			$( 'div.notice.install-gutenverse-plugin-notice' ).on( 'click', 'button.notice-dismiss', function( event ) {
				event.preventDefault();
				$.post( ajaxurl, {
					action: '{{slug}}_set_admin_notice_viewed',
					nonce: '<?php echo esc_html( wp_create_nonce( '{{slug}}_admin_notice' ) ); ?>',
				} );
			} );

			$('#gutenverse-install-plugin').on('click', function(e) {
				var hasFinishClass = $(this).hasClass('finished');
				var hasLoaderClass = $(this).hasClass('loader');

				if(!hasFinishClass) {
					e.preventDefault();
				}

				if(!hasLoaderClass && !hasFinishClass) {
					promises = [];
					var plugins = <?php echo wp_json_encode( $plugins ); ?>;
					$(this).addClass('loader').text('');

					sequenceInstall(plugins);
					Promise.all(promises).then(() => {						
						window.location.reload();
						$(this).removeClass('loader').addClass('finished').text('Visit Theme Dashboard');
					});
				}
			});
		} );
		</script>
		<div class="notice is-dismissible install-gutenverse-plugin-notice">
			<div class="gutenverse-notice-inner">
				<div class="gutenverse-notice-content">
					<div class="gutenverse-notice-text">
						<h3><?php esc_html_e( 'Take Your Website To New Height with', 'oigny-lite' ); ?> <span>Gutenverse!</span></h3> 
						<p><?php esc_html_e( 'Oigny Lite theme work best with Gutenverse plugin. By installing Gutenverse plugin you may access Oigny Lite templates built with Gutenverse and get access to more than 40 free blocks, hundred free Layout and Section.', 'oigny-lite' ); ?></p>
						<div class="gutenverse-bottom">
							<a class="gutenverse-button" id="gutenverse-install-plugin" href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'themes.php?page=oigny-lite-dashboard' ), 'install-plugin_gutenverse' ) ); ?>">
								<?php echo esc_html( __( 'Install Required Plugins', 'oigny-lite' ) ); ?>
							</a>
						</div>
					</div>
					<div class="gutenverse-notice-image">
						<img src="<?php echo esc_url( OIGNY_LITE_URI . '/assets/img/banner-install-gutenverse-2.png' ); ?>"/>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Generate Global Font
	 *
	 * @param string $value  Value of the option.
	 *
	 * @return string
	 */
	public function global_header_style( $value ) {
		$theme_name      = get_stylesheet();
		$global_variable = get_option( 'gutenverse-global-variable-font-' . $theme_name );

		if ( empty( $global_variable ) && function_exists( 'gutenverse_global_font_style_generator' ) ) {
			$font_variable = $this->default_font_variable();
			$value        .= \gutenverse_global_font_style_generator( $font_variable );
		}

		return $value;
	}

	/**
	 * Header Font.
	 *
	 * @param mixed $value  Value of the option.
	 *
	 * @return mixed Value of the option.
	 */
	public function default_header_font( $value ) {
		if ( ! $value ) {
			$value = array(
				array(
					'value'  => 'Alfa Slab One',
					'type'   => 'google',
					'weight' => 'bold',
				),
			);
		}

		return $value;
	}

	/**
	 * Alter Default Font.
	 *
	 * @param array $config Array of Config.
	 *
	 * @return array
	 */
	public function default_font( $config ) {
		if ( empty( $config['globalVariable']['fonts'] ) ) {
			$config['globalVariable']['fonts'] = $this->default_font_variable();

			return $config;
		}

		if ( ! empty( $config['globalVariable']['fonts'] ) ) {
			// Handle existing fonts.
			$theme_name   = get_stylesheet();
			$initial_font = get_option( 'gutenverse-font-init-' . $theme_name );

			if ( ! $initial_font ) {
				$config['globalVariable']['fonts'] = array_merge( $config['globalVariable']['fonts'], $this->default_font_variable() );
				update_option( 'gutenverse-font-init-' . $theme_name, true );
			}
		}

		return $config;
	}

	/**
	 * Default Font Variable.
	 *
	 * @return array
	 */
	public function default_font_variable() {
		return array(
            array (
  'id' => 'TzEKKr',
  'name' => 'H1',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '116',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '58',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
    ),
    'weight' => '500',
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
  ),
),array (
  'id' => '5iRm0A',
  'name' => 'H1 Italic',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Times New Roman',
      'value' => 'Times New Roman',
      'type' => 'system',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '142',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '76',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '48',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'weight' => '300',
    'style' => 'italic',
  ),
),array (
  'id' => 'UpJPjv',
  'name' => 'H1 alt',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '82',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '58',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => 'NeJzQY',
  'name' => 'H1 Italic alt',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Times New Roman',
      'value' => 'Times New Roman',
      'type' => 'system',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '112',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '76',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '48',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'style' => 'italic',
    'weight' => '300',
  ),
),array (
  'id' => 'ENk7Om',
  'name' => 'H2',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '52',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '24',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.4',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => 'FvbTdd',
  'name' => 'H2 Italic',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Times New Roman',
      'value' => 'Times New Roman',
      'type' => 'system',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '72',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '52',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
    ),
    'style' => 'italic',
    'weight' => '500',
  ),
),array (
  'id' => 'iDPt6e',
  'name' => 'H3',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '42',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '24',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.5',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => '3qYIFw',
  'name' => 'H3 Italic',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Times New Roman',
      'value' => 'Times New Roman',
      'type' => 'system',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '62',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '52',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '36',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
    ),
    'weight' => '500',
    'style' => 'italic',
  ),
),array (
  'id' => 'ce9oFN',
  'name' => 'H4',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '24',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
      'Mobile' => 
      array (
        'unit' => 'em',
        'point' => '1.4',
      ),
    ),
    'weight' => '600',
  ),
),array (
  'id' => 'IJGuA3',
  'name' => 'H5',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '22',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '16',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.5',
      ),
      'Tablet' => 
      array (
        'unit' => 'em',
        'point' => '',
      ),
      'Mobile' => 
      array (
        'unit' => 'em',
        'point' => '1.7',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => 'K8rf8i',
  'name' => 'H6',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => 'C3z2EQ',
  'name' => 'Button',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'spacing' => 
    array (
      'Desktop' => '0.05',
    ),
    'weight' => '600',
  ),
),array (
  'id' => 'XWrhCQ',
  'name' => 'Button Hero',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'spacing' => 
    array (
      'Desktop' => '0.05',
    ),
    'weight' => '600',
  ),
),array (
  'id' => 'iCRGVU',
  'name' => 'Nav Menu',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '12',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'weight' => '600',
    'spacing' => 
    array (
      'Desktop' => '0.05',
    ),
  ),
),array (
  'id' => 'xSXxly',
  'name' => 'Text',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Montserrat',
      'value' => 'Montserrat',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.6',
      ),
    ),
    'weight' => '300',
  ),
),array (
  'id' => '6CnHAN',
  'name' => 'Text 16px',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Montserrat',
      'value' => 'Montserrat',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '16',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.6',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => '0siBQN',
  'name' => 'Text 14px',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Montserrat',
      'value' => 'Montserrat',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.6',
      ),
    ),
    'weight' => '300',
  ),
),array (
  'id' => 'T2Afs1',
  'name' => 'Funfact Number',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '32',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '32',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '32',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.2',
      ),
    ),
    'weight' => '600',
  ),
),array (
  'id' => 'NyNJWy',
  'name' => 'Testimonial',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '32',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '30',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '24',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.5',
      ),
    ),
    'weight' => '500',
  ),
),array (
  'id' => 'v72M0U',
  'name' => 'Label',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '14',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1',
      ),
    ),
    'weight' => '500',
    'spacing' => 
    array (
      'Desktop' => '0.08',
    ),
  ),
),array (
  'id' => 'cjS9DY',
  'name' => 'Block Post Title',
  'font' => 
  array (
    'font' => 
    array (
      'label' => 'Michroma',
      'value' => 'Michroma',
      'type' => 'google',
    ),
    'size' => 
    array (
      'Desktop' => 
      array (
        'point' => '28',
        'unit' => 'px',
      ),
      'Tablet' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
      'Mobile' => 
      array (
        'point' => '18',
        'unit' => 'px',
      ),
    ),
    'lineHeight' => 
    array (
      'Desktop' => 
      array (
        'unit' => 'em',
        'point' => '1.5',
      ),
    ),
    'weight' => '500',
  ),
),
		);
	}



	/**
	 * Add Template to Editor.
	 *
	 * @param array $template_files Path to Template File.
	 * @param array $template_type Template Type.
	 *
	 * @return array
	 */
	public function add_template( $template_files, $template_type ) {
		if ( 'wp_template' === $template_type ) {
			$new_templates = array(
				'home',
				'projects',
				'contact',
				'blog',
				'404',
				'index',
				'page',
				'single',
				'archive',
				'search',
				'about'
			);

			foreach ( $new_templates as $template ) {
				$template_files[] = array(
					'slug'  => $template,
					'path'  => $this->change_stylesheet_directory() . "/templates/{$template}.html",
					'theme' => get_template(),
					'type'  => 'wp_template',
				);
			}
		}

		return $template_files;
	}

	/**
	 * Use gutenverse template file instead.
	 *
	 * @param string $template_file Path to Template File.
	 * @param string $theme_slug Theme Slug.
	 * @param string $template_slug Template Slug.
	 *
	 * @return string
	 */
	public function template_path( $template_file, $theme_slug, $template_slug ) {
		switch ( $template_slug ) {
            case 'home':
					return $this->change_stylesheet_directory() . 'templates/home.html';
			case 'header':
					return $this->change_stylesheet_directory() . 'parts/header.html';
			case 'footer':
					return $this->change_stylesheet_directory() . 'parts/footer.html';
			case 'projects':
					return $this->change_stylesheet_directory() . 'templates/projects.html';
			case 'contact':
					return $this->change_stylesheet_directory() . 'templates/contact.html';
			case 'blog':
					return $this->change_stylesheet_directory() . 'templates/blog.html';
			case '404':
					return $this->change_stylesheet_directory() . 'templates/404.html';
			case 'index':
					return $this->change_stylesheet_directory() . 'templates/index.html';
			case 'page':
					return $this->change_stylesheet_directory() . 'templates/page.html';
			case 'single':
					return $this->change_stylesheet_directory() . 'templates/single.html';
			case 'archive':
					return $this->change_stylesheet_directory() . 'templates/archive.html';
			case 'search':
					return $this->change_stylesheet_directory() . 'templates/search.html';
			case 'about':
					return $this->change_stylesheet_directory() . 'templates/about.html';
		}

		return $template_file;
	}

	/**
	 * Register Block Pattern.
	 */
	public function register_block_patterns() {
		new Block_Patterns();
	}

	/**
	 * Enqueue scripts and styles.
	 */
	public function dashboard_scripts() {
		$screen = get_current_screen();
		wp_enqueue_script('wp-api-fetch');

		if ( is_admin() ) {
			// enqueue css.
			wp_enqueue_style(
				'oigny-lite-dashboard',
				OIGNY_LITE_URI . '/assets/css/theme-dashboard.css',
				array(),
				OIGNY_LITE_VERSION
			);

			// enqueue js.
			wp_enqueue_script(
				'oigny-lite-dashboard',
				OIGNY_LITE_URI . '/assets/js/theme-dashboard.js',
				array( 'wp-api-fetch' ),
				OIGNY_LITE_VERSION,
				true
			);

			wp_localize_script( 'oigny-lite-dashboard', 'GutenThemeConfig', $this->theme_config() );
		}
	}

	/**
	 * Check if plugin is installed.
	 *
	 * @param string $plugin_slug plugin slug.
	 * 
	 * @return boolean
	 */
	public function is_installed( $plugin_slug ) {
		$all_plugins = get_plugins();
		foreach ( $all_plugins as $plugin_file => $plugin_data ) {
			$plugin_dir = dirname($plugin_file);

			if ($plugin_dir === $plugin_slug) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Register static data to be used in theme's js file
	 */
	public function theme_config() {
		$active_plugins = get_option( 'active_plugins' );
		$plugins = array();
		foreach( $active_plugins as $active ) {
			$plugins[] = explode( '/', $active)[0];
		}

		$config = array(
			'home_url'     => home_url(),
			'version'      => OIGNY_LITE_VERSION,
			'images'       => OIGNY_LITE_URI . '/assets/img/',
			'title'        => esc_html__( 'Oigny Lite', 'oigny-lite' ),
			'description'  => esc_html__( 'Oigny Lite is Dark and modern style Agency theme template for WordPress that supports fullsite editing and is fully compatible with the Gutenverse plugin. Oigny Lite is perfect for Digital Agency, Creative Agency, Online Portfolio, Designer and Freelancer websites. You can use the included core and Gutenverse versions to make it easier to create the website you desire. We want to ensure that you have the best experience using WordPress to edit your site.', 'oigny-lite' ),
			'pluginTitle'  => esc_html__( 'Plugin Requirement', 'oigny-lite' ),
			'pluginDesc'   => esc_html__( 'This theme require some plugins. Please make sure all the plugin below are installed and activated.', 'oigny-lite' ),
			'note'         => esc_html__( '', 'oigny-lite' ),
			'note2'        => esc_html__( '', 'oigny-lite' ),
			'demo'         => esc_html__( '', 'oigny-lite' ),
			'demoUrl'      => esc_url( 'https://gutenverse.com/demo?name=oigny-lite' ),
			'install'      => '',
			'installText'  => esc_html__( 'Install Gutenverse Plugin', 'oigny-lite' ),
			'activateText' => esc_html__( 'Activate Gutenverse Plugin', 'oigny-lite' ),
			'doneText'     => esc_html__( 'Gutenverse Plugin Installed', 'oigny-lite' ),
			'dashboardPage'=> admin_url( 'themes.php?page=oigny-lite-dashboard' ),
			'logo'         => false,
			'slug'         => 'oigny-lite',
			'upgradePro'   => 'https://gutenverse.com/pro',
			'supportLink'  => 'https://support.jegtheme.com/forums/forum/fse-themes/',
			'libraryApi'   => 'https://gutenverse.com//wp-json/gutenverse-server/v1',
			'pages'        => array(
				'page-0' => OIGNY_LITE_URI . 'assets/img/ss-oigny-lite-home.webp',
				'page-1' => OIGNY_LITE_URI . 'assets/img/ss-oigny-lite-about.webp',
				'page-2' => OIGNY_LITE_URI . 'assets/img/ss-oigny-lite-project.webp',
				'page-3' => OIGNY_LITE_URI . 'assets/img/ss-oigny-lite-blog.webp',
				'page-4' => OIGNY_LITE_URI . 'assets/img/ss-oigny-lite-contact.webp'
			),
			'plugins'      => array(
				array(
					'slug'       => 'gutenverse',
					'title'      => 'Gutenverse',
					'short_desc' => 'GUTENVERSE – GUTENBERG BLOCKS AND WEBSITE BUILDER FOR SITE EDITOR, TEMPLATE LIBRARY, POPUP BUILDER, ADVANCED ANIMATION EFFECTS, 45+ FREE USER-FRIENDLY BLOCKS',
					'active'     => in_array( 'gutenverse', $plugins, true ),
					'installed'  => $this->is_installed( 'gutenverse' ),
					'icons'      => array (
  '1x' => 'https://ps.w.org/gutenverse/assets/icon-128x128.gif?rev=3132408',
  '2x' => 'https://ps.w.org/gutenverse/assets/icon-256x256.gif?rev=3132408',
),
				),
				array(
					'slug'       => 'gutenverse-form',
					'title'      => 'Gutenverse Form',
					'short_desc' => 'GUTENVERSE FORM – FORM BUILDER FOR GUTENBERG BLOCK EDITOR, MULTI-STEP FORMS, CONDITIONAL LOGIC, PAYMENT, CALCULATION, 15+ FREE USER-FRIENDLY FORM BLOCKS',
					'active'     => in_array( 'gutenverse-form', $plugins, true ),
					'installed'  => $this->is_installed( 'gutenverse-form' ),
					'icons'      => array (
  '1x' => 'https://ps.w.org/gutenverse-form/assets/icon-128x128.png?rev=3135966',
),
				)
			),
			'assign'       => array(
				
			),
			'dashboardData'=> array(
				'comparison' => array (
  'core_template_count' => 9,
  'lite_theme_template_count' => 13,
  'lite_block_count' => 52,
  'lite_template_count' => 200,
  'pro_theme_template_count' => 17,
  'pro_block_count' => 70,
  'pro_template_count' => 700,
  'description' => 'Here\'s a comparison of Oigny Core, Oigny Lite, and Oigny Pro to help you determine which version best suits your needs.',
  'name_core' => 'Oigny Core',
  'name_lite' => 'Oigny Lite',
  'name_pro' => 'Oigny',
)
			),
		);

		if ( isset( $config['assign'] ) && $config['assign'] ) {
			$assign = $config['assign'];
			foreach ( $assign as $key => $value ) {
				$query = new \WP_Query(
					array(
						'post_type'      => 'page',
						'post_status'    => 'publish',
						'title'          => '' !== $value['page'] ? $value['page'] : $value['title'],
						'posts_per_page' => 1,
					)
				);

				if ( $query->have_posts() ) {
					$post                     = $query->posts[0];
					$page_template            = get_page_template_slug( $post->ID );
					$assign[ $key ]['status'] = array(
						'exists'         => true,
						'using_template' => $page_template === $value['slug'],
					);

				} else {
					$assign[ $key ]['status'] = array(
						'exists'         => false,
						'using_template' => false,
					);
				}

				wp_reset_postdata();
			}
			$config['assign'] = $assign;
		}

		return $config;
	}

	/**
	 * Add Menu
	 */
	public function admin_menu() {
		add_theme_page(
			'Oigny Lite Dashboard',
			'Oigny Lite Dashboard',
			'manage_options',
			'oigny-lite-dashboard',
			array( $this, 'load_dashboard' ),
			1
		);
	}

	/**
	 * Template page
	 */
	public function load_dashboard() {
		?>
			<div id="gutenverse-theme-dashboard">
			</div>
		<?php
	}
}
