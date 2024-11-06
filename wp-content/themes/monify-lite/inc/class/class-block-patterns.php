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

use WP_Block_Pattern_Categories_Registry;

/**
 * Init Class
 *
 * @package monify-lite
 */
class Block_Patterns {

	/**
	 * Instance variable
	 *
	 * @var $instance
	 */
	private static $instance;

	/**
	 * Class instance.
	 *
	 * @return BlockPatterns
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
	public function __construct() {
		$this->register_block_patterns();
	}

	/**
	 * Register Block Patterns
	 */
	private function register_block_patterns() {
		$block_pattern_categories = array(
			'monify-lite-basic' => array( 'label' => __( 'Monify Lite Basic Patterns', 'monify-lite' ) ),
		);

		if ( defined( 'GUTENVERSE' ) ) {
			$block_pattern_categories['monify-lite-gutenverse'] = array( 'label' => __( 'Monify Lite Gutenverse Patterns', 'monify-lite' ) );
			$block_pattern_categories['monify-lite-pro'] = array( 'label' => __( 'Monify Lite Gutenverse PRO Patterns', 'monify-lite' ) );
		}

		$block_pattern_categories = apply_filters( 'monify-lite_block_pattern_categories', $block_pattern_categories );

		foreach ( $block_pattern_categories as $name => $properties ) {
			if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
				register_block_pattern_category( $name, $properties );
			}
		}

		$block_patterns = array(
            'monify-lite-home-core-hero',			'monify-lite-home-core-about',			'monify-lite-home-core-client-logo',			'monify-lite-home-core-services',			'monify-lite-home-core-funfact',			'monify-lite-home-core-testimonial',			'monify-lite-home-core-blog',			'monify-lite-page-core-home',			'monify-lite-single-core-hero',			'monify-lite-archive-core-hero',			'monify-lite-search-core-hero',			'monify-lite-404-core-hero',			'monify-lite-index-core-hero',
		);

		if ( defined( 'GUTENVERSE' ) ) {
            $block_patterns[] = 'monify-lite-home-gutenverse-hero';			$block_patterns[] = 'monify-lite-home-gutenverse-about';			$block_patterns[] = 'monify-lite-gutenverse-client-logo';			$block_patterns[] = 'monify-lite-home-gutenverse-services';			$block_patterns[] = 'monify-lite-home-gutenverse-why-choose-us';			$block_patterns[] = 'monify-lite-gutenverse-testimonials';			$block_patterns[] = 'monify-lite-home-gutenverse-blog';			$block_patterns[] = 'monify-lite-about-gutenverse-hero';			$block_patterns[] = 'monify-lite-about-gutenverse-story';			$block_patterns[] = 'monify-lite-gutenverse-client-logo';			$block_patterns[] = 'monify-lite-about-gutenverse-team';			$block_patterns[] = 'monify-lite-service-gutenverse-hero';			$block_patterns[] = 'monify-lite-service-gutenverse-services';			$block_patterns[] = 'monify-lite-gutenverse-testimonials';			$block_patterns[] = 'monify-lite-service-gutenverse-funfact';			$block_patterns[] = 'monify-lite-blog-gutenverse-hero';			$block_patterns[] = 'monify-lite-blog-gutenverse-content';			$block_patterns[] = 'monify-lite-gutenverse-header';			$block_patterns[] = 'monify-lite-gutenverse-footer';			$block_patterns[] = 'monify-lite-single-gutenverse-hero';			$block_patterns[] = 'monify-lite-index-gutenverse-hero';			$block_patterns[] = 'monify-lite-page-gutenverse-hero';			$block_patterns[] = 'monify-lite-archive-gutenverse-hero';			$block_patterns[] = 'monify-lite-search-gutenverse-hero';			$block_patterns[] = 'monify-lite-404-gutenverse-hero';
            
		}

		$block_patterns = apply_filters( 'monify-lite_block_patterns', $block_patterns );

		if ( function_exists( 'register_block_pattern' ) ) {
			foreach ( $block_patterns as $block_pattern ) {
				$pattern_file = get_theme_file_path( '/inc/patterns/' . $block_pattern . '.php' );

				register_block_pattern(
					'monify-lite/' . $block_pattern,
					require $pattern_file
				);
			}
		}
	}
}
