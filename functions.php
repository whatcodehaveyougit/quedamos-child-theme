<?php


add_action( 'wp_enqueue_scripts', 'twentytwentyfour_child_scripts' );
function twentytwentyfour_child_scripts() {
	wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
  wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'parent-style' ) );


	wp_enqueue_style( 'parcel', get_stylesheet_directory_uri() . '/dist/styles/style.css', array(), '1.0' );
	wp_enqueue_script( 'parcel-js', get_stylesheet_directory_uri() . '/dist/scripts/scripts.js', array(), '1.0', true );
}



function custom_header_code() {
	?>
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> -->
<link href="https://cdn.jsdelivr.net/npm/boxicons@2.1.1/css/boxicons.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/remixicon@2.5.0/fonts/remixicon.css" rel="stylesheet">	<?php

}
add_action( 'wp_head', 'custom_header_code' );

