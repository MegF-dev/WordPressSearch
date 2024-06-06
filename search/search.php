<?php
function get_singular_terms($terms) {
    $singular_terms = [];
    foreach ($terms as $term) {
        if ($term === 'cookies') {
            $singular_terms[] = 'cookie';
        } elseif (substr($term, -3) === 'ies') {
            $singular_terms[] = substr($term, 0, -3) . 'y';
        } elseif (substr($term, -1) === 's' && substr($term, -2) !== 'ss') {
            $singular_terms[] = substr($term, 0, -1);
        } elseif ($term === 'teapots') {
            $singular_terms[] = 'teapot';
        }  elseif ($term === 'vouchers') {
            $singular_terms[] = 'voucher';
        }else {
            $singular_terms[] = $term;
        }
    }
    return array_unique($singular_terms);
}
function extract_terms_from_query($query) {
    $words = explode(' ', $query);
    return get_singular_terms($words);
}

function detect_and_fetch_brand_data($search_term) {
    if (!is_multi_word_brand_search($search_term)) {
        
        return; 
    }
    
    if (strpos($search_term, ' ') === false) {
        return null; 
    }

    $normalized_search_term = strtolower($search_term);
    $brands = get_terms([
        'taxonomy' => 'brand',
        'hide_empty' => false,
        'fields' => 'all'  
    ]);

    foreach ($brands as $brand) {
       
        if (strtolower($brand->name) === $normalized_search_term) {
            $is_brand_search = true;

            $matched_brands = [
                [
                    'term_id' => $brand->term_id,
                    'name' => $brand->name,
                    'permalink' => get_term_link($brand),
                ]
            ];

            $child_brand_data = [];
            $child_terms = get_terms([
                'taxonomy' => 'brand',
                'parent' => $brand->term_id,
                'hide_empty' => false
            ]);

            foreach ($child_terms as $child) {
                $products_query = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'orderby' => 'relevance', 
                    'order' => 'DESC',
                    'tax_query' => [
                        [
                            'taxonomy' => 'brand',
                            'field' => 'id',
                            'terms' => $child->term_id,
                            'include_children' => false
                        ]
                    ],
                    'meta_query' => [
                        ['key' => '_stock_status', 'value' => 'instock']
                    ]
                ]);
                $image_id = get_field('product_brand_square_image', 'brand_' . $child->term_id);
                $image_url = wp_get_attachment_image_url($image_id, 'full');

                $full_child_name =  '<span style="display: block;"> ' . $brand->name . '</span> ' . $child->name;

            
                $product_results = [];
                if ($products_query->have_posts()) {
                    while ($products_query->have_posts()) {
                        $products_query->the_post();
                        if (count($product_results) < 3) {
                            $product_results[] = [
                                'title' => '<strong>' . $brand->name . '</strong> '. get_the_title(),
                                'permalink' => get_permalink(),
                                'image' => get_the_post_thumbnail_url(null, 'thumbnail'),
                                'price' => wc_get_product(get_the_ID())->get_price_html(),
                            ];
                        }
                        
                    }
                }

                $child_brand_data[] = [
                    'term_id' => $child->term_id,
                    'name' => $full_child_name,
                    'image_url' => $image_url,
                    'permalink' => get_term_link($child),
                    'products' => $product_results 
                ];
            }

            return [
                'products' => $product_results,
                'brands' => $matched_brands,
                'child_brands' => $child_brand_data,
                'is_brand_search' => $is_brand_search,
            ];
        }
    }

    return null; 
}

function is_multi_word_brand_search($search_term) {


    $categories = get_terms([
        'taxonomy' => 'product_cat',
        'hide_empty' => false,
        'fields' => 'names'
    ]);

    $normalized_search_term = strtolower($search_term);

    foreach ($categories as $category) {
        if (strtolower($category) === $normalized_search_term) {
            return false; 
        }
    }

    $brands = get_terms([
        'taxonomy' => 'brand',
        'hide_empty' => false,
        'fields' => 'names' 
    ]);

    $multi_word_brands = array_filter($brands, function($brand) {
        return strpos($brand, ' ') !== false; 
    });
    error_log("Multi-word brands found: " . implode(", ", $multi_word_brands)); 

    $normalized_search_term = strtolower($search_term);

    foreach ($multi_word_brands as $brand) {
        if (strtolower($brand) === $normalized_search_term) {
            error_log("Search term matched multi-word brand: " . $brand);
            return true;
        }
    }
    error_log("No match found for search term as a multi-word brand."); 
    return false; 
}

function custom_ajax_product_search() {
    
    if(!isset($_POST['frontEndSearch'])) return;
    
    $raw_search_term = sanitize_text_field($_POST['term']);
    $search_terms = extract_terms_from_query($raw_search_term);
    $brand_data = detect_and_fetch_brand_data($raw_search_term);
    if ($brand_data) {
        wp_send_json($brand_data);
    }

    $sku_search_results = [];
    $sku_search_query = new WP_Query([
        'post_type' => ['product', 'product_variation'],  
        'posts_per_page' => -1,  
        'meta_query' => [
            [
                'key' => '_sku',
                'value' => $raw_search_term,
                'compare' => '=',
            ],
        ],
    ]);
    
    if ($sku_search_query->have_posts()) {
        while ($sku_search_query->have_posts()) {
            $sku_search_query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
    
            if ($product->is_type('variation')) {               
                $parent_id = $product->get_parent_id();
                $parent_product = wc_get_product($parent_id);
                $title = get_the_title($parent_id);
                $permalink = get_permalink($parent_id);
            } else {                
                $title = get_the_title();
                $permalink = get_permalink();
            }
    
            $sku_search_results[] = [
                'title' => $title,
                'permalink' => $permalink,
                'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                'price' => $product->get_price_html(),
                'brand' => '',
            ];
        }
        wp_reset_postdata();
    }
    
    if (!empty($sku_search_results)) {
        wp_send_json([
            'products' => $sku_search_results,
            'brands' => [],
            'categories' => [],
            'tags' => [],
        ]);
        wp_die();
    }
    
 /*
    *
    This code collects terms from specified taxonomies ('product_cat', 'product_tag', 'brand') 
    based on provided search terms. It retrieves up to 10 non-empty terms per taxonomy for each 
    search term, ordered by relevance. Finally, it limits the collected terms to a maximum of 
    15 unique terms per taxonomy.

    *
    */



    $collected_terms = ['product_cat' => [], 'product_tag' => [], 'brand' => [], 'search-tag' => []];

    foreach ($search_terms as $term) {
        // error_log('Search Term: ' . $term);
        foreach (['product_cat', 'product_tag', 'brand','search-tag'] as $taxonomy) {
            $args = [
                'taxonomy' => $taxonomy,
                'search' => $term,
                'orderby' => 'relevance',
                'order' => 'DESC',
                'hide_empty' => true,
                'number' => 10,
                'fields' => 'all',
                
            ];
            $terms_found_by_name = get_terms($args);

        $args['search_columns'] = ['slug'];
        $terms_found_by_slug = get_terms($args);

        $terms_found = array_unique(array_merge($terms_found_by_name, $terms_found_by_slug), SORT_REGULAR);

        if (!empty($terms_found)) {
            if (!isset($collected_terms[$taxonomy])) {
                $collected_terms[$taxonomy] = [];
            }
            $collected_terms[$taxonomy] = array_merge($collected_terms[$taxonomy], $terms_found);
        }
    }
}
    // error_log('Collected Terms: ' . print_r($collected_terms, true));
    foreach ($collected_terms as $taxonomy => $terms) {
        $collected_terms[$taxonomy] = array_slice(array_unique($terms, SORT_REGULAR), 0, 15);
    }

     /*
    *
    Adds brand name to brand category

    *
    */
    foreach ($collected_terms['brand'] as &$brand) {
        $base_parent_brand_name = '';
        $current_term_id = $brand->parent;
        
        while ($current_term_id > 0) {
            $parent_brand_term = get_term($current_term_id, 'brand');
            
            if (!is_wp_error($parent_brand_term) && !empty($parent_brand_term)) {
                $base_parent_brand_name = $parent_brand_term->name . ' > ' . $base_parent_brand_name;
                $current_term_id = $parent_brand_term->parent;
            } else {
                break;
            }
        }
        
        $brand->name = $base_parent_brand_name . $brand->name;
    }
     /*
    *
    This code retrieves the term IDs from the collected terms in the 'product_cat', 
    'product_tag', and 'brand' taxonomies. It uses the `wp_list_pluck()` 
    function to extract the 'term_id' property from each term object in the 
    respective taxonomy arrays and stores the resulting arrays of term IDs in the variables 
    `$category_ids`, `$tag_ids`, and `$brand_ids`.

    *
    */
    $category_ids = wp_list_pluck($collected_terms['product_cat'], 'term_id');
    $tag_ids = wp_list_pluck($collected_terms['product_tag'], 'term_id');
    $brand_ids = wp_list_pluck($collected_terms['brand'], 'term_id');
    $search_ids = wp_list_pluck($collected_terms['search-tag'], 'term_id');

  /*
    *
    This code retrieves product IDs associated with the collected brand terms using get_posts(). 
    It then uses those product IDs to retrieve related categories from the 'product_cat' taxonomy 
    using get_terms(). The resulting product IDs and related categories are stored in the 
    $brand_product_ids and $brand_related_categories variables, respectively.

    *
    */
    $brand_product_ids = [];
    $brand_related_categories = [];
    // error_log('Brand IDs: ' . print_r($brand_ids, true));
    if (!empty($brand_ids)) {
        $products_args = [
            'post_type' => 'product',
            'posts_per_page' => -1,  
            'orderby' => 'relevance',
            'order' => 'DESC',
            'fields' => 'ids',
            'tax_query' => [
                [
                    'taxonomy' => 'brand',
                    'field' => 'term_id',
                    'terms' => $brand_ids,
                    'include_children' => true 
                ]
            ]
        ];
        $brand_product_ids = get_posts($products_args);
    
        if (!empty($brand_product_ids)) {
            $category_args = [
                'taxonomy' => 'product_cat',
                'object_ids' => $brand_product_ids,
                'hide_empty' => false,
                'include_children' => true 
            ];
            $brand_related_categories = get_terms($category_args);
        }
    }


    
    if (!empty($brand_related_categories)) {
        foreach ($brand_related_categories as $category) {
            if (!in_array($category, $collected_terms['product_cat'], true)) {
                $collected_terms['product_cat'][] = $category;
            }
        }
    }
     /*
    *
   This code iterates over the 'product_cat' terms in the $collected_terms array and 
   enhances each category object with additional properties. It retrieves the category 
   image URL, term link, and generates a display name that includes the category's ancestors 
   if the category count is greater than 1.

    *
    */
    foreach ($collected_terms['product_cat'] as &$category) {
        $category_image = get_field('product_category_square_image', 'product_cat_' . $category->term_id);
        $category->category_image = $category_image ? $category_image['url'] : '';
        $category->link = get_term_link($category->term_id, 'product_cat');
        if ($category_counts[$category->name] > 1) {
            $ancestors = get_ancestors($category->term_id, 'product_cat');
            $ancestor_names = array();
            if (!empty($ancestors)) {
                $last_ancestor = end($ancestors);
                $ancestor_term = get_term($last_ancestor, 'product_cat');
                if ($ancestor_term && !is_wp_error($ancestor_term)) {
                    $ancestor_names[] = $ancestor_term->name;
                }
            }
            $ancestor_names[] = $category->name;
            $category->display_name = implode(' > ', array_filter($ancestor_names));
        } else {
            $category->display_name = $category->name;
        }
    }
    
    /*
    *
    The provided code performs a WordPress product search query based on a given search term, 
    category IDs, tag IDs, brand IDs, and search tag IDs. It retrieves the matching products,
     limited to a maximum of 3 results, along with their details such as title, permalink, thumbnail image, 
     price, and brand name. The code also retrieves the parent brand name for each product if available.

    */
    $search_string = implode(' ', $search_terms); 
    $product_args = [
        'post_type' => 'product',
        'posts_per_page' => -1,
        'orderby' => 'relevance',
        'order' => 'DESC',
        
        's' => $search_string,
        'meta_query' => [['key' => '_stock_status', 'value' => 'instock']],
        'tax_query' => [
            'relation' => 'OR',
            ['taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $category_ids],
            ['taxonomy' => 'product_tag', 'field' => 'term_id', 'terms' => $tag_ids],
            ['taxonomy' => 'brand', 'field' => 'term_id', 'terms' => $brand_ids],
            ['taxonomy' => 'search-tag', 'field' => 'term_id', 'terms' => $search_ids],
        ],
    ];
    $brand_args = [
        'taxonomy' => 'brand',
        'name' => $search_string,
        'hide_empty' => false,
        'number' => 1  
    ];
    
    $brand_terms = get_terms($brand_args);
    $brand_results = array();

    foreach ($brand_terms as $brand_term) {
       
        $base_parent_brand_name = '';
        $current_term_id = $brand_term->parent;
        while ($current_term_id > 0) {
            $parent_brand_term = get_term($current_term_id, 'brand');
            if (!is_wp_error($parent_brand_term) && !empty($parent_brand_term)) {
                $base_parent_brand_name = $parent_brand_term->name . " ";
                $current_term_id = $parent_brand_term->parent;
            } else {
                break;
            }
        }
     
    }
   
    $product_query = new WP_Query($product_args);
    $product_results = [];
    $matched_brand_ids = [];

    if ($product_query->have_posts()) {
        while ($product_query->have_posts()) {
            $product_query->the_post();
            $product_id = get_the_ID();
            $product = wc_get_product($product_id);
            $brand_terms = get_the_terms($product_id, 'brand');
            $base_parent_brand_name = '';
            if (!empty($brand_terms)) {
                foreach ($brand_terms as $brand_term) {
                    $matched_brand_ids[] = $brand_term->term_id;
                    if ($brand_term->parent == 0) {
                        $base_parent_brand_name = $brand_term->name;
                        break;
                    }
                }
            }
    
    
            if (count($product_results) < 3) {
                $product_results[] = array(
                    'title' => get_the_title(),
                    'permalink' => get_permalink(),
                    'image' => get_the_post_thumbnail_url($product_id, 'thumbnail'),
                    'price' => $product->get_price_html(),
                    'brand' => $base_parent_brand_name,
                );
            }
        }
    }
    $matched_brand_ids = array_unique($matched_brand_ids);

    $matched_brands = [];
    
    foreach ($matched_brand_ids as $brand_id) {
        $brand_term = get_term($brand_id, 'brand');
        if (!is_wp_error($brand_term) && !empty($brand_term) && $brand_term->parent == 0) { 
            $base_parent_brand_name = '';
            $current_term_id = $brand_term->parent;

            while ($current_term_id > 0) {
                $parent_brand_term = get_term($current_term_id, 'brand');

                if (!is_wp_error($parent_brand_term) && !empty($parent_brand_term)) {
                    $base_parent_brand_name = $parent_brand_term->name . ' > ' . $base_parent_brand_name;
                    $current_term_id = $parent_brand_term->parent;
                } else {
                    break;
                }
            }

            $brand_name = $base_parent_brand_name . $brand_term->name;
            $matched_brands[] = array(
                'name' => $brand_name,
                'permalink' => get_term_link($brand_term),
            );
        }
    }
    foreach ($collected_terms as $taxonomy => $terms) {
        $unique_terms = [];
        foreach ($terms as $term) {
            $unique_terms[$term->term_id] = $term;
        }
        $collected_terms[$taxonomy] = array_slice(array_values($unique_terms), 0, 10);
    }
    $search_words = explode(' ', $raw_search_term);
    $is_brand_search = false;
    $matched_brands = [];
    $child_brand_data = []; 
    
    foreach ($search_words as $word) {
        $brand_terms = get_terms([
            'taxonomy' => 'brand',
            'name' => $word,
            'hide_empty' => false,
            'number' => 1
        ]);
    
        if (!empty($brand_terms)) {
            $brand = reset($brand_terms); 
            if ($brand->parent == 0) { 
                $is_brand_search = true;
    
                
                $matched_brands[] = [
                    'term_id' => $brand->term_id,
                    'name' => $brand->name,
                    'permalink' => get_term_link($brand)
                ];
    
                $child_terms = get_terms([
                    'taxonomy' => 'brand',
                    'parent' => $brand->term_id,
                    'hide_empty' => false
                ]);
    
                foreach ($child_terms as $child) {
                    $products_query = new WP_Query([
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'field' => 'ids',
                    'tax_query' => [
                        [
                            'taxonomy' => 'brand',
                            'field' => 'id',
                            'terms' => $child->term_id,
                            'include_children' => false
                        ]
                    ],
                    'meta_query' => [
                        [
                            'key' => '_stock_status',
                            'value' => 'instock',
                            'compare' => '='
                        ]
                    ]
                ]);
                if ($products_query->have_posts()) {
                    $image_id = get_field('product_brand_square_image', 'brand_' . $child->term_id);
                    $image_url = wp_get_attachment_image_url($image_id, 'full');
                
                    
                   
                    $full_child_name =  '<span class="custom-search-items">' . $brand->name . '</span> ' . $child->name;
                
                    // Check if the child's full name contains the search term (case-insensitive)
                    if (stripos($full_child_name, $raw_search_term) !== false) {
                        $child_brand_data[] = [
                            'term_id' => $child->term_id,
                            'name' => $full_child_name,
                            'image_url' => $image_url,
                            'permalink' => get_term_link($child)
                        ];
                    }
                }
                }
                   
                
               
                break; // Stop the loop once a brand is found
            } else {
                error_log("Skipping child brand found in search.");
            }
        }
    }
    
    if (!$is_brand_search) {
        error_log("Normal search - Categories appear but brands disappear");
    }
    $categories = array_slice($collected_terms['product_cat'], 0, 5);
    
    wp_send_json([
        'products' => $product_results,
        'brands' => $matched_brands,
       
        'child_brands' => $child_brand_data,
        'categories' => $categories,
        'tags' => $collected_terms['product_tag'],
        'search_tag' => $collected_terms['search-tag'],
        'is_brand_search' => $is_brand_search, 
    ]);
    wp_die();
}

add_action('wp_ajax_nopriv_custom_product_search', 'custom_ajax_product_search');
add_action('wp_ajax_custom_product_search', 'custom_ajax_product_search');
