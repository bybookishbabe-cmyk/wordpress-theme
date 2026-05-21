<?php
/**
 * Society member gate.
 *
 * @package ByBookishBabeShopifyPort
 */

$join_url    = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$account_url = home_url('/account/');
$back_page   = get_page_by_path('smut-sentiment-society');
$back_url    = $back_page instanceof WP_Post ? get_permalink($back_page) : home_url('/smut-sentiment-society/');
?>
<div class="sss-membergate" id="sssMemberGate">
	<div class="sss-membergate__card">
		<p class="sss-membergate__eyebrow">society archive</p>
		<h2 class="sss-membergate__title">this page is member only</h2>
		<p class="sss-membergate__copy">enter the email you use for your paid substack membership, then this page will unlock automatically once your society access is found.</p>
		<div class="sss-membergate__note">no separate wordpress account is required.</div>
		<div class="sss-membergate__actions">
			<a class="sss-membergate__btn sss-membergate__btn--primary" href="<?php echo esc_url($account_url); ?>">open account</a>
			<a class="sss-membergate__btn" href="<?php echo esc_url($join_url); ?>">upgrade to paid society</a>
			<a class="sss-membergate__btn" href="<?php echo esc_url($back_url); ?>">back</a>
		</div>
	</div>
</div>
