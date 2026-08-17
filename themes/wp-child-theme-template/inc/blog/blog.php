<?php
/**
 * Blog module.
 *
 * Everything specific to posts: the single-article display meta consumed by
 * templates/single.html, the related-posts query scoping, and the blog
 * listing's filter row, card and page size, consumed by templates/home.html and
 * templates/category.html.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/article-meta.php';
require_once __DIR__ . '/article-author.php';
require_once __DIR__ . '/article-headings.php';
require_once __DIR__ . '/article-featured-image.php';
require_once __DIR__ . '/article-toc.php';
require_once __DIR__ . '/category-filter.php';
require_once __DIR__ . '/post-card.php';
require_once __DIR__ . '/listing-query.php';
