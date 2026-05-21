<?php
/**
 * Society member gate.
 *
 * @package ByBookishBabeShopifyPort
 */

$join_url    = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');
$account_url = is_user_logged_in() ? (function_exists('bbb_wc_account_url') ? bbb_wc_account_url() : home_url('/account/')) : wp_login_url(get_permalink());
$back_page   = get_page_by_path('smut-sentiment-society');
$back_url    = $back_page instanceof WP_Post ? get_permalink($back_page) : home_url('/smut-sentiment-society/');
?>
<div class="sss-membergate" id="sssMemberGate">
	<div class="sss-membergate__card">
		<p class="sss-membergate__eyebrow">society archive</p>
		<h2 class="sss-membergate__title">this page is member only</h2>
		<?php if (is_user_logged_in()) : ?>
			<p class="sss-membergate__copy">you are logged in, but this account does not have society access yet. use the same email as your paid substack membership, or join the society to unlock the private library, printables, templates, and quote wall.</p>
			<div class="sss-membergate__note">if you already pay on substack, the next step is linking that paid email to your wordpress account so this account gets society access automatically.</div>
		<?php else : ?>
			<p class="sss-membergate__copy">log in to your account first, then this page will unlock automatically once your account has society access.</p>
			<div class="sss-membergate__note">use the same email you use for your paid substack membership so we can tie your access to the right account.</div>
		<?php endif; ?>
		<div class="sss-membergate__actions">
			<?php if (is_user_logged_in()) : ?>
				<a class="sss-membergate__btn sss-membergate__btn--primary" href="<?php echo esc_url($join_url); ?>">upgrade to paid society</a>
				<a class="sss-membergate__btn" href="<?php echo esc_url($account_url); ?>">open account</a>
			<?php else : ?>
				<a class="sss-membergate__btn sss-membergate__btn--primary" href="<?php echo esc_url($account_url); ?>">log in</a>
				<a class="sss-membergate__btn" href="<?php echo esc_url($join_url); ?>">upgrade to paid society</a>
			<?php endif; ?>
			<a class="sss-membergate__btn" href="<?php echo esc_url($back_url); ?>">back</a>
		</div>
	</div>
</div>
