<?php
/**
 * Newspack Multibranded site Show Competition Navigation meta.
 *
 * @package Newspack
 */

namespace Newspack_Multibranded_Site\Meta;

use Newspack_Multibranded_Site\Meta;

/**
 * Class to handle the Show Competition Navigation Meta
 *
 * Controls whether to display competition navigation with logos
 * on brand taxonomy archive pages.
 */
class Show_Competition_Nav extends Meta {

	/**
	 * Gets the meta key
	 *
	 * @return string
	 */
	public static function get_key() {
		return '_show_competition_nav';
	}

	/**
	 * Gets the meta description
	 *
	 * @return string
	 */
	public static function get_description() {
		return __( 'Whether to display competition navigation with logos on taxonomy archive pages', 'newspack-multibranded-site' );
	}

	/**
	 * Gets the meta schema
	 *
	 * @return array
	 */
	public static function get_schema() {
		return array(
			'type'    => 'boolean',
			'default' => false,
		);
	}
}
