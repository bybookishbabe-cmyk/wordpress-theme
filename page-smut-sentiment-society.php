<?php
/**
 * Template Name: the society landing
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!function_exists('bbb_society_landing_field_map')) {
	function bbb_society_landing_field_map(array $entry): array {
		$fields = array();
		foreach ((array) ($entry['fields'] ?? array()) as $field) {
			if (is_array($field) && !empty($field['key'])) {
				$fields[(string) $field['key']] = $field;
			}
		}

		return $fields;
	}
}

if (!function_exists('bbb_society_landing_field_value')) {
	function bbb_society_landing_field_value(array $fields, string $key, string $default = ''): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return $default;
		}

		$field = $fields[$key];
		$value = $field['jsonValue'] ?? $field['value'] ?? $default;
		if (is_array($value)) {
			$value = $field['value'] ?? $default;
		}

		return trim((string) $value);
	}
}

if (!function_exists('bbb_society_landing_active_drop')) {
	function bbb_society_landing_active_drop(): array {
		if (function_exists('bbb_sss_drop_importer_active_entry')) {
			$imported = bbb_sss_drop_importer_active_entry();
			if ($imported) {
				return $imported;
			}
		}

		$json = function_exists('bbb_sss_drop_importer_export_json') ? bbb_sss_drop_importer_export_json() : '';
		if ('' === $json) {
			$path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_drop.json');
			if (!file_exists($path)) {
				return array();
			}

			$file_json = file_get_contents($path);
			$json = is_string($file_json) ? $file_json : '';
		}

		$data = json_decode($json, true);
		if (!is_array($data) || empty($data['entries']) || !is_array($data['entries'])) {
			return array();
		}

		$today = (int) current_time('timestamp');
		$best = array();
		$best_time = 0;
		$fallback = array();
		$fallback_time = 0;

		foreach ($data['entries'] as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			$fields = bbb_society_landing_field_map($entry);
			$date = bbb_society_landing_field_value($fields, 'release_date');
			$time = '' !== $date ? strtotime($date . ' 00:00:00') : false;
			if (!$time) {
				continue;
			}

			$end = bbb_society_landing_field_value($fields, 'end_date');
			$end_time = '' !== $end ? strtotime($end . ' 23:59:59') : false;
			if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
				$best = $entry;
				$best_time = $time;
			}

			if ($time <= $today && $time >= $fallback_time) {
				$fallback = $entry;
				$fallback_time = $time;
			}
		}

		return $best ?: $fallback;
	}
}

if (!function_exists('bbb_society_landing_ref_nodes')) {
	function bbb_society_landing_ref_nodes(array $fields, array $keys): array {
		$nodes = array();
		foreach ($keys as $key) {
			if (!empty($fields[$key]['reference']) && is_array($fields[$key]['reference'])) {
				$nodes[] = $fields[$key]['reference'];
			}

			foreach ((array) ($fields[$key]['references']['nodes'] ?? array()) as $node) {
				if (is_array($node)) {
					$nodes[] = $node;
				}
			}
		}

		return $nodes;
	}
}

if (!function_exists('bbb_society_landing_product_export_images')) {
	function bbb_society_landing_product_export_images(): array {
		static $images = null;
		if (null !== $images) {
			return $images;
		}

		$images = array();
		$products = function_exists('bbb_society_product_importer_export_rows') ? bbb_society_product_importer_export_rows() : array();
		foreach ($products as $product) {
			if (!is_array($product) || empty($product['handle']) || empty($product['image_url'])) {
				continue;
			}

			$images[sanitize_title((string) $product['handle'])] = bbb_society_landing_upload_url((string) $product['image_url']);
		}

		return $images;
	}
}

if (!function_exists('bbb_society_landing_upload_url')) {
	function bbb_society_landing_upload_url(string $url): string {
		$url = trim($url);
		if ('' === $url) {
			return '';
		}

		if (function_exists('bbb_society_product_importer_media_url')) {
			$media_url = bbb_society_product_importer_media_url($url);
			if ('' !== $media_url) {
				return $media_url;
			}
		}

		if (str_starts_with($url, '/wp-content/')) {
			$url = home_url($url);
		} else {
			$path = (string) wp_parse_url($url, PHP_URL_PATH);
			if (str_starts_with($path, '/wp-content/')) {
				$query = (string) wp_parse_url($url, PHP_URL_QUERY);
				$url = home_url($path . ('' !== $query ? '?' . $query : ''));
			}
		}

		$host = (string) wp_parse_url($url, PHP_URL_HOST);
		if (preg_match('/(^localhost$|^127\.|\.local$)/', $host)) {
			$url = set_url_scheme($url, 'http');
		}

		return esc_url_raw($url);
	}
}

if (!function_exists('bbb_society_landing_download_image')) {
	function bbb_society_landing_download_image(string $handle): string {
		if ('' === $handle || !post_type_exists('download')) {
			return '';
		}

		$download = get_page_by_path($handle, OBJECT, 'download');
		if (!$download instanceof WP_Post) {
			return '';
		}

		$image = get_the_post_thumbnail_url($download, 'medium_large');
		if (is_string($image) && '' !== $image) {
			return bbb_society_landing_upload_url($image);
		}

		return bbb_society_landing_upload_url((string) get_post_meta($download->ID, '_bbb_source_image_url', true));
	}
}

if (!function_exists('bbb_society_landing_product_url')) {
	function bbb_society_landing_product_url(string $handle): string {
		$handle = sanitize_title($handle);
		if ('' === $handle) {
			return bbb_page_url('shop');
		}

		if (post_type_exists('product')) {
			$product = get_page_by_path($handle, OBJECT, 'product');
			if ($product instanceof WP_Post) {
				return get_permalink($product);
			}
		}

		return home_url('/product/' . $handle . '/');
	}
}

$reader_state = 'visitor';
$is_paid_society_member = function_exists('bbb_reader_is_society') && bbb_reader_is_society();
if ($is_paid_society_member) {
	$reader_state = 'paid member';
} elseif (is_user_logged_in()) {
	$reader_state = 'free member';
}

$monthly_theme = strtolower((string) date_i18n('F')) . ' theme';
$monthly_hub = array(
	'kicker' => strtolower((string) get_theme_mod('bbb_society_month_kicker', 'monthly theme')),
	'title'  => strtolower((string) get_theme_mod('bbb_society_month_title', 'burn for me')),
	'text'   => strtolower((string) get_theme_mod('bbb_society_month_text', 'dark romance month with mafia, obsession, enemies to lovers, and the member tools that keep the whole reading life in one place.')),
	'image'  => '',
);
if ('this month inside the society' === $monthly_hub['kicker']) {
	$monthly_hub['kicker'] = 'monthly theme';
}
$monthly_theme_url = bbb_page_url('monthly-theme');
$active_drop = bbb_society_landing_active_drop();
$active_fields = $active_drop ? bbb_society_landing_field_map($active_drop) : array();
$drop_products = array();
$fallback_images = array();

if ($active_fields) {
	$monthly_hub['kicker'] = 'monthly theme';
	$monthly_hub['title'] = strtolower(bbb_society_landing_field_value($active_fields, 'name', $monthly_hub['title']));
	$monthly_hub['text'] = strtolower(bbb_society_landing_field_value($active_fields, 'quote_text', $monthly_hub['text']));
	$monthly_hub['image'] = (string) ($active_fields['calendar_image']['reference']['image']['url'] ?? $active_fields['gram_image']['reference']['image']['url'] ?? '');

	$fallback_nodes = bbb_society_landing_ref_nodes($active_fields, array('wallpaper_images', 'mood_images', 'era_images'));
	foreach ($fallback_nodes as $node) {
		$image = (string) ($node['image']['url'] ?? $node['url'] ?? '');
		if ('' !== $image) {
			$fallback_images[] = $image;
		}
	}

	$product_images = bbb_society_landing_product_export_images();
	$product_nodes = bbb_society_landing_ref_nodes(
		$active_fields,
		array(
			'bonus_printable_product',
			'bonus_physical_product',
			'monthly_collection_printable_products',
			'monthly_collection_physical_products',
		)
	);
	$seen_products = array();
	foreach ($product_nodes as $index => $product) {
		$handle = sanitize_title((string) ($product['handle'] ?? ''));
		$title = strtolower((string) ($product['title'] ?? $handle));
		if ('' === $handle || isset($seen_products[$handle])) {
			continue;
		}

		$seen_products[$handle] = true;
		$image = $product_images[$handle] ?? '';
		if ('' === $image) {
			$image = bbb_society_landing_download_image($handle);
		}

		if ('' === $image && post_type_exists('product')) {
			$wp_product = get_page_by_path($handle, OBJECT, 'product');
			if ($wp_product instanceof WP_Post) {
				$image = (string) (get_the_post_thumbnail_url($wp_product, 'medium') ?: get_post_meta($wp_product->ID, '_bbb_source_image_url', true));
			}
		}

		if ('' === $image && !empty($fallback_images)) {
			$image = $fallback_images[$index % count($fallback_images)];
		}

		$drop_products[] = array(
			'title'  => $title,
			'handle' => $handle,
			'image'  => $image,
			'url'    => bbb_society_landing_product_url($handle),
		);
	}
}

$sections = array(
	array(
		'label' => 'the newsletter',
		'items' => array(
			array('title' => 'about', 'copy' => 'what the society is, who it is for, and how the newsletter fits in.', 'url' => bbb_page_url('about-the-society'), 'badge' => 'start', 'emoji' => '💌'),
			array('title' => 'recent', 'copy' => 'the latest newsletter issues and current dispatches.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'latest', 'emoji' => '🗞️'),
			array('title' => 'full archive', 'copy' => 'the complete newsletter shelf, wired to the imported issues.', 'url' => bbb_page_url('society-newsletter-archive'), 'badge' => 'archive', 'emoji' => '🗂️'),
			array('title' => 'society submissions', 'copy' => 'send in your hot takes, quotes, recs, and reader-core thoughts for the newsletter.', 'url' => bbb_page_url('society-submissions'), 'badge' => 'submit', 'emoji' => '✍️'),
		),
	),
	array(
		'label' => 'society exclusives',
		'items' => array(
			array('title' => 'reader favorites', 'copy' => 'the 10 most-visited quizzes, trope pages, guides, and blog reads.', 'url' => bbb_page_url('popular-pages'), 'badge' => 'popular', 'emoji' => '📖'),
			array('title' => 'exclusive rec lists', 'copy' => 'if you liked pages built from the books-like template.', 'url' => bbb_page_url('if-you-liked-pages'), 'badge' => 'society', 'emoji' => '🌹'),
			array('title' => 'early access', 'copy' => 'posts and picks before they go public.', 'url' => bbb_page_url('society-newsletter-recent'), 'badge' => 'preview', 'emoji' => '🔐'),
		),
	),
	array(
		'label' => 'member tools',
		'items' => array(
			array('title' => 'book tracking calendar', 'copy' => 'the shopify read tracker: click a day, choose the book you read, and let the cover live there.', 'url' => bbb_page_url('book-tracking-calendar'), 'badge' => 'society', 'emoji' => '📅'),
			array('title' => 'my bookshelf', 'copy' => 'your saved books, current obsessions, and personal romance archive.', 'url' => bbb_page_url('my-bookshelf'), 'badge' => 'free', 'emoji' => '📚'),
			array('title' => 'member dashboard', 'copy' => 'made-for-you reader logic, mood-based recommendations, and smarter next-read picks.', 'url' => bbb_page_url('member-dashboard'), 'badge' => 'society', 'emoji' => '✨'),
		),
	),
	array(
		'label' => 'shop perks',
		'items' => array(
			array('title' => 'monthly freebie', 'copy' => 'a rotating digital good for paid members.', 'url' => bbb_page_url('shop'), 'badge' => 'society', 'emoji' => '🎁'),
			array('title' => 'shop discount', 'copy' => 'member savings on templates, printables, and extras.', 'url' => bbb_page_url('shop'), 'badge' => 'society', 'emoji' => '🏷️'),
		),
	),
);

get_header();
?>

<section class="bbb-society-landing" aria-labelledby="bbb-society-title">
	<div class="bbb-society-landing__inner">
		<div class="bbb-society-landing__hero">
			<p class="bbb-society-landing__eyebrow">the smut and sentiment society</p>
			<h1 id="bbb-society-title">the society</h1>
			<p class="bbb-society-landing__intro">
				a central page for the newsletter, the archive, and the society pieces that live around each issue.
			</p>
			<div class="bbb-society-landing__status">
				<span class="bbb-society-landing__statusLabel">current view</span>
				<strong><?php echo esc_html($reader_state); ?></strong>
			</div>
		</div>

		<aside class="bbb-society-theme bbb-society-theme--main" aria-label="<?php echo esc_attr($monthly_theme); ?>">
			<a class="bbb-society-theme__featureLink" href="<?php echo esc_url($monthly_theme_url); ?>">
				<span class="bbb-society-theme__content">
					<p class="bbb-society-theme__eyebrow"><?php echo esc_html($monthly_hub['kicker']); ?></p>
					<h2><?php echo esc_html($monthly_hub['title']); ?></h2>
					<p><?php echo esc_html($monthly_hub['text']); ?></p>
					<span class="bbb-society-theme__cta">open monthly theme</span>
				</span>
				<?php if ($monthly_hub['image'] || $drop_products) : ?>
					<span class="bbb-society-theme__preview" aria-label="monthly theme preview">
						<?php if ($monthly_hub['image']) : ?>
							<span class="bbb-society-theme__dropImage">
								<img src="<?php echo esc_url($monthly_hub['image']); ?>" alt="<?php echo esc_attr($monthly_hub['title']); ?>" loading="lazy">
							</span>
						<?php endif; ?>
						<?php if ($drop_products) : ?>
							<span class="bbb-society-theme__products">
								<?php foreach (array_slice($drop_products, 0, 4) as $product) : ?>
									<span class="bbb-society-theme__product">
										<?php if ($product['image']) : ?>
											<img src="<?php echo esc_url($product['image']); ?>" alt="<?php echo esc_attr($product['title']); ?>" loading="lazy">
										<?php endif; ?>
									</span>
								<?php endforeach; ?>
							</span>
						<?php endif; ?>
					</span>
				<?php endif; ?>
			</a>
		</aside>

		<div class="bbb-society-sections">
			<?php foreach ($sections as $section) : ?>
				<section class="bbb-society-section" aria-labelledby="<?php echo esc_attr(sanitize_title($section['label'])); ?>">
					<h2 id="<?php echo esc_attr(sanitize_title($section['label'])); ?>"><?php echo esc_html($section['label']); ?></h2>
					<div class="bbb-society-link-grid">
						<?php foreach ($section['items'] as $item) : ?>
							<a class="bbb-society-link-card" href="<?php echo esc_url($item['url']); ?>">
								<span class="bbb-society-link-card__top">
									<span class="bbb-society-link-card__emoji" aria-hidden="true"><?php echo esc_html($item['emoji'] ?? '♡'); ?></span>
									<span class="bbb-society-link-card__title"><?php echo esc_html($item['title']); ?></span>
									<span class="bbb-society-link-card__badge"><?php echo esc_html($item['badge']); ?></span>
								</span>
								<span class="bbb-society-link-card__copy"><?php echo esc_html($item['copy']); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php
get_footer();
