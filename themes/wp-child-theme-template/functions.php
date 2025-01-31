<?php

add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
    // Get the file modification time for cache busting
    $child_style_version = filemtime( get_stylesheet_directory() . '/style.css' );
    $parcel_css_version = filemtime( get_stylesheet_directory() . '/dist/styles/style.css' );
    $parcel_js_version = filemtime( get_stylesheet_directory() . '/dist/scripts/scripts.js' );

    // Enqueue styles
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ), $child_style_version );
    wp_enqueue_style( 'parcel', get_stylesheet_directory_uri() . '/dist/styles/style.css', array(), $parcel_css_version );

    // Enqueue scripts
    wp_enqueue_script( 'parcel-js', get_stylesheet_directory_uri() . '/dist/scripts/scripts.js', array(), $parcel_js_version, true );
}

// Add Google site verification meta
function add_google_site_verification_meta() {
    echo '<meta name="google-site-verification" content="M0ai-YlNd-1QADH-_SSVnMMBmiSgPCzEz8ZjAUqgZho" />' . "\n";
}
add_action( 'wp_head', 'add_google_site_verification_meta' );