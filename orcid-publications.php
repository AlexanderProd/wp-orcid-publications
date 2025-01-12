<?php
/*
Plugin Name: ORCID Publications
Description: Display publications from ORCID using a block
Version: 1.0
Author: Alexander Hörl
*/

// Prevent direct access to this file
if (!defined('ABSPATH')) {
    exit;
}

// Add action to enqueue styles
add_action('wp_enqueue_scripts', 'orcid_publications_enqueue_styles');
add_action('init', 'orcid_publications_register_block');

function orcid_publications_enqueue_styles() {
    wp_enqueue_style(
        'orcid-publications',
        plugins_url('assets/css/orcid-publications.css', __FILE__),
        array(),
        '1.0.0'
    );
}

function orcid_publications_register_block() {
    // Register our block script
    wp_register_script(
        'orcid-publications-block',
        plugins_url('assets/js/build/index.js', __FILE__),
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components')
    );

    // Register the block
    register_block_type('orcid-publications/publications', array(
        'editor_script' => 'orcid-publications-block',
        'render_callback' => 'display_orcid_publications',
        'attributes' => array(
            'orcid' => array(
                'type' => 'string',
                'default' => ''
            ),
            'titleTag' => array(
                'type' => 'string',
                'default' => 'h3'
            ),
            'fontSize' => array(
                'type' => 'number',
                'default' => 16
            ),
            'showYear' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'showType' => array(
                'type' => 'boolean',
                'default' => true
            ),
            'layout' => array(
                'type' => 'string',
                'default' => 'list'
            )
        )
    ));
}

function display_orcid_publications($attributes) {
    $orcid = $attributes['orcid'] ?? '';
    $title_tag = $attributes['titleTag'] ?? 'h3';
    $font_size = $attributes['fontSize'] ?? 16;
    $show_year = $attributes['showYear'] ?? true;
    $show_type = $attributes['showType'] ?? true;
    $layout = $attributes['layout'] ?? 'list';
    
    if (empty($orcid)) {
        return '<p>Please provide an ORCID ID</p>';
    }

    // Cache key for transient
    $cache_key = 'orcid_pubs_' . $orcid;
    
    // Check if we have cached data
    $publications = get_transient($cache_key);
    
    if (false === $publications) {
        // Fetch publications from ORCID API
        $api_url = 'https://pub.orcid.org/v3.0/' . $orcid . '/works';
        $response = wp_remote_get($api_url, array(
            'headers' => array(
                'Accept' => 'application/json'
            )
        ));

        if (is_wp_error($response)) {
            return '<p>Error fetching publications</p>';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);
        
        if (!$data || !isset($data->group)) {
            return '<p>No publications found</p>';
        }

        $publications = array();
        foreach ($data->group as $work) {
            $work_summary = $work->{'work-summary'}[0];
            
            $pub = array(
                'title' => $work_summary->title->title->value ?? '',
                'year' => $work_summary->{'publication-date'}->year->value ?? '',
                'type' => $work_summary->type ?? '',
                'url' => '',
            );

            // Get external URL if available
            if (isset($work_summary->{'external-ids'}->{'external-id'})) {
                foreach ($work_summary->{'external-ids'}->{'external-id'} as $external_id) {
                    if ($external_id->{'external-id-type'} === 'doi') {
                        $pub['url'] = 'https://doi.org/' . $external_id->{'external-id-value'};
                        break;
                    }
                }
            }

            $publications[] = $pub;
        }

        // Cache the results for 12 hours
        set_transient($cache_key, $publications, 12 * HOUR_IN_SECONDS);
    }

    // Generate HTML output with dynamic classes and styles
    $wrapper_class = 'wp-block-orcid-publications';
    $wrapper_class .= ' is-layout-' . esc_attr($layout);
    
    $style = sprintf(
        'style="--publication-font-size: %dpx;"',
        esc_attr($font_size)
    );

    $output = sprintf('<div class="%s" %s>', $wrapper_class, $style);
    
    foreach ($publications as $pub) {
        $output .= '<div class="wp-block-orcid-publications__item">';
        
        // Title with dynamic tag
        if (!empty($pub['url'])) {
            $output .= sprintf('<%1$s class="wp-block-orcid-publications__title"><a href="%2$s" target="_blank">%3$s</a></%1$s>',
                esc_attr($title_tag),
                esc_url($pub['url']),
                esc_html($pub['title'])
            );
        } else {
            $output .= sprintf('<%1$s class="wp-block-orcid-publications__title">%2$s</%1$s>',
                esc_attr($title_tag),
                esc_html($pub['title'])
            );
        }

        $meta_parts = array();
        if ($show_year && !empty($pub['year'])) {
            $meta_parts[] = sprintf(
                '<span class="wp-block-orcid-publications__year">(%s)</span>',
                esc_html($pub['year'])
            );
        }
        if ($show_type && !empty($pub['type'])) {
            $meta_parts[] = sprintf(
                '<span class="wp-block-orcid-publications__type">%s</span>',
                esc_html($pub['type'])
            );
        }
        
        if (!empty($meta_parts)) {
            $output .= '<div class="wp-block-orcid-publications__meta">';
            $output .= implode(' ', $meta_parts);
            $output .= '</div>';
        }
        
        $output .= '</div>';
    }
    
    $output .= '</div>';

    return $output;
} 