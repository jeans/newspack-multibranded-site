<?php
/**
 * Newspack Multibranded site Sports Data IDs meta.
 *
 * @package Newspack
 */

namespace Newspack_Multibranded_Site\Meta;

use Newspack_Multibranded_Site\Meta;

/**
 * Class to handle the Sports Data IDs Meta
 *
 * Stores sports data provider IDs for integrations with:
 * - Heimspiel
 * - Sportradar
 * - Statsperform
 */
class Sports_Data_Ids extends Meta {

	/**
	 * Gets the meta key
	 *
	 * @return string
	 */
	public static function get_key() {
		return '_sports_data_ids';
	}

	/**
	 * Gets the meta description
	 *
	 * @return string
	 */
	public static function get_description() {
		return __( 'Sports data provider IDs for this brand', 'newspack-multibranded-site' );
	}

	/**
	 * Gets the meta schema
	 *
	 * Schema defines an array of objects with provider and ID fields.
	 * Example: [{"provider": "Heimspiel", "id": "12345"}, {"provider": "Sportradar", "id": "67890"}]
	 *
	 * @return array
	 */
	public static function get_schema() {
		return array(
			'type'        => 'array',
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'provider' => array(
						'type' => 'string',
						'enum' => array( 'Heimspiel', 'Sportradar', 'Statsperform' ),
					),
					'id'       => array(
						'type' => 'string',
					),
				),
			),
			'default'     => array(),
			'description' => __( 'Array of sports data provider IDs', 'newspack-multibranded-site' ),
		);
	}
}
