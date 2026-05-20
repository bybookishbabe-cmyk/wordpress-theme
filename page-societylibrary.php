<?php
/**
 * Template Name: Society Main Dashboard
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

get_header();
bbb_render_section('sss-member-dashboard');
bbb_render_component('sss-folder-tabs');
get_footer();
