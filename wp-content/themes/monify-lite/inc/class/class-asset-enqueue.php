<?php
/**
 * Block Pattern Class
 *
 * @author Jegstudio
 * @package monify-lite
 */
namespace Monify_Lite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Init Class
 *
 * @package monify-lite
 */
class Asset_Enqueue {
	/**
	 * Class constructor.
	 */
	public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'enqueue_block_assets', array( $this, 'enqueue_scripts' ) );
	}

    /**
	 * Enqueue scripts and styles.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'monify-lite-style', get_stylesheet_uri(), array(), MONIFY_LITE_VERSION );

		wp_enqueue_style( 'presset', MONIFY_LITE_URI . '/assets/css/presset.css', array(), MONIFY_LITE_VERSION );
		wp_enqueue_style( 'custom-styling', MONIFY_LITE_URI . '/assets/css/custom-styling.css', array(), MONIFY_LITE_VERSION );
		wp_enqueue_script( 'animation-script', MONIFY_LITE_URI . '/assets/js/animation-script.js', array(), MONIFY_LITE_VERSION, true );


        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
    }
}
