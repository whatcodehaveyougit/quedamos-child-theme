<?php
/**
 * Blog module.
 *
 * Everything specific to posts: the single-article display meta consumed by
 * templates/single.html, and the related-posts query scoping.
 *
 * @package Quedamos
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/article-meta.php';
require_once __DIR__ . '/article-headings.php';
require_once __DIR__ . '/article-featured-image.php';
require_once __DIR__ . '/article-toc.php';
