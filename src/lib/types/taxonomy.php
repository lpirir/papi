<?php

/**
 * Get taxonomy type id by term id.
 *
 * @param  int $term_id
 *
 * @return string
 */
function papi_get_taxonomy_type_id( $term_id = 0 ) {
	return papi_get_entry_type_id( $term_id, 'term' );
}

/**
 * Get the taxonomy type name.
 *
 * @param  int $term_id
 *
 * @return string
 */
function papi_get_taxonomy_type_name( $term_id = 0 ) {
	$term_id = papi_get_term_id( $term_id );

	if ( empty( $term_id ) ) {
		return '';
	}

	$entry_type_id = papi_get_taxonomy_type_id( $term_id );

	if ( empty( $entry_type_id ) ) {
		return '';
	}

	$entry_type = papi_get_entry_type_by_id( $entry_type_id );

	if ( empty( $entry_type ) ) {
		return '';
	}

	return $entry_type->name;
}

/**
 * Load the entry type id on a taxonomy.
 *
 * @param  string $entry_type_id
 * @param  string $type
 *
 * @return string
 */
function papi_load_taxonomy_type_id( $entry_type_id = '', $type = 'term' ) {
	if ( $type !== 'term' ) {
		return $entry_type_id;
	}

	$key      = papi_get_page_type_key();
	$term_id  = papi_get_term_id();
	$taxonomy = papi_get_taxonomy( $term_id );

	// If we have a term id we can load the entry type id
	// from the term.
	if ( $term_id > 0 && papi_supports_term_meta() ) {
		$meta_value    = get_term_meta( $term_id, $key, true );
		$entry_type_id = empty( $meta_value ) ? '' : $meta_value;
	}

	// Load entry type id from the container if it exists.
	if ( empty( $entry_type_id ) ) {
		$key = sprintf( 'entry_type_id.taxonomy.%s', $taxonomy );

		if ( papi()->exists( $key )  ) {
			return papi()->make( $key );
		}
	}

	return $entry_type_id;
}

add_filter( 'papi/entry_type_id', 'papi_load_taxonomy_type_id', 10, 2 );

/**
 * Get all taxonomies Papi should work with.
 *
 * @return array
 */
function papi_get_taxonomies() {
	$taxonomies  = [];
	$entry_types = papi_get_all_entry_types( [
		'types' => 'taxonomy'
	] );

	foreach ( $entry_types as $entry_type ) {
		$taxonomies = array_merge( $taxonomies, papi_to_array( $entry_type->taxonomy ) );
	}

	return array_unique( $taxonomies );
}

/**
 * Set taxonomy type to a term.
 *
 * @param  mixed  $term_id
 * @param  string $taxonomy_type
 *
 * @return bool
 */
function papi_set_taxonomy_type_id( $term_id, $taxonomy_type ) {
	if ( papi_entry_type_exists( $taxonomy_type ) ) {
		return update_term_meta( papi_get_term_id( $term_id ), papi_get_page_type_key(), $taxonomy_type );
	}

	return false;
}

/**
 * Echo the taxonomy type name.
 *
 * @param  int $term_id
 *
 * @return string
 */
function the_papi_taxonomy_type_name( $term_id = 0 ) {
	echo papi_get_taxonomy_type_name( $term_id );
}