<?php
/**
 * Block Pattern Class
 *
 * @author Jegstudio
 * @package oigny-lite
 */

namespace Oigny_Lite;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Block_Pattern_Categories_Registry;

/**
 * Init Class
 *
 * @package oigny-lite
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
			'oigny-lite-basic' => array( 'label' => __( 'Oigny Lite Basic Patterns', 'oigny-lite' ) ),
		);

		if ( defined( 'GUTENVERSE' ) ) {
			$block_pattern_categories['oigny-lite-gutenverse'] = array( 'label' => __( 'Oigny Lite Gutenverse Patterns', 'oigny-lite' ) );
			$block_pattern_categories['oigny-lite-pro'] = array( 'label' => __( 'Oigny Lite Gutenverse PRO Patterns', 'oigny-lite' ) );
		}

		$block_pattern_categories = apply_filters( 'oigny-lite_block_pattern_categories', $block_pattern_categories );

		foreach ( $block_pattern_categories as $name => $properties ) {
			if ( ! WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( $name ) ) {
				register_block_pattern_category( $name, $properties );
			}
		}

		$block_patterns = array(
            'oigny-lite-home-core-about',			'oigny-lite-home-core-hero',			'oigny-lite-home-core-about',			'oigny-lite-home-core-service',			'oigny-lite-home-core-feature',			'oigny-lite-home-core-project',			'oigny-lite-home-core-blog',			'oigny-lite-home-core-cta',			'oigny-lite-single-core-hero',			'oigny-lite-archive-core-hero',			'oigny-lite-index-core-hero',			'oigny-lite-404-core-hero',			'oigny-lite-page-core-hero',			'oigny-lite-search-core-hero',
		);

		if ( defined( 'GUTENVERSE' ) ) {
            $block_patterns[] = 'oigny-lite-home-gutenverse-hero';			$block_patterns[] = 'oigny-lite-home-gutenverse-about';			$block_patterns[] = 'oigny-lite-home-gutenverse-services';			$block_patterns[] = 'oigny-lite-home-gutenverse-feature';			$block_patterns[] = 'oigny-lite-home-gutenverse-projects';			$block_patterns[] = 'oigny-lite-home-gutenverse-blog';			$block_patterns[] = 'oigny-lite-gutenverse-cta';			$block_patterns[] = 'oigny-lite-gutenverse-header';			$block_patterns[] = 'oigny-lite-gutenverse-footer';			$block_patterns[] = 'oigny-lite-projects-gutenverse-hero';			$block_patterns[] = 'oigny-lite-projects-gutenverse-projects';			$block_patterns[] = 'oigny-lite-projects-gutenverse-feature';			$block_patterns[] = 'oigny-lite-projects-gutenverse-testimonials';			$block_patterns[] = 'oigny-lite-gutenverse-cta';			$block_patterns[] = 'oigny-Lite-contact-gutenverse-hero';			$block_patterns[] = 'oigny-lite-contact-gutenverse-contact';			$block_patterns[] = 'oigny-lite-contact-gutenverse-faq';			$block_patterns[] = 'oigny-lite-blog-gutenverse-hero';			$block_patterns[] = 'oigny-lite-blog-gutenverse-post-block';			$block_patterns[] = 'oigny-lite-blog-gutenverse-newsletter';			$block_patterns[] = 'oigny-lite-blog-gutenverse-list';			$block_patterns[] = 'oigny-lite-404-gutenverse-hero';			$block_patterns[] = 'oigny-lite-index-gutenverse-hero';			$block_patterns[] = 'oigny-lite-page-gutenverse-hero';			$block_patterns[] = 'oigny-lite-single-post-gutenverse-hero';			$block_patterns[] = 'oigny-lite-single-post-gutenverse-content';			$block_patterns[] = 'oigny-lite-archive-gutenverse-hero';			$block_patterns[] = 'oigny-lite-search-gutenverse-hero';			$block_patterns[] = 'oigny-lite-about-gutenverse-hero';			$block_patterns[] = 'oigny-lite-about-gutenverse-feature';			$block_patterns[] = 'oigny-lite-about-gutenverse-stats';			$block_patterns[] = 'oigny-lite-about-gutenverse-team';
            
		}

		$block_patterns = apply_filters( 'oigny-lite_block_patterns', $block_patterns );

		if ( function_exists( 'register_block_pattern' ) ) {
			foreach ( $block_patterns as $block_pattern ) {
				$pattern_file = get_theme_file_path( '/inc/patterns/' . $block_pattern . '.php' );

				register_block_pattern(
					'oigny-lite/' . $block_pattern,
					require $pattern_file
				);
			}
		}
	}
}
