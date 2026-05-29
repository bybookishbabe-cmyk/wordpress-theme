<?php
/**
 * Read-next and specific-link shortcodes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function sss_specific_link_clusters(): array {
	return array(
		'dark' => array('/pages/stalker-romance-books', '/pages/captor-captive-romance-books', '/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you'),
		'morallygraymen' => array('/pages/dark-romance-books', '/pages/villain-gets-the-girl-romance-books', '/pages/touch-her-and-die-books', '/pages/stalker-romance-books'),
		'darkobsession' => array('/pages/dark-romance-books', '/pages/touch-her-and-die-books', '/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you', '/pages/enemies-to-lovers'),
		'stalker' => array('/pages/stalker-romance-books', '/pages/dark-romance-books', '/pages/touch-her-and-die-books'),
		'sports' => array('/pages/slow-burn-books', '/pages/fake-dating-romance-books', '/pages/forced-proximity-romance-books'),
		'romantasy' => array('/blogs/curated-romance-guides/the-ultimate-romantasy-reading-guide', '/pages/fated-mates-romance-books', '/blogs/curated-romance-guides/spicy-romantasy-books-that-are-actually-worth-it'),
		'darkromantasy' => array('/pages/romantasy-books', '/blogs/curated-romance-guides/dark-romantasy-books', '/pages/touch-her-and-die-books', '/pages/villain-gets-the-girl-romance-books'),
		'morallygrayfantasy' => array('/blogs/curated-romance-guides/the-ultimate-romantasy-reading-guide', '/blogs/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you', '/pages/touch-her-and-die-books', '/pages/villain-gets-the-girl-romance-books'),
		'soft' => array('/pages/slow-burn-books', '/pages/friends-to-lovers-romance-books', '/pages/small-town-romance-books', '/pages/second-chance-romance-books'),
		'slowburn' => array('/pages/enemies-to-lovers', '/pages/forced-proximity-romance-books', '/pages/he-falls-first-romance-books'),
	);
}

function sss_readnext_context(int $post_id): string {
	$guide = sss_article_post(sss_article_field('guide_category', $post_id, null));
	$trope = sss_article_post(sss_article_field('trope', $post_id, null));
	$parts = array();
	foreach (array($guide, $trope) as $post) {
		if ($post) {
			$parts[] = $post->post_name;
			$parts[] = get_the_title($post);
		}
	}

	return strtolower(implode(' ', $parts));
}

function sss_detect_cluster(string $context): string {
	$rules = array(
		'stalker' => array('stalker', 'obsession', 'obsessive'),
		'darkobsession' => array('darkobsession', 'dark-obsession', 'dark obsession'),
		'darkromantasy' => array('darkromantasy', 'dark-romantasy', 'dark romantasy', 'ever king'),
		'morallygrayfantasy' => array('morallygrayfantasy', 'morally-gray-fantasy', 'morally gray fantasy'),
		'sports' => array('sport', 'hockey', 'football', 'athlete', 'baseball', 'basketball'),
		'romantasy' => array('romantasy', 'fantasy', 'fae', 'fated', 'dragon', 'magic'),
		'slowburn' => array('slow-burn', 'slow burn'),
		'soft' => array('soft', 'small-town', 'small town', 'second-chance', 'second chance', 'friends-to-lovers', 'friends to lovers'),
		'morallygraymen' => array('morally-gray', 'morally gray'),
		'dark' => array('captor', 'captive', 'touch her and die', 'villain', 'dark', 'extra-dark'),
	);
	foreach ($rules as $cluster => $needles) {
		foreach ($needles as $needle) {
			if (str_contains($context, $needle)) {
				return $cluster;
			}
		}
	}

	return 'default';
}

function sss_resolve_shopify_path(string $path): array {
	$slug = basename($path);
	if (str_starts_with($path, '/blogs/')) {
		$post = get_posts(array('name' => $slug, 'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1));
		if ($post) {
			return array('url' => get_permalink($post[0]), 'title' => get_the_title($post[0]), 'post' => $post[0]);
		}
		return array('url' => home_url('/blogs/curated-romance-guides/' . $slug . '/'), 'title' => ucwords(str_replace('-', ' ', $slug)), 'post' => null);
	}

	$page = get_page_by_path($slug);
	return array(
		'url' => $page ? get_permalink($page) : home_url('/pages/' . $slug . '/'),
		'title' => $page ? get_the_title($page) : ucwords(str_replace('-', ' ', $slug)),
		'post' => $page,
	);
}

function sss_specific_link_from_value($value): ?array {
	if ($value instanceof WP_Post) {
		return array('url' => get_permalink($value), 'title' => get_the_title($value), 'post' => $value);
	}
	if (is_numeric($value)) {
		$post = get_post((int) $value);
		if ($post instanceof WP_Post) {
			return array('url' => get_permalink($post), 'title' => get_the_title($post), 'post' => $post);
		}
	}
	if (is_array($value)) {
		if (isset($value['url'])) {
			$title = (string) ($value['title'] ?? $value['label'] ?? $value['name'] ?? $value['url']);
			return array('url' => (string) $value['url'], 'title' => $title, 'post' => null);
		}
		if (isset($value['ID'])) {
			return sss_specific_link_from_value((int) $value['ID']);
		}
		if (isset($value[0])) {
			return sss_specific_link_from_value($value[0]);
		}
	}
	if (is_string($value) && trim($value) !== '') {
		$value = trim($value);
		if (str_starts_with($value, 'http')) {
			$path = (string) wp_parse_url($value, PHP_URL_PATH);
			$home_host = (string) wp_parse_url(home_url('/'), PHP_URL_HOST);
			$link_host = (string) wp_parse_url($value, PHP_URL_HOST);
			if ($path && $home_host && $link_host === $home_host) {
				return sss_resolve_shopify_path($path);
			}
			$title = $path ? basename(untrailingslashit($path)) : $value;
			return array('url' => $value, 'title' => ucwords(str_replace('-', ' ', $title)), 'post' => null);
		}
		if (str_starts_with($value, '/')) {
			return sss_resolve_shopify_path($value);
		}
		$post = get_page_by_path(sanitize_title($value), OBJECT, array('post', 'page'));
		if ($post instanceof WP_Post) {
			return array('url' => get_permalink($post), 'title' => get_the_title($post), 'post' => $post);
		}
	}

	return null;
}

function sss_specific_manual_links(int $post_id): array {
	$links = array();
	$raw_links = sss_article_field('specific_links', $post_id, array());
	if (is_array($raw_links)) {
		foreach ($raw_links as $item) {
			$link = sss_specific_link_from_value($item);
			if (!$link && is_array($item)) {
				$link = sss_specific_link_from_value($item['link'] ?? $item['page'] ?? $item['post'] ?? $item['url'] ?? null);
			}
			if ($link) {
				$link['title'] = (string) ($item['label'] ?? $item['title'] ?? $link['title']);
				$links[] = $link;
			}
		}
	}

	for ($i = 1; $i <= 3; $i++) {
		if (count($links) >= 3) {
			break;
		}
		$link = sss_specific_link_from_value(sss_article_field('specific_link_' . $i, $post_id, null));
		if (!$link) {
			$url = (string) sss_article_field('specific_link_' . $i . '_url', $post_id, '');
			if ($url) {
				$link = sss_resolve_shopify_path($url);
			}
		}
		if ($link) {
			$label = (string) sss_article_field('specific_link_' . $i . '_label', $post_id, '');
			if (!$label) {
				$label = (string) sss_article_field('specific_link_' . $i . '_title', $post_id, '');
			}
			if ($label) {
				$link['title'] = $label;
			}
			$links[] = $link;
		}
	}

	$deduped = array();
	foreach ($links as $link) {
		if (empty($link['url']) || isset($deduped[$link['url']])) {
			continue;
		}
		$deduped[$link['url']] = $link;
	}

	return array_slice(array_values($deduped), 0, 3);
}

function sss_normalize_specific_cluster(string $cluster): string {
	$cluster = strtolower(trim($cluster));
	$cluster = str_replace(array('_', ' '), '-', $cluster);
	$aliases = array(
		'dark-obsession'        => 'darkobsession',
		'darkromance'           => 'dark',
		'dark-romance'          => 'dark',
		'dark-romantasy'        => 'darkromantasy',
		'morally-gray-fantasy'  => 'morallygrayfantasy',
		'morally-gray-men'      => 'morallygraymen',
		'morallygray'           => 'morallygraymen',
		'morally-gray'          => 'morallygraymen',
		'slow-burn'             => 'slowburn',
	);

	return $aliases[$cluster] ?? str_replace('-', '', $cluster);
}

function sss_specific_cluster_links(string $cluster): array {
	$clusters = sss_specific_link_clusters();
	$cluster  = sss_normalize_specific_cluster($cluster);
	if (!isset($clusters[$cluster])) {
		return array();
	}

	$links = array();
	foreach ($clusters[$cluster] as $path) {
		$link = sss_specific_link_from_value($path);
		if ($link) {
			$links[] = $link;
		}
	}

	return array_slice($links, 0, 4);
}

function sss_specific_links_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID(), 'cluster' => ''), $atts, 'sss_specific_links');
	$post_id = (int) $atts['post_id'];
	$prompt = (string) sss_article_field('specific_prompt', $post_id, 'looking for something more specific?');
	$links = sss_specific_manual_links($post_id);
	if (!$links) {
		$cluster = (string) $atts['cluster'];
		if ('' === trim($cluster)) {
			$cluster = sss_detect_cluster(sss_readnext_context($post_id));
		}
		$links = sss_specific_cluster_links($cluster);
	}
	if (!$links) {
		return '';
	}

	ob_start();
	?>
<nav class="blog-specific-links" aria-label="specific guide links">
  <div class="blog-specific-links__head">
    <span class="blog-specific-links__kicker">keep browsing</span>
    <span class="blog-specific-links__prompt"><?php echo esc_html($prompt); ?></span>
  </div>
  <div class="blog-specific-links__grid">
  <?php foreach ($links as $i => $link) : ?>
    <a class="blog-specific-links__card" href="<?php echo esc_url($link['url']); ?>">
      <span class="blog-specific-links__number"><?php echo esc_html(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)); ?></span>
      <span class="blog-specific-links__title"><?php echo esc_html(strtolower((string) $link['title'])); ?></span>
      <span class="blog-specific-links__cta">open guide →</span>
    </a>
  <?php endforeach; ?>
  </div>
</nav>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_specific_links', 'sss_specific_links_shortcode');

function sss_bigspecific_shortcode(): string {
	$url = function_exists('bbb_resolve_page_url') ? bbb_resolve_page_url('what-to-read-next') : home_url('/what-to-read-next/');

	return '<div class="bbb-bigspecific"><p class="bbb-bigspecific__kicker">reader matchmaker</p><p class="bbb-bigspecific__title">want a dangerously specific recommendation?</p><p class="bbb-bigspecific__sub">pick a book you loved and get a romance rec that is almost too specific to be legal.</p><a class="bbb-bigspecific__cta" href="' . esc_url($url) . '">find your match &rarr;</a></div>';
}
add_shortcode('sss_bigspecific', 'sss_bigspecific_shortcode');
add_shortcode('bigspecific', 'sss_bigspecific_shortcode');

function sss_what_to_read_next_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_what_to_read_next');
	$post_id = (int) $atts['post_id'];
	$books = sss_article_post_books($post_id);
	$first = $books[0] ?? null;
	$second = $books[1] ?? null;
	if (!$first instanceof WP_Post) {
		return sss_bigspecific_shortcode();
	}

	$context = sss_readnext_context($post_id);
	$cluster = sss_detect_cluster($context);
	if (!$second instanceof WP_Post) {
		$second = sss_find_readnext_book($first, $cluster);
	}

	$first_data = sss_article_book_data($first->ID);
	$second_data = $second instanceof WP_Post ? sss_article_book_data($second->ID) : null;
	$url = function_exists('bbb_resolve_page_url') ? bbb_resolve_page_url('what-to-read-next') : home_url('/what-to-read-next/');
	$url = add_query_arg('book', (string) $first_data['handle'], $url);
	$guide = sss_article_post(sss_article_field('guide_category', $post_id, null));
	$trope = sss_article_post(sss_article_field('trope', $post_id, null));
	$label = $guide instanceof WP_Post ? strtolower(get_the_title($guide)) : '';
	if (!$label && $trope instanceof WP_Post) {
		$label = strtolower(get_the_title($trope));
	}
	$label = $label ?: 'romance';
	$copy = 'find your next read after ' . strtolower((string) $first_data['title']) . ' →';
	if ($label && 'romance' !== $label) {
		$copy = 'find your next ' . $label . ' read after ' . strtolower((string) $first_data['title']) . ' →';
	}

	ob_start();
	?>
<a class="blog-next-read blog-next-read--article" href="<?php echo esc_url($url); ?>" aria-label="<?php echo esc_attr('find your next read after ' . $first_data['title']); ?>">
  <span class="blog-next-read__stage" aria-hidden="true">
    <?php if (!empty($first_data['cover'])) : ?>
      <span class="blog-next-read__book">
        <span class="blog-next-read__bookCover">
          <img src="<?php echo esc_url($first_data['cover']); ?>" alt="<?php echo esc_attr($first_data['title']); ?>" loading="lazy">
        </span>
        <span class="blog-next-read__bookLabel">start here</span>
      </span>
    <?php endif; ?>
    <?php if ($second_data && !empty($second_data['cover'])) : ?>
      <span class="blog-next-read__arrow">→</span>
      <span class="blog-next-read__book">
        <span class="blog-next-read__bookCover">
          <img src="<?php echo esc_url($second_data['cover']); ?>" alt="<?php echo esc_attr($second_data['title']); ?>" loading="lazy">
        </span>
        <span class="blog-next-read__bookLabel">try next</span>
      </span>
    <?php endif; ?>
  </span>
  <span class="blog-next-read__copy"><?php echo esc_html($copy); ?></span>
</a>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_what_to_read_next', 'sss_what_to_read_next_shortcode');

function sss_faq_book_title_patterns(): array {
	static $patterns = null;
	if (null !== $patterns) {
		return $patterns;
	}

	$books = function_exists('sss_article_all_visible_books')
		? sss_article_all_visible_books()
		: get_posts(
			array(
				'post_type'      => array('bbb_book', 'sss_book'),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

	$titles = array();
	foreach ($books as $book) {
		if (!$book instanceof WP_Post) {
			continue;
		}

		$title = trim(wp_strip_all_tags(get_the_title($book)));
		if ('' === $title) {
			continue;
		}

		$normalized = function_exists('sss_article_match_text') ? sss_article_match_text($title) : strtolower($title);
		$word_count = count(preg_split('/\s+/', $normalized) ?: array());
		if ($word_count < 2 && strlen($normalized) < 10) {
			continue;
		}

		$titles[$title] = $title;
	}

	$titles = array_values($titles);
	usort(
		$titles,
		static function (string $first, string $second): int {
			return strlen($second) <=> strlen($first);
		}
	);

	$patterns = array_map(
		static function (string $title): string {
			$pattern = preg_quote($title, '/');
			$pattern = str_replace(array("'", '’'), "[’']", $pattern);

			return '/(?<![\p{L}\p{N}])(' . $pattern . ')(?![\p{L}\p{N}])/iu';
		},
		$titles
	);

	return $patterns;
}

function sss_faq_italicize_book_titles(string $html): string {
	$patterns = sss_faq_book_title_patterns();
	if (!$patterns || '' === trim($html)) {
		return $html;
	}

	$chunks = function_exists('wp_html_split') ? wp_html_split($html) : preg_split('/(<[^>]+>)/', $html, -1, PREG_SPLIT_DELIM_CAPTURE);
	if (!is_array($chunks)) {
		return $html;
	}

	$in_em = false;
	foreach ($chunks as $index => $chunk) {
		if (str_starts_with($chunk, '<')) {
			if (preg_match('/^<\s*em\b/i', $chunk)) {
				$in_em = true;
			} elseif (preg_match('/^<\s*\/\s*em\s*>/i', $chunk)) {
				$in_em = false;
			}
			continue;
		}

		if ($in_em || '' === trim($chunk)) {
			continue;
		}

		foreach ($patterns as $pattern) {
			$chunk = (string) preg_replace($pattern, '<em>$1</em>', $chunk);
		}
		$chunks[$index] = $chunk;
	}

	return implode('', $chunks);
}

function sss_faq_shortcode($atts, ?string $content = null): string {
	if (null === $content || trim($content) === '') {
		return '';
	}

	$content = (string) preg_replace('/<p\b[^>]*>\s*(\[\/?(?:faq|q|a)\])\s*<\/p>/i', '$1', $content);
	$content = str_ireplace(array('[FAQ]', '[/FAQ]', '[Q]', '[/Q]', '[A]', '[/A]'), array('[faq]', '[/faq]', '[q]', '[/q]', '[a]', '[/a]'), $content);
	$items = '';

	if (preg_match_all('/\[(q|a)\](.*?)\[\/\1\]/is', $content, $tokens, PREG_SET_ORDER)) {
		$question = '';
		foreach ($tokens as $token) {
			$type = strtolower($token[1]);
			$body = trim($token[2]);
			if ('q' === $type) {
				$question = trim(wp_strip_all_tags(do_shortcode($body)));
				continue;
			}
			if ('a' !== $type || '' === $question || '' === $body) {
				continue;
			}
			$answer = sss_faq_italicize_book_titles(wp_kses_post(do_shortcode($body)));
			$items .= '<details class="blog-faq__item"><summary class="blog-faq__question"><span>' . wp_kses_post(sss_faq_italicize_book_titles(esc_html($question))) . '</span><span class="blog-faq__arrow" aria-hidden="true">⌄</span></summary><div class="blog-faq__answer">' . wp_kses_post($answer) . '</div></details>';
			$question = '';
		}
	}

	if (!$items) {
		$rendered = do_shortcode($content);
		if (preg_match_all('/<div class="blog-faq__q">(.*?)<\/div>\s*<div class="blog-faq__a">(.*?)<\/div>/is', $rendered, $pairs, PREG_SET_ORDER)) {
			foreach ($pairs as $pair) {
				$question = trim(wp_strip_all_tags($pair[1]));
				$answer   = trim($pair[2]);
				if (!$question || !$answer) {
					continue;
				}
				$answer = sss_faq_italicize_book_titles(wp_kses_post($answer));
				$items .= '<details class="blog-faq__item"><summary class="blog-faq__question"><span>' . wp_kses_post(sss_faq_italicize_book_titles(esc_html($question))) . '</span><span class="blog-faq__arrow" aria-hidden="true">⌄</span></summary><div class="blog-faq__answer">' . wp_kses_post($answer) . '</div></details>';
			}
		}
		if (!$items) {
			return '<section class="blog-faq">' . wp_kses_post($rendered) . '</section>';
		}
	}

	return '<section class="blog-faq" aria-label="frequently asked questions"><div class="blog-faq__list">' . $items . '</div></section>';
}
add_shortcode('faq', 'sss_faq_shortcode');

function sss_faq_question_shortcode($atts, ?string $content = null): string {
	return '<div class="blog-faq__q">' . wp_kses_post(do_shortcode((string) $content)) . '</div>';
}
add_shortcode('q', 'sss_faq_question_shortcode');

function sss_faq_answer_shortcode($atts, ?string $content = null): string {
	return '<div class="blog-faq__a">' . wp_kses_post(do_shortcode((string) $content)) . '</div>';
}
add_shortcode('a', 'sss_faq_answer_shortcode');

function sss_readnext_copy(string $cluster, ?WP_Post $secondary_page, ?WP_Post $guide_article, ?WP_Post $browse_page, ?WP_Post $rec_book): array {
	$default = array(
		'guide_label' => $guide_article ? get_the_title($guide_article) : ($secondary_page ? get_the_title($secondary_page) : 'dark romance books with morally gray men'),
		'guide_blurb' => 'the full list for readers who want more of this',
		'browse_label' => $browse_page ? get_the_title($browse_page) : '',
		'browse_blurb' => 'browse the full page',
		'browse_emoji' => '↗',
		'rec_blurb' => 'same energy, same tension, and the kind of pull that keeps you reading too late',
	);
	$copy = array(
		'stalker' => array('the full dark romance list with morally gray men', 'everything that hits the same way. read the guide', 'browse all stalker romance books', 'the full trope page. stalker romance', '👤', $rec_book && 'haunting-adeline' === $rec_book->post_name ? 'the stalker romance that made the trope famous. obsessive, dark, and fully committed.' : 'stalker romance, obsessive energy, and the same kind of dark pull.'),
		'romantasy' => array('spicy romantasy', 'the hot side of the genre when you want more fantasy with more heat', 'browse all romantasy books', 'browse the full fantasy romance page', '🔮', 'romantasy, high stakes, and the kind of fantasy obsession that eats your whole night'),
		'sports' => array($secondary_page ? get_the_title($secondary_page) : 'fake dating romance', 'the easiest next lane if you want the tension to stay fun and addictive', $browse_page ? get_the_title($browse_page) : 'browse all slow burn romance books', 'browse the full page', '🏒', 'chemistry, tension, and that same bingeable sports romance energy'),
		'slowburn' => array($secondary_page ? get_the_title($secondary_page) : 'forced proximity', 'the next best move if you want the yearning turned all the way up', $browse_page ? get_the_title($browse_page) : 'browse all enemies to lovers books', 'browse the full trope page', '🫠', 'slow burn, emotional tension, and the kind of payoff worth waiting for'),
		'soft' => array($secondary_page ? get_the_title($secondary_page) : 'friends to lovers', 'for readers who want more comfort, softness, and emotional payoff', $browse_page ? get_the_title($browse_page) : 'browse all slow burn books', 'browse the full page', '🤍', 'soft feelings, emotional payoff, and the kind of romance that lingers'),
		'morallygraymen' => array('the full dark romance list with morally gray men', 'everything that hits the same way. read the guide', 'browse all dark romance books', 'browse the full page', '🖤', 'morally gray obsession, dark energy, and another hero you should not be rooting for'),
	);
	if (!isset($copy[$cluster])) {
		return $default;
	}
	$row = $copy[$cluster];
	return array('guide_label' => $row[0], 'guide_blurb' => $row[1], 'browse_label' => $row[2], 'browse_blurb' => $row[3], 'browse_emoji' => $row[4], 'rec_blurb' => $row[5]);
}

function sss_find_readnext_book(WP_Post $anchor, string $cluster): ?WP_Post {
	$anchor_data = sss_article_book_data($anchor->ID);
	$anchor_tropes = wp_list_pluck($anchor_data['tropes'], 'slug');
	$best = null;
	$best_score = -1;

	foreach (sss_article_all_visible_books() as $book) {
		$data = sss_article_book_data($book->ID);
		if ($book->ID === $anchor->ID || strtolower($data['title']) === strtolower($anchor_data['title'])) {
			continue;
		}
		$series_number = (int) $data['series_number'];
		if ($series_number !== 0 && $series_number !== 1 && !$data['standalone']) {
			continue;
		}
		$score = 0;
		if (($data['shelf']['slug'] ?? '') === ($anchor_data['shelf']['slug'] ?? '') || strtolower((string) ($data['shelf']['name'] ?? '')) === strtolower((string) ($anchor_data['shelf']['name'] ?? ''))) {
			$score += 4;
		}
		foreach (wp_list_pluck($data['tropes'], 'slug') as $slug) {
			if (in_array($slug, $anchor_tropes, true)) {
				$score += 2;
			}
		}
		if ($data['spice'] > 0 && $anchor_data['spice'] > 0 && abs($data['spice'] - $anchor_data['spice']) <= 1) {
			$score++;
		}
		if ('stalker' === $cluster && 'haunting-adeline' === $book->post_name) {
			$score += 6;
		}
		if ('romantasy' === $cluster && (str_contains($book->post_name, 'fae') || str_contains($book->post_name, 'dragon'))) {
			$score++;
		}
		if ($score > $best_score) {
			$best = $book;
			$best_score = $score;
		}
	}

	return $best;
}

function sss_posts_in_guides(int $exclude): array {
	$posts = get_posts(array('post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => -1, 'post__not_in' => array($exclude), 'category_name' => 'curated-romance-guides'));
	if ($posts) {
		return $posts;
	}

	return get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post__not_in'   => array($exclude),
			'meta_key'       => '_shopify_blog_handle',
			'meta_value'     => 'curated-romance-guides',
		)
	);
}

function sss_find_article_for_book(WP_Post $book, int $exclude): ?WP_Post {
	foreach (sss_posts_in_guides($exclude) as $post) {
		foreach (sss_article_post_books($post->ID) as $candidate) {
			if ($candidate->ID === $book->ID || $candidate->post_name === $book->post_name || strtolower(get_the_title($candidate)) === strtolower(get_the_title($book))) {
				return $post;
			}
		}
	}
	return null;
}

function sss_find_article_by_context(string $context, int $exclude, ?WP_Post $different_from = null): ?WP_Post {
	foreach (sss_posts_in_guides($exclude) as $post) {
		if ($different_from && (int) $post->ID === (int) $different_from->ID) {
			continue;
		}
		$guide = sss_article_post(sss_article_field('guide_category', $post->ID, null));
		$trope = sss_article_post(sss_article_field('trope', $post->ID, null));
		foreach (array($guide, $trope) as $item) {
			if ($item && (str_contains($context, strtolower(get_the_title($item))) || str_contains($context, strtolower($item->post_name)))) {
				return $post;
			}
		}
	}

	return null;
}

function sss_readnext_shortcode($atts): string {
	$atts = shortcode_atts(array('post_id' => get_the_ID()), $atts, 'sss_readnext');
	$post_id = (int) $atts['post_id'];
	$books = sss_article_post_books($post_id);
	$anchor = $books[0] ?? null;
	if (!$anchor instanceof WP_Post) {
		return '';
	}

	$context = sss_readnext_context($post_id);
	$cluster = sss_detect_cluster($context);
	$paths = sss_specific_link_clusters()[$cluster] ?? sss_specific_link_clusters()['dark'];
	$page_paths = array_values(array_filter($paths, static fn(string $path): bool => str_starts_with($path, '/pages/')));
	$browse_link = isset($page_paths[0]) ? sss_resolve_shopify_path($page_paths[0]) : array('post' => null, 'url' => '', 'title' => '');
	$secondary_link = isset($page_paths[1]) ? sss_resolve_shopify_path($page_paths[1]) : array('post' => null, 'url' => '', 'title' => '');
	$guide_link = null;
	foreach ($paths as $path) {
		if (str_starts_with($path, '/blogs/')) {
			$guide_link = sss_resolve_shopify_path($path);
			break;
		}
	}

	$rec_book = sss_find_readnext_book($anchor, $cluster);
	$rec_article = $rec_book ? sss_find_article_for_book($rec_book, $post_id) : null;
	if (!$rec_article) {
		$rec_article = sss_find_article_by_context($context, $post_id);
	}
	$guide_article = $guide_link && $guide_link['post'] instanceof WP_Post ? $guide_link['post'] : null;
	if (!$guide_article || ($rec_article && (int) $guide_article->ID === (int) $rec_article->ID)) {
		$guide_article = sss_find_article_by_context($context, $post_id, $rec_article);
	}
	$copy = sss_readnext_copy($cluster, $secondary_link['post'] instanceof WP_Post ? $secondary_link['post'] : null, $guide_article, $browse_link['post'] instanceof WP_Post ? $browse_link['post'] : null, $rec_book);

	ob_start();
	?>
<section class="blog-readnext" aria-label="read these next">
  <div class="blog-readnext__eyebrow">read these next</div>
  <h2 class="blog-readnext__title">if you liked <?php echo esc_html(get_the_title($anchor)); ?>, read these next</h2>
  <p class="blog-readnext__intro">if this hit the right nerve — here's where to go next.</p>
  <div class="blog-readnext__list">

    <?php if ($rec_book && $rec_article) : ?>
      <?php $rec_data = sss_article_book_data($rec_book->ID); ?>
    <a class="blog-readnext__item blog-readnext__item--story" href="<?php echo esc_url(get_permalink($rec_article)); ?>">
      <span class="blog-readnext__arrow" aria-hidden="true">→</span>
      <span class="blog-readnext__media blog-readnext__media--book">
        <img src="<?php echo esc_url($rec_data['cover']); ?>" alt="<?php echo esc_attr($rec_data['title']); ?>" loading="lazy">
      </span>
      <span class="blog-readnext__body">
        <span class="blog-readnext__label"><?php echo esc_html(strtolower($rec_data['title'])); ?></span>
        <span class="blog-readnext__meta"><?php echo esc_html($copy['rec_blurb']); ?></span>
      </span>
    </a>
    <?php endif; ?>

    <?php if ($guide_article) : ?>
    <a class="blog-readnext__item blog-readnext__item--story" href="<?php echo esc_url(get_permalink($guide_article)); ?>">
      <span class="blog-readnext__arrow" aria-hidden="true">→</span>
      <span class="blog-readnext__media blog-readnext__media--article">
        <?php echo get_the_post_thumbnail($guide_article, 'medium', array('loading' => 'lazy', 'alt' => get_the_title($guide_article))); ?>
      </span>
      <span class="blog-readnext__body">
        <span class="blog-readnext__label"><?php echo esc_html($copy['guide_label']); ?></span>
        <span class="blog-readnext__meta"><?php echo esc_html($copy['guide_blurb']); ?></span>
      </span>
    </a>
    <?php endif; ?>

    <?php if (!empty($browse_link['url'])) : ?>
    <a class="blog-readnext__item blog-readnext__item--browse" href="<?php echo esc_url($browse_link['url']); ?>">
      <span class="blog-readnext__arrow" aria-hidden="true">→</span>
      <span class="blog-readnext__media blog-readnext__media--badge" aria-hidden="true"><?php echo esc_html($copy['browse_emoji']); ?></span>
      <span class="blog-readnext__body">
        <span class="blog-readnext__label"><?php echo esc_html($copy['browse_label']); ?></span>
        <span class="blog-readnext__meta"><?php echo esc_html($copy['browse_blurb']); ?></span>
      </span>
    </a>
    <?php endif; ?>

  </div>
</section>
	<?php
	return ob_get_clean();
}
add_shortcode('sss_readnext', 'sss_readnext_shortcode');
