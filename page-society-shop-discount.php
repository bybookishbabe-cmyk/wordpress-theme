<?php
/**
 * Template Name: Society Shop Discount
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$has_access = function_exists('bbb_society_discount_member_has_access') && bbb_society_discount_member_has_access();
$percent = function_exists('bbb_society_discount_percent') ? bbb_society_discount_percent() : 0;

get_header();

if (!$has_access) {
	if (function_exists('bbb_society_render_locked_preview_page')) {
		bbb_society_render_locked_preview_page(
			array(
				'access'      => 'member',
				'kicker'      => 'member preview',
				'title'       => 'shop discount',
				'intro'       => 'preview the monthly Society shop perk before joining to unlock the discount.',
				'panel_title' => 'join to unlock the discount',
				'panel_copy'  => 'free and paid society members can apply this month\'s discount to one shop order.',
				'cta'         => 'join the society',
				'items'       => array(
					($percent > 0 ? (string) $percent . '% off one order this month' : 'a monthly member discount'),
					'applies from the Society page once your member access is active',
					'refreshes with a new monthly Society perk',
				),
			)
		);
	}
	get_footer();
	return;
}
?>

<section class="bbb-access-preview" aria-labelledby="bbb-shop-discount-title">
	<div class="bbb-access-preview__wrap page-width">
		<header class="bbb-access-preview__hero">
			<p class="bbb-access-preview__kicker">member perk</p>
			<h1 id="bbb-shop-discount-title">shop discount</h1>
			<p>your Society discount is ready on the Society page.</p>
		</header>
		<div class="bbb-access-preview__panel">
			<div>
				<p class="bbb-access-preview__eyebrow">unlocked</p>
				<h2><?php echo esc_html($percent > 0 ? (string) $percent . '% off' : 'monthly discount'); ?></h2>
				<p>open the Society page and apply your discount from the shop perks card.</p>
			</div>
			<a class="bbb-access-preview__button" href="<?php echo esc_url(bbb_page_url('smut-sentiment-society') . '#society-shop-discount'); ?>">open discount</a>
			<a class="bbb-access-preview__back" href="<?php echo esc_url(bbb_page_url('shop')); ?>">open the shop</a>
		</div>
	</div>
</section>

<?php
get_footer();
