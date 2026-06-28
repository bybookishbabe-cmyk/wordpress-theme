<?php
/**
 * Tokenized read-only social planner share.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

nocache_headers();
if (!headers_sent()) {
	header('X-Robots-Tag: noindex, nofollow', true);
}

$bbb_share_token = isset($_GET['token']) ? sanitize_key((string) wp_unslash($_GET['token'])) : '';
$bbb_share       = function_exists('bbb_social_calendar_shared_view') ? bbb_social_calendar_shared_view($bbb_share_token) : array();
$bbb_rows        = isset($bbb_share['rows']) && is_array($bbb_share['rows']) ? $bbb_share['rows'] : array();

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="robots" content="noindex,nofollow">
	<title>Social Planner Share</title>
	<style>
		body{background:#f6f6f6;color:#111;font-family:Arial,sans-serif;margin:0;padding:24px}
		main{margin:0 auto;max-width:1180px}
		h1{font-size:30px;line-height:1;margin:0 0 6px}
		.meta{color:#555;margin:0 0 20px}
		.grid{display:grid;gap:14px}
		.pin{background:#fff;border:1px solid #ddd;border-radius:10px;display:grid;gap:14px;grid-template-columns:180px 1fr;padding:12px}
		.time{background:#111;border-radius:8px;color:#fff;font-weight:800;margin-bottom:10px;padding:9px 10px;text-transform:uppercase}
		.image{background:#eee;border-radius:8px;min-height:230px;overflow:hidden}
		.image img{display:block;height:auto;width:100%}
		.image-url{background:#f1f1f1;border:1px solid #ddd;border-radius:8px;color:#111;font-size:12px;line-height:1.35;margin-top:8px;padding:8px;word-break:break-all}
		.image-url span{color:#666;display:block;font-size:10px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
		.missing{align-items:center;color:#777;display:flex;font-weight:700;justify-content:center;min-height:230px}
		.fields{display:grid;gap:9px}
		.field{border-bottom:1px solid #eee;padding-bottom:8px}
		.field span{color:#777;display:block;font-size:11px;font-weight:800;letter-spacing:.04em;text-transform:uppercase}
		.field p,.field a{color:#111;font-size:14px;line-height:1.38;margin:3px 0 0;white-space:pre-wrap;word-break:break-word}
		.empty{background:#fff;border:1px solid #ddd;border-radius:10px;padding:20px}
		@media (max-width:700px){body{padding:14px}.pin{grid-template-columns:1fr}}
	</style>
</head>
<body>
	<main>
		<h1>Social Planner Pinterest Share</h1>
		<?php if ($bbb_rows) : ?>
			<p class="meta"><?php echo esc_html((string) ($bbb_share['range'] ?? 'Pinterest pins')); ?> · created <?php echo esc_html((string) ($bbb_share['createdAt'] ?? '')); ?></p>
			<div class="grid">
				<?php foreach ($bbb_rows as $row) : ?>
					<article class="pin">
						<div>
							<div class="time"><?php echo esc_html((string) ($row['dateLabel'] ?? '')); ?><br><?php echo esc_html((string) ($row['pin'] ?? 'Pin')); ?> · <?php echo esc_html((string) ($row['time'] ?? '')); ?></div>
							<div class="image">
								<?php if (!empty($row['image'])) : ?>
									<img src="<?php echo esc_url((string) $row['image']); ?>" alt="">
								<?php else : ?>
									<div class="missing">No image</div>
								<?php endif; ?>
							</div>
							<?php if (!empty($row['image'])) : ?>
								<div class="image-url"><span>Image URL</span><?php echo esc_html((string) $row['image']); ?></div>
							<?php endif; ?>
						</div>
						<div class="fields">
							<div class="field"><span>Image URL</span><?php echo !empty($row['image']) ? '<a href="' . esc_url((string) $row['image']) . '">' . esc_html((string) $row['image']) . '</a>' : '<p>Not filled</p>'; ?></div>
							<div class="field"><span>Title</span><p><?php echo esc_html((string) (($row['title'] ?? '') ?: 'Not filled')); ?></p></div>
							<div class="field"><span>Description</span><p><?php echo esc_html((string) (($row['description'] ?? '') ?: 'Not filled')); ?></p></div>
							<div class="field"><span>Link</span><?php echo !empty($row['link']) ? '<a href="' . esc_url((string) $row['link']) . '">' . esc_html((string) $row['link']) . '</a>' : '<p>Not filled</p>'; ?></div>
							<div class="field"><span>Board</span><p><?php echo esc_html((string) (($row['board'] ?? '') ?: 'Not filled')); ?></p></div>
							<div class="field"><span>Note</span><p><?php echo esc_html((string) ($row['note'] ?? '')); ?></p></div>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="empty">This share link is missing or expired.</div>
		<?php endif; ?>
	</main>
</body>
</html>
