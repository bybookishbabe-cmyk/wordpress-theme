<?php
/**
 * Template Name: My Notes
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, false);
if (function_exists('bbb_enqueue_css')) {
	bbb_enqueue_css('bbb-sss-library', 'assets/css/sss-library.css');
} else {
	wp_enqueue_style('bbb-sss-library', get_template_directory_uri() . '/assets/css/sss-library.css', array(), wp_get_theme()->get('Version'));
}
if (function_exists('bbb_enqueue_js')) {
	bbb_enqueue_js('bbb-sss-library', 'assets/js/sss-library.js', array('bbb-supabase'), true);
} else {
	wp_enqueue_script('bbb-sss-library', get_template_directory_uri() . '/assets/js/sss-library.js', array('bbb-supabase'), wp_get_theme()->get('Version'), true);
}

get_header();

$has_notes_access = function_exists('bbb_reader_can_use_notes')
	? bbb_reader_can_use_notes()
	: (function_exists('bbb_reader_has_member_identity') && bbb_reader_has_member_identity());
?>

<main class="sss-lib sss-lib--journal<?php echo $has_notes_access ? ' sss-lib--society-unlocked' : ''; ?>" id="sss-lib-public" data-sss-lib="<?php echo esc_attr($has_notes_access ? 'society' : 'public'); ?>">
	<section class="sss-lib__wrap bbb-reader-journal" data-reader-journal>
		<div class="bbb-reader-journal__head">
			<p class="bbb-reader-journal__kicker">your reading journal</p>
			<h1>the margin notes you kept</h1>
			<p class="bbb-reader-journal__privacy">your notes are completely private — only you can see them.</p>
			<label class="bbb-reader-journal__search">
				<span class="screen-reader-text">search your notes</span>
				<input type="search" data-reader-journal-search placeholder="search by book title, author, or a line you wrote">
			</label>
		</div>
		<div class="bbb-reader-journal__list" data-reader-journal-list>
			<div class="bbb-reader-journal__empty">looking for your private notes...</div>
		</div>
	</section>
</main>

<?php get_footer(); ?>
