<?php
/**
 * Template Name: SSS Made For You
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$bbb_mfy_local_preview_mode = isset($_GET['mfy_preview']) ? (string) wp_unslash($_GET['mfy_preview']) : '';
$bbb_mfy_local_preview = function_exists('bbb_reader_is_local_request')
	&& bbb_reader_is_local_request()
	&& in_array($bbb_mfy_local_preview_mode, array('1', 'free', 'paid'), true);

// Paid members: full dashboard. Free members (email on file, no paid sub): quiz + gated results.
// Non-members: hard locked-preview gate — same as before.
$bbb_mfy_is_paid = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
$bbb_mfy_is_free = !$bbb_mfy_is_paid
	&& function_exists('bbb_society_reader_has_member_access')
	&& bbb_society_reader_has_member_access();

if ($bbb_mfy_local_preview) {
	$bbb_mfy_is_paid = 'paid' === $bbb_mfy_local_preview_mode;
	$bbb_mfy_is_free = !$bbb_mfy_is_paid;
}

if (!$bbb_mfy_local_preview && !$bbb_mfy_is_paid && !$bbb_mfy_is_free) {
	get_header();
	if (function_exists('bbb_society_render_locked_preview_page')) {
		bbb_society_render_locked_preview_page(
			array(
				'access'      => 'paid',
				'kicker'      => 'paid society preview',
				'title'       => 'made for you',
				'intro'       => 'preview the personalized reader dashboard before unlocking the recommendations and mood logic.',
				'panel_title' => 'upgrade to open your dashboard',
				'panel_copy'  => 'paid society members get made-for-you reader logic, mood-based recommendations, and smarter next-read picks.',
				'items'       => array(
					'personal reader pattern summary',
					'mood-based next-read picks',
					'society-only recommendation logic',
				),
			)
		);
	}
	get_footer();
	return;
}

if (!function_exists('bbb_made_for_you_books')) {
	function bbb_made_for_you_decode_text(string $value): string {
		$decoded = $value;
		for ($i = 0; $i < 3; $i++) {
			$next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
			if ($next === $decoded) {
				break;
			}
			$decoded = $next;
		}

		return $decoded;
	}

	function bbb_made_for_you_book_can_surface_quotes(int $book_id): bool {
		if ($book_id <= 0 || 'publish' !== get_post_status($book_id)) {
			return false;
		}
		if (function_exists('bbb_book_is_publicly_visible') && !bbb_book_is_publicly_visible($book_id)) {
			return false;
		}
		if (function_exists('bbb_book_is_hidden') && bbb_book_is_hidden($book_id)) {
			return false;
		}
		if (function_exists('sss_book_is_visible') && !sss_book_is_visible($book_id)) {
			return false;
		}

		return true;
	}

	function bbb_made_for_you_boyfriend_name_for_book(int $book_id, string $boyfriend_name): string {
		$boyfriend_name = trim($boyfriend_name);
		if ('' === $boyfriend_name || $book_id <= 0 || !post_type_exists('bbb_boyfriend')) {
			return $boyfriend_name;
		}

		$profiles = get_posts(
			array(
				'post_type'              => 'bbb_boyfriend',
				'post_status'            => 'publish',
				's'                      => $boyfriend_name,
				'posts_per_page'         => 8,
				'no_found_rows'          => true,
				'update_post_meta_cache' => true,
				'update_post_term_cache' => false,
			)
		);
		$matched_different_book = false;

		foreach ($profiles as $profile) {
			if (!$profile instanceof WP_Post || sanitize_title(get_the_title($profile)) !== sanitize_title($boyfriend_name)) {
				continue;
			}

			$profile_book_ids = function_exists('bbb_fictional_boyfriend_book_ids')
				? array_map('absint', bbb_fictional_boyfriend_book_ids((int) $profile->ID))
				: array();

			if (!$profile_book_ids) {
				return $boyfriend_name;
			}

			if (in_array($book_id, $profile_book_ids, true)) {
				return $boyfriend_name;
			}

			$matched_different_book = true;
		}

		return $matched_different_book ? '' : $boyfriend_name;
	}

	function bbb_made_for_you_books(): array {
		if (!function_exists('bbb_books_like_all_visible_books') || !function_exists('bbb_books_like_book_data')) {
			return array();
		}

		$books = array();
		foreach (bbb_books_like_all_visible_books() as $book_post) {
			if (!$book_post instanceof WP_Post) {
				continue;
			}

			$data   = bbb_books_like_book_data((int) $book_post->ID);
			$tropes = array();
			$boyfriend_name = bbb_made_for_you_boyfriend_name_for_book(
				(int) $book_post->ID,
				bbb_made_for_you_decode_text((string) ($data['boyfriend_name'] ?? ''))
			);
			foreach ((array) ($data['tropes'] ?? array()) as $trope) {
				$name = (string) ($trope['name'] ?? '');
				if ('' !== $name) {
					$tropes[] = $name;
				}
			}

			$books[] = array(
				'id'              => (int) $book_post->ID,
				'handle'          => (string) ($data['handle'] ?? $book_post->post_name),
				'title'           => bbb_made_for_you_decode_text((string) ($data['title'] ?? get_the_title($book_post))),
				'author'          => bbb_made_for_you_decode_text((string) ($data['author'] ?? '')),
				'cover'           => function_exists('bbb_get_book_cover_url') ? (string) bbb_get_book_cover_url((int) $book_post->ID) : '',
				'shelf'           => (string) ($data['shelf']['name'] ?? ''),
				'boyfriend_name'  => $boyfriend_name,
				'boyfriend_type'  => (string) ($data['boyfriend'] ?? ''),
				'spice'           => (int) ($data['spice'] ?? 0),
				'tension'         => (int) ($data['tension'] ?? 0),
				'damage'          => (int) ($data['damage'] ?? 0),
				'darkness'        => (int) ($data['darkness'] ?? 0),
				'yearning'        => (int) ($data['yearning'] ?? 0),
				'ku'              => !empty($data['ku']),
				'tropes'          => $tropes,
				'newsletter'      => (string) ($data['newsletter'] ?? ''),
				'most_like'       => array_values(array_filter(array_map('strval', (array) ($data['most_like_handles'] ?? array())))),
			);
		}

		return $books;
	}
}

if (!function_exists('bbb_made_for_you_quotes')) {
	function bbb_made_for_you_quotes(array $books): array {
		if (!post_type_exists('sss_quote')) {
			return array();
		}

		$books_by_id     = array();
		$books_by_handle = array();
		foreach ($books as $book) {
			$books_by_id[(int) $book['id']] = $book;
			if (!empty($book['handle'])) {
				$books_by_handle[(string) $book['handle']] = $book;
			}
		}

		$quotes = get_posts(
			array(
				'post_type'      => 'sss_quote',
				'post_status'    => 'publish',
				'posts_per_page' => 75,
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		$out = array();
		foreach ($quotes as $quote) {
			if (!$quote instanceof WP_Post) {
				continue;
			}

			$text = trim((string) get_post_meta($quote->ID, '_quote_text', true));
			if ('' === $text) {
				$text = trim((string) get_post_meta($quote->ID, 'quote', true));
			}
			if ('' === $text) {
				$text = trim(wp_strip_all_tags($quote->post_content));
			}
			if ('' === $text) {
				continue;
			}

			$book_id = max(
				(int) get_post_meta($quote->ID, '_quote_book_id', true),
				(int) get_post_meta($quote->ID, '_quote_library_book_id', true),
				(int) get_post_meta($quote->ID, 'book_id', true),
				(int) get_post_meta($quote->ID, 'library_book_id', true)
			);
			if ($book_id > 0 && !bbb_made_for_you_book_can_surface_quotes($book_id)) {
				continue;
			}
			$handle  = (string) get_post_meta($quote->ID, '_quote_book_handle', true);
			$handle  = '' !== $handle ? $handle : (string) get_post_meta($quote->ID, 'book_handle', true);
			$book    = $books_by_id[$book_id] ?? $books_by_handle[$handle] ?? null;

			if (!$book) {
				continue;
			}

			$shelf = strtolower((string) ($book['shelf'] ?? ''));
			$theme = 'gray';
			if (str_contains($shelf, 'fantasy') || str_contains($shelf, 'romantasy')) {
				$theme = 'blue';
			} elseif (str_contains($shelf, 'dark') || str_contains($shelf, 'private')) {
				$theme = 'red';
			} elseif (str_contains($shelf, 'soft') || str_contains($shelf, 'sentimental') || str_contains($shelf, 'starter')) {
				$theme = 'yellow';
			}

			$out[] = array(
				'handle' => (string) $book['handle'],
				'title'  => bbb_made_for_you_decode_text((string) $book['title']),
				'author' => bbb_made_for_you_decode_text((string) $book['author']),
				'quote'  => bbb_made_for_you_decode_text(wp_strip_all_tags($text)),
				'theme'  => $theme,
			);
		}

		return $out;
	}
}

if (!function_exists('bbb_made_for_you_newsletters')) {
	function bbb_made_for_you_newsletters(): array {
		if (!function_exists('bbb_society_get_newsletter_issues')) {
			return array();
		}

		$out = array();
		foreach (bbb_society_get_newsletter_issues(24) as $issue) {
			if (!$issue instanceof WP_Post) {
				continue;
			}

			$image = function_exists('bbb_society_newsletter_issue_image')
				? bbb_society_newsletter_issue_image($issue)
				: array('url' => '', 'alt' => '');

			$out[] = array(
				'title'   => get_the_title($issue),
				'url'     => function_exists('bbb_society_newsletter_issue_url') ? bbb_society_newsletter_issue_url($issue) : get_permalink($issue),
				'summary' => function_exists('bbb_society_newsletter_issue_summary') ? bbb_society_newsletter_issue_summary($issue) : '',
				'image'   => (string) ($image['url'] ?? ''),
				'alt'     => (string) ($image['alt'] ?? ''),
			);
		}

		return $out;
	}
}

if (!function_exists('bbb_made_for_you_blog_posts')) {
	function bbb_made_for_you_blog_posts(): array {
		$query = new WP_Query(array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 36,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		));

		$out = array();
		foreach ($query->posts as $post) {
			if (!$post instanceof WP_Post) {
				continue;
			}

			$image_id = get_post_thumbnail_id($post);
			$image = $image_id ? wp_get_attachment_image_src($image_id, 'medium_large') : false;
			$categories = wp_get_post_terms($post->ID, 'category', array('fields' => 'names'));
			$tags = wp_get_post_terms($post->ID, 'post_tag', array('fields' => 'names'));
			$terms = array_merge(
				is_wp_error($categories) ? array() : $categories,
				is_wp_error($tags) ? array() : $tags
			);

			$out[] = array(
				'title'   => get_the_title($post),
				'url'     => get_permalink($post),
				'summary' => wp_trim_words(get_the_excerpt($post), 22),
				'image'   => $image ? (string) $image[0] : '',
				'alt'     => $image_id ? (string) get_post_meta($image_id, '_wp_attachment_image_alt', true) : '',
				'terms'   => array_values(array_filter(array_map('strval', $terms))),
			);
		}
		wp_reset_postdata();

		return $out;
	}
}

if (!function_exists('bbb_made_for_you_fallback_next_card')) {
	function bbb_made_for_you_fallback_next_card(array $book, int $index): string {
		$labels = array('our pick', 'backup plan', 'wildcard');
		$colors = array(
			array('#F4C0D1', '#993556'),
			array('#FBEAF0', '#72243E'),
			array('#ED93B1', '#72243E'),
		);
		$color = $colors[$index % count($colors)];
		$title = (string) ($book['title'] ?? 'your next read');
		$author = (string) ($book['author'] ?? '');
		$cover = (string) ($book['cover'] ?? '');
		$spice = max(0, min(5, (int) ($book['spice'] ?? 0)));
		$tropes = array_slice(array_values(array_filter(array_map('strval', (array) ($book['tropes'] ?? array())))), 0, 3);

		ob_start();
		?>
		<article class="sss-mfy__nextOpinionCard<?php echo 0 === $index ? ' sss-mfy__nextOpinionCard--primary' : ''; ?>">
			<div class="sss-mfy__nextOpinionCover" style="--mfy-rec-bg:<?php echo esc_attr($color[0]); ?>;--mfy-rec-ink:<?php echo esc_attr($color[1]); ?>">
				<?php if ('' !== $cover) : ?>
					<span class="sss-lib__coverWrap"><img class="sss-lib__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title . ' book cover'); ?>" loading="lazy" decoding="async"></span>
				<?php else : ?>
					<div class="sss-mfy__fallbackCover sss-mfy__fallbackCover--mini"><span><?php echo esc_html($title); ?></span></div>
				<?php endif; ?>
			</div>
			<div class="sss-mfy__nextOpinionBody">
				<span class="sss-mfy__nextOpinionLabel"><?php echo esc_html($labels[$index] ?? 'made for you'); ?></span>
				<strong><?php echo esc_html($title); ?></strong>
				<?php if ('' !== $author) : ?>
					<small><?php echo esc_html($author); ?></small>
				<?php endif; ?>
				<div class="sss-mfy__dashSpice" aria-label="<?php echo esc_attr((string) $spice); ?> out of 5 spice">
					<?php for ($i = 1; $i <= 5; $i++) : ?>
						<span class="<?php echo $i <= $spice ? 'is-on' : ''; ?>"></span>
					<?php endfor; ?>
				</div>
				<p><?php echo esc_html(0 === $index ? 'matched to your reader type and spice lane.' : 'kept close because it fits the reader pattern.'); ?></p>
				<div class="sss-mfy__nextOpinionReasons">
					<?php foreach ($tropes as $trope) : ?>
						<span><?php echo esc_html($trope); ?></span>
					<?php endforeach; ?>
					<?php if (empty($tropes)) : ?>
						<span>profile pick</span>
					<?php endif; ?>
				</div>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}
}

if (!function_exists('bbb_made_for_you_token')) {
	function bbb_made_for_you_token(string $value): string {
		$value = strtolower(trim($value));
		return (string) preg_replace('/[^a-z0-9]+/', ' ', $value);
	}
}

if (!function_exists('bbb_made_for_you_book_has_token')) {
	function bbb_made_for_you_book_has_token(array $book, array $needles): bool {
		$haystack = bbb_made_for_you_token(
			(string) ($book['shelf'] ?? '') . ' ' . implode(' ', array_map('strval', (array) ($book['tropes'] ?? array())))
		);
		foreach ($needles as $needle) {
			if ('' !== $needle && false !== strpos($haystack, $needle)) {
				return true;
			}
		}
		return false;
	}
}

if (!function_exists('bbb_made_for_you_soft_profile_active')) {
	function bbb_made_for_you_soft_profile_active(array $profile): bool {
		$reader_type = (string) ($profile['reader_type_prior'] ?? $profile['theme'] ?? '');
		$heat_lane   = (string) ($profile['heat_lane'] ?? '');
		$spice_dial  = (string) ($profile['spice_dial'] ?? '');
		return 'sweet_romance_devotee' === $reader_type || 'closed' === $heat_lane || 'soft_open_door' === $spice_dial;
	}
}

if (!function_exists('bbb_made_for_you_soft_incompatible_book')) {
	function bbb_made_for_you_soft_incompatible_book(array $book): bool {
		if ((int) ($book['spice'] ?? 0) > 2 || (int) ($book['darkness'] ?? 0) >= 2) {
			return true;
		}
		return bbb_made_for_you_book_has_token(
			$book,
			array('dark romance', 'dark academia', 'mafia', 'bully romance', 'touch her and die', 'stalker', 'obsession', 'morally gray')
		);
	}
}

if (!function_exists('bbb_made_for_you_soft_fallback_score')) {
	function bbb_made_for_you_soft_fallback_score(array $book): int {
		$score = 0;
		if ((int) ($book['spice'] ?? 0) <= 1) {
			$score += 6;
		} elseif ((int) ($book['spice'] ?? 0) <= 2) {
			$score += 3;
		}
		if ((int) ($book['darkness'] ?? 0) <= 1) {
			$score += 4;
		}
		if (bbb_made_for_you_book_has_token($book, array('friends to lovers', 'found family', 'small town', 'grumpy sunshine', 'sports romance', 'sweet'))) {
			$score += 8;
		}
		if (bbb_made_for_you_book_has_token($book, array('enemies to lovers', 'forbidden', 'forced proximity'))) {
			$score -= 2;
		}
		return $score;
	}
}

if (!function_exists('bbb_made_for_you_next_fallback_books')) {
	function bbb_made_for_you_next_fallback_books(array $books, array $profile): array {
		$soft_profile = bbb_made_for_you_soft_profile_active($profile);
		$candidates   = array_values(
			array_filter(
				$books,
				static function ($book) use ($soft_profile) {
					return is_array($book) && (!$soft_profile || !bbb_made_for_you_soft_incompatible_book($book));
				}
			)
		);

		if ($soft_profile) {
			usort(
				$candidates,
				static function (array $a, array $b): int {
					$a_score = bbb_made_for_you_soft_fallback_score($a);
					$b_score = bbb_made_for_you_soft_fallback_score($b);
					if ($a_score === $b_score) {
						return ((int) ($a['spice'] ?? 0)) <=> ((int) ($b['spice'] ?? 0));
					}
					return $b_score <=> $a_score;
				}
			);
		}

		$fallbacks = array_slice($candidates, 0, 3);
		if (count($fallbacks) < 3) {
			$fallbacks = array_merge(
				$fallbacks,
				array_slice(
					array(
						array(
							'title'  => $soft_profile ? 'soft romance starter pick' : 'reader profile starter pick',
							'author' => 'made for your profile',
							'spice'  => $soft_profile ? 1 : 3,
							'tropes' => $soft_profile ? array('friends to lovers', 'low spice', 'comfort read') : array('favorite trope', 'spice match', 'profile pick'),
						),
						array(
							'title'  => 'slow burn backup plan',
							'author' => 'made for your profile',
							'spice'  => 2,
							'tropes' => array('slow burn', 'reader type', 'next read'),
						),
						array(
							'title'  => $soft_profile ? 'sweet shelf wildcard' : 'wildcard shelf match',
							'author' => 'made for your profile',
							'spice'  => $soft_profile ? 1 : 4,
							'tropes' => $soft_profile ? array('sweet romance', 'soft open door', 'profile pick') : array('wildcard', 'tension', 'profile pick'),
						),
					),
					0,
					3 - count($fallbacks)
				)
			);
		}
		return $fallbacks;
	}
}

if (!function_exists('bbb_made_for_you_profile_reader_type')) {
	function bbb_made_for_you_profile_reader_type(array $profile): string {
		return sanitize_key((string) ($profile['reader_type_prior'] ?? $profile['theme'] ?? 'romance_reader')) ?: 'romance_reader';
	}
}

if (!function_exists('bbb_made_for_you_reader_type_signals')) {
	function bbb_made_for_you_reader_type_signals(string $reader_type): array {
		$signals = array(
			'chaos_reader'           => array('why choose', 'forbidden romance', 'chaos', 'high spice', 'spicy'),
			'dark_romance_girlie'    => array('dark romance', 'dark academia', 'mafia romance', 'mafia', 'touch her and die', 'morally gray', 'stalker', 'obsession', 'bully romance'),
			'fantasy_girlie'         => array('romantasy', 'fantasy romance', 'fantasy', 'fated mates', 'magic'),
			'jersey_chaser'          => array('sports romance', 'hockey romance', 'baseball romance', 'football romance', 'athlete'),
			'slow_burn_girlie'       => array('slow burn', 'he falls first', 'second chance', 'yearning'),
			'tension_addict'         => array('enemies to lovers', 'forced proximity', 'grumpy sunshine', 'banter'),
			'fake_dating_fanatic'    => array('fake dating', 'one bed', 'marriage of convenience', 'contract'),
			'sweet_romance_devotee'  => array('friends to lovers', 'small town', 'found family', 'sweet romance', 'comfort', 'soft'),
			'romance_reader'         => array('romance', 'trope', 'reader'),
		);
		return $signals[$reader_type] ?? $signals['romance_reader'];
	}
}

if (!function_exists('bbb_made_for_you_reader_type_fallback_links')) {
	function bbb_made_for_you_trope_emoji_key(string $trope): string {
		$key = trim(strtolower($trope));
		$key = str_replace('&', 'and', $key);
		$key = preg_replace('/[^a-z0-9]+/', '-', $key) ?: '';
		$key = trim($key, '-');
		$aliases = array(
			'forbidden-romance' => 'forbidden-love',
			'small-town-romance' => 'small-town',
			'grumpy-sunshine' => 'grumpy-x-sunshine',
		);

		return $aliases[$key] ?? ($key ?: 'romance');
	}

	function bbb_made_for_you_reader_type_fallback_links(array $profile): array {
		$reader_type = bbb_made_for_you_profile_reader_type($profile);
		$favorite_trope = bbb_made_for_you_token((string) ($profile['favorite_trope'] ?? 'romance'));
		$map = array(
			'chaos_reader' => array(
				array('why choose romance guide', 'maximum plot complications', '/romance-trope-dictionary/#why-choose', 'why choose'),
				array('forbidden romance picks', 'because restraint is optional', '/romance-trope-dictionary/#forbidden-love', 'forbidden romance'),
				array('books like your current chaos', 'same-energy rec lists', '/if-you-liked-pages/', 'chaos romance'),
			),
			'dark_romance_girlie' => array(
				array('dark romance guide', 'danger, obsession, devotion', '/romance-trope-dictionary/#dark-romance', 'dark romance'),
				array('mafia romance picks', 'morally gray and possessive', '/romance-trope-dictionary/#mafia-romance', 'mafia romance'),
				array('touch her and die books', 'protective menace energy', '/romance-trope-dictionary/#touch-her-and-die', 'touch her and die'),
			),
			'fantasy_girlie' => array(
				array('romantasy reading guide', 'magic, stakes, yearning', '/romance-trope-dictionary/#romantasy', 'romantasy'),
				array('fated mates picks', 'destiny with teeth', '/romance-trope-dictionary/#fated-mates', 'fated mates'),
				array('fantasy romance moodboards', 'visuals for your next spiral', '/romance-book-moodboards/', 'fantasy romance'),
			),
			'jersey_chaser' => array(
				array('sports romance guide', 'athletes with feelings', '/romance-trope-dictionary/#sports-romance', 'sports romance'),
				array('hockey romance picks', 'rink lights and soft landings', '/romance-trope-dictionary/#hockey-romance', 'hockey romance'),
				array('baseball romance picks', 'dugout longing, obviously', '/romance-trope-dictionary/#baseball-romance', 'baseball romance'),
			),
			'slow_burn_girlie' => array(
				array('slow burn romance guide', 'the almost-touch economy', '/romance-trope-dictionary/#slow-burn', 'slow burn'),
				array('he falls first picks', 'yearning with receipts', '/romance-trope-dictionary/#he-falls-first', 'he falls first'),
				array('second chance romance', 'old feelings, new damage', '/romance-trope-dictionary/#second-chance', 'second chance romance'),
			),
			'tension_addict' => array(
				array('enemies to lovers guide', 'banter, friction, payoff', '/romance-trope-dictionary/#enemies-to-lovers', 'enemies to lovers'),
				array('forced proximity picks', 'one room, too much tension', '/romance-trope-dictionary/#forced-proximity', 'forced proximity'),
				array('grumpy sunshine books', 'the argument becomes affection', '/romance-trope-dictionary/#grumpy-sunshine', 'grumpy sunshine'),
			),
			'fake_dating_fanatic' => array(
				array('fake dating romance guide', 'public lie, private feelings', '/romance-trope-dictionary/#fake-dating', 'fake dating'),
				array('one bed picks', 'logistical romance problems', '/romance-trope-dictionary/#one-bed', 'one bed'),
				array('marriage of convenience', 'contract first, feelings later', '/romance-trope-dictionary/#marriage-of-convenience', 'marriage of convenience'),
			),
			'sweet_romance_devotee' => array(
				array('friends to lovers guide', 'softness that still aches', '/romance-trope-dictionary/#friends-to-lovers', 'friends to lovers'),
				array('small town romance picks', 'comfort, gossip, porch lights', '/romance-trope-dictionary/#small-town', 'small town romance'),
				array('found family romance', 'tender chaos, chosen people', '/romance-trope-dictionary/#found-family', 'found family'),
			),
			'romance_reader' => array(
				array('what to read next', 'fresh picks from your profile', '/what-to-read-next/', $favorite_trope),
				array('romance trope dictionary', 'browse your strongest signal', '/romance-trope-dictionary/', $favorite_trope),
				array('book moodboards', 'find the visual lane', '/romance-book-moodboards/', $favorite_trope),
			),
		);

		return array_map(
			static function (array $item): array {
				$path = (string) $item[2];
				$trope = (string) ($item[3] ?? '');
				$is_trope_page = false !== strpos($path, '/romance-trope-dictionary/');
				if ($is_trope_page && false === strpos($path, '#') && '' !== $trope) {
					$path = '/romance-trope-dictionary/#' . bbb_made_for_you_trope_emoji_key($trope);
				}
				return array(
					'badge'   => $is_trope_page ? 'trope page' : 'blog pick',
					'title'   => $item[0],
					'summary' => $item[1],
					'url'     => home_url($path),
					'image'   => '',
					'alt'     => '',
					'emoji'   => $is_trope_page ? bbb_made_for_you_trope_emoji_key($trope) : '',
				);
			},
			$map[$reader_type] ?? $map['romance_reader']
		);
	}
}

if (!function_exists('bbb_made_for_you_content_match_score')) {
	function bbb_made_for_you_content_match_score(array $item, array $profile): int {
		$reader_type = bbb_made_for_you_profile_reader_type($profile);
		$favorite_trope = bbb_made_for_you_token((string) ($profile['favorite_trope'] ?? ''));
		$haystack = bbb_made_for_you_token(
			(string) ($item['title'] ?? '') . ' ' .
			(string) ($item['summary'] ?? '') . ' ' .
			implode(' ', array_map('strval', (array) ($item['terms'] ?? array())))
		);
		$score = 0;

		foreach (bbb_made_for_you_reader_type_signals($reader_type) as $signal) {
			if ('' !== $signal && false !== strpos($haystack, bbb_made_for_you_token($signal))) {
				$score += 6;
			}
		}
		if ('' !== $favorite_trope && false !== strpos($haystack, $favorite_trope)) {
			$score += 8;
		}
		if (bbb_made_for_you_soft_profile_active($profile)) {
			foreach (array('dark romance', 'dark academia', 'mafia', 'bully romance', 'touch her and die', 'stalker', 'obsession', 'morally gray') as $conflict) {
				if (false !== strpos($haystack, bbb_made_for_you_token($conflict))) {
					$score -= 20;
				}
			}
		}
		return $score;
	}
}

if (!function_exists('bbb_made_for_you_feature_fallback_links')) {
	function bbb_made_for_you_feature_fallback_links(array $posts, array $newsletters, array $profile): array {
		$seen = array();
		$scored_posts = array_map(
			static function (array $post) use ($profile): array {
				return array(
					'post'  => $post,
					'score' => bbb_made_for_you_content_match_score($post, $profile),
				);
			},
			$posts
		);
		usort(
			$scored_posts,
			static function (array $a, array $b): int {
				return ((int) $b['score']) <=> ((int) $a['score']);
			}
		);

		$links = array();
		foreach ($scored_posts as $entry) {
			if (count($links) >= 3 || (int) $entry['score'] <= 0) {
				continue;
			}
			$post = (array) $entry['post'];
			$url = (string) ($post['url'] ?? '');
			if ('' === $url || isset($seen[$url])) {
				continue;
			}
			$seen[$url] = true;
			$links[] = array(
				'badge'   => 'matched post',
				'title'   => (string) ($post['title'] ?? ''),
				'summary' => (string) ($post['summary'] ?? 'matched to your reader type'),
				'url'     => $url,
				'image'   => (string) ($post['image'] ?? ''),
				'alt'     => (string) ($post['alt'] ?? ''),
			);
		}

		foreach (bbb_made_for_you_reader_type_fallback_links($profile) as $link) {
			if (count($links) >= 3) {
				break;
			}
			if (isset($seen[(string) $link['url']])) {
				continue;
			}
			$seen[(string) $link['url']] = true;
			$links[] = $link;
		}

		$newsletter = null;
		$scored_newsletters = array_map(
			static function (array $issue) use ($profile): array {
				return array(
					'issue' => $issue,
					'score' => bbb_made_for_you_content_match_score($issue, $profile),
				);
			},
			$newsletters
		);
		usort(
			$scored_newsletters,
			static function (array $a, array $b): int {
				return ((int) $b['score']) <=> ((int) $a['score']);
			}
		);
		if (!empty($scored_newsletters) && (int) $scored_newsletters[0]['score'] > 0) {
			$newsletter = (array) $scored_newsletters[0]['issue'];
		} elseif (!empty($newsletters)) {
			$newsletter = (array) $newsletters[0];
		}
		if ($newsletter) {
			$links[] = array(
				'badge'   => 'newsletter match',
				'title'   => (string) ($newsletter['title'] ?? 'latest society newsletter'),
				'summary' => (string) ($newsletter['summary'] ?? 'matched to your reader type'),
				'url'     => (string) ($newsletter['url'] ?? '/society-newsletter-recent/'),
				'image'   => (string) ($newsletter['image'] ?? ''),
				'alt'     => (string) ($newsletter['alt'] ?? ''),
			);
		}
		return $links;
	}
}

$mfy_books  = bbb_made_for_you_books();
$mfy_quotes = bbb_made_for_you_quotes($mfy_books);
$mfy_newsletters = bbb_made_for_you_newsletters();
$mfy_blog_posts = bbb_made_for_you_blog_posts();
$mfy_boyfriends = function_exists('bbb_reader_quiz_boyfriend_profiles') ? bbb_reader_quiz_boyfriend_profiles() : array();
$mfy_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$mfy_reader_email = is_array($mfy_identity) ? (string) ($mfy_identity['email'] ?? '') : '';
$mfy_reader_user_id = is_array($mfy_identity) ? (int) ($mfy_identity['userId'] ?? 0) : 0;
$mfy_account_key = $mfy_reader_user_id > 0
	? 'user-' . $mfy_reader_user_id
	: ('' !== $mfy_reader_email ? 'email-' . md5(strtolower($mfy_reader_email)) : '');
$mfy_account_books = '' !== $mfy_reader_email && function_exists('bbb_reader_fetch_account_books_for_identity')
	? bbb_reader_fetch_account_books_for_identity($mfy_reader_email, $mfy_reader_user_id)
	: array();
$mfy_account_statuses = '' !== $mfy_reader_email && function_exists('bbb_reader_fetch_account_book_statuses_for_identity')
	? bbb_reader_fetch_account_book_statuses_for_identity($mfy_reader_email, $mfy_reader_user_id)
	: array();
$mfy_account_insights = function_exists('bbb_reader_account_insights')
	? bbb_reader_account_insights($mfy_account_books, $mfy_account_statuses)
	: array('books' => $mfy_account_books, 'bookStatuses' => $mfy_account_statuses, 'readerType' => array(), 'nextRead' => null);
if (function_exists('bbb_reader_mfy_profile_for_identity')) {
	$mfy_account_insights['madeForYouProfile'] = bbb_reader_mfy_profile_for_identity((array) ($mfy_identity ?: array()));
}
$mfy_account_insights['accountKey'] = $mfy_account_key;
$mfy_profile_for_fallbacks = is_array($mfy_account_insights['madeForYouProfile'] ?? null) ? (array) $mfy_account_insights['madeForYouProfile'] : array();
$mfy_next_fallback_books = bbb_made_for_you_next_fallback_books(
	$mfy_books,
	$mfy_profile_for_fallbacks
);
$mfy_feature_fallback_links = bbb_made_for_you_feature_fallback_links($mfy_blog_posts, $mfy_newsletters, $mfy_profile_for_fallbacks);
$mfy_access = $bbb_mfy_is_paid ? 'paid' : 'free';
$mfy_join_url = (string) get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$mfy_notes_url = function_exists('bbb_page_url') ? bbb_page_url('my-notes') : home_url('/my-notes/');
$mfy_library_url = function_exists('bbb_page_url') ? bbb_page_url('library') : home_url('/library/');
$mfy_monthly_theme_url = function_exists('bbb_page_url') ? bbb_page_url('june-2026-monthly-theme') : home_url('/june-2026-monthly-theme/');
$mfy_monthly_parts = array(
	array(
		'emoji' => '🖼️',
		'title' => 'kindle inserts',
		'copy'  => '4 printable designs by size',
		'url'   => $mfy_monthly_theme_url . '#burn-downloads',
	),
	array(
		'emoji' => '📱',
		'title' => 'phone wallpapers',
		'copy'  => 'lockscreen versions of each design',
		'url'   => $mfy_monthly_theme_url . '#bbb-burn-wallpapers-title',
	),
	array(
		'emoji' => '📅',
		'title' => 'june calendar',
		'copy'  => 'monthly tracker preview + download',
		'url'   => $mfy_monthly_theme_url . '#bbb-burn-calendar-title',
	),
	array(
		'emoji' => '🎧',
		'title' => 'playlist vibes',
		'copy'  => 'songs matched to the mood',
		'url'   => $mfy_monthly_theme_url . '#bbb-burn-playlist-title',
	),
);
$mfy_css_path = get_theme_file_path('assets/css/sss-library.css');
$mfy_js_path  = get_theme_file_path('assets/js/sss-library.js');
wp_enqueue_script('bbb-supabase', 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2', array(), null, true);
wp_enqueue_style('bbb-sss-library', get_theme_file_uri('assets/css/sss-library.css'), array(), file_exists($mfy_css_path) ? (string) filemtime($mfy_css_path) : wp_get_theme()->get('Version'));
wp_enqueue_script('bbb-sss-library', get_theme_file_uri('assets/js/sss-library.js'), array('bbb-supabase'), file_exists($mfy_js_path) ? (string) filemtime($mfy_js_path) : wp_get_theme()->get('Version'), true);
wp_localize_script(
	'bbb-sss-library',
	'BBBReaderAccountApi',
	array(
		'endpoint'        => set_url_scheme(rest_url('bbb/v1/reader-account'), is_ssl() ? 'https' : 'http'),
		'emailEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/email-session'), is_ssl() ? 'https' : 'http'),
		'shelfEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/shelf'), is_ssl() ? 'https' : 'http'),
		'spiceEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/spice-profile'), is_ssl() ? 'https' : 'http'),
		'profileEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/made-for-you'), is_ssl() ? 'https' : 'http'),
		'profileVersion'  => function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : 'mfy-2026-06-11-reader-types',
		'nonce'           => wp_create_nonce('wp_rest'),
	)
);

get_header();
?>
<section class="sss-lib sss-lib--mfy-page" id="sss-lib-made-for-you" data-sss-lib="society">
		<div class="sss-lib__wrap">
			<header class="sss-lib__head">
				<p class="sss-lib__kicker">private reader file</p>
				<h1 class="sss-lib__title">member dashboard</h1>
				<p class="sss-lib__sub">your taste, your spice, your finished shelf, and the books most likely to hit next.</p>
			</header>
			<?php if (!$mfy_books) : ?>
				<div class="sss-mfy__empty sss-mfy__empty--page">member dashboard is connected, but there are not any visible library books available for recommendations yet.</div>
			<?php endif; ?>

			<section class="sss-lib__madeForYou" id="sssMadeForYou" data-mfy-access="<?php echo esc_attr($mfy_access); ?>" data-mfy-account-key="<?php echo esc_attr($mfy_account_key); ?>">
				<script>window.bbbMfyInlineLoaded = true;</script>
				<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
				<script src="<?php echo esc_url(get_theme_file_uri('assets/js/sss-library.js') . '?ver=' . (file_exists($mfy_js_path) ? (string) filemtime($mfy_js_path) : wp_get_theme()->get('Version'))); ?>"></script>
				<div class="sss-mfy">
					<div class="sss-mfy__quiz" id="sssMadeForYouQuiz">
						<div class="sss-mfy__eyebrow">a little reader profiling. a little emotional damage. a much smarter next-read list.</div>
						<div class="sss-mfy__prepNote">
							<strong>before you start:</strong>
							results get much better once you have gone through the <a class="sss-mfy__prepLink" href="<?php echo esc_url(bbb_page_url('library')); ?>">library</a> and favorited, saved, or read a few books.
						</div>
						<div class="sss-mfy__progress">
							<div class="sss-mfy__progressMeta">
								<span id="sssMfyStepCount">question 1 of 5</span>
								<button type="button" class="sss-mfy__resetLink" id="sssMadeForYouReset">start over</button>
							</div>
							<div class="sss-mfy__progressTrack">
								<span class="sss-mfy__progressFill" id="sssMfyProgressFill"></span>
							</div>
						</div>

						<div class="sss-mfy__track" id="sssMfyTrack">
							<div class="sss-mfy__slide is-active" data-mfy-question="name" data-mfy-step="0">
								<div class="sss-mfy__label">what name goes on your library card?</div>
								<div class="sss-mfy__nameField">
									<label class="sss-mfy__nameLabel" for="sssMfyNameInput">name</label>
									<input type="text" id="sssMfyNameInput" class="sss-mfy__nameInput" placeholder="name on library card" maxlength="40" autocomplete="off">
									<button type="button" class="sss-lib__finderBtn" id="sssMfyNameContinue">next</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="heat_lane" data-mfy-step="1">
								<div class="sss-mfy__label">how open is the door?</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="heat_lane" data-value="closed">closed. the tension is the point</button>
									<button type="button" data-mfy-answer="heat_lane" data-value="cracked">cracked. a scene or two, tastefully feral</button>
									<button type="button" data-mfy-answer="heat_lane" data-value="open">open. that's why we're here</button>
									<button type="button" data-mfy-answer="heat_lane" data-value="unhinged">unhinged. if i'm not worried about them, why bother</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="group_chat_text" data-mfy-step="2">
								<div class="sss-mfy__label">it's 2am. which text are you sending the group chat?</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="group_chat_text" data-value="slow_burn_girlie">chapter 40 and they STILL haven't touched. i've never been happier</button>
									<button type="button" data-mfy-answer="group_chat_text" data-value="tension_addict">they're arguing in a kitchen and i would die for both of them</button>
									<button type="button" data-mfy-answer="group_chat_text" data-value="fake_dating_fanatic">the fake dating contract has rules. they've already broken three</button>
									<button type="button" data-mfy-answer="group_chat_text" data-value="dark_romance_girlie">he is so unwell about her. anyway, rooting for him</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="love_interest" data-mfy-step="3">
								<div class="sss-mfy__label">pick a love interest. don't overthink it.</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="love_interest" data-value="fantasy_girlie">fae prince with a centuries-old grudge and exactly one weakness</button>
									<button type="button" data-mfy-answer="love_interest" data-value="jersey_chaser">team captain who's mean to everyone except her</button>
									<button type="button" data-mfy-answer="love_interest" data-value="sweet_romance_devotee">grumpy small-town man who fixed her porch without being asked</button>
									<button type="button" data-mfy-answer="love_interest" data-value="dark_romance_girlie">the villain. you weren't going to say the hero, be serious</button>
								</div>
							</div>

							<div class="sss-mfy__slide" data-mfy-question="wall_line" data-mfy-step="4">
								<div class="sss-mfy__label">which line makes you put the book down and stare at the wall?</div>
								<div class="sss-mfy__options">
									<button type="button" data-mfy-answer="wall_line" data-value="fake_dating_fanatic">there was only one bed</button>
									<button type="button" data-mfy-answer="wall_line" data-value="dark_romance_girlie">who did this to you</button>
									<button type="button" data-mfy-answer="wall_line" data-value="slow_burn_girlie">he falls first. he falls harder</button>
									<button type="button" data-mfy-answer="wall_line" data-value="chaos_reader">why would i pick just one love interest. why</button>
								</div>
							</div>
						</div>

						<div class="sss-mfy__actions">
							<button type="button" class="sss-lib__finderBtn sss-lib__finderBtn--ghost" id="sssMadeForYouBack">back</button>
							<a class="sss-lib__finderBtn" id="sssMadeForYouFinish" href="<?php echo esc_url(add_query_arg('mfy_results', '1')); ?>" hidden>view my results</a>
							<div class="sss-mfy__continueNote">tap an answer to keep going</div>
						</div>
					</div>

					<div class="sss-mfy__results" id="sssMadeForYouResults" hidden>
						<div class="sss-mfy__resultsHead">
							<div class="sss-mfy__resultsIdentity">
								<div class="sss-mfy__eyebrow" id="sssMfyDashboardKicker">curated for you</div>
								<div class="sss-mfy__resultsTitle" id="sssMfyDashboardTitle">member dashboard</div>
							</div>
							<button type="button" class="sss-mfy__resetLink" id="sssMadeForYouResetResults">reset</button>
						</div>

						<div class="sss-mfy__dashboardShell">
							<div class="sss-mfy__dashboardIntro">
								<div>
									<div class="sss-mfy__cardKicker" id="sssMfyHeroKicker">bybookishbabe · the society</div>
									<div class="sss-mfy__heroTitle" id="sssMfyCoreTitle">your reader space</div>
									<div class="sss-mfy__heroBody" id="sssMfyCoreBody">your dashboard is tuned to your profile.</div>
								</div>
								<span class="sss-mfy__personaBadge" id="sssMfyPersonaBadge">the romance reader</span>
							</div>
							<div class="sss-mfy__profileStats" aria-label="reader profile snapshot">
								<div class="sss-mfy__profileStat">
									<span>reader type</span>
									<strong id="sssMfyVisibleReaderType">romance reader</strong>
									<small id="sssMfyVisibleReaderSignal">your current lane</small>
								</div>
								<div class="sss-mfy__profileStat sss-mfy__profileStat--spice">
									<span>spice lane</span>
									<strong id="sssMfyVisibleSpice">🌶🌶🌶</strong>
									<small id="sssMfyVisibleSpiceLabel">level 3 of 5</small>
								</div>
								<div class="sss-mfy__profileStat">
									<span>top trope</span>
									<strong id="sssMfyVisibleTrope">your trope</strong>
									<small id="sssMfyVisibleTheme">dashboard theme</small>
								</div>
							</div>

							<section class="sss-mfy__societyDashboard" data-society-dashboard data-member-tier="<?php echo esc_attr($bbb_mfy_is_paid ? 'paid' : 'free'); ?>">
								<div class="sss-mfy__societyTopline">
									<div>
										<span data-society-tier-label><?php echo esc_html($bbb_mfy_is_paid ? 'paid member · the society' : 'free member · the society'); ?></span>
										<strong data-society-dashboard-title>your reader type is loading</strong>
									</div>
									<button type="button" class="sss-mfy__societyThemeBtn" data-society-theme-button><?php echo esc_html($bbb_mfy_is_paid ? 'reader theme' : 'burn bright'); ?></button>
								</div>

								<div class="sss-mfy__societyGrid">
									<article class="sss-mfy__societyCard">
										<div class="sss-mfy__societyCardHead">
											<span>shelf preview</span>
											<strong data-society-save-count>0 saved</strong>
										</div>
										<div class="sss-mfy__shelfPreview" data-society-shelf-preview></div>
									</article>

									<article class="sss-mfy__societyCard sss-mfy__societyCard--dna">
										<div class="sss-mfy__societyCardHead">
											<span>trope dna</span>
											<strong data-society-dna-status><?php echo esc_html($bbb_mfy_is_paid ? 'live' : 'preview'); ?></strong>
										</div>
										<div class="sss-mfy__tropeDna" data-society-trope-dna></div>
									</article>

									<article class="sss-mfy__societyCard sss-mfy__societyCard--bf">
										<div class="sss-mfy__societyCardHead">
											<span>fictional boyfriend</span>
											<strong data-society-bf-name>matching</strong>
										</div>
										<div class="sss-mfy__societyBf" data-society-bf-card></div>
									</article>
								</div>

								<div class="sss-mfy__societyPerks" data-society-perks></div>
							</section>

							<section class="sss-mfy__quickActions" aria-labelledby="sssMfyQuickLinksTitle">
								<div class="sss-mfy__sectionHead sss-mfy__sectionHead--spaced">
									<span class="sss-mfy__sectionTitle" id="sssMfyQuickLinksTitle">quick links</span>
									<span class="sss-mfy__sectionSub">jump back into the reader tools you use most</span>
								</div>
								<div class="sss-mfy__quickGrid" id="sssMfyQuickLinks">
									<a href="<?php echo esc_url(function_exists('bbb_page_url') ? bbb_page_url('reader-quizzes') : home_url('/reader-quizzes/')); ?>"><b aria-hidden="true">🏆</b><span><strong>reader quiz</strong><small>refresh your type</small></span></a>
									<a href="<?php echo esc_url(function_exists('bbb_page_url') ? bbb_page_url('fictional-boyfriend-quiz') : home_url('/fictional-boyfriend-quiz/')); ?>"><b aria-hidden="true">💘</b><span><strong>boyfriend quiz</strong><small>find your fictional man</small></span></a>
									<a href="<?php echo esc_url($mfy_library_url); ?>"><b aria-hidden="true">📚</b><span><strong>the library</strong><small>save books + notes</small></span></a>
									<a href="<?php echo esc_url(function_exists('bbb_page_url') ? bbb_page_url('romance-books-by-spice-level') : home_url('/romance-books-by-spice-level/')); ?>"><b aria-hidden="true">🌶</b><span><strong>spice picks</strong><small>browse by heat</small></span></a>
								</div>
							</section>

							<div class="sss-mfy__nextOpinion" data-mfy-next-opinion>
								<div class="sss-mfy__sectionHead">
									<span class="sss-mfy__sectionTitle">what we think you should read next</span>
									<span class="sss-mfy__sectionSub">based on your answers, saved shelf, favorite trope, and spice</span>
								</div>
								<div class="sss-mfy__nextOpinionGrid" id="sssMfyNextOpinion">
									<?php foreach ($mfy_next_fallback_books as $fallback_index => $fallback_book) : ?>
										<?php echo bbb_made_for_you_fallback_next_card((array) $fallback_book, (int) $fallback_index); ?>
									<?php endforeach; ?>
								</div>
							</div>

							<section class="sss-mfy__monthlyAccess" aria-labelledby="sssMfyMonthlyAccessTitle">
								<div class="sss-mfy__sectionHead">
									<span class="sss-mfy__sectionTitle" id="sssMfyMonthlyAccessTitle">this month</span>
								</div>
								<div class="sss-mfy__monthCard sss-mfy__monthCard--burn-bright<?php echo $bbb_mfy_is_free ? ' is-locked-for-free' : ''; ?>">
									<div class="sss-mfy__monthIntro">
										<span><?php echo esc_html($bbb_mfy_is_free ? 'upgrade to get' : 'full access'); ?></span>
										<strong>burn bright</strong>
										<small>June theme: printable inserts, wallpapers, calendar, and playlist.</small>
									</div>
										<a class="sss-mfy__monthHero" href="<?php echo esc_url($mfy_monthly_theme_url); ?>" aria-label="Open the Burn Bright monthly theme">
											<img src="<?php echo esc_url(get_theme_file_uri('assets/monthly-themes/june-2026/previews/burn-bright-og.png')); ?>" alt="Burn Bright June 2026 monthly theme preview" loading="lazy" decoding="async">
										</a>
										<div class="sss-mfy__monthPartGrid" aria-label="Burn Bright access links">
											<?php foreach ($mfy_monthly_parts as $part) : ?>
												<a href="<?php echo esc_url($part['url']); ?>">
													<b aria-hidden="true"><?php echo esc_html($part['emoji']); ?></b>
													<span>
														<strong><?php echo esc_html($part['title']); ?></strong>
														<small><?php echo esc_html($part['copy']); ?></small>
													</span>
												</a>
											<?php endforeach; ?>
										</div>
									</div>
								</section>

								<div class="sss-mfy__dashboardColumns">
									<div class="sss-mfy__dashboardMain">
										<div class="sss-mfy__sectionHead">
											<span class="sss-mfy__sectionTitle">bookshelf</span>
											<a class="sss-mfy__sectionLink" href="<?php echo esc_url(bbb_page_url('my-bookshelf')); ?>">view all</a>
										</div>
										<div class="sss-mfy__bookshelfCard">
											<div class="sss-mfy__bookshelfTabs" role="tablist" aria-label="bookshelf preview">
												<button type="button" class="is-active" data-mfy-bookshelf-tab="reading">reading</button>
												<button type="button" data-mfy-bookshelf-tab="tbr">want to read</button>
												<button type="button" data-mfy-bookshelf-tab="read">finished</button>
											</div>
											<div class="sss-mfy__bookshelfList" id="sssMfyDashboardBookshelf">
												<?php foreach ($mfy_next_fallback_books as $index => $book) : ?>
													<?php
													$title = (string) ($book['title'] ?? 'your next read');
													$author = (string) ($book['author'] ?? '');
													$cover = (string) ($book['cover'] ?? '');
													$tropes = array_slice(array_values(array_filter(array_map('strval', (array) ($book['tropes'] ?? array())))), 0, 2);
													$colors = array(
														array('#F4C0D1', '#993556'),
														array('#FBEAF0', '#72243E'),
														array('#ED93B1', '#72243E'),
													);
													$color = $colors[$index % count($colors)];
													?>
													<div class="sss-mfy__bookRow">
														<div class="sss-mfy__miniCover" style="--mfy-rec-bg:<?php echo esc_attr($color[0]); ?>;--mfy-rec-ink:<?php echo esc_attr($color[1]); ?>">
															<?php if ('' !== $cover) : ?>
																<span class="sss-lib__coverWrap"><img class="sss-lib__cover" src="<?php echo esc_url($cover); ?>" alt="<?php echo esc_attr($title . ' book cover'); ?>" loading="lazy" decoding="async"></span>
															<?php else : ?>
																<span><?php echo esc_html($title); ?></span>
															<?php endif; ?>
														</div>
														<div>
															<strong><?php echo esc_html($title); ?></strong>
															<?php if ('' !== $author) : ?>
																<small><?php echo esc_html($author); ?></small>
															<?php endif; ?>
															<div>
																<?php foreach ($tropes as $trope) : ?>
																	<span><?php echo esc_html($trope); ?></span>
																<?php endforeach; ?>
																<?php if (empty($tropes)) : ?>
																	<span>profile pick</span>
																<?php endif; ?>
															</div>
														</div>
													</div>
												<?php endforeach; ?>
											</div>
											<a class="sss-mfy__manageShelf" href="<?php echo esc_url(bbb_page_url('my-bookshelf')); ?>">manage your full bookshelf →</a>
										</div>
									</div>

										<?php $bbb_mfy_has_notes_access = function_exists('bbb_reader_can_use_notes') ? bbb_reader_can_use_notes() : !$bbb_mfy_is_free; ?>
										<div class="sss-mfy__dashboardNotes">
											<div class="sss-mfy__sectionHead">
												<span class="sss-mfy__sectionTitle">your notes</span>
												<span class="sss-mfy__sectionSub">pulled from your book notes</span>
											</div>
											<div class="sss-mfy__notesCard<?php echo $bbb_mfy_has_notes_access ? '' : ' sss-mfy__lockedSection is-locked'; ?>">
												<div<?php echo $bbb_mfy_has_notes_access ? '' : ' class="sss-mfy__lockedContent"'; ?>>
													<div class="sss-mfy__notesList" id="sssMfyNotesList" data-mfy-book-notes-preview data-notes-url="<?php echo esc_url($mfy_notes_url); ?>" data-library-url="<?php echo esc_url($mfy_library_url); ?>">
														<div class="sss-mfy__noteItem"><p>"the betrayal works because he notices what everyone else missed."</p><span>example private note</span></div>
														<div class="sss-mfy__noteItem"><p>"save this one for when i want obsessive, messy, touch-her-and-die energy."</p><span>example private note</span></div>
												</div>
													<div class="sss-mfy__notesActions">
														<a class="sss-lib__finderBtn sss-lib__finderBtn--ghost" href="<?php echo esc_url($mfy_notes_url); ?>">open reading journal</a>
													</div>
												</div>
													<?php if (!$bbb_mfy_has_notes_access) : ?>
														<div class="sss-mfy__freegate">
															<div class="sss-mfy__freegate__inner">
																<strong class="sss-mfy__freegate__title">join to write notes</strong>
																<p class="sss-mfy__freegate__copy">free and paid members can keep private book notes here.</p>
																<a class="sss-mfy__freegate__btn" href="<?php echo esc_url($mfy_join_url); ?>">join</a>
															</div>
														</div>
													<?php endif; ?>
											</div>
										</div>
									</div>

									<div class="sss-mfy__sectionHead sss-mfy__sectionHead--spaced" data-mfy-feature-head>
										<span class="sss-mfy__sectionTitle">made for you reads</span>
										<span class="sss-mfy__sectionSub">blog posts and newsletter picks matched to your reader type</span>
									</div>
								<div class="sss-mfy__featureLinks" id="sssMfyFeatureLinks">
									<?php foreach ($mfy_feature_fallback_links as $link) : ?>
										<?php
										$title = trim((string) ($link['title'] ?? ''));
										$url = trim((string) ($link['url'] ?? ''));
										if ('' === $title || '' === $url) {
											continue;
										}
										$image = trim((string) ($link['image'] ?? ''));
										$emoji = trim((string) ($link['emoji'] ?? ''));
										?>
										<a href="<?php echo esc_url($url); ?>">
											<span class="sss-mfy__featureMedia<?php echo '' !== $image ? ' sss-mfy__featureMedia--image' : ('' !== $emoji ? ' sss-mfy__featureMedia--emoji' : ''); ?>">
												<?php if ('' !== $image) : ?>
													<img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr((string) ($link['alt'] ?? '')); ?>" loading="lazy" decoding="async">
												<?php elseif ('' !== $emoji) : ?>
													<img class="bbb-custom-emoji" src="<?php echo esc_url(get_theme_file_uri('assets/images/custom-emojis/' . $emoji . '.png')); ?>" alt="" aria-hidden="true" loading="lazy" decoding="async">
												<?php else : ?>
													<b aria-hidden="true">📚</b>
												<?php endif; ?>
											</span>
											<span>
												<em><?php echo esc_html((string) ($link['badge'] ?? 'matched read')); ?></em>
												<strong><?php echo esc_html($title); ?></strong>
												<small><?php echo esc_html((string) ($link['summary'] ?? 'matched to your reader type')); ?></small>
											</span>
										</a>
									<?php endforeach; ?>
								</div>

							<div class="sss-mfy__compat" hidden>
								<div id="sssMfyHeroRain"></div>
								<div id="sssMfyCoreEmojiBadge"></div>
								<div id="sssMfyCoreEmotion"></div>
								<div id="sssMfyDashboardSpice"></div>
								<div id="sssMfyDashboardSpiceLabel"></div>
								<div id="sssMfyDashboardReaderType"></div>
								<div id="sssMfyDashboardReaderSignal"></div>
								<div id="sssMfyDashboardTrope"></div>
								<div id="sssMfyDashboardTheme"></div>
								<div id="sssMfyDashboardThemeSignal"></div>
								<div id="sssMfyThemeTokens"></div>
								<div id="sssMfyBoyfriendKicker"></div>
								<div id="sssMfyTypeTitle"></div>
								<div id="sssMfyTypeBody"></div>
								<div id="sssMfyBoyfriendEmojiBadge"></div>
								<div id="sssMfyBoyfriendRain"></div>
								<div id="sssMfyMatchBook"></div>
								<div id="sssMfyReadsKicker"></div>
								<div id="sssMfyReadsEmojiBadge"></div>
								<div id="sssMfyReadsRain"></div>
								<div id="sssMfyResultsMeta"></div>
								<div id="sssMfyShelfKicker"></div>
								<div id="sssMfyShelfEmojiBadge"></div>
								<div id="sssMfyShelfRain"></div>
								<div id="sssMfyShelfTitle"></div>
								<div id="sssMfyShelfBody"></div>
								<div id="sssMfyQuoteRain"></div>
								<div id="sssMfyQuoteEyebrow"></div>
								<div id="sssMfyReadShelfEyebrow"></div>
								<div id="sssMfyReadShelfMeta"></div>
								<div id="sssMfyReadShelfRow"></div>
								<div id="sssMfyReadTropes"></div>
								<div id="sssMfyReadShelfInsight"></div>
								<div id="sssMfyReadNextTitle"></div>
								<div id="sssMfyReadNextRow"></div>
								<div id="sssMfySavedQuotesMeta"></div>
								<div id="sssMfySavedQuotesRow"></div>
								<blockquote id="sssMfyQuoteCard"><p id="sssMfyQuoteText"></p><footer id="sssMfyQuoteSource"></footer></blockquote>
								<button type="button" id="sssMfyNextResult"></button>
							</div>
						</div>

						<div class="sss-mfy__customize" id="sssMfyCustomize" hidden>
							<div class="sss-mfy__customizeHead">
								<div class="sss-mfy__eyebrow">make it more you</div>
								<div class="sss-mfy__customizeTitle">add a few personal layers</div>
								<p class="sss-mfy__customizeCopy">these choices tune the dashboard before the full breakdown opens.</p>
							</div>

							<div class="sss-mfy__addonRow" id="sssMfyAddonRow">
								<button type="button" class="sss-mfy__addonCard" data-mfy-addon="spice_dial"><span class="sss-mfy__addonEmoji">spice</span><span class="sss-mfy__addonText"><strong>spice dial</strong><small id="sssMfyManDialSummary">be honest</small></span></button>
								<button type="button" class="sss-mfy__addonCard" data-mfy-addon="favorite_trope"><span class="sss-mfy__addonEmoji">trope</span><span class="sss-mfy__addonText"><strong>favorite trope</strong><small id="sssMfyFavoriteTropeSummary">your default lane</small></span></button>
							</div>

							<div class="sss-mfy__addonModules">
								<section class="sss-mfy__addonModule sss-mfy__addonModule--dial" data-mfy-module="spice_dial" hidden>
									<div class="sss-mfy__addonHeader">
										<div><div class="sss-mfy__cardKicker">spice dial</div><h3>tell me how spicy you want the lower recs to run</h3></div>
										<button type="button" class="sss-mfy__collapse" data-mfy-close="spice_dial">close</button>
									</div>
									<div class="sss-mfy__dialWrap" id="sssMfyManDial">
										<div class="sss-mfy__dialOrb" id="sssMfyManDialOrb"><div class="sss-mfy__dialCenter"><span class="sss-mfy__dialMini">current spice lane</span><strong id="sssMfyManDialValue">soft open door</strong></div></div>
										<div class="sss-mfy__dialLabels"><span>soft open door</span><span>some heat</span><span>balanced</span><span>high spice</span><span>wreck me</span></div>
										<input type="range" min="0" max="4" step="1" value="0" id="sssMfyManDialInput" class="sss-mfy__dialInput">
										<div class="sss-mfy__dialChoices" id="sssMfyManDialChoices">
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="soft_open_door">soft open door</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="some_heat">some heat</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="balanced">balanced</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="high_spice">high spice</button>
											<button type="button" class="sss-mfy__dialChoice" data-mfy-dial-choice="wreck_me">wreck me</button>
										</div>
									</div>
									<p class="sss-mfy__moduleNote" id="sssMfyManDialNote">this will only tune the reads suggested below your member dashboard.</p>
									<div class="sss-mfy__addonActions"><button type="button" class="sss-lib__finderBtn" id="sssMfySaveManDial">save dial</button></div>
								</section>

								<section class="sss-mfy__addonModule sss-mfy__addonModule--trope" data-mfy-module="favorite_trope" hidden>
									<div class="sss-mfy__addonHeader">
										<div><div class="sss-mfy__cardKicker">favorite trope</div><h3>the lane your dashboard should always remember</h3></div>
										<button type="button" class="sss-mfy__collapse" data-mfy-close="favorite_trope">close</button>
									</div>
									<div class="sss-mfy__chipSet" id="sssMfyFavoriteTropes">
										<button type="button" data-mfy-favorite-trope="slow burn">slow burn</button>
										<button type="button" data-mfy-favorite-trope="enemies to lovers">enemies to lovers</button>
										<button type="button" data-mfy-favorite-trope="friends to lovers">friends to lovers</button>
										<button type="button" data-mfy-favorite-trope="fake dating">fake dating</button>
										<button type="button" data-mfy-favorite-trope="forbidden romance">forbidden romance</button>
										<button type="button" data-mfy-favorite-trope="touch her and die">touch her and die</button>
										<button type="button" data-mfy-favorite-trope="second chance">second chance</button>
										<button type="button" data-mfy-favorite-trope="why choose">why choose</button>
										<button type="button" data-mfy-favorite-trope="mafia romance">mafia romance</button>
										<button type="button" data-mfy-favorite-trope="romantasy">romantasy</button>
										<button type="button" data-mfy-favorite-trope="morally gray">morally gray</button>
										<button type="button" data-mfy-favorite-trope="found family">found family</button>
									</div>
									<p class="sss-mfy__moduleNote">this becomes a saved taste signal across your dashboard, bookshelf patterns, and recommendations.</p>
									<div class="sss-mfy__addonActions"><button type="button" class="sss-lib__finderBtn" id="sssMfySaveFavoriteTrope">save trope</button></div>
								</section>

							</div>

							<div class="sss-mfy__customizeActions">
								<button type="button" class="sss-lib__finderBtn" id="sssMfySeeFullBreakdown">see full breakdown</button>
							</div>
						</div>

					</div>
				</div>

				<div class="sss-mfy__sourceGrid" hidden aria-hidden="true">
					<?php foreach ($mfy_books as $book) : ?>
						<?php echo bbb_render_library_book_card((int) $book['id']); ?>
					<?php endforeach; ?>
				</div>

					<script type="application/json" id="sssMadeForYouData"><?php echo wp_json_encode(array_values($mfy_books)); ?></script>
					<script type="application/json" id="sssMadeForYouQuotes"><?php echo wp_json_encode(array_values($mfy_quotes)); ?></script>
					<script type="application/json" id="sssMadeForYouNewsletters"><?php echo wp_json_encode(array_values($mfy_newsletters)); ?></script>
					<script type="application/json" id="sssMadeForYouBlogPosts"><?php echo wp_json_encode(array_values($mfy_blog_posts)); ?></script>
					<script type="application/json" id="sssMadeForYouBoyfriends"><?php echo wp_json_encode(array_values($mfy_boyfriends)); ?></script>
				<script type="application/json" id="sssReaderTypesData"><?php echo wp_json_encode(function_exists('bbb_reader_type_registry') ? bbb_reader_type_registry() : array()); ?></script>
				<script type="application/json" id="sssMadeForYouAccountData"><?php echo wp_json_encode($mfy_account_insights); ?></script>
				<script>
					window.BBBReaderAccountApi = <?php echo wp_json_encode(array(
						'endpoint'        => set_url_scheme(rest_url('bbb/v1/reader-account'), is_ssl() ? 'https' : 'http'),
						'emailEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/email-session'), is_ssl() ? 'https' : 'http'),
							'shelfEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/shelf'), is_ssl() ? 'https' : 'http'),
							'spiceEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/spice-profile'), is_ssl() ? 'https' : 'http'),
							'profileEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/made-for-you'), is_ssl() ? 'https' : 'http'),
							'notesEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/notes'), is_ssl() ? 'https' : 'http'),
							'accountKey'      => $mfy_account_key,
							'hasNotesAccess'  => $bbb_mfy_has_notes_access,
							'nonce'           => wp_create_nonce('wp_rest'),
						)); ?>;
				</script>
				<script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
				<script src="<?php echo esc_url(get_theme_file_uri('assets/js/sss-library.js') . '?ver=' . (file_exists($mfy_js_path) ? (string) filemtime($mfy_js_path) : wp_get_theme()->get('Version'))); ?>"></script>
				<script>
				(function(){
					if (document.querySelector('script[src*="assets/js/sss-library.js"]')) return;
					function loadLibrary(){
						var script = document.createElement('script');
						script.src = <?php echo wp_json_encode(get_theme_file_uri('assets/js/sss-library.js') . '?ver=' . (file_exists($mfy_js_path) ? (string) filemtime($mfy_js_path) : wp_get_theme()->get('Version'))); ?>;
						script.onload = function(){
							if (window.initMadeForYou) window.initMadeForYou();
						};
						document.body.appendChild(script);
					}
					if (window.supabase) {
						loadLibrary();
						return;
					}
					var supabase = document.createElement('script');
					supabase.src = 'https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2';
					supabase.onload = loadLibrary;
					document.body.appendChild(supabase);
				})();
				</script>
			</section>
		</div>
</section>
<?php if ($bbb_mfy_is_free) : ?>
<style>
/* Free-member locked dashboard panels */
[data-mfy-access="free"] .sss-mfy__lockedSection {
	position: relative;
	overflow: hidden;
}

[data-mfy-access="free"] .sss-mfy__lockedSection.is-locked {
	min-height: 150px;
}

[data-mfy-access="free"] .sss-mfy__lockedContent {
	filter: blur(8px);
	pointer-events: none;
	user-select: none;
	opacity: .52;
}

[data-mfy-access="free"] .sss-mfy__lockedSection.is-locked .sss-mfy__sectionHead {
	filter: blur(3px);
	pointer-events: none;
	user-select: none;
	opacity: .68;
}

	[data-mfy-access="free"] .sss-mfy__lockedSection.is-locked .sss-mfy__sectionHead--spaced {
		filter: none;
		opacity: 1;
	}
	
	.sss-mfy__freegate {
	position: absolute;
	inset: 0;
	display: flex;
	align-items: center;
	justify-content: center;
	background: linear-gradient(to top, rgba(0,0,0,.9) 0%, rgba(0,0,0,.6) 58%, rgba(0,0,0,.2) 100%);
	border-radius: inherit;
	z-index: 5;
	padding: 20px;
	text-align: center;
	backdrop-filter: blur(1px);
}
.sss-mfy__freegate__inner {
	display: grid;
	gap: 10px;
	max-width: 320px;
}
.sss-mfy__freegate__emoji {
	margin: 0;
	font-size: 26px;
	line-height: 1;
}
.sss-mfy__freegate__title {
	margin: 0;
	color: #fff;
	font-family: Cormorant, "Cormorant Garamond", Georgia, serif;
	font-size: clamp(24px, 3vw, 32px);
	font-weight: 600;
	line-height: 1.08;
	text-transform: lowercase;
}
.sss-mfy__freegate__copy {
	margin: 0;
	color: rgba(246,246,246,.72);
	font-size: 13px;
	line-height: 1.55;
	text-transform: lowercase;
}
.sss-mfy__freegate__btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	min-height: 42px;
	padding: 0 18px;
	border: 1px solid rgba(246,179,212,.45);
	border-radius: 999px;
	background: rgba(246,179,212,.12);
	color: #fff;
	font-size: 13px;
	font-weight: 600;
	text-decoration: none;
	text-transform: lowercase;
	transition: background .18s ease, border-color .18s ease;
}
.sss-mfy__freegate__btn:hover,
.sss-mfy__freegate__btn:focus {
	background: rgba(246,179,212,.22);
	border-color: rgba(246,179,212,.65);
	outline: none;
}
	[data-mfy-access="free"] .sss-mfy__monthCard.is-locked-for-free {
		position: relative;
		border-color: rgba(246,179,212,.5);
		box-shadow: 0 20px 60px rgba(0,0,0,.18), inset 0 0 0 1px rgba(246,179,212,.24);
	}
	[data-mfy-access="free"] .sss-mfy__monthCard.is-locked-for-free::after {
		content: "locked";
		position: absolute;
		top: 18px;
		right: 18px;
		display: inline-flex;
		align-items: center;
		justify-content: center;
		min-height: 30px;
		padding: 0 12px;
		border: 1px solid rgba(255,255,255,.46);
		border-radius: 999px;
		background: rgba(0,0,0,.38);
		color: #fff;
		font-size: 11px;
		font-weight: 700;
		text-transform: uppercase;
		letter-spacing: .08em;
		pointer-events: none;
	}
	</style>
<?php endif; ?>
<?php
get_footer();
