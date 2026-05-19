<?php
/**
 * Temporary entry point for the Shopify-faithful port.
 *
 * This file intentionally does not attempt to render Liquid. It gives WordPress
 * a valid theme entry while the copied Shopify sections/snippets are converted
 * into PHP template parts.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class('bbb-shopify-port-staging'); ?>>
	<?php wp_body_open(); ?>
	<main class="bbb-port-staging" style="min-height:100vh;padding:64px 20px;background:#0b0b0b;color:#f6f6f6;font-family:Georgia,serif;">
		<div style="max-width:760px;margin:0 auto;">
			<p style="letter-spacing:.18em;text-transform:uppercase;color:#ff8ac7;font-size:12px;">Shopify faithful port</p>
			<h1 style="font-size:clamp(36px,6vw,72px);line-height:1;margin:0 0 18px;">Shopify theme copy is staged.</h1>
			<p style="font-size:18px;line-height:1.7;color:rgba(246,246,246,.72);">The previous WordPress build is preserved in <code>/firstpass</code>. The Shopify source folders now live at the theme root so the next pass can convert Liquid sections and snippets directly instead of approximating them.</p>
		</div>
	</main>
	<?php wp_footer(); ?>
</body>
</html>
