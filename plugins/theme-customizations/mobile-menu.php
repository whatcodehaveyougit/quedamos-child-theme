<?php

function modify_navigation_block_output($block_content, $block) {
    // Check if the block is a navigation block
    if ($block['blockName'] === 'core/navigation' && $block['attrs']['className'] === 'q-navigation-mobile') {
        // Convert block attributes to HTML
        $block_attributes = '';
        if (!empty($block['attrs']) && is_array($block['attrs'])) {
            foreach ($block['attrs'] as $attr_key => $attr_value) {
                $block_attributes .= ' ' . esc_attr($attr_key) . '="' . esc_attr($attr_value) . '"';
            }
        }

       // Your custom HTML
       $custom_html = '<div class="mobile-menu-top-bar">
        <div class="wp-block-site-logo">
            <a href="http://localhost:10009/" class="custom-logo-link" rel="home" aria-current="page">
                <img
                    width="60"
                    height="20"
                    src="http://localhost:10009/wp-content/uploads/2024/09/logo.webp"
                    class="custom-logo"
                    alt="spanish lessons edinburgh logo"
                    decoding="async"
                    srcset="http://localhost:10009/wp-content/uploads/2024/09/logo.webp 469w, http://localhost:10009/wp-content/uploads/2024/09/logo-300x102.webp 300w"
                    sizes="(max-width: 60px) 100vw, 60px">
            </a>
        </div>
        <div>
            <button
                aria-label="Close menu"
                class="close-btn"
                data-wp-on--click="actions.closeMenuOnClick"
                style="display: block;">
                <img src="http://localhost:10009/wp-content/uploads/2024/10/cross.png" />
            </button>
        </div>
</div>
';
       // Fnd the position of the first occurrence of <ul> tag
       $ul_position = strpos($block_content, '<ul');

       // Insert custom HTML before the <ul> tag
       if ($ul_position !== false) {
           $block_content = substr_replace($block_content, $custom_html, $ul_position, 0);
       } else {
           // Log if <ul> tag is not found
           error_log('Navigation Block: <ul> tag not found');
       }
    }

    return $block_content;
}
add_filter('render_block', 'modify_navigation_block_output', 10, 2);