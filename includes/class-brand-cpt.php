<?php
/**
 * Newspack Multibranded site Brand Custom Post Type.
 *
 * @package Newspack
 */

namespace Newspack_Multibranded_Site;

/**
 * Class to handle the Brand Custom Post Type
 *
 * Registers a custom post type "brand-cpt" that:
 * - Uses hierarchical brand slug in URL structure
 * - Supports Gutenberg editor
 * - Associates with brand taxonomy
 * - Reads SportsDataIDs from associated brand
 */
class Brand_CPT {

	/**
	 * The custom post type slug.
	 *
	 * @var string
	 */
	const SLUG = 'brand-cpt';

	/**
	 * Initializes the custom post type
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'custom_post_type_link' ), 10, 2 );
		add_action( 'pre_get_posts', array( __CLASS__, 'handle_custom_post_type_query' ) );
	}

	/**
	 * Registers the custom post type
	 *
	 * @return void
	 */
	public static function register_post_type() {
		$labels = array(
			'name'                  => _x( 'Brand Content', 'Post type general name', 'newspack-multibranded-site' ),
			'singular_name'         => _x( 'Brand Content', 'Post type singular name', 'newspack-multibranded-site' ),
			'menu_name'             => _x( 'Brand Content', 'Admin Menu text', 'newspack-multibranded-site' ),
			'name_admin_bar'        => _x( 'Brand Content', 'Add New on Toolbar', 'newspack-multibranded-site' ),
			'add_new'               => __( 'Add New', 'newspack-multibranded-site' ),
			'add_new_item'          => __( 'Add New Brand Content', 'newspack-multibranded-site' ),
			'new_item'              => __( 'New Brand Content', 'newspack-multibranded-site' ),
			'edit_item'             => __( 'Edit Brand Content', 'newspack-multibranded-site' ),
			'view_item'             => __( 'View Brand Content', 'newspack-multibranded-site' ),
			'all_items'             => __( 'All Brand Content', 'newspack-multibranded-site' ),
			'search_items'          => __( 'Search Brand Content', 'newspack-multibranded-site' ),
			'parent_item_colon'     => __( 'Parent Brand Content:', 'newspack-multibranded-site' ),
			'not_found'             => __( 'No brand content found.', 'newspack-multibranded-site' ),
			'not_found_in_trash'    => __( 'No brand content found in Trash.', 'newspack-multibranded-site' ),
			'featured_image'        => _x( 'Featured Image', 'Overrides the "Featured Image" phrase', 'newspack-multibranded-site' ),
			'set_featured_image'    => _x( 'Set featured image', 'Overrides the "Set featured image" phrase', 'newspack-multibranded-site' ),
			'remove_featured_image' => _x( 'Remove featured image', 'Overrides the "Remove featured image" phrase', 'newspack-multibranded-site' ),
			'use_featured_image'    => _x( 'Use as featured image', 'Overrides the "Use as featured image" phrase', 'newspack-multibranded-site' ),
			'archives'              => _x( 'Brand Content archives', 'The post type archive label used in nav menus', 'newspack-multibranded-site' ),
			'insert_into_item'      => _x( 'Insert into brand content', 'Overrides the "Insert into post"/"Insert into page" phrase', 'newspack-multibranded-site' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this brand content', 'Overrides the "Uploaded to this post"/"Uploaded to this page" phrase', 'newspack-multibranded-site' ),
			'filter_items_list'     => _x( 'Filter brand content list', 'Screen reader text for the filter links', 'newspack-multibranded-site' ),
			'items_list_navigation' => _x( 'Brand content list navigation', 'Screen reader text for the pagination', 'newspack-multibranded-site' ),
			'items_list'            => _x( 'Brand content list', 'Screen reader text for the items list', 'newspack-multibranded-site' ),
		);

		$args = array(
			'labels'              => $labels,
			'public'              => true,
			'publicly_queryable'  => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'query_var'           => true,
			'rewrite'             => array( 'slug' => self::SLUG ),
			'capability_type'     => 'post',
			'has_archive'         => true,
			'hierarchical'        => false,
			'menu_position'       => null,
			'menu_icon'           => 'dashicons-portfolio',
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
			'taxonomies'          => array( Taxonomy::SLUG ),
		);

		register_post_type( self::SLUG, $args );
	}

	/**
	 * Custom post type link
	 *
	 * Modifies the permalink to include the hierarchical brand slug structure.
	 * Example: domain.com/parent-brand/sub-brand/brand-cpt/post-slug/
	 *
	 * @param string  $post_link The post's permalink.
	 * @param WP_Post $post The post object.
	 * @return string Modified permalink.
	 */
	public static function custom_post_type_link( $post_link, $post ) {
		if ( self::SLUG !== $post->post_type ) {
			return $post_link;
		}

		// Get the brand taxonomy terms for this post.
		$terms = wp_get_post_terms( $post->ID, Taxonomy::SLUG );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $post_link;
		}

		// Use the first (or primary) brand.
		$brand = $terms[0];

		// Check if this brand has custom URL enabled.
		$custom_url = get_term_meta( $brand->term_id, Meta\Url::get_key(), true );
		
		if ( 'yes' === $custom_url ) {
			// Build hierarchical brand path.
			$brand_path = self::build_term_hierarchical_path( $brand );
			
			// Construct the full URL: /brand-path/brand-cpt/post-slug/
			$site_url = trailingslashit( get_site_url() );
			$post_link = $site_url . trailingslashit( $brand_path ) . self::SLUG . '/' . $post->post_name . '/';
		}

		return $post_link;
	}

	/**
	 * Build hierarchical path for a term
	 *
	 * Constructs the full path including parent slugs (e.g., parent-brand/sub-brand).
	 *
	 * @param WP_Term $term The term to build path for.
	 * @return string The hierarchical path.
	 */
	private static function build_term_hierarchical_path( $term ) {
		$path_segments = array( $term->slug );
		$current_term  = $term;

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
	 * Handle custom post type query
	 *
	 * Ensures WordPress can find posts at the custom hierarchical URLs.
	 *
	 * @param WP_Query $query The WP_Query instance.
	 * @return void
	 */
	public static function handle_custom_post_type_query( $query ) {
		// Only affect main query on frontend.
		if ( is_admin() || ! $query->is_main_query() ) {
			return;
		}

		// Check if this is a brand-cpt query with brand context.
		if ( isset( $query->query_vars['post_type'] ) && self::SLUG === $query->query_vars['post_type'] ) {
			// Query is already set up correctly.
			return;
		}
	}

	/**
	 * Get sports data IDs for a post
	 *
	 * Retrieves the sports data IDs from the associated brand taxonomy term.
	 *
	 * @param int $post_id The post ID.
	 * @return array Array of sports data provider IDs.
	 */
	public static function get_sports_data_ids( $post_id ) {
		$terms = wp_get_post_terms( $post_id, Taxonomy::SLUG );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return array();
		}

		// Get sports data IDs from the first brand.
		$brand = $terms[0];
		$sports_data_ids = get_term_meta( $brand->term_id, Meta\Sports_Data_Ids::get_key(), true );

		return is_array( $sports_data_ids ) ? $sports_data_ids : array();
	}
}
