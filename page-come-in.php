<?php
/**
 * Social landing page for /come-in/.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$come_in_css_path = get_theme_file_path('assets/css/come-in.css');
wp_enqueue_style(
	'bbb-come-in',
	get_theme_file_uri('assets/css/come-in.css'),
	array('bbb-bookshelf-signup'),
	file_exists($come_in_css_path) ? (string) filemtime($come_in_css_path) : wp_get_theme()->get('Version')
);

$society_join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$society_join_url = '' !== trim((string) $society_join_url) ? (string) $society_join_url : 'https://thesmutandsentimentsociety.substack.com/subscribe';

$come_in_links = array(
	array(
		'title' => 'find your next read',
		'desc'  => 'browse by trope, spice level, or mood. your next obsession is already waiting.',
		'url'   => bbb_page_url('what-to-read-next'),
		'image' => 'assets/images/come-in/next-read.svg',
		'alt'   => '',
		'tone'  => 'library',
		'badge' => '📚 for the readers who get it',
	),
	array(
		'title' => 'take the fictional boyfriend quiz',
		'desc'  => 'find out what is wrong with you, lovingly, and share the damage.',
		'url'   => bbb_page_url('fictional-boyfriend-quiz'),
		'image' => 'assets/images/come-in/quiz.svg',
		'alt'   => '',
		'tone'  => 'quiz',
		'badge' => '💘 i got aaron warner. who will you get?',
	),
	array(
		'title' => 'browse by spice level',
		'desc'  => 'from soft slow burns to filthy little choices. choose your heat before you commit.',
		'url'   => bbb_page_url('romance-books-by-spice-level'),
		'image' => 'assets/images/come-in/spice.svg',
		'alt'   => '',
		'tone'  => 'spice',
		'badge' => '🌶 from 1 chili to 5',
	),
	array(
		'title' => 'shop bookish downloads',
		'desc'  => 'kindle inserts, reading trackers, and printables for the aesthetic romance reader.',
		'url'   => bbb_page_url('shop'),
		'image' => 'assets/images/come-in/shop.svg',
		'alt'   => '',
		'tone'  => 'shop',
		'badge' => '🛍 pretty little reader things',
	),
	array(
		'title' => 'romance book moodboards',
		'desc'  => 'pin the aesthetic, save the book, and browse moodboards by trope.',
		'url'   => home_url('/romance-book-moodboards/'),
		'image' => 'assets/images/come-in/pinterest.svg',
		'alt'   => '',
		'tone'  => 'pinterest',
		'badge' => '📌 save now, spiral later',
	),
);

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none bbb-come-in" role="main" tabindex="-1">
	<section class="bbb-come-in__shell" aria-labelledby="come-in-title">
		<div class="bbb-come-in__mast">
			<h1 id="come-in-title" class="bbb-come-in__title">you found us.</h1>
			<p class="bbb-come-in__intro">dark romance recs, a spice library, reader quizzes, and a Sunday letter that will ruin your reading life in the best way.</p>
			<a class="bbb-come-in__join" href="<?php echo esc_url($society_join_url); ?>" target="_blank" rel="noopener">
				<span class="bbb-come-in__joinLogo" aria-hidden="true">
					<img src="<?php echo esc_url(get_theme_file_uri('assets/SSS_Logo.png')); ?>" alt="" loading="lazy">
				</span>
				<span class="bbb-come-in__joinCopy">
					<span class="bbb-come-in__joinKicker">smut & sentiment society</span>
					<strong>join the society</strong>
				</span>
				<span class="bbb-come-in__joinText">weekly recs, member perks, and the full bookish experience.</span>
				<span class="bbb-come-in__joinArrow" aria-hidden="true">→</span>
			</a>
		</div>

		<div class="bbb-come-in__divider"><span>or dive straight in</span></div>

		<nav class="bbb-come-in__links" aria-label="ByBookishBabe social landing links">
			<?php foreach ($come_in_links as $item) : ?>
				<a
					class="bbb-come-in__card bbb-come-in__card--<?php echo esc_attr((string) $item['tone']); ?><?php echo !empty($item['featured']) ? ' is-featured' : ''; ?>"
					href="<?php echo esc_url((string) $item['url']); ?>"
					<?php echo !empty($item['external']) ? 'target="_blank" rel="noopener"' : ''; ?>
				>
					<span class="bbb-come-in__icon bbb-come-in__icon--<?php echo esc_attr((string) $item['tone']); ?>" aria-hidden="true">
						<img src="<?php echo esc_url(get_theme_file_uri((string) $item['image'])); ?>" alt="<?php echo esc_attr((string) ($item['alt'] ?? '')); ?>" loading="lazy">
					</span>
					<span class="bbb-come-in__cardBody">
						<strong><?php echo esc_html((string) $item['title']); ?></strong>
						<span><?php echo esc_html((string) $item['desc']); ?></span>
						<?php if (!empty($item['badge'])) : ?>
							<em><?php echo esc_html((string) $item['badge']); ?></em>
						<?php endif; ?>
					</span>
					<span class="bbb-come-in__arrow" aria-hidden="true">→</span>
				</a>
				<?php endforeach; ?>
			</nav>
	</section>
</main>

<?php
get_footer();
