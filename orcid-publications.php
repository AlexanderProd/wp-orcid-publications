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
            )
        )
    ));
}

function display_orcid_publications($attributes) {
    $orcid = $attributes['orcid'] ?? '';
    
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

    // Generate HTML output
    $output = '<div class="orcid-publications">';
    
    foreach ($publications as $pub) {
        $output .= '<div class="publication">';
        if (!empty($pub['url'])) {
            $output .= '<h3><a href="' . esc_url($pub['url']) . '" target="_blank">' . 
                      esc_html($pub['title']) . '</a></h3>';
        } else {
            $output .= '<h3>' . esc_html($pub['title']) . '</h3>';
        }
        $output .= '<p>';
        if (!empty($pub['year'])) {
            $output .= '<span class="year">(' . esc_html($pub['year']) . ')</span> ';
        }
        if (!empty($pub['type'])) {
            $output .= '<span class="type">' . esc_html($pub['type']) . '</span>';
        }
        $output .= '</p>';
        $output .= '</div>';
    }
    
    $output .= '</div>';

    return $output;
} 