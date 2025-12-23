# Sports Directory Implementation Summary

This document summarizes the enhancements made to the Newspack Multibranded Site plugin to support sports directory functionality.

## Overview

The plugin has been extended to support hierarchical brand structures, sports data integration, and a custom post type for brand-specific content. These changes enable the creation of sophisticated sports directory sites with nested brand structures like `domain.com/parent-brand/sub-brand/`.

## Major Features Implemented

### 1. Hierarchical Brand Taxonomy

**File**: `includes/class-taxonomy.php`

The existing brand taxonomy now supports true hierarchical relationships:
- Parent-child brand relationships
- Nested URL structures (e.g., `/sports/football/`)
- Maintains backward compatibility with flat structures

**File**: `includes/customizations/class-url.php`

Enhanced URL parsing to handle:
- Flat brand URLs: `/brand-name/`
- Hierarchical brand URLs: `/parent-brand/sub-brand/`
- Multi-level hierarchies: `/level1/level2/level3/`

Key methods added:
- `find_term_by_hierarchical_path()` - Matches URL segments to terms
- `build_term_hierarchical_path()` - Constructs full hierarchical paths

### 2. Sportspack Admin Menu

**File**: `includes/class-admin.php`

A new "Sportspack" menu has been added to the WordPress Admin Dashboard with two sub-menu items:
- **Brand Design**: Links to `wp-admin/admin.php?page=newspack-multi-branded-sites`
- **Brand Taxonomy**: Links to `wp-admin/edit-tags.php?taxonomy=brand`

This provides quick access to brand configuration and taxonomy management.

### 3. Sports Data Integration

#### Sports Data IDs Meta Field

**File**: `includes/meta/class-sports-data-ids.php`

A new meta field stores sports data provider IDs as an array of objects:

```php
[
  {
    "provider": "Heimspiel",
    "id": "12345"
  },
  {
    "provider": "Sportradar",
    "id": "67890"
  }
]
```

Supported providers:
- Heimspiel
- Sportradar
- Statsperform

#### Competition Navigation Meta Field

**File**: `includes/meta/class-show-competition-nav.php`

A boolean meta field to control whether competition navigation with logos should be displayed on taxonomy archive pages.

### 4. Brand Custom Post Type

**File**: `includes/class-brand-cpt.php`

A new custom post type `brand-cpt` has been created with:

**Features**:
- Gutenberg editor support
- Brand taxonomy association
- Hierarchical URL structure based on associated brand
- Access to sports data IDs from associated brand

**URL Structure**:
- Flat brand: `/brand-name/brand-cpt/post-slug/`
- Hierarchical brand: `/parent-brand/sub-brand/brand-cpt/post-slug/`

**Key Methods**:
- `custom_post_type_link()` - Generates permalinks with brand hierarchy
- `handle_custom_post_type_query()` - Resolves hierarchical URLs
- `get_sports_data_ids()` - Retrieves sports data from associated brand

### 5. Admin UI Enhancements

**File**: `src/admin/views/brands/Brand.js`

The brand editor now displays:
- Hierarchical URL preview
- Parent path information for nested brands
- Clear indication of brand hierarchy

Helper functions:
- `buildHierarchicalPath()` - Constructs full brand path
- `buildParentPath()` - Constructs parent path only
- `getBaseUrlComponents()` - Calculates URL components

## REST API

All new meta fields are exposed via WordPress REST API:
- Sports Data IDs: `/wp/v2/brand/{id}` - `meta._sports_data_ids`
- Competition Nav: `/wp/v2/brand/{id}` - `meta._show_competition_nav`

## Testing

Comprehensive unit tests have been added:

### Test Files

1. **test-hierarchical-brands.php**
   - Tests hierarchical brand URL parsing
   - Tests URL generation for nested brands
   - Tests backward compatibility with flat brands
   - Tests deeply nested hierarchies (3+ levels)

2. **test-brand-cpt.php**
   - Tests CPT registration
   - Tests taxonomy association
   - Tests permalink generation (flat and hierarchical)
   - Tests sports data ID retrieval
   - Tests posts without brands

3. **test-sports-meta-fields.php**
   - Tests sports data IDs CRUD operations
   - Tests competition navigation toggle
   - Tests default values
   - Tests multiple providers

4. **test-meta.php** (updated)
   - Added capability tests for new meta fields

## Usage Examples

### Creating a Hierarchical Brand Structure

```php
// Create parent brand
$sports_brand = wp_insert_term( 'Sports', 'brand' );
add_term_meta( $sports_brand['term_id'], '_custom_url', 'yes' );

// Create child brand
$football_brand = wp_insert_term(
    'Football',
    'brand',
    [ 'parent' => $sports_brand['term_id'] ]
);
add_term_meta( $football_brand['term_id'], '_custom_url', 'yes' );

// Result: domain.com/sports/football/
```

### Adding Sports Data IDs

```php
$sports_data = [
    [
        'provider' => 'Heimspiel',
        'id'       => '12345',
    ],
    [
        'provider' => 'Sportradar',
        'id'       => '67890',
    ],
];
add_term_meta( $brand_id, '_sports_data_ids', $sports_data );
```

### Creating Brand Content

```php
// Create a brand-cpt post
$post_id = wp_insert_post([
    'post_title'  => 'Match Report',
    'post_type'   => 'brand-cpt',
    'post_status' => 'publish',
]);

// Associate with brand
wp_set_post_terms( $post_id, $football_brand['term_id'], 'brand' );

// Result: domain.com/sports/football/brand-cpt/match-report/
```

### Retrieving Sports Data

```php
// Get sports data for a brand-cpt post
$sports_data = Newspack_Multibranded_Site\Brand_CPT::get_sports_data_ids( $post_id );

foreach ( $sports_data as $data ) {
    echo $data['provider'] . ': ' . $data['id'];
}
```

## Backward Compatibility

All changes maintain full backward compatibility:
- Existing flat brand URLs continue to work
- Non-hierarchical brands function as before
- All existing meta fields remain unchanged
- No database migrations required

## Security Considerations

- All meta fields use proper capability checks
- REST API endpoints respect WordPress permissions
- User input is sanitized
- No SQL injection vulnerabilities (uses WP_Query and term APIs)
- No XSS vulnerabilities (proper escaping in admin UI)

## Performance Considerations

- Hierarchical path building uses minimal queries
- Parent chain traversal is optimized with early returns
- No additional queries on regular page loads
- Meta field queries use WordPress caching

### 6. Newspack Collections Branding Support

**Files**: 
- `includes/class-taxonomy.php`
- `includes/meta/class-post-primary-brand.php`

The plugin now supports branding for Newspack Collections custom post type:

**Features**:
- Brand taxonomy automatically applies to collections when Newspack Collections plugin is active
- Collections can be assigned to one or multiple brands
- Primary brand meta field for collections with multiple brand assignments (via Post_Primary_Brand)
- Automatic brand detection based on assigned brands, primary brand, or category association
- Full REST API support for managing collection brands

**Key Implementation Details**:

1. **Dynamic Post Type Registration**: The brand taxonomy dynamically includes the collections post type when the `newspack_collections_get_post_type_slug()` function is available:

```php
if ( function_exists( 'newspack_collections_get_post_type_slug' ) ) {
    $post_types[] = newspack_collections_get_post_type_slug();
}
```

2. **Unified Primary Brand Meta**: The `Post_Primary_Brand` meta class now handles all post types (posts, pages, popups, and collections) by using `Taxonomy::get_post_types()` instead of the static `POST_TYPES` constant. This ensures that primary brand functionality is automatically available for all supported post types.

3. **Brand Detection Logic**: Collections support all brand detection methods:
   - Single brand assignment (automatic)
   - Primary brand from `_primary_brand` meta field
   - Brand inheritance from category associations

4. **Frontend Integration**: All existing customizations (body classes, logos, theme colors, menus) automatically work with branded collections through the `Taxonomy::get_current()` method.

**Usage Examples**:

```php
// Create a branded collection
$collection_id = wp_insert_post([
    'post_title' => 'Featured Stories',
    'post_type'  => newspack_collections_get_post_type_slug(),
    'post_status' => 'publish',
]);

// Assign to brand
wp_set_post_terms( $collection_id, $brand_term_id, 'brand' );

// Set primary brand for multi-brand collections
update_post_meta( $collection_id, '_primary_brand', $primary_brand_id );

// Get current brand for a collection
$brand = Newspack_Multibranded_Site\Taxonomy::get_current_brand_for_post( $collection_id );
```

**Testing**: Comprehensive unit tests in `tests/unit-tests/test-collection-branding.php` verify:
- Brand assignment to collections
- Primary brand functionality
- Category-based brand fallback
- REST API meta registration
- Integration with existing brand detection logic

## Future Enhancements

Potential areas for future development:
1. Admin UI for managing sports data IDs in the brand editor
2. Template tags for displaying competition navigation
3. Widget for competition logos
4. Integration with specific sports data providers
5. Bulk import tools for sports data
6. Advanced filtering by sports data provider
7. Collection-specific branding UI in the Newspack Collections editor

## Migration Notes

For existing installations:
1. No data migration needed
2. Existing brands continue to function
3. New features are opt-in
4. Can gradually adopt hierarchical structure

## Support

For questions or issues:
- Review unit tests for usage examples
- Check inline documentation in PHP files
- Refer to WordPress taxonomy and CPT documentation
