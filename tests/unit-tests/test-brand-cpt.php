<?php
/**
 * Class TestBrandCPT
 *
 * @package Newspack_Multibranded_Site
 */

use Newspack_Multibranded_Site\Taxonomy;
use Newspack_Multibranded_Site\Brand_CPT;
use Newspack_Multibranded_Site\Meta\Url as Url_Meta;
use Newspack_Multibranded_Site\Meta\Sports_Data_Ids;

/**
 * Test Brand Custom Post Type functionality.
 */
class TestBrandCPT extends WP_UnitTestCase {

	/**
	 * Test CPT is registered
	 */
	public function test_cpt_registered() {
		$this->assertTrue( post_type_exists( Brand_CPT::SLUG ), 'Brand CPT should be registered' );
	}

	/**
	 * Test CPT has brand taxonomy
	 */
	public function test_cpt_has_brand_taxonomy() {
		$taxonomies = get_object_taxonomies( Brand_CPT::SLUG );
		$this->assertContains( Taxonomy::SLUG, $taxonomies, 'Brand CPT should support brand taxonomy' );
	}

	/**
	 * Test CPT permalink with flat brand
	 */
	public function test_cpt_permalink_flat_brand() {
		// Create brand.
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
				'slug'     => 'my-brand',
			)
		);
		add_term_meta( $brand->term_id, Url_Meta::get_key(), 'yes' );

		// Create post.
		$post = $this->factory->post->create_and_get(
			array(
				'post_type'  => Brand_CPT::SLUG,
				'post_title' => 'Test Post',
			)
		);
		wp_set_post_terms( $post->ID, $brand->term_id, Taxonomy::SLUG );

		$this->set_permalink_structure( '/%postname%/' );

		// Check permalink includes brand and cpt slug.
		$permalink = get_permalink( $post->ID );
		$this->assertStringContainsString( 'my-brand', $permalink );
		$this->assertStringContainsString( 'brand-cpt', $permalink );
	}

	/**
	 * Test CPT permalink with hierarchical brand
	 */
	public function test_cpt_permalink_hierarchical_brand() {
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

		// Create post.
		$post = $this->factory->post->create_and_get(
			array(
				'post_type'  => Brand_CPT::SLUG,
				'post_title' => 'Match Report',
			)
		);
		wp_set_post_terms( $post->ID, $child_brand->term_id, Taxonomy::SLUG );

		$this->set_permalink_structure( '/%postname%/' );

		// Check permalink includes hierarchical brand path.
		$permalink = get_permalink( $post->ID );
		$this->assertStringContainsString( 'sports/football', $permalink );
		$this->assertStringContainsString( 'brand-cpt', $permalink );
	}

	/**
	 * Test getting sports data IDs
	 */
	public function test_get_sports_data_ids() {
		// Create brand with sports data IDs.
		$brand = $this->factory->term->create_and_get(
			array(
				'taxonomy' => Taxonomy::SLUG,
			)
		);

		$sports_data = array(
			array(
				'provider' => 'Heimspiel',
				'id'       => '12345',
			),
			array(
				'provider' => 'Sportradar',
				'id'       => '67890',
			),
		);
		add_term_meta( $brand->term_id, Sports_Data_Ids::get_key(), $sports_data );

		// Create post associated with brand.
		$post = $this->factory->post->create_and_get(
			array(
				'post_type' => Brand_CPT::SLUG,
			)
		);
		wp_set_post_terms( $post->ID, $brand->term_id, Taxonomy::SLUG );

		// Get sports data IDs.
		$retrieved_data = Brand_CPT::get_sports_data_ids( $post->ID );

		$this->assertIsArray( $retrieved_data );
		$this->assertCount( 2, $retrieved_data );
		$this->assertSame( 'Heimspiel', $retrieved_data[0]['provider'] );
		$this->assertSame( '12345', $retrieved_data[0]['id'] );
	}

	/**
	 * Test CPT without brand returns default permalink
	 */
	public function test_cpt_without_brand() {
		$post = $this->factory->post->create_and_get(
			array(
				'post_type'  => Brand_CPT::SLUG,
				'post_title' => 'No Brand Post',
			)
		);

		$this->set_permalink_structure( '/%postname%/' );

		// Should return default permalink structure.
		$permalink = get_permalink( $post->ID );
		$this->assertStringContainsString( 'brand-cpt', $permalink );
	}
}
