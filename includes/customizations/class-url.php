<?php
/**
 * Newspack Multibranded site taxonomy.
 *
 * @package Newspack
 */

namespace Newspack_Multibranded_Site\Customizations;

use Newspack_Multibranded_Site\Meta\Url as Url_Meta;
use Newspack_Multibranded_Site\Taxonomy;

/**
 * Class to handle the Url Customization
 */
class Url {

	/**
	 * Initializes
	 */
	public static function init() {
		add_action( 'parse_request', [ __CLASS__, 'parse_request' ] );
		add_filter( 'pre_term_link', [ __CLASS__, 'pre_term_link' ], 10, 3 );
	}

	/**
	 * Parse the request
	 *
	 * Handles both flat and hierarchical brand URL structures:
	 * - Flat: /brand-slug/
	 * - Hierarchical: /parent-brand/sub-brand/
	 *
	 * @param WP $wp The WP object.
	 * @return void
	 */
	public static function parse_request( $wp ) {
		$matched_query = wp_parse_args( $wp->matched_query );

		if ( empty( $matched_query['pagename'] ) && empty( $matched_query['name'] ) ) {
			return;
		}

		$pagename = $matched_query['pagename'] ?? $matched_query['name'];

		$terms = get_terms(
			array(
				'taxonomy'   => Taxonomy::SLUG,
				'hide_empty' => false,
				'meta_key'   => Url_Meta::get_key(), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_value' => 'yes', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		// Try exact slug match first (for flat structure).
		foreach ( $terms as $term ) {
			if ( $term->slug === $pagename ) {
				if ( isset( $wp->query_vars['name'] ) ) {
					unset( $wp->query_vars['name'] );
				}
				if ( isset( $wp->query_vars['pagename'] ) ) {
					unset( $wp->query_vars['pagename'] );
				}
				if ( isset( $wp->query_vars['page'] ) ) {
					unset( $wp->query_vars['page'] );
				}

				$wp->query_vars[ Taxonomy::SLUG ] = $term->slug;
				return;
			}
		}

		// Try hierarchical path match (e.g., parent-brand/sub-brand).
		$path_parts = explode( '/', trim( $pagename, '/' ) );
		if ( count( $path_parts ) > 1 ) {
			// Build the hierarchical path and check if it matches a term with parent.
			$matched_term = self::find_term_by_hierarchical_path( $path_parts, $terms );
			if ( $matched_term ) {
				if ( isset( $wp->query_vars['name'] ) ) {
					unset( $wp->query_vars['name'] );
				}
				if ( isset( $wp->query_vars['pagename'] ) ) {
					unset( $wp->query_vars['pagename'] );
				}
				if ( isset( $wp->query_vars['page'] ) ) {
					unset( $wp->query_vars['page'] );
				}

				$wp->query_vars[ Taxonomy::SLUG ] = $matched_term->slug;
			}
		}
	}

	/**
	 * Find a term by hierarchical path
	 *
	 * Matches path segments like ['parent-brand', 'sub-brand'] to the corresponding term.
	 *
	 * @param array $path_parts Array of URL path segments.
	 * @param array $terms Array of terms with custom URLs enabled.
	 * @return \WP_Term|null The matched term or null.
	 */
	private static function find_term_by_hierarchical_path( $path_parts, $terms ) {
		// The last part should be the term slug we're looking for.
		$target_slug = $path_parts[ count( $path_parts ) - 1 ];
		
		foreach ( $terms as $term ) {
			if ( $term->slug === $target_slug ) {
				// Build the hierarchical path for this term.
				$term_path = self::build_term_hierarchical_path( $term );
				$term_path_parts = explode( '/', trim( $term_path, '/' ) );
				
				// Check if paths match.
				if ( $term_path_parts === $path_parts ) {
					return $term;
				}
			}
		}
		
		return null;
	}

	/**
	 * Build hierarchical path for a term
	 *
	 * Constructs the full path including parent slugs (e.g., parent-brand/sub-brand).
	 *
	 * @param \WP_Term $term The term to build path for.
	 * @return string The hierarchical path.
	 */
	private static function build_term_hierarchical_path( $term ) {
		$path_segments = array( $term->slug );
		$current_term = $term;
		
		// Walk up the parent chain.
		while ( $current_term->parent ) {
			$parent = get_term( $current_term->parent, Taxonomy::SLUG );
			if ( ! $parent || is_wp_error( $parent ) ) {
				break;
			}
			array_unshift( $path_segments, $parent->slug );
			$current_term = $parent;
		}
		
		return implode( '/', $path_segments );
	}

	/**
	 * Make sure the term link uses custom URL structure if enabled
	 *
	 * Supports both flat and hierarchical structures:
	 * - Flat: /brand-slug/
	 * - Hierarchical: /parent-brand/sub-brand/
	 *
	 * @param string  $termlink The term link.
	 * @param WP_Term $term The term object.
	 * @return string
	 */
	public static function pre_term_link( $termlink, $term ) {
		if ( Taxonomy::SLUG !== $term->taxonomy ) {
			return $termlink;
		}

		$custom_url = get_term_meta( $term->term_id, Url_Meta::get_key(), true );
		if ( 'yes' === $custom_url ) {
			// Build hierarchical path if term has parents.
			$termlink = self::build_term_hierarchical_path( $term );
		}

		return $termlink;
	}
}
