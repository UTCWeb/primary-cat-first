<?php
/**
* Plugin Name:       Primary Category First in REST API
* Plugin URI:        https://blog.utc.edu/
* Description:       Yoast or Rank Math primary category to front of categories array in REST API response for posts.
* Version:           1.0.1
* Author:            Chris Gilligan
* Author URI:        https://chrisgilligan.com
* License:           GPL-2.0+
* License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
* Text Domain:       primary-category-rest
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

/**
* Reorder categories in REST API to place primary category first.
* Supports Yoast SEO and Rank Math primary category functions.
*
* @param WP_REST_Response $data    response object.
* @param WP_Post          $post    post object.
* @param WP_REST_Request  $request request object.
* @return WP_REST_Response
*/
function pcr_reorder_rest_api_categories($data, $post, $context)
{
    // Check if the post has categories.
    if (empty($data->data['categories'])) {
        return $data;
    }

    $primary_category_id = null;
    $categories = $data->data['categories'];

    // Check for Yoast SEO primary category.
    if (class_exists('WPSEO_Primary_Term')) {
        $primary_term_obj = new WPSEO_Primary_Term('category', $post->ID);
        $primary_category_id = $primary_term_obj->get_primary_term();
    }
    // Check for Rank Math primary category.
    elseif (function_exists('rank_math_get_primary_taxonomy_id')) {
        $primary_category_id = rank_math_get_primary_taxonomy_id('category', $post->ID);
    }

    // If primary category is found, reorder array.
    if ($primary_category_id) {
        $reordered_categories = [];
        $primary_category_index = -1;

        // Find primary category in current array.
        foreach ($categories as $index => $category_id) {
            if ($category_id === $primary_category_id) {
                $primary_category_index = $index;
                break;
            }
        }

        // Move primary category to front of categories array.
        if ($primary_category_index !== -1) {
            $primary_category = array_splice($categories, $primary_category_index, 1);
            $reordered_categories = array_merge($primary_category, $categories);
            $data->data['categories'] = $reordered_categories;
        }
    }
    return $data;
}
add_filter('rest_prepare_post', 'pcr_reorder_rest_api_categories', 10, 3);
/**
Disable XML-RPC API to improve security.
*/
add_filter('xmlrpc_enabled', '__return_false');
add_filter('xmlrpc_methods', '__return_empty_array');
