<?php
declare(strict_types=1);

if (!function_exists('bbb_sss_drop_field_map')) {
	function bbb_sss_drop_field_map(array $entry): array {
		$fields = array();
		foreach ((array) ($entry['fields'] ?? array()) as $field) {
			if (!is_array($field) || empty($field['key'])) {
				continue;
			}

			$fields[(string) $field['key']] = $field;
		}

		return $fields;
	}
}

if (!function_exists('bbb_sss_drop_value')) {
	function bbb_sss_drop_value(array $fields, string $key, string $default = ''): string {
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

if (!function_exists('bbb_sss_drop_file_url')) {
	function bbb_sss_drop_file_url(array $fields, string $key): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return '';
		}

		$field = $fields[$key];
		$url   = $field['reference']['image']['url'] ?? $field['reference']['url'] ?? '';

		return is_string($url) ? $url : '';
	}
}

if (!function_exists('bbb_sss_drop_file_urls')) {
	function bbb_sss_drop_file_urls(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		$urls = array();
		foreach ($fields[$key]['references']['nodes'] as $node) {
			$url = $node['image']['url'] ?? $node['url'] ?? '';
			if (is_string($url) && '' !== $url) {
				$urls[] = $url;
			}
		}

		return $urls;
	}
}

if (!function_exists('bbb_sss_drop_file_items')) {
	function bbb_sss_drop_file_items(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		$items = array();
		foreach ($fields[$key]['references']['nodes'] as $node) {
			$url = $node['image']['url'] ?? $node['url'] ?? '';
			if (!is_string($url) || '' === $url) {
				continue;
			}

			$items[] = array(
				'url' => $url,
				'alt' => (string) ($node['image']['altText'] ?? ''),
			);
		}

		return $items;
	}
}

if (!function_exists('bbb_sss_drop_reference_items')) {
	function bbb_sss_drop_reference_items(array $fields, string $key): array {
		if (empty($fields[$key]['references']['nodes']) || !is_array($fields[$key]['references']['nodes'])) {
			return array();
		}

		return $fields[$key]['references']['nodes'];
	}
}

if (!function_exists('bbb_sss_drop_reference_item')) {
	function bbb_sss_drop_reference_item(array $fields, string $key): array {
		return !empty($fields[$key]['reference']) && is_array($fields[$key]['reference']) ? $fields[$key]['reference'] : array();
	}
}

if (!function_exists('bbb_sss_drop_link_url')) {
	function bbb_sss_drop_link_url(array $fields, string $key): string {
		if (empty($fields[$key]) || !is_array($fields[$key])) {
			return '';
		}

		$field = $fields[$key];
		$value = $field['jsonValue'] ?? $field['value'] ?? '';
		if (is_array($value)) {
			return trim((string) ($value['url'] ?? ''));
		}

		$value = trim((string) $value);
		if (str_starts_with($value, '{')) {
			$decoded = json_decode($value, true);
			return is_array($decoded) ? trim((string) ($decoded['url'] ?? '')) : '';
		}

		return $value;
	}
}

if (!function_exists('bbb_sss_drop_product_url')) {
	function bbb_sss_drop_product_url(array $product): string {
		$handle = sanitize_title((string) ($product['handle'] ?? ''));
		if ('' === $handle) {
			return bbb_page_url('shop');
		}

		if (post_type_exists('product')) {
			$wp_product = get_page_by_path($handle, OBJECT, 'product');
			if ($wp_product instanceof WP_Post) {
				return get_permalink($wp_product);
			}
		}

		return home_url('/product/' . $handle . '/');
	}
}

if (!function_exists('bbb_sss_render_drop_products')) {
	function bbb_sss_render_drop_products(array $products, string $heading, string $kicker): void {
		if (!$products) {
			return;
		}
		?>
		<section class="sss-drop-theme__products">
			<div class="sss-drop-theme__sectionHead">
				<p><?php echo esc_html($kicker); ?></p>
				<h2><?php echo esc_html($heading); ?></h2>
			</div>
			<div class="sss-drop-theme__productGrid">
				<?php foreach ($products as $product) : ?>
					<?php
					$title  = strtolower((string) ($product['title'] ?? 'printable'));
					$handle = sanitize_title((string) ($product['handle'] ?? ''));
					$url    = bbb_sss_drop_product_url($product);
					?>
					<a href="<?php echo esc_url($url); ?>" data-shopify-product-handle="<?php echo esc_attr($handle); ?>">
						<span><?php echo esc_html($title); ?></span>
						<small><?php echo esc_html($handle ? 'wordpress product slug: ' . $handle : 'product reference'); ?></small>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
	}
}

if (!function_exists('bbb_sss_active_drop')) {
	function bbb_sss_active_drop(): array {
		if (function_exists('bbb_sss_drop_importer_active_entry')) {
			$imported = bbb_sss_drop_importer_active_entry();
			if ($imported) {
				return $imported;
			}
		}

		$current_handle = '';
		if (function_exists('bbb_sss_drop_importer_current_handle')) {
			$current_handle = bbb_sss_drop_importer_current_handle();
		} elseif (function_exists('get_field')) {
			$field_handle = get_field('current_drop_handle', 'option');
			$current_handle = is_string($field_handle) ? sanitize_title($field_handle) : '';
		}

		$imported_posts = get_posts(
			array(
				'post_type'      => 'sss_drop',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'meta_key'       => '_bbb_sss_drop_release_date',
				'orderby'        => 'meta_value',
				'order'          => 'DESC',
			)
		);

		if ($imported_posts) {
			$today = (int) current_time('timestamp');
			$best = null;
			$best_time = 0;
			$fallback = null;
			$fallback_time = 0;
			$newest = null;
			$newest_time = 0;

			foreach ($imported_posts as $post) {
				if (!$post instanceof WP_Post) {
					continue;
				}

				$post_handle = sanitize_title((string) get_post_meta($post->ID, '_bbb_sss_drop_handle', true));
				if ('' !== $current_handle && ($post->post_name === $current_handle || $post_handle === $current_handle)) {
					$entry = json_decode((string) get_post_meta($post->ID, '_bbb_sss_drop_entry', true), true);
					return is_array($entry) ? $entry : array();
				}

				$release = (string) get_post_meta($post->ID, '_bbb_sss_drop_release_date', true);
				$time = '' !== $release ? strtotime($release . ' 00:00:00') : false;
				if (!$time) {
					continue;
				}

				$end = (string) get_post_meta($post->ID, '_bbb_sss_drop_end_date', true);
				$end_time = '' !== $end ? strtotime($end . ' 23:59:59') : false;
				if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
					$best = $post;
					$best_time = $time;
				}

				if ($time <= $today && $time >= $fallback_time) {
					$fallback = $post;
					$fallback_time = $time;
				}

				if ($time >= $newest_time) {
					$newest = $post;
					$newest_time = $time;
				}
			}

			$active_post = $best ?: $fallback ?: $newest ?: $imported_posts[0];
			if ($active_post instanceof WP_Post) {
				$entry = json_decode((string) get_post_meta($active_post->ID, '_bbb_sss_drop_entry', true), true);
				if (is_array($entry)) {
					return $entry;
				}
			}
		}

		$path = get_theme_file_path('firstpass/migration/exports/metaobjects/sss_drop.json');
		if (!file_exists($path)) {
			return array();
		}

		$raw = file_get_contents($path);
		if (!is_string($raw) || '' === trim($raw)) {
			return array();
		}

		$data = json_decode($raw, true);
		if (!is_array($data) || empty($data['entries']) || !is_array($data['entries'])) {
			return array();
		}

		$today    = (int) current_time('timestamp');
		$best     = array();
		$best_time = 0;
		$fallback = array();
		$fallback_time = 0;
		$newest = array();
		$newest_time = 0;
		foreach ($data['entries'] as $entry) {
			if (!is_array($entry)) {
				continue;
			}

			if ('' !== $current_handle && sanitize_title((string) ($entry['handle'] ?? '')) === $current_handle) {
				return $entry;
			}

			$fields = bbb_sss_drop_field_map($entry);
			$date   = bbb_sss_drop_value($fields, 'release_date');
			if ('' === $date) {
				continue;
			}

			$time = strtotime($date . ' 00:00:00');
			if (!$time) {
				continue;
			}

			$end_date = bbb_sss_drop_value($fields, 'end_date');
			$end_time = '' !== $end_date ? strtotime($end_date . ' 23:59:59') : false;
			if ($time <= $today && (!$end_time || $end_time >= $today) && $time >= $best_time) {
				$best      = $entry;
				$best_time = $time;
			}

			if ($time <= $today && $time >= $fallback_time) {
				$fallback      = $entry;
				$fallback_time = $time;
			}

			if ($time >= $newest_time) {
				$newest      = $entry;
				$newest_time = $time;
			}
		}

		return $best ?: $fallback ?: $newest;
	}
}

$drop = bbb_sss_active_drop();
$fields = $drop ? bbb_sss_drop_field_map($drop) : array();

if (!$drop) {
	?>
	<section class="sss-drop-theme sss-drop-theme--empty">
		<div class="sss-drop-theme__wrap">
			<header class="sss-drop-theme__hero">
				<p class="sss-drop-theme__kicker">monthly theme</p>
				<h1>drop data missing</h1>
				<p class="sss-drop-theme__mood">import the Shopify <code>sss_drop.json</code> in WordPress under <code>Users > Society Drops</code>, or keep the export file at <code>firstpass/migration/exports/metaobjects/sss_drop.json</code>.</p>
			</header>
		</div>
	</section>
	<style>
	.sss-drop-theme{background:#050505;color:#f6f6f6;padding:clamp(34px,6vw,76px) 18px;text-transform:lowercase}
	.sss-drop-theme__wrap{width:min(1180px,100%);margin:0 auto}
	.sss-drop-theme__hero{text-align:center;padding:10px 0 30px}
	.sss-drop-theme__kicker{margin:0 0 8px;color:#ff8ac7;font-size:11px;letter-spacing:.16em;text-transform:lowercase}
	.sss-drop-theme h1{margin:0;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:clamp(48px,8vw,104px);font-weight:400;line-height:.9;letter-spacing:0;text-transform:lowercase}
	.sss-drop-theme__mood{max-width:760px;margin:18px auto 0;color:rgba(246,246,246,.72);font-size:15px;line-height:1.6}
	.sss-drop-theme code{color:#fff}
	</style>
	<?php
	return;
}

$name       = bbb_sss_drop_value($fields, 'name', 'monthly theme');
$mood_title = bbb_sss_drop_value($fields, 'moodboard_title', $name);
$quote      = bbb_sss_drop_value($fields, 'quote_text', 'the atmosphere, the notes, and the songs are all gathered here.');
$accent     = bbb_sss_drop_value($fields, 'mood_accent', '#ff8ac7');
$pill_bg    = bbb_sss_drop_value($fields, 'mood_pill_bg', '#151515');
$pill_ink   = bbb_sss_drop_value($fields, 'mood_pill_ink', '#f6f6f6');
$emoji_list = bbb_sss_drop_value($fields, 'emoji_list', '🕯️, 🖤, ✦');
$gram_image = bbb_sss_drop_file_url($fields, 'gram_image');
$gram_kicker = bbb_sss_drop_value($fields, 'gram_kicker', 'from the gram');
$gram_title  = bbb_sss_drop_value($fields, 'gram_title', 'this belongs on your kindle');
$gram_sub    = bbb_sss_drop_value($fields, 'gram_sub', '');
$gram_caption = bbb_sss_drop_value($fields, 'gram_caption', '');
$spotify_url = bbb_sss_drop_value($fields, 'spotify_url');
$spotify_id  = '';
if ('' !== $spotify_url) {
	$spotify_path = (string) wp_parse_url($spotify_url, PHP_URL_PATH);
	$spotify_bits = array_values(array_filter(explode('/', $spotify_path)));
	$spotify_id = (string) end($spotify_bits);
}
$calendar_image = bbb_sss_drop_file_url($fields, 'calendar_image');
$calendar_pdf   = bbb_sss_drop_file_url($fields, 'calendar_pdf');
$pdf_link       = bbb_sss_drop_link_url($fields, 'pdf_link');
$canva_link     = bbb_sss_drop_link_url($fields, 'canva_link');
$wallpapers = bbb_sss_drop_file_items($fields, 'wallpaper_images');
if (!$wallpapers) {
	$wallpapers = bbb_sss_drop_file_items($fields, 'mood_images');
}
$mood_images = bbb_sss_drop_file_items($fields, 'mood_images');
$mood_stickers = bbb_sss_drop_file_items($fields, 'mood_stickers');
$era_images = bbb_sss_drop_file_items($fields, 'era_images');
$mood_quotes_raw = $fields['mood_quotes']['jsonValue'] ?? array();
$mood_quotes = is_array($mood_quotes_raw) ? array_values(array_filter(array_map('strval', $mood_quotes_raw))) : array();
$prompts_raw = bbb_sss_drop_value($fields, 'prompts');
$prompts = array_values(
	array_filter(
		array_map('trim', preg_split('/\s*\|\|\s*/', $prompts_raw) ?: array())
	)
);
$mood_pills = bbb_sss_drop_reference_items($fields, 'trial');
$printable_products = bbb_sss_drop_reference_items($fields, 'monthly_collection_printable_products');
$physical_products = bbb_sss_drop_reference_items($fields, 'monthly_collection_physical_products');
$bonus_printable = bbb_sss_drop_reference_item($fields, 'bonus_printable_product');
$bonus_physical = bbb_sss_drop_reference_item($fields, 'bonus_physical_product');
$bonus_products = array_values(array_filter(array($bonus_printable, $bonus_physical)));
$drop_handle = (string) ($drop['handle'] ?? '');
$drop_nav = array_filter(
	array(
		array('href' => '#drop-atmosphere', 'label' => 'atmosphere', 'show' => '' !== $gram_image || '' !== $spotify_id),
		array('href' => '#drop-moodboard', 'label' => 'moodboard', 'show' => (bool) ($mood_images || $mood_stickers || $era_images || $mood_quotes)),
		array('href' => '#drop-wallpapers', 'label' => 'wallpapers', 'show' => (bool) $wallpapers),
		array('href' => '#drop-calendar', 'label' => 'calendar', 'show' => '' !== $calendar_image || (bool) $prompts),
		array('href' => '#drop-products', 'label' => 'shop the drop', 'show' => (bool) ($printable_products || $physical_products || $bonus_products)),
	),
	static fn(array $item): bool => (bool) $item['show']
);
?>
<section class="sss-drop-theme" style="--drop-accent: <?php echo esc_attr($accent); ?>; --drop-pill-bg: <?php echo esc_attr($pill_bg); ?>; --drop-pill-ink: <?php echo esc_attr($pill_ink); ?>;">
	<div class="sss-drop-theme__wrap">
		<header class="sss-drop-theme__hero">
			<p class="sss-drop-theme__kicker">monthly theme</p>
			<h1><?php echo esc_html(strtolower($name)); ?></h1>
			<?php if ('' !== $drop_handle) : ?>
				<p class="sss-drop-theme__handle">sss drop: <?php echo esc_html($drop_handle); ?></p>
			<?php endif; ?>
			<p class="sss-drop-theme__mood"><?php echo esc_html(strtolower($mood_title)); ?></p>
			<?php if ('' !== $quote) : ?>
				<blockquote><?php echo nl2br(esc_html(strtolower($quote))); ?></blockquote>
			<?php endif; ?>
			<div class="sss-drop-theme__emojis" aria-label="theme mood">
				<?php foreach (array_filter(array_map('trim', explode(',', $emoji_list))) as $emoji) : ?>
					<span><?php echo esc_html($emoji); ?></span>
				<?php endforeach; ?>
			</div>
		</header>

		<?php if ($drop_nav) : ?>
			<nav class="sss-drop-theme__nav" aria-label="monthly theme sections">
				<?php foreach ($drop_nav as $item) : ?>
					<a href="<?php echo esc_url($item['href']); ?>"><?php echo esc_html($item['label']); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ($mood_pills) : ?>
			<section class="sss-drop-theme__pills" aria-label="reader mood pills">
				<h2>how are we entering this theme?</h2>
				<div class="sss-drop-theme__pillRow">
					<?php foreach ($mood_pills as $pill) : ?>
						<span><?php echo esc_html(strtolower((string) ($pill['displayName'] ?? $pill['handle'] ?? 'mood'))); ?></span>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="sss-drop-theme__grid" id="drop-atmosphere">
			<?php if ('' !== $gram_image) : ?>
				<article class="sss-drop-theme__panel sss-drop-theme__panel--gram">
					<div>
						<p><?php echo esc_html(strtolower($gram_kicker)); ?></p>
						<h2><?php echo esc_html(strtolower($gram_title)); ?></h2>
						<?php if ('' !== $gram_sub) : ?>
							<span><?php echo esc_html(strtolower($gram_sub)); ?></span>
						<?php endif; ?>
					</div>
					<img src="<?php echo esc_url($gram_image); ?>" alt="<?php echo esc_attr($name); ?>" loading="lazy">
					<?php if ('' !== $gram_caption) : ?>
						<small><?php echo esc_html(strtolower($gram_caption)); ?></small>
					<?php endif; ?>
				</article>
			<?php endif; ?>

			<?php if ('' !== $spotify_id) : ?>
				<article class="sss-drop-theme__panel">
					<p>playlist</p>
					<h2><?php echo esc_html(strtolower($name)); ?></h2>
					<iframe src="https://open.spotify.com/embed/playlist/<?php echo esc_attr($spotify_id); ?>?theme=0" width="100%" height="380" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture"></iframe>
				</article>
			<?php endif; ?>
		</div>

		<?php if ($mood_images || $mood_stickers || $era_images || $mood_quotes) : ?>
			<section class="sss-drop-theme__moodboard" id="drop-moodboard">
				<div class="sss-drop-theme__sectionHead">
					<p>moodboard</p>
					<h2>the vibe file</h2>
				</div>
				<?php if ($mood_quotes) : ?>
					<div class="sss-drop-theme__quoteRow">
						<?php foreach ($mood_quotes as $mood_quote) : ?>
							<span><?php echo esc_html(strtolower($mood_quote)); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php foreach (array('mood images' => $mood_images, 'stickers' => $mood_stickers, 'era images' => $era_images) as $label => $assets) : ?>
					<?php if ($assets) : ?>
						<p class="sss-drop-theme__assetLabel"><?php echo esc_html($label); ?></p>
						<div class="sss-drop-theme__assetGrid">
							<?php foreach ($assets as $asset) : ?>
								<a href="<?php echo esc_url($asset['url']); ?>" target="_blank" rel="noopener">
									<img src="<?php echo esc_url($asset['url']); ?>" alt="<?php echo esc_attr($asset['alt'] ?: $name . ' ' . $label); ?>" loading="lazy">
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				<?php endforeach; ?>
			</section>
		<?php endif; ?>

		<?php if ($wallpapers) : ?>
			<section class="sss-drop-theme__wallpapers" id="drop-wallpapers">
				<div class="sss-drop-theme__sectionHead">
					<p>wallpapers</p>
					<h2>the visual file</h2>
					<?php if ('' !== bbb_sss_drop_value($fields, 'wallpaper_canva_url')) : ?>
						<a href="<?php echo esc_url(bbb_sss_drop_value($fields, 'wallpaper_canva_url')); ?>" target="_blank" rel="noopener">edit in canva</a>
					<?php endif; ?>
				</div>
				<div class="sss-drop-theme__wallpaperGrid">
					<?php foreach ($wallpapers as $index => $wallpaper) : ?>
						<a href="<?php echo esc_url($wallpaper['url']); ?>" target="_blank" rel="noopener">
							<img src="<?php echo esc_url($wallpaper['url']); ?>" alt="<?php echo esc_attr($wallpaper['alt'] ?: $name . ' wallpaper ' . ((int) $index + 1)); ?>" loading="lazy">
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ('' !== $calendar_image || $prompts) : ?>
			<section class="sss-drop-theme__calendar" id="drop-calendar">
				<div class="sss-drop-theme__sectionHead">
					<p>journal + calendar</p>
					<h2>daily prompts</h2>
					<div class="sss-drop-theme__actions">
						<?php if ('' !== $calendar_pdf) : ?>
							<a href="<?php echo esc_url($calendar_pdf); ?>" target="_blank" rel="noopener">download pdf</a>
						<?php endif; ?>
						<?php if ('' !== $pdf_link) : ?>
							<a href="<?php echo esc_url($pdf_link); ?>" target="_blank" rel="noopener">open pdf link</a>
						<?php endif; ?>
						<?php if ('' !== $canva_link) : ?>
							<a href="<?php echo esc_url($canva_link); ?>" target="_blank" rel="noopener">edit in canva</a>
						<?php endif; ?>
					</div>
				</div>
				<?php if ('' !== $calendar_image) : ?>
					<img class="sss-drop-theme__calendarImage" src="<?php echo esc_url($calendar_image); ?>" alt="<?php echo esc_attr($name . ' calendar'); ?>" loading="lazy">
				<?php endif; ?>
				<?php if ($prompts) : ?>
					<ol class="sss-drop-theme__prompts">
						<?php foreach (array_slice($prompts, 0, 31) as $prompt) : ?>
							<li><?php echo esc_html(strtolower($prompt)); ?></li>
						<?php endforeach; ?>
					</ol>
				<?php endif; ?>
			</section>
		<?php endif; ?>

		<div id="drop-products">
		<?php bbb_sss_render_drop_products($printable_products, 'printable kindle inserts', 'current drop'); ?>
		<?php bbb_sss_render_drop_products($physical_products, 'physical kindle inserts', 'current drop'); ?>
		<?php bbb_sss_render_drop_products($bonus_products, 'bonus products', 'member bonus'); ?>
		</div>
	</div>
</section>

<style>
.sss-drop-theme{background:#050505;color:#f6f6f6;padding:clamp(34px,6vw,76px) 18px;text-transform:lowercase}
.sss-drop-theme__wrap{width:min(1180px,100%);margin:0 auto}
.sss-drop-theme__hero{text-align:center;padding:10px 0 30px}
.sss-drop-theme__kicker,.sss-drop-theme__sectionHead p,.sss-drop-theme__panel p{margin:0 0 8px;color:var(--drop-accent);font-size:11px;letter-spacing:.16em;text-transform:lowercase}
.sss-drop-theme h1,.sss-drop-theme h2{margin:0;color:#fff;font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-weight:400;letter-spacing:0;text-transform:lowercase}
.sss-drop-theme h1{font-size:clamp(48px,8vw,104px);line-height:.9}
.sss-drop-theme h2{font-size:clamp(25px,4vw,44px);line-height:1}
.sss-drop-theme__handle{display:inline-flex;margin:14px auto 0;padding:7px 11px;border:1px solid rgba(255,255,255,.12);border-radius:999px;background:rgba(255,255,255,.04);color:rgba(246,246,246,.62);font-size:11px;letter-spacing:.08em}
.sss-drop-theme__mood{margin:12px auto 0;color:rgba(246,246,246,.72);font-size:15px;line-height:1.6}
.sss-drop-theme blockquote{max-width:820px;margin:22px auto 0;color:rgba(246,246,246,.88);font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:clamp(24px,4vw,42px);font-style:italic;line-height:1.12}
.sss-drop-theme__emojis{display:flex;justify-content:center;gap:10px;margin-top:18px}
.sss-drop-theme__emojis span,.sss-drop-theme__pillRow span{display:inline-flex;align-items:center;justify-content:center;min-height:34px;padding:8px 12px;border:1px solid rgba(255,255,255,.14);border-radius:999px;background:rgba(255,255,255,.045)}
.sss-drop-theme__nav{position:sticky;top:0;z-index:5;display:flex;flex-wrap:wrap;justify-content:center;gap:8px;margin:0 0 24px;padding:10px;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(5,5,5,.86);backdrop-filter:blur(14px);box-shadow:0 16px 40px rgba(0,0,0,.28)}
.sss-drop-theme__nav a{display:inline-flex;align-items:center;min-height:36px;padding:0 13px;border:1px solid rgba(255,255,255,.13);border-radius:999px;background:rgba(255,255,255,.05);color:rgba(246,246,246,.82);font-size:12px;letter-spacing:.08em;text-decoration:none}
.sss-drop-theme__nav a:hover,.sss-drop-theme__nav a:focus{border-color:var(--drop-accent);color:#fff;outline:none}
.sss-drop-theme__pills{margin:0 auto 26px;text-align:center}
.sss-drop-theme__pillRow{display:flex;flex-wrap:wrap;justify-content:center;gap:9px;margin-top:14px}
.sss-drop-theme__pillRow span{background:var(--drop-pill-bg);color:var(--drop-pill-ink);border-color:rgba(255,255,255,.22);font-size:13px}
.sss-drop-theme__grid{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);gap:16px;margin-top:24px}
.sss-drop-theme__panel,.sss-drop-theme__wallpapers,.sss-drop-theme__calendar,.sss-drop-theme__products,.sss-drop-theme__moodboard{border:1px solid rgba(255,255,255,.12);border-radius:10px;background:rgba(255,255,255,.035);box-shadow:0 20px 60px rgba(0,0,0,.34)}
.sss-drop-theme__panel{padding:16px}
.sss-drop-theme__panel--gram{display:grid;gap:14px}
.sss-drop-theme__panel img{display:block;width:100%;border-radius:8px}
.sss-drop-theme__panel span,.sss-drop-theme__panel small{display:block;margin-top:8px;color:rgba(246,246,246,.68);font-size:13px;line-height:1.5}
.sss-drop-theme iframe{display:block;margin-top:14px;border:0;border-radius:8px;background:#111}
.sss-drop-theme__wallpapers,.sss-drop-theme__calendar,.sss-drop-theme__products,.sss-drop-theme__moodboard{margin-top:16px;padding:16px}
.sss-drop-theme__sectionHead{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:14px}
.sss-drop-theme__sectionHead a,.sss-drop-theme__actions a,.sss-drop-theme__productGrid a{color:#ff8ac7;text-decoration:none}
.sss-drop-theme__quoteRow{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.sss-drop-theme__quoteRow span{display:inline-flex;padding:8px 10px;border:1px solid rgba(255,255,255,.1);border-radius:999px;background:rgba(0,0,0,.2);color:rgba(246,246,246,.72);font-size:12px}
.sss-drop-theme__assetLabel{margin:16px 0 8px;color:var(--drop-accent);font-size:11px;letter-spacing:.14em;text-transform:lowercase}
.sss-drop-theme__assetGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.sss-drop-theme__assetGrid img{display:block;width:100%;aspect-ratio:1;object-fit:cover;border-radius:8px;background:#111}
.sss-drop-theme__wallpaperGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.sss-drop-theme__wallpaperGrid img{display:block;width:100%;aspect-ratio:9/16;object-fit:cover;border-radius:8px}
.sss-drop-theme__calendarImage{display:block;width:100%;border-radius:8px}
.sss-drop-theme__actions{display:flex;flex-wrap:wrap;gap:10px;justify-content:flex-end}
.sss-drop-theme__prompts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;margin:14px 0 0;padding:0;list-style:none;counter-reset:prompts}
.sss-drop-theme__prompts li{counter-increment:prompts;padding:11px;border:1px solid rgba(255,255,255,.1);border-radius:8px;background:rgba(0,0,0,.2);color:rgba(246,246,246,.74);font-size:13px;line-height:1.45}
.sss-drop-theme__prompts li:before{content:counter(prompts,decimal-leading-zero);display:block;margin-bottom:6px;color:var(--drop-accent);font-size:10px;letter-spacing:.12em}
.sss-drop-theme__productGrid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px}
.sss-drop-theme__productGrid a{min-height:92px;padding:12px;border:1px solid rgba(255,255,255,.12);border-radius:8px;background:rgba(0,0,0,.2);font-family:Cormorant,"Cormorant Garamond",Georgia,serif;font-size:21px;line-height:1.05}
.sss-drop-theme__productGrid span,.sss-drop-theme__productGrid small{display:block}
.sss-drop-theme__productGrid small{margin-top:10px;color:rgba(246,246,246,.46);font-family:inherit;font-size:11px;line-height:1.35}
@media (max-width:800px){.sss-drop-theme__grid,.sss-drop-theme__prompts,.sss-drop-theme__productGrid,.sss-drop-theme__assetGrid{grid-template-columns:1fr}.sss-drop-theme__nav{position:relative;justify-content:flex-start;overflow-x:auto;flex-wrap:nowrap}.sss-drop-theme__nav a{white-space:nowrap}.sss-drop-theme__wallpaperGrid{display:flex;overflow-x:auto;padding-bottom:4px}.sss-drop-theme__wallpaperGrid a{min-width:46%}.sss-drop-theme__sectionHead{display:block}.sss-drop-theme__actions{justify-content:flex-start;margin-top:10px}}
</style>
