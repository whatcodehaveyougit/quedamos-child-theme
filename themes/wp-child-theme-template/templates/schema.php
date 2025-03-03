<?php

function output_acf_schema() {
  $schema = get_field('schema');
  if ($schema) {
    return '<script type="application/ld+json">' . wp_kses_post($schema) . '</script>';

  }
  return ''; // Return empty if no schema is set
}

add_shortcode('acf_schema', 'output_acf_schema');