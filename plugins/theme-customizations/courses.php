<?php

function course_query_shortcode($atts) {
	// Shortcode attributes
	$atts = shortcode_atts(array(
			'post_type' => 'course',
			'posts_per_page' => -1,
			'tag' => 'one-to-one', // Default tag to filter by
	), $atts, 'course_query');

	// Arguments for WP_Query
	$args = array(
			'post_type' => $atts['post_type'],
			'posts_per_page' => intval($atts['posts_per_page']),
			'tax_query' => array(
					array(
							'taxonomy' => 'post_tag', // Adjust if using a custom taxonomy
							'field'    => 'slug',
							'terms'    => $atts['tag'],
					),
			),
	);

	// Custom Query
	$query = new WP_Query($args);

	// Start output buffering
	ob_start();

	// The Loop
	if ($query->have_posts()) {
			while ($query->have_posts()) {
					$query->the_post();
					$post_id = get_the_ID();
					$permalink = get_the_permalink();
					$featured_image = get_the_post_thumbnail($post_id, 'full', array('class' => 'card-featured-image'));
					$course_description = get_field('course_description', $post_id);
					?>
					<a href="<?php echo esc_url($permalink); ?>" class="course-item">
							<?php echo $featured_image; ?>
							<div class="course-content">
									<h2><?php the_title(); ?></h2>
									<?php
									// Get the tags associated with the post
									$tags = get_the_terms($post_id, 'post_tag'); // Adjust taxonomy if needed

									if (!empty($tags) && !is_wp_error($tags)) {
											echo '<section class="custom-post-tags">';

											foreach ($tags as $tag) {
													echo '<div class="tag-item">' . esc_html($tag->name) . '</div>';
											}

											echo '</section>';
									}
									?>
									<p><?php echo esc_html($course_description); ?></p>
							</div>
					</a>
					<?php
			}
			wp_reset_postdata();
	} else {
			echo 'No courses found.';
	}

	// Return the buffered content
	return ob_get_clean();
}

// Register the shortcode
add_shortcode('course_query', 'course_query_shortcode');
