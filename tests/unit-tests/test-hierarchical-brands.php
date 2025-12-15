<?php
/**
 * Class TestHierarchicalBrands
 *
 * @package Newspack_Multibranded_Site
 */

use Newspack_Multibranded_Site\Taxonomy;
use Newspack_Multibranded_Site\Meta\Url as Url_Meta;

/**
 * Test hierarchical brand taxonomy behavior.
 */
class TestHierarchicalBrands extends WP_UnitTestCase {

	/**
	 * Test hierarchical brand URL parsing
	 */
	public function test_hierarchical_brand_url_parsing() {
		// Create parent brand.
		$parent_brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'parent-brand',
			)
		);
		add_term_meta( $parent_brand->term_id, Url_Meta::get_key(), 'yes' );

		// Create child brand.
		$child_brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'sub-brand',
				'parent'   => $parent_brand->term_id,
			)
		);
		add_term_meta( $child_brand->term_id, Url_Meta::get_key(), 'yes' );

		$this->set_permalink_structure( '/%postname%/' );

		// Test parent brand URL.
		$this->go_to( home_url( 'parent-brand' ) );
		$this->assertTrue( is_tax() );
		$this->assertSame( $parent_brand->term_id, get_queried_object_id() );

		// Test hierarchical child brand URL.
		$this->go_to( home_url( 'parent-brand/sub-brand' ) );
		$this->assertTrue( is_tax() );
		$this->assertSame( $child_brand->term_id, get_queried_object_id() );
	}

	/**
	 * Test hierarchical brand URL generation
	 */
	public function test_hierarchical_brand_url_generation() {
		// Create parent brand.
		$parent_brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'sports',
			)
		);
		add_term_meta( $parent_brand->term_id, Url_Meta::get_key(), 'yes' );

		// Create child brand.
		$child_brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'football',
				'parent'   => $parent_brand->term_id,
			)
		);
		add_term_meta( $child_brand->term_id, Url_Meta::get_key(), 'yes' );

		// Test parent brand link.
		$parent_link = get_term_link( $parent_brand );
		$this->assertStringContainsString( 'sports', $parent_link );

		// Test child brand link contains hierarchical path.
		$child_link = get_term_link( $child_brand );
		$this->assertStringContainsString( 'sports/football', $child_link );
	}

	/**
	 * Test flat (non-hierarchical) brand URLs still work
	 */
	public function test_flat_brand_url_still_works() {
		$flat_brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'flat-brand',
			)
		);
		add_term_meta( $flat_brand->term_id, Url_Meta::get_key(), 'yes' );

		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( home_url( 'flat-brand' ) );
		$this->assertTrue( is_tax() );
		$this->assertSame( $flat_brand->term_id, get_queried_object_id() );
	}

	/**
	 * Test deeply nested brand hierarchy
	 */
	public function test_deeply_nested_brand_hierarchy() {
		// Create three-level hierarchy.
		$level1 = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'level1',
			)
		);
		add_term_meta( $level1->term_id, Url_Meta::get_key(), 'yes' );

		$level2 = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'level2',
				'parent'   => $level1->term_id,
			)
		);
		add_term_meta( $level2->term_id, Url_Meta::get_key(), 'yes' );

		$level3 = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'level3',
				'parent'   => $level2->term_id,
			)
		);
		add_term_meta( $level3->term_id, Url_Meta::get_key(), 'yes' );

		$this->set_permalink_structure( '/%postname%/' );

		// Test three-level hierarchical URL.
		$this->go_to( home_url( 'level1/level2/level3' ) );
		$this->assertTrue( is_tax() );
		$this->assertSame( $level3->term_id, get_queried_object_id() );
	}
}
