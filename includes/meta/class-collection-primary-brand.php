<?php
/**
 * Newspack Multibranded site taxonomy.
 *
 * @package Newspack
 */

namespace Newspack_Multibranded_Site\Meta;

use Newspack_Multibranded_Site\Meta;
use Newspack_Multibranded_Site\Taxonomy;

/**
 * Class to handle the Collection Primary brand Meta
 */
class Collection_Primary_Brand extends Meta {

	/**
	 * The meta type
	 *
	 * @var string
	 */
	public static $type = 'post';

	/**
	 * Gets the meta key
	 *
	 * @return string
	 */
	public static function get_key() {
		return Taxonomy::PRIMARY_META_KEY;
	}

	/**
	 * Gets the meta description
	 *
	 * @return string
	 */
	public static function get_description() {
		return __( 'The primary brand for a collection, if a collection belongs to more than one brand', 'newspack-multibranded-site' );
	}

	/**
	 * Get the list of post types that should have a primary brand selector.
	 *
	 * @return array The list of post type slugs.
	 */
	public static function get_post_types() {
		$post_types = array();
		if ( function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			$post_types[] = newspack_collections_get_post_type_slug();
		}
		return $post_types;
	}

	/**
	 * Gets the meta schema
	 *
	 * @return array
	 */
	public static function get_schema() {
		return array(
			'type'     => 'integer',
			'nullable' => true,
		);
	}
}
