<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Tests for Collection branding functionality.
 *
 * @package Newspack_Multibranded_Site
 */

use Newspack_Multibranded_Site\Taxonomy;

/**
 * Test collection branding functionality.
 */
class Test_Collection_Branding extends WP_UnitTestCase {

	/**
	 * Mock Newspack Collections post type slug.
	 *
	 * @var string
	 */
	const COLLECTION_POST_TYPE = 'newspack_collection';

	/**
	 * Test brands.
	 *
	 * @var WP_Term[]
	 */
	protected $brands = array();

	/**
	 * Setting up the test.
	 *
	 * @before
	 */
	public function set_up() {
		parent::set_up();

		// Register a mock collection post type for testing.
		register_post_type(
			self::COLLECTION_POST_TYPE,
			array(
				'public'      => true,
				'label'       => 'Collections',
				'supports'    => array( 'title', 'editor' ),
				'show_in_rest' => true,
			)
		);

		// Create test brands.
		$this->brands['brand1'] = $this->factory->term->create_and_get( array( 'taxonomy' => Taxonomy::SLUG ) );
		$this->brands['brand2'] = $this->factory->term->create_and_get( array( 'taxonomy' => Taxonomy::SLUG ) );
	}

	/**
	 * Tear down after test.
	 *
	 * @after
	 */
	public function tear_down() {
		// Unregister the mock post type.
		unregister_post_type( self::COLLECTION_POST_TYPE );
		parent::tear_down();
	}

	/**
	 * Test that brand taxonomy can be assigned to collections when function exists.
	 */
	public function test_collection_post_type_can_be_branded() {
		// Create a mock function for getting the collection post type slug.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		// Re-initialize taxonomy to pick up the collections post type.
		Taxonomy::register_taxonomy();

		// Create a collection post.
		$collection = $this->factory->post->create_and_get(
			array(
				'post_type'  => self::COLLECTION_POST_TYPE,
				'post_title' => 'Test Collection',
			)
		);

		// Verify collection can be assigned to brand.
		$result = wp_set_post_terms( $collection->ID, $this->brands['brand1']->term_id, Taxonomy::SLUG );
		$this->assertIsArray( $result, 'Brand should be assignable to collection' );
		$this->assertNotEmpty( $result, 'Brand assignment should return term IDs' );

		// Verify the brand was assigned.
		$terms = wp_get_post_terms( $collection->ID, Taxonomy::SLUG );
		$this->assertCount( 1, $terms );
		$this->assertSame( $this->brands['brand1']->term_id, $terms[0]->term_id );
	}

	/**
	 * Test get_current_brand_for_post with collection.
	 */
	public function test_get_current_brand_for_collection() {
		// Mock the function.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		// Create a collection.
		$collection = $this->factory->post->create_and_get(
			array(
				'post_type'  => self::COLLECTION_POST_TYPE,
				'post_title' => 'Branded Collection',
			)
		);

		// No brand assigned initially.
		$this->assertNull( Taxonomy::get_current_brand_for_post( $collection->ID ), 'Should return null when no brand is assigned' );

		// Assign a single brand.
		wp_set_post_terms( $collection->ID, $this->brands['brand1']->term_id, Taxonomy::SLUG );
		$brand = Taxonomy::get_current_brand_for_post( $collection->ID );
		$this->assertInstanceOf( WP_Term::class, $brand );
		$this->assertSame( $this->brands['brand1']->term_id, $brand->term_id, 'Should return the assigned brand' );

		// Assign multiple brands.
		wp_set_post_terms( $collection->ID, array( $this->brands['brand1']->term_id, $this->brands['brand2']->term_id ), Taxonomy::SLUG );
		$this->assertNull( Taxonomy::get_current_brand_for_post( $collection->ID ), 'Should return null when multiple brands are assigned without primary' );
	}

	/**
	 * Test collection primary brand meta.
	 */
	public function test_collection_primary_brand_meta() {
		// Mock the function.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		// Create a collection with multiple brands.
		$collection = $this->factory->post->create_and_get(
			array(
				'post_type'  => self::COLLECTION_POST_TYPE,
				'post_title' => 'Multi-Brand Collection',
			)
		);

		wp_set_post_terms( $collection->ID, array( $this->brands['brand1']->term_id, $this->brands['brand2']->term_id ), Taxonomy::SLUG );

		// Set primary brand.
		update_post_meta( $collection->ID, Taxonomy::PRIMARY_META_KEY, $this->brands['brand2']->term_id );

		// Verify primary brand is returned.
		$brand = Taxonomy::get_current_brand_for_post( $collection->ID );
		$this->assertInstanceOf( WP_Term::class, $brand );
		$this->assertSame( $this->brands['brand2']->term_id, $brand->term_id, 'Should return the primary brand when multiple brands are assigned' );
	}

	/**
	 * Test that collections are included in get_post_types when the function exists.
	 */
	public function test_collections_in_get_post_types() {
		// Mock the function.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		$post_types = Taxonomy::get_post_types();
		$this->assertContains( self::COLLECTION_POST_TYPE, $post_types, 'Collections post type should be included in supported post types' );
	}

	/**
	 * Test collection branding REST API meta registration.
	 */
	public function test_collection_meta_registered() {
		// Mock the function.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		// Re-register to pick up collections.
		Taxonomy::register_taxonomy();

		// Check if meta is registered for collections.
		$registered = get_registered_meta_keys( 'post', self::COLLECTION_POST_TYPE );
		$this->assertArrayHasKey( Taxonomy::PRIMARY_META_KEY, $registered, 'Primary brand meta should be registered for collections' );
	}

	/**
	 * Test that collection branding works with category fallback.
	 */
	public function test_collection_category_brand_fallback() {
		// Mock the function.
		if ( ! function_exists( 'newspack_collections_get_post_type_slug' ) ) {
			/**
			 * Mock function to get collection post type slug.
			 *
			 * @return string
			 */
			function newspack_collections_get_post_type_slug() {
				return Test_Collection_Branding::COLLECTION_POST_TYPE;
			}
		}

		// Create a category with a primary brand.
		$category = $this->factory->term->create_and_get( array( 'taxonomy' => 'category' ) );
		add_term_meta( $category->term_id, Taxonomy::PRIMARY_META_KEY, $this->brands['brand1']->term_id );

		// Create a collection in that category.
		$collection = $this->factory->post->create_and_get(
			array(
				'post_type'     => self::COLLECTION_POST_TYPE,
				'post_title'    => 'Categorized Collection',
				'post_category' => array( $category->term_id ),
			)
		);

		// Should return the brand from the category.
		$brand = Taxonomy::get_current_brand_for_post( $collection->ID );
		$this->assertInstanceOf( WP_Term::class, $brand );
		$this->assertSame( $this->brands['brand1']->term_id, $brand->term_id, 'Should return brand from category when no direct brand is assigned' );
	}
}
