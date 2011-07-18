<?php
/**
 * @package Single_Category_Permalink
 * @author Scott Reilly
 * @version 2.0
 */
/*
Plugin Name: Single Category Permalink
Version: 2.0
Plugin URI: http://coffee2code.com/wp-plugins/single-category-permalink
Author: Scott Reilly
Author URI: http://coffee2code.com
Description: Reduce permalinks (category or post) that include entire hierarchy of categories to just having the lowest level category.

Compatible with WordPress 1.5+, 2.0+, 2.1+, 2.2+, 2.3+, 2.5+, 2.6+, 2.7+, 2.8+, 2.9+, 3.0+, 3.1+, 3.2+.

=>> Read the accompanying readme.txt file for instructions and documentation.
=>> Also, visit the plugin's homepage for additional information and updates.
=>> Or visit: http://wordpress.org/extend/plugins/single-category-permalinks/

*/

/*
Copyright (c) 2007-2011 by Scott Reilly (aka coffee2code)

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation 
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, 
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the 
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/

if ( ! function_exists( 'c2c_single_category_postlink' ) ) :
/**
 * Returns post URI for a given post.
 *
 * If the post permalink structure includes %category%, then this function
 * kicks into gear to reduce a hierarchical category structure to its lowest
 * category.
 *
 * @param string $permalink The default URI for the post
 * @param WP_Post $post The post
 * @return string The post URI
 */
function c2c_single_category_postlink( $permalink, $post ) {
	$permalink_structure = get_option( 'permalink_structure' );

	if ( strpos( $permalink_structure, '%category%' ) !== false ) {
		$cats = get_the_category( $post->ID );
		if ( $cats )
			usort( $cats, '_usort_terms_by_ID' ); // order by ID
		$category = $cats[0]->slug;

		if ( $parent = $cats[0]->parent )
			$category = get_category_parents( $parent, false, '/', true ) . $category;

		// Now we know what the permalink component involving category hierarchy consists of.  Get rid of it.
		$permalink = str_replace( $category, $cats[0]->slug, $permalink );
	}

	return $permalink;
}
endif;

if ( ! function_exists( 'c2c_single_category_catlink' ) ) :
/**
 * Returns category URI for a given category.
 *
 * If the given category is hierarchical, then this function kicks into gear to
 * reduce a hierarchical category structure to its lowest category in the link.
 *
 * @param string $catlink The default URI for the category
 * @param int $category_id The category ID
 * @return string The category URI
 */
function c2c_single_category_catlink( $catlink, $category_id ) {
	global $wp_rewrite;
	$catlink = $wp_rewrite->get_category_permastruct();

	if ( empty( $catlink ) ) {
		$file = get_option( 'siteurl' ) . '/';
		$catlink = $file . '?cat=' . $category_id;
	} else {
		$category = &get_category( $category_id );
		if ( is_wp_error( $category ) )
			return $category;
		$category_nicename = $category->slug;

		//$catlink = str_replace('/category/', '/', $catlink);
		$catlink = str_replace( '%category%', $category_nicename, $catlink );
		$catlink = get_option( 'siteurl' ) . '/' . user_trailingslashit( $catlink, 'category' );
	}

	return $catlink;
}
endif;

if ( ! function_exists( 'c2c_single_category_redirect' ) ) :
/**
 * Redirects fully hierarchical category links to the single category link.
 *
 * @since 2.0
 *
 * @uses filter c2c_single_category_redirect_code
 */
function c2c_single_category_redirect() {
	global $wp_query, $post;
	$redirect = null;
	$category_name = isset( $wp_query->query['category_name'] ) ? $wp_query->query['category_name'] : '';

	if ( is_category() ) {
		if ( ! empty( $category_name ) && $category_name != $wp_query->query_vars['category_name'] )
			$redirect = c2c_single_category_catlink( '', $wp_query->query_vars['cat'] );
	}
	elseif ( is_single() ) {
		if ( ! empty( $category_name ) && substr_count( $category_name, '/' ) > 1 )
			$redirect = get_permalink( $post );
	}

	if ( $redirect )
		wp_redirect( $redirect, apply_filters( 'c2c_single_category_redirect_status', 302 ) );
}
endif;

add_filter( 'category_link',     'c2c_single_category_catlink', 10, 2 );
add_filter( 'post_link',         'c2c_single_category_postlink', 10, 2 );
add_filter( 'template_redirect', 'c2c_single_category_redirect' );

?>