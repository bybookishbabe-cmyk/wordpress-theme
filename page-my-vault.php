<?php
/**
 * Template Name: My Vault
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

nocache_headers();

add_filter(
	'body_class',
	static function (array $classes): array {
		$classes[] = 'bbb-my-vault-page';
		return $classes;
	}
);

$identity = function_exists('bbb_vault_current_identity') ? bbb_vault_current_identity() : null;
$has_identity = is_array($identity) && '' !== trim((string) ($identity['email'] ?? ''));
$has_full_vault = $has_identity && function_exists('bbb_vault_user_has_full_access') && bbb_vault_user_has_full_access($identity);
$assets = function_exists('bbb_vault_assets') ? bbb_vault_assets() : array();
$groups = array(
	'kindle inserts' => array(),
	'templates'      => array(),
	'trackers'       => array(),
	'extras'         => array(),
);
foreach ($assets as $asset) {
	if (!is_array($asset)) {
		continue;
	}
	$group = (string) ($asset['group'] ?? 'extras');
	if (!isset($groups[$group])) {
		$group = 'extras';
	}
	$groups[$group][] = $asset;
}

$asset_count = count($assets);
$file_count = array_reduce(
	$assets,
	static fn(int $carry, array $asset): int => $carry + (int) ($asset['fileCount'] ?? 0),
	0
);
$account_url = function_exists('bbb_page_url') ? bbb_page_url('account') : home_url('/account/');
$shop_url = function_exists('bbb_page_url') ? bbb_page_url('shop') : home_url('/shop/');
$buy_url = function_exists('bbb_vault_buy_url') ? bbb_vault_buy_url() : $shop_url;
$reader_email_error = isset($_GET['reader_email_error']) ? sanitize_text_field((string) wp_unslash($_GET['reader_email_error'])) : '';

if (!function_exists('bbb_vault_first_matching_filter')) {
	function bbb_vault_first_matching_filter(string $haystack, array $groups, string $fallback = 'other'): string {
		foreach ($groups as $slug => $needles) {
			foreach ($needles as $needle) {
				$needle = trim((string) $needle);
				if ('' === $needle) {
					continue;
				}

				if (preg_match('/(^|[^a-z0-9])' . preg_quote($needle, '/') . '([^a-z0-9]|$)/', $haystack)) {
					return (string) $slug;
				}
			}
		}

		return $fallback;
	}
}

if (!function_exists('bbb_vault_filter_slug')) {
	function bbb_vault_filter_slug(string $value): string {
		return sanitize_title($value);
	}
}

if (!function_exists('bbb_vault_color_groups')) {
	function bbb_vault_color_groups(): array {
		return array(
			'pink'    => array('pink', 'blush', 'rose', 'rosy', 'mauve', 'berry', 'fuchsia', 'magenta'),
			'red'     => array('red', 'crimson', 'scarlet', 'burgundy', 'wine', 'cherry'),
			'orange'  => array('orange', 'peach', 'coral', 'apricot', 'terracotta', 'autumn', 'fall'),
			'yellow'  => array('yellow', 'gold', 'golden', 'honey', 'butter'),
			'green'   => array('green', 'sage', 'olive', 'emerald', 'mint', 'forest'),
			'blue'    => array('blue', 'navy', 'sky', 'teal', 'turquoise', 'aqua'),
			'purple'  => array('purple', 'lavender', 'lilac', 'violet', 'plum'),
			'black'   => array('black', 'noir', 'gothic', 'charcoal'),
			'neutral' => array('white', 'cream', 'ivory', 'nude', 'beige', 'tan', 'neutral', 'brown', 'espresso', 'chocolate'),
			'gray'    => array('gray', 'grey', 'silver', 'fog'),
		);
	}
}

if (!function_exists('bbb_vault_theme_groups')) {
	function bbb_vault_theme_groups(): array {
		return array(
			'dark-romance' => array('dark romance', 'dark', 'gothic', 'villain', 'obsession', 'ruin', 'fourth wing'),
			'soft-romance' => array('soft romance', 'soft', 'romance', 'romantic', 'blush', 'happily ever after'),
			'fantasy'      => array('fantasy', 'fairy', 'fae', 'dragon', 'wing', 'fairytale', 'once upon'),
			'floral'       => array('floral', 'flower', 'rose', 'garden'),
			'seasonal'     => array('seasonal', 'spring', 'summer', 'autumn', 'fall', 'winter', 'holiday', 'christmas'),
			'western'      => array('western', 'cowboy'),
			'minimal'      => array('minimal', 'neutral', 'nude', 'subtle', 'classic'),
			'smutty'       => array('smutty', 'smut', 'spicy'),
			'aesthetic'    => array('aesthetic', 'bookish', 'reading', 'pages'),
		);
	}
}

if (!function_exists('bbb_vault_product_export')) {
	function bbb_vault_product_export(string $handle): array {
		$handle = sanitize_title($handle);
		if ('' === $handle || !function_exists('bbb_society_product_importer_export_rows')) {
			return array();
		}

		foreach (bbb_society_product_importer_export_rows() as $product) {
			if (is_array($product) && sanitize_title((string) ($product['handle'] ?? '')) === $handle) {
				return $product;
			}
		}

		return array();
	}
}

if (!function_exists('bbb_vault_filter_haystack')) {
	function bbb_vault_filter_haystack(array $asset, string $group_name): string {
		$post_id = (int) ($asset['id'] ?? 0);
		$terms = array();
		foreach (array('download_category', 'download_tag', 'product_cat', 'product_tag') as $taxonomy) {
			if ($post_id < 1 || !taxonomy_exists($taxonomy)) {
				continue;
			}

			$post_terms = get_the_terms($post_id, $taxonomy);
			if (is_array($post_terms)) {
				$terms = array_merge($terms, wp_list_pluck($post_terms, 'name'));
			}
		}

		$file_labels = array();
		foreach ((array) ($asset['files'] ?? array()) as $file) {
			if (is_array($file) && !empty($file['label'])) {
				$file_labels[] = (string) $file['label'];
			}
		}

		$handle = $post_id > 0 ? (string) get_post_field('post_name', $post_id) : '';
		$export = bbb_vault_product_export($handle);

		return strtolower(
			(string) ($asset['title'] ?? '') . ' ' .
			$group_name . ' ' .
			($post_id > 0 ? (string) get_post_meta($post_id, '_bbb_shopify_product_type', true) : '') . ' ' .
			implode(' ', $terms) . ' ' .
			(string) ($export['categories'] ?? '') . ' ' .
			(string) ($export['tags'] ?? '') . ' ' .
			implode(' ', $file_labels)
		);
	}
}

if (!function_exists('bbb_vault_image_file_path')) {
	function bbb_vault_image_file_path(int $post_id, string $image_url): string {
		$attachment_ids = array();
		$thumbnail_id = $post_id ? (int) get_post_thumbnail_id($post_id) : 0;
		if ($thumbnail_id) {
			$attachment_ids[] = $thumbnail_id;
		}

		if ($post_id) {
			$media_attachment_ids = get_post_meta($post_id, '_bbb_product_media_attachment_ids', true);
			if (is_array($media_attachment_ids)) {
				foreach ($media_attachment_ids as $attachment_id) {
					$attachment_ids[] = (int) $attachment_id;
				}
			}
		}

		foreach (array_unique(array_filter($attachment_ids)) as $attachment_id) {
			$file = get_attached_file((int) $attachment_id);
			if (is_string($file) && is_readable($file)) {
				return $file;
			}
		}

		$attachment_id = function_exists('attachment_url_to_postid') ? (int) attachment_url_to_postid($image_url) : 0;
		if ($attachment_id) {
			$file = get_attached_file($attachment_id);
			if (is_string($file) && is_readable($file)) {
				return $file;
			}
		}

		$uploads = wp_get_upload_dir();
		$path = (string) wp_parse_url($image_url, PHP_URL_PATH);
		if ('' !== $path && !empty($uploads['baseurl']) && !empty($uploads['basedir'])) {
			$base_path = (string) wp_parse_url((string) $uploads['baseurl'], PHP_URL_PATH);
			if ('' !== $base_path && str_starts_with($path, $base_path)) {
				$file = rtrim((string) $uploads['basedir'], '/') . substr($path, strlen($base_path));
				if (is_readable($file)) {
					return $file;
				}
			}
		}

		if (defined('ABSPATH') && str_starts_with($path, '/wp-content/')) {
			$file = rtrim((string) ABSPATH, '/') . $path;
			if (is_readable($file)) {
				return $file;
			}
		}

		return '';
	}
}

if (!function_exists('bbb_vault_rgb_to_hsv')) {
	function bbb_vault_rgb_to_hsv(int $red, int $green, int $blue): array {
		$r = $red / 255;
		$g = $green / 255;
		$b = $blue / 255;
		$max = max($r, $g, $b);
		$min = min($r, $g, $b);
		$delta = $max - $min;

		if ($delta <= 0.000001) {
			$hue = 0.0;
		} elseif ($max === $r) {
			$hue = 60 * fmod((($g - $b) / $delta), 6);
		} elseif ($max === $g) {
			$hue = 60 * ((($b - $r) / $delta) + 2);
		} else {
			$hue = 60 * ((($r - $g) / $delta) + 4);
		}

		if ($hue < 0) {
			$hue += 360;
		}

		return array($hue, $max <= 0.000001 ? 0.0 : $delta / $max, $max);
	}
}

if (!function_exists('bbb_vault_hue_color_bucket')) {
	function bbb_vault_hue_color_bucket(float $hue): string {
		if ($hue < 16 || $hue >= 346) {
			return 'red';
		}
		if ($hue < 40) {
			return 'orange';
		}
		if ($hue < 68) {
			return 'yellow';
		}
		if ($hue < 166) {
			return 'green';
		}
		if ($hue < 250) {
			return 'blue';
		}
		if ($hue < 292) {
			return 'purple';
		}
		if ($hue < 346) {
			return 'pink';
		}

		return 'mixed';
	}
}

if (!function_exists('bbb_vault_image_color_buckets')) {
	function bbb_vault_image_color_buckets(string $image_source): array {
		$is_remote = str_starts_with($image_source, 'http://') || str_starts_with($image_source, 'https://');
		if ('' === $image_source || (!$is_remote && !is_readable($image_source)) || !function_exists('imagecreatetruecolor')) {
			return array();
		}

		$info = @getimagesize($image_source);
		if (!is_array($info) || empty($info[0]) || empty($info[1]) || empty($info['mime'])) {
			return array();
		}

		$loader = array(
			'image/jpeg' => 'imagecreatefromjpeg',
			'image/png'  => 'imagecreatefrompng',
			'image/webp' => 'imagecreatefromwebp',
			'image/gif'  => 'imagecreatefromgif',
		)[$info['mime']] ?? '';

		if ('' === $loader || !function_exists($loader)) {
			return array();
		}

		$source = @$loader($image_source);
		if (!$source) {
			return array();
		}

		$size = 64;
		$sample = imagecreatetruecolor($size, $size);
		imagealphablending($sample, false);
		imagesavealpha($sample, true);
		imagecopyresampled($sample, $source, 0, 0, 0, 0, $size, $size, (int) $info[0], (int) $info[1]);
		imagedestroy($source);

		$scores = array_fill_keys(array('pink', 'red', 'orange', 'yellow', 'green', 'blue', 'purple', 'black', 'neutral', 'gray'), 0.0);
		for ($y = 0; $y < $size; $y++) {
			for ($x = 0; $x < $size; $x++) {
				$pixel = imagecolorat($sample, $x, $y);
				$alpha = ($pixel & 0x7F000000) >> 24;
				if ($alpha > 80) {
					continue;
				}

				$red = ($pixel >> 16) & 0xFF;
				$green = ($pixel >> 8) & 0xFF;
				$blue = $pixel & 0xFF;
				list($hue, $saturation, $value) = bbb_vault_rgb_to_hsv($red, $green, $blue);

				if ($value > 0.88 && $saturation < 0.22) {
					continue;
				}

				if ($value < 0.24 && $saturation < 0.42) {
					$scores['black'] += 1.7;
					continue;
				}

				if ($saturation < 0.18) {
					if ($value < 0.58) {
						$scores['gray'] += 0.9;
					} elseif ($value < 0.86) {
						$scores['neutral'] += 0.5;
					}
					continue;
				}

				if ($hue >= 16 && $hue < 40 && $saturation < 0.42 && $value > 0.38) {
					$scores['neutral'] += 0.55 + (1 - abs($value - 0.62));
					continue;
				}

				$bucket = bbb_vault_hue_color_bucket($hue);
				$weight = 0.75 + $saturation + (1 - abs($value - 0.52));
				$scores[$bucket] += $weight;
			}
		}
		imagedestroy($sample);

		arsort($scores);
		$top_bucket = (string) array_key_first($scores);
		$top_score = (float) current($scores);
		$all_score = (float) array_sum($scores);

		if ($top_score < 18 || $all_score <= 0) {
			return array();
		}

		$buckets = array();
		foreach ($scores as $bucket => $score) {
			$score = (float) $score;
			if ($score < 14) {
				continue;
			}

			$share = $score / $all_score;
			if ($bucket === $top_bucket || $share >= 0.14 || $score / max($top_score, 1) >= 0.55) {
				$buckets[] = (string) $bucket;
			}

			if (count($buckets) >= 4) {
				break;
			}
		}

		return array_values(array_unique($buckets));
	}
}

if (!function_exists('bbb_vault_visual_color_map')) {
	function bbb_vault_visual_color_map(): array {
		static $map = null;
		if (is_array($map)) {
			return $map;
		}

		$path = get_theme_file_path('data/shop-visual-colors.json');
		if (!is_readable($path)) {
			$map = array();
			return $map;
		}

		$data = json_decode((string) file_get_contents($path), true);
		$map = is_array($data) ? $data : array();
		return $map;
	}
}

if (!function_exists('bbb_vault_visual_color_filters')) {
	function bbb_vault_visual_color_filters(int $post_id, string $image_url, array $fallback, string $handle = ''): array {
		$handle = sanitize_title($handle);
		if ('' !== $handle) {
			$map = bbb_vault_visual_color_map();
			if (!empty($map[$handle]) && is_array($map[$handle])) {
				return array_values(array_filter(array_map('bbb_vault_filter_slug', $map[$handle])));
			}
		}

		if ($post_id < 1 || '' === $image_url) {
			return $fallback;
		}

		$image_source = bbb_vault_image_file_path($post_id, $image_url);
		if ('' === $image_source) {
			return $fallback;
		}

		$source_version = is_readable($image_source) ? (string) @filemtime($image_source) : md5($image_source);
		$cache_key = md5('vault-visual-color-v1|' . $image_source . '|' . $source_version);
		$cached = (string) get_post_meta($post_id, '_bbb_vault_visual_color_cache', true);
		if (str_contains($cached, '|')) {
			list($cached_key, $cached_color) = explode('|', $cached, 2);
			if ($cached_key === $cache_key && '' !== $cached_color) {
				return array_values(array_filter(array_map('bbb_vault_filter_slug', preg_split('/\s+/', $cached_color) ?: array())));
			}
		}

		$colors = bbb_vault_image_color_buckets($image_source);
		if (!$colors) {
			$colors = $fallback;
		}

		$colors = array_values(array_filter(array_map('bbb_vault_filter_slug', $colors)));
		update_post_meta($post_id, '_bbb_vault_visual_color_cache', $cache_key . '|' . implode(' ', $colors));
		return $colors;
	}
}

if (!function_exists('bbb_vault_filter_values')) {
	function bbb_vault_filter_values(array $asset, string $group_name): array {
		$post_id = (int) ($asset['id'] ?? 0);
		$handle = $post_id > 0 ? (string) get_post_field('post_name', $post_id) : '';
		$haystack = bbb_vault_filter_haystack($asset, $group_name);
		$color = bbb_vault_first_matching_filter($haystack, bbb_vault_color_groups(), 'mixed');
		$colors = bbb_vault_visual_color_filters($post_id, (string) ($asset['image'] ?? ''), array($color), $handle);
		$theme = bbb_vault_first_matching_filter($haystack, bbb_vault_theme_groups(), 'aesthetic');

		return array(
			'kind'   => sanitize_title($group_name),
			'color'  => implode(' ', array_values(array_unique($colors))),
			'theme'  => bbb_vault_filter_slug($theme),
			'search' => trim((string) preg_replace('/\s+/', ' ', $haystack)),
		);
	}
}

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-vault">
		<div class="bbb-vault__wrap">
			<header class="bbb-vault__hero">
				<p class="bbb-vault__kicker">my vault</p>
				<h1>everything you unlocked, in one place.</h1>
				<p>all your eligible digital products, templates, inserts, trackers, and bookish extras live here after bybookishbabe vault access is tied to your reader email.</p>
				<div class="bbb-vault__stats" aria-label="vault stats">
					<span><strong><?php echo esc_html((string) $asset_count); ?></strong> products</span>
					<span><strong><?php echo esc_html((string) $file_count); ?></strong> files</span>
					<span><strong><?php echo esc_html($has_full_vault ? 'open' : 'locked'); ?></strong> access</span>
				</div>
			</header>

			<?php if (!$has_identity) : ?>
				<section class="bbb-vault__gate" id="reader-email-access" aria-label="open vault account">
					<p class="bbb-vault__eyebrow">reader email needed</p>
					<h2>open the email tied to your purchase.</h2>
					<p>Use the same email you checked out with. If bybookishbabe vault access is on that email, the downloads open here automatically.</p>
					<form class="bbb-vault__emailForm" method="post" action="<?php echo esc_url($account_url); ?>">
						<input type="hidden" name="bbb_reader_email_access" value="1">
						<input type="hidden" name="return_to" value="<?php echo esc_url(home_url('/my-vault/')); ?>">
						<label class="screen-reader-text" for="bbb-vault-reader-email">reader email</label>
						<input id="bbb-vault-reader-email" type="email" name="email" autocomplete="email" placeholder="you@example.com" required>
						<button type="submit">open vault</button>
					</form>
					<?php if ($reader_email_error) : ?>
						<p class="bbb-vault__notice bbb-vault__notice--error"><?php echo esc_html($reader_email_error); ?></p>
					<?php endif; ?>
				</section>
			<?php elseif (!$has_full_vault) : ?>
				<section class="bbb-vault__gate" aria-label="vault locked">
					<p class="bbb-vault__eyebrow">locked right now</p>
					<h2>Your reader account is open, but bybookishbabe vault access is not attached yet.</h2>
					<p>Logged in as <strong><?php echo esc_html((string) ($identity['email'] ?? '')); ?></strong>. Buy the bybookishbabe vault with this email, or switch emails if you used a different one at checkout.</p>
					<div class="bbb-vault__actions">
						<a class="bbb-vault__button" href="<?php echo esc_url($buy_url); ?>">unlock bybookishbabe vault <span class="bbb-vault__comparePrice">$49</span> <strong>$34</strong></a>
						<a class="bbb-vault__button bbb-vault__button--ghost" href="<?php echo esc_url(add_query_arg('bbb_reader_logout', '1', $account_url)); ?>">use a different email</a>
					</div>
				</section>
			<?php else : ?>
				<nav class="bbb-vault__tabs" aria-label="vault sections">
					<?php foreach ($groups as $group_name => $items) : ?>
						<?php if (!$items) : ?>
							<?php continue; ?>
						<?php endif; ?>
						<a href="#vault-<?php echo esc_attr(sanitize_title($group_name)); ?>"><?php echo esc_html($group_name); ?></a>
					<?php endforeach; ?>
				</nav>

				<section class="bbb-vault__filterPanel" aria-label="filter vault designs" data-bbb-vault-filters>
					<div class="bbb-vault__filterHead">
						<p class="bbb-vault__eyebrow">browse by vibe</p>
						<p data-bbb-vault-filter-count><?php echo esc_html((string) $asset_count); ?> designs</p>
					</div>
					<div class="bbb-vault__filterControls">
						<label>
							<span>color</span>
							<select data-bbb-vault-filter="color">
								<option value="">all colors</option>
								<option value="pink">pink</option>
								<option value="red">red</option>
								<option value="orange">orange</option>
								<option value="yellow">yellow / gold</option>
								<option value="green">green</option>
								<option value="blue">blue</option>
								<option value="purple">purple</option>
								<option value="black">black</option>
								<option value="neutral">neutral</option>
								<option value="gray">gray</option>
								<option value="mixed">mixed</option>
							</select>
						</label>
						<label>
							<span>theme</span>
							<select data-bbb-vault-filter="theme">
								<option value="">all themes</option>
								<option value="dark-romance">dark romance</option>
								<option value="soft-romance">soft romance</option>
								<option value="fantasy">fantasy</option>
								<option value="floral">floral</option>
								<option value="seasonal">seasonal</option>
								<option value="western">western</option>
								<option value="minimal">minimal</option>
								<option value="smutty">smutty</option>
								<option value="aesthetic">aesthetic</option>
							</select>
						</label>
						<label>
							<span>search</span>
							<input type="search" data-bbb-vault-search placeholder="title, mood, collection">
						</label>
						<button type="button" data-bbb-vault-filter-reset>reset</button>
					</div>
				</section>
				<p class="bbb-vault__noResults" data-bbb-vault-no-results hidden>no vault designs match those filters yet.</p>

				<?php foreach ($groups as $group_name => $items) : ?>
					<?php if (!$items) : ?>
						<?php continue; ?>
					<?php endif; ?>
					<section class="bbb-vault__section" id="vault-<?php echo esc_attr(sanitize_title($group_name)); ?>" aria-labelledby="vault-title-<?php echo esc_attr(sanitize_title($group_name)); ?>" data-bbb-vault-section>
						<div class="bbb-vault__sectionHead">
							<div>
								<p class="bbb-vault__eyebrow">bybookishbabe vault</p>
								<h2 id="vault-title-<?php echo esc_attr(sanitize_title($group_name)); ?>"><?php echo esc_html($group_name); ?></h2>
							</div>
							<span><?php echo esc_html((string) count($items)); ?> ready</span>
						</div>
						<div class="bbb-vault__grid">
							<?php foreach ($items as $asset) : ?>
								<?php $filter_values = bbb_vault_filter_values($asset, $group_name); ?>
								<article
									class="bbb-vault__card"
									data-bbb-vault-card
									data-filter-kind="<?php echo esc_attr($filter_values['kind']); ?>"
									data-filter-color="<?php echo esc_attr($filter_values['color']); ?>"
									data-filter-theme="<?php echo esc_attr($filter_values['theme']); ?>"
									data-filter-search="<?php echo esc_attr($filter_values['search']); ?>"
								>
									<?php if (!empty($asset['image'])) : ?>
										<a class="bbb-vault__media" href="<?php echo esc_url((string) $asset['url']); ?>">
											<img src="<?php echo esc_url((string) $asset['image']); ?>" alt="<?php echo esc_attr((string) $asset['title']); ?>" loading="lazy">
										</a>
									<?php else : ?>
										<a class="bbb-vault__media bbb-vault__media--empty" href="<?php echo esc_url((string) $asset['url']); ?>" aria-label="<?php echo esc_attr((string) $asset['title']); ?>"></a>
									<?php endif; ?>
									<div class="bbb-vault__cardBody">
										<h3><?php echo esc_html((string) $asset['title']); ?></h3>
										<div class="bbb-vault__downloads">
											<?php foreach ((array) ($asset['files'] ?? array()) as $file) : ?>
												<?php if (!is_array($file) || empty($file['url'])) : ?>
													<?php continue; ?>
												<?php endif; ?>
												<a href="<?php echo esc_url((string) $file['url']); ?>" target="_blank" rel="noopener" download>
													<span><?php echo esc_html((string) ($file['label'] ?? 'download')); ?></span>
													<small>download</small>
												</a>
											<?php endforeach; ?>
										</div>
									</div>
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</section>
</main>

<style>
.bbb-vault {
	background: #120d10;
	color: #fff;
	min-height: 70vh;
	padding: 44px 18px 72px;
}
.bbb-vault__wrap {
	margin: 0 auto;
	max-width: 1180px;
}
.bbb-vault__hero {
	border-bottom: 1px solid rgba(255, 255, 255, .14);
	display: grid;
	gap: 14px;
	padding: 34px 0 28px;
}
.bbb-vault__kicker,
.bbb-vault__eyebrow {
	color: #f6a8ca;
	font: 700 12px/1.2 var(--font-body-family, system-ui);
	letter-spacing: .12em;
	margin: 0;
	text-transform: uppercase;
}
.bbb-vault h1 {
	font: 700 clamp(38px, 6vw, 78px)/.92 var(--font-heading-family, serif);
	letter-spacing: 0;
	margin: 0;
	max-width: 760px;
}
.bbb-vault h2,
.bbb-vault h3,
.bbb-vault p {
	margin: 0;
}
.bbb-vault__hero > p,
.bbb-vault__gate > p {
	color: rgba(255, 255, 255, .72);
	font-size: 17px;
	line-height: 1.65;
	max-width: 680px;
}
.bbb-vault__stats,
.bbb-vault__tabs,
.bbb-vault__actions {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}
.bbb-vault__stats span,
.bbb-vault__tabs a {
	background: rgba(255, 255, 255, .06);
	border: 1px solid rgba(255, 255, 255, .16);
	border-radius: 8px;
	color: #fff;
	padding: 10px 12px;
	text-decoration: none;
}
.bbb-vault__stats strong {
	color: #ffd8e8;
	display: block;
}
.bbb-vault__gate {
	background: rgba(255, 255, 255, .06);
	border: 1px solid rgba(255, 255, 255, .14);
	border-radius: 8px;
	display: grid;
	gap: 16px;
	margin-top: 28px;
	padding: 24px;
}
.bbb-vault__gate h2 {
	font-size: 30px;
	line-height: 1.05;
	max-width: 760px;
}
.bbb-vault__button,
.bbb-vault__emailForm button {
	align-items: center;
	background: #f6a8ca;
	border: 1px solid #f6a8ca;
	border-radius: 8px;
	color: #120d10;
	display: inline-flex;
	font-weight: 800;
	gap: 7px;
	justify-content: center;
	min-height: 44px;
	padding: 0 16px;
	text-decoration: none;
}
.bbb-vault__button strong {
	font: inherit;
}
.bbb-vault__comparePrice {
	opacity: .62;
	text-decoration: line-through;
	text-decoration-thickness: 2px;
}
.bbb-vault__button--ghost {
	background: transparent;
	color: #fff;
}
.bbb-vault__emailForm {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
}
.bbb-vault__emailForm input {
	background: #fff;
	border: 1px solid rgba(255, 255, 255, .18);
	border-radius: 8px;
	color: #120d10;
	min-height: 44px;
	min-width: min(100%, 320px);
	padding: 0 12px;
}
.bbb-vault__notice--error {
	color: #ffc5c5;
}
.bbb-vault__tabs {
	background: #120d10;
	margin: 20px 0;
	padding: 10px 0;
	position: sticky;
	top: 0;
	z-index: 2;
}
.bbb-vault__filterPanel {
	background: rgba(255, 255, 255, .045);
	border: 1px solid rgba(255, 255, 255, .14);
	border-radius: 8px;
	margin: 0 0 22px;
	padding: 16px;
}
.bbb-vault__filterHead {
	align-items: end;
	display: flex;
	gap: 14px;
	justify-content: space-between;
	margin-bottom: 12px;
}
.bbb-vault__filterHead .bbb-vault__eyebrow {
	margin: 0;
}
.bbb-vault__filterHead p:last-child {
	color: rgba(255, 255, 255, .62);
	font-size: 12px;
	font-weight: 800;
	letter-spacing: .12em;
	margin: 0;
	text-transform: uppercase;
}
.bbb-vault__filterControls {
	display: grid;
	gap: 10px;
	grid-template-columns: minmax(140px, .9fr) minmax(140px, .9fr) minmax(220px, 1.4fr) minmax(92px, .45fr);
}
.bbb-vault__filterControls label {
	display: grid;
	gap: 5px;
}
.bbb-vault__filterControls span {
	color: #f6a8ca;
	font-size: 10px;
	font-weight: 900;
	line-height: 1;
	text-transform: lowercase;
}
.bbb-vault__filterControls select,
.bbb-vault__filterControls input {
	background: #0c080a;
	border: 1px solid rgba(246, 168, 202, .32);
	border-radius: 4px;
	color: #fff;
	font-size: 13px;
	font-weight: 800;
	line-height: 1.2;
	min-height: 38px;
	text-transform: lowercase;
	width: 100%;
}
.bbb-vault__filterControls select {
	appearance: auto;
	padding: 8px 28px 8px 10px;
}
.bbb-vault__filterControls input {
	padding: 8px 10px;
}
.bbb-vault__filterControls select:focus-visible,
.bbb-vault__filterControls input:focus-visible,
.bbb-vault__filterControls button:focus-visible {
	border-color: #f6a8ca;
	outline: 2px solid rgba(246, 168, 202, .22);
	outline-offset: 2px;
}
.bbb-vault__filterControls button {
	align-self: end;
	background: rgba(255, 255, 255, .055);
	border: 1px solid rgba(246, 168, 202, .42);
	border-radius: 4px;
	color: #fff;
	cursor: pointer;
	font-size: 12px;
	font-weight: 900;
	line-height: 1;
	min-height: 38px;
	text-transform: lowercase;
}
.bbb-vault__filterControls button:hover {
	background: rgba(246, 168, 202, .13);
	border-color: #f6a8ca;
}
.bbb-vault__noResults {
	background: rgba(255, 255, 255, .055);
	border: 1px solid rgba(255, 255, 255, .14);
	border-radius: 8px;
	color: rgba(255, 255, 255, .72);
	font-size: 14px;
	font-weight: 800;
	line-height: 1.4;
	margin: 0 0 24px;
	padding: 18px;
}
.bbb-vault [hidden],
.bbb-vault [data-bbb-vault-card][hidden],
.bbb-vault [data-bbb-vault-section][hidden],
.bbb-vault [data-bbb-vault-no-results][hidden] {
	display: none !important;
}
.bbb-vault__section {
	border-top: 1px solid rgba(255, 255, 255, .12);
	display: grid;
	gap: 18px;
	padding: 26px 0;
}
.bbb-vault__sectionHead {
	align-items: end;
	display: flex;
	gap: 16px;
	justify-content: space-between;
}
.bbb-vault__sectionHead h2 {
	font-size: 34px;
	text-transform: lowercase;
}
.bbb-vault__sectionHead > span {
	color: #ffd8e8;
}
.bbb-vault__grid {
	display: grid;
	gap: 16px;
	grid-template-columns: repeat(3, minmax(0, 1fr));
}
.bbb-vault__card {
	background: rgba(255, 255, 255, .055);
	border: 1px solid rgba(255, 255, 255, .14);
	border-radius: 8px;
	display: grid;
	grid-template-rows: auto 1fr;
	min-width: 0;
	overflow: hidden;
}
.bbb-vault__media {
	aspect-ratio: 4 / 3;
	background: #21171d;
	display: block;
	min-width: 0;
}
.bbb-vault__media img {
	display: block;
	height: 100%;
	object-fit: cover;
	width: 100%;
}
.bbb-vault__cardBody {
	display: grid;
	gap: 12px;
	min-width: 0;
	padding: 14px;
}
.bbb-vault__card h3 {
	font-size: 18px;
	line-height: 1.2;
	min-width: 0;
	overflow-wrap: anywhere;
}
.bbb-vault__downloads {
	display: grid;
	gap: 8px;
	grid-template-columns: repeat(2, minmax(0, 1fr));
	min-width: 0;
}
.bbb-vault__downloads a {
	align-items: center;
	background: rgba(246, 168, 202, .1);
	border: 1px solid rgba(246, 168, 202, .45);
	border-radius: 7px;
	color: #fff;
	display: flex;
	flex-direction: column;
	justify-content: center;
	min-height: 46px;
	min-width: 0;
	overflow: hidden;
	padding: 7px;
	text-align: center;
	text-decoration: none;
}
.bbb-vault__downloads span {
	font-size: 12px;
	font-weight: 800;
	max-width: 100%;
	overflow: hidden;
	text-overflow: ellipsis;
	white-space: nowrap;
}
.bbb-vault__downloads small {
	color: rgba(255, 255, 255, .58);
	font-size: 10px;
	text-transform: lowercase;
}
@media (max-width: 860px) {
	.bbb-vault__grid {
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
}
@media (max-width: 560px) {
	.bbb-vault {
		padding-inline: 12px;
	}
	.bbb-vault h1 {
		font-size: 38px;
	}
	.bbb-vault__hero > p,
	.bbb-vault__gate > p {
		font-size: 14px;
		line-height: 1.5;
	}
	.bbb-vault__stats {
		display: grid;
		grid-template-columns: repeat(3, minmax(0, 1fr));
	}
	.bbb-vault__stats span,
	.bbb-vault__tabs a {
		padding: 9px 8px;
		text-align: center;
	}
	.bbb-vault__tabs {
		display: grid;
		grid-template-columns: repeat(2, minmax(0, 1fr));
		position: static;
	}
	.bbb-vault__filterHead {
		align-items: start;
		flex-direction: column;
		gap: 8px;
	}
	.bbb-vault__filterControls {
		grid-template-columns: repeat(2, minmax(0, 1fr));
		gap: 8px;
	}
	.bbb-vault__filterControls label:nth-of-type(3),
	.bbb-vault__filterControls button {
		grid-column: 1 / -1;
	}
	.bbb-vault__sectionHead {
		align-items: start;
		flex-direction: column;
		gap: 6px;
	}
	.bbb-vault__sectionHead h2 {
		font-size: 28px;
	}
	.bbb-vault__grid {
		gap: 8px;
		grid-template-columns: repeat(2, minmax(0, 1fr));
	}
	.bbb-vault__cardBody {
		gap: 9px;
		padding: 10px;
	}
	.bbb-vault__card h3 {
		font-size: 14px;
		line-height: 1.15;
	}
	.bbb-vault__downloads {
		gap: 6px;
		grid-template-columns: 1fr;
	}
	.bbb-vault__downloads a {
		min-height: 38px;
		padding: 6px;
	}
	.bbb-vault__downloads span {
		font-size: 11px;
	}
}
@media (max-width: 380px) {
	.bbb-vault__grid,
	.bbb-vault__stats,
	.bbb-vault__tabs {
		grid-template-columns: 1fr;
	}
}
</style>

<script>
(function(){
	function initVaultFilters(){
		var root = document.querySelector('[data-bbb-vault-filters]');
		if (!root || root.getAttribute('data-bbb-vault-ready') === 'true') return;
		root.setAttribute('data-bbb-vault-ready', 'true');

		var cards = Array.prototype.slice.call(document.querySelectorAll('[data-bbb-vault-card]'));
		var sections = Array.prototype.slice.call(document.querySelectorAll('[data-bbb-vault-section]'));
		var controls = Array.prototype.slice.call(root.querySelectorAll('[data-bbb-vault-filter]'));
		var search = root.querySelector('[data-bbb-vault-search]');
		var reset = root.querySelector('[data-bbb-vault-filter-reset]');
		var count = document.querySelector('[data-bbb-vault-filter-count]');
		var noResults = document.querySelector('[data-bbb-vault-no-results]');

		function normalize(value) {
			return String(value || '').trim().toLowerCase();
		}

		function activeFilters() {
			var filters = {};
			controls.forEach(function(control){
				filters[control.getAttribute('data-bbb-vault-filter')] = normalize(control.value);
			});
			filters.search = normalize(search ? search.value : '');
			return filters;
		}

		function hasToken(value, token) {
			return normalize(value).split(/\s+/).indexOf(token) !== -1;
		}

		function cardMatches(card, filters) {
			return (!filters.color || hasToken(card.getAttribute('data-filter-color'), filters.color))
				&& (!filters.theme || card.getAttribute('data-filter-theme') === filters.theme)
				&& (!filters.search || normalize(card.getAttribute('data-filter-search')).indexOf(filters.search) !== -1);
		}

		function setVisible(element, visible) {
			element.hidden = !visible;
			element.style.display = visible ? '' : 'none';
		}

		function updateSections() {
			sections.forEach(function(section){
				var visibleCards = section.querySelectorAll('[data-bbb-vault-card]:not([hidden])');
				setVisible(section, visibleCards.length > 0);
			});
		}

		function render() {
			var filters = activeFilters();
			var visible = 0;

			cards.forEach(function(card){
				var matches = cardMatches(card, filters);
				setVisible(card, matches);
				if (matches) visible += 1;
			});

			updateSections();

			if (count) {
				count.textContent = visible + (visible === 1 ? ' design' : ' designs');
			}

			if (noResults) {
				setVisible(noResults, visible === 0);
			}
		}

		root.addEventListener('change', render);
		root.addEventListener('input', render);

		if (reset) {
			reset.addEventListener('click', function(){
				controls.forEach(function(control){
					control.value = '';
				});
				if (search) search.value = '';
				render();
			});
		}

		render();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initVaultFilters);
	} else {
		initVaultFilters();
	}
})();
</script>

<?php
get_footer();
