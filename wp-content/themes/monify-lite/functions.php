<?php
/**
 * Theme Functions
 *
 * @author Jegstudio
 * @package monify-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

defined( 'MONIFY_LITE_VERSION' ) || define( 'MONIFY_LITE_VERSION', '1.0.1' );
defined( 'MONIFY_LITE_DIR' ) || define( 'MONIFY_LITE_DIR', trailingslashit( get_template_directory() ) );
defined( 'MONIFY_LITE_URI' ) || define( 'MONIFY_LITE_URI', trailingslashit( get_template_directory_uri() ) );

require get_parent_theme_file_path( 'inc/autoload.php' );

Monify_Lite\Init::instance();
