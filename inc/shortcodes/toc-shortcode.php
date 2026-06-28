<?php
/**
 * Table of contents shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_toc_shortcode_has_toc(string $content): bool {
	return has_shortcode($content, 'toc') || has_shortcode($content, 'bbb_toc');
}

function bbb_toc_unique_id(string $text, array &$seen): string {
	$base = sanitize_title($text);

	if ('' === $base) {
		$base = 'section';
	}

	$count = (int) ($seen[$base] ?? 0);
	$seen[$base] = $count + 1;

	return $count > 0 ? $base . '-' . ($count + 1) : $base;
}

function bbb_toc_heading_id(string $heading_html, string $heading_text, array &$seen): string {
	if (preg_match('/\sid=(["\'])(.*?)\1/i', $heading_html, $matches)) {
		$id = sanitize_html_class($matches[2]);
		if ('' !== $id) {
			$seen[$id] = max(1, (int) ($seen[$id] ?? 0));
		}

		return $id;
	}

	return bbb_toc_unique_id($heading_text, $seen);
}

function bbb_toc_should_skip_heading(string $text): bool {
	$normalized = strtolower(trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($text)) ?? $text));

	foreach (array('my take', 'want more like this? say less.', 'join the newsletter') as $skip_heading) {
		if ($normalized === $skip_heading) {
			return true;
		}
	}

	return false;
}

function bbb_toc_get_headings(int $post_id): array {
	$content = (string) get_post_field('post_content', $post_id);
	if ('' === trim($content) || !bbb_toc_shortcode_has_toc($content)) {
		return array();
	}

	$content = preg_replace('/\[\/?bbb_toc[^\]]*\]|\[\/?toc[^\]]*\]/i', '', $content) ?? $content;
	$html    = do_blocks($content);

	if (function_exists('sss_token_engine')) {
		$html = sss_token_engine($html, $post_id);
	}

	if (!preg_match_all('/<h([23])\b([^>]*)>(.*?)<\/h\1>/is', $html, $matches, PREG_SET_ORDER)) {
		return array();
	}

	$seen     = array();
	$headings = array();
	foreach ($matches as $match) {
		$text = trim(wp_strip_all_tags(strip_shortcodes($match[3])));
		if ('' === $text || bbb_toc_should_skip_heading($text)) {
			continue;
		}

		$headings[] = array(
			'level' => (int) $match[1],
			'id'    => bbb_toc_heading_id($match[0], $text, $seen),
			'text'  => $text,
		);
	}

	return $headings;
}

function bbb_toc_shortcode($atts = array()): string {
	$atts = shortcode_atts(
		array(
			'title'     => 'pick your poison.',
			'min_items' => 2,
		),
		$atts,
		'toc'
	);

	$post_id  = get_the_ID();
	$headings = $post_id ? bbb_toc_get_headings((int) $post_id) : array();

	if (count($headings) < max(1, (int) $atts['min_items'])) {
		return '';
	}

	ob_start();
	?>
<nav class="bbb-toc" aria-label="table of contents">
  <p class="bbb-toc__eyebrow"><?php echo esc_html((string) $atts['title']); ?></p>
  <ul class="bbb-toc__list">
    <?php foreach ($headings as $heading) : ?>
      <?php $is_faq = 'frequently asked questions' === strtolower($heading['text']); ?>
      <li class="bbb-toc__item bbb-toc__item--h<?php echo esc_attr((string) $heading['level']); ?><?php echo $is_faq ? ' bbb-toc__item--faq' : ''; ?>">
        <?php
		$heading_text = $heading['text'];
		$emoji        = '';
		if (!$is_faq && preg_match('/^([^\p{L}\p{N}\s]+)\s*(.+)$/u', $heading_text, $parts)) {
			$emoji        = trim($parts[1]);
			$heading_text = trim($parts[2]);
		}
		?>
        <a class="bbb-toc__link" href="#<?php echo esc_attr($heading['id']); ?>">
          <?php if ('' !== $emoji) : ?>
            <span class="bbb-toc__emoji" aria-hidden="true"><?php echo esc_html($emoji); ?></span>
          <?php endif; ?>
          <span class="bbb-toc__text"><?php echo esc_html($heading_text); ?></span>
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</nav>
	<?php
	return ob_get_clean();
}
add_shortcode('toc', 'bbb_toc_shortcode');
add_shortcode('bbb_toc', 'bbb_toc_shortcode');

add_filter(
	'the_content',
	static function (string $content): string {
		if (!str_contains($content, 'bbb-toc') || !preg_match('/<h[23]\b/i', $content)) {
			return $content;
		}

		$seen = array();

		return preg_replace_callback(
			'/<h([23])\b([^>]*)>(.*?)<\/h\1>/is',
			static function (array $matches) use (&$seen): string {
				if (preg_match('/\sid=(["\'])(.*?)\1/i', $matches[2])) {
					$id = sanitize_html_class($matches[2]);
					if ('' !== $id) {
						$seen[$id] = max(1, (int) ($seen[$id] ?? 0));
					}

					return $matches[0];
				}

				$text = trim(wp_strip_all_tags(strip_shortcodes($matches[3])));
				if ('' === $text) {
					return $matches[0];
				}

				$id = bbb_toc_unique_id($text, $seen);

				return '<h' . $matches[1] . $matches[2] . ' id="' . esc_attr($id) . '">' . $matches[3] . '</h' . $matches[1] . '>';
			},
			$content
		) ?? $content;
	},
	12
);
