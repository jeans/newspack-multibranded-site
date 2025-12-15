<?php
/**
 * Class TestSportsMetaFields
 *
 * @package Newspack_Multibranded_Site
 */

use Newspack_Multibranded_Site\Taxonomy;
use Newspack_Multibranded_Site\Meta\Sports_Data_Ids;
use Newspack_Multibranded_Site\Meta\Show_Competition_Nav;

/**
 * Test new sports-related meta fields.
 */
class TestSportsMetaFields extends WP_UnitTestCase {

	/**
	 * Test Sports Data IDs meta field
	 */
	public function test_sports_data_ids_meta() {
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		// Test adding sports data IDs.
		$sports_data = array(
			array(
				'provider' => 'Heimspiel',
				'id'       => 'heim123',
			),
			array(
				'provider' => 'Sportradar',
				'id'       => 'sport456',
			),
			array(
				'provider' => 'Statsperform',
				'id'       => 'stats789',
			),
		);

		add_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), $sports_data );

		// Test retrieving sports data IDs.
		$retrieved = get_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), true );

		$this->assertIsArray( $retrieved );
		$this->assertCount( 3, $retrieved );
		$this->assertSame( 'Heimspiel', $retrieved[0]['provider'] );
		$this->assertSame( 'heim123', $retrieved[0]['id'] );
		$this->assertSame( 'Sportradar', $retrieved[1]['provider'] );
		$this->assertSame( 'sport456', $retrieved[1]['id'] );
		$this->assertSame( 'Statsperform', $retrieved[2]['provider'] );
		$this->assertSame( 'stats789', $retrieved[2]['id'] );
	}

	/**
	 * Test Show Competition Nav meta field
	 */
	public function test_show_competition_nav_meta() {
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		// Test enabling competition nav.
		add_term_meta( $brand->term_id, Show_Competition_Nav::get_key(), true );
		$value = get_term_meta( $brand->term_id, Show_Competition_Nav::get_key(), true );
		$this->assertTrue( (bool) $value );

		// Test disabling competition nav.
		update_term_meta( $brand->term_id, Show_Competition_Nav::get_key(), false );
		$value = get_term_meta( $brand->term_id, Show_Competition_Nav::get_key(), true );
		$this->assertFalse( (bool) $value );
	}

	/**
	 * Test default value for Show Competition Nav
	 */
	public function test_show_competition_nav_default() {
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		// Without setting, should return empty/false.
		$value = get_term_meta( $brand->term_id, Show_Competition_Nav::get_key(), true );
		$this->assertEmpty( $value );
	}

	/**
	 * Test empty sports data IDs
	 */
	public function test_empty_sports_data_ids() {
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		// Without setting, should return empty.
		$value = get_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), true );
		$this->assertEmpty( $value );
	}

	/**
	 * Test updating sports data IDs
	 */
	public function test_update_sports_data_ids() {
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		// Add initial data.
		$initial_data = array(
			array(
				'provider' => 'Heimspiel',
				'id'       => '111',
			),
		);
		add_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), $initial_data );

		// Update with new data.
		$updated_data = array(
			array(
				'provider' => 'Heimspiel',
				'id'       => '222',
			),
			array(
				'provider' => 'Sportradar',
				'id'       => '333',
			),
		);
		update_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), $updated_data );

		$retrieved = get_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), true );
		$this->assertCount( 2, $retrieved );
		$this->assertSame( '222', $retrieved[0]['id'] );
	}
}
