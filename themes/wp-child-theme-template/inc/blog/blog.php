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
