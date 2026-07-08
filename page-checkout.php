<?php
/**
 * Checkout page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-edd-checkout', 'assets/css/edd-checkout.css', array('bbb-base'));

$vault_upgrade_url = function_exists('bbb_vault_upgrade_checkout_url') ? bbb_vault_upgrade_checkout_url() : (function_exists('bbb_vault_buy_url') ? bbb_vault_buy_url() : home_url('/downloads/bybookishbabe-vault/'));
$vault_upgrade_price = function_exists('bbb_vault_price_label') ? bbb_vault_price_label() : '';
$vault_upgrade_button = 'upgrade to vault' . ('' !== $vault_upgrade_price ? ' - ' . $vault_upgrade_price : '');
$society_discount_enabled = function_exists('bbb_society_discount_percent');
$society_discount_percent = $society_discount_enabled ? bbb_society_discount_percent() : 0;
$society_discount_access = function_exists('bbb_society_discount_member_has_access') && bbb_society_discount_member_has_access();
$society_discount_used = function_exists('bbb_society_discount_has_used_this_month') && bbb_society_discount_has_used_this_month();
$society_discount_applied = function_exists('bbb_society_discount_is_applied_to_session') && bbb_society_discount_is_applied_to_session();
$society_discount_status = isset($_GET['society_discount']) ? sanitize_key((string) wp_unslash($_GET['society_discount'])) : '';
$society_discount_label = $society_discount_percent > 0 ? (string) $society_discount_percent . '% off' : 'monthly discount';
$society_page_url = function_exists('bbb_page_url') ? bbb_page_url('smut-sentiment-society') : home_url('/smut-sentiment-society/');

get_header();
?>

<main class="bbb-checkout" id="main">
	<section class="bbb-checkout__hero">
		<div class="bbb-checkout__inner">
			<p class="bbb-checkout__kicker">instant downloads</p>
			<h1><?php echo esc_html(strtolower(get_the_title() ?: 'checkout')); ?></h1>
			<p>secure your downloads, then your files will be waiting in your receipt and account area.</p>
			<ol class="bbb-checkout__steps" aria-label="checkout steps">
				<li><span>1</span>cart</li>
				<li class="is-active"><span>2</span>details</li>
				<li><span>3</span>access</li>
			</ol>
		</div>
	</section>

	<section class="bbb-checkout__body">
		<div class="bbb-checkout__panel">
			<?php
			while (have_posts()) :
				the_post();
				the_content();
			endwhile;
			?>
			<?php if ($society_discount_enabled) : ?>
				<aside id="society-shop-discount" class="bbb-checkout__societyDiscount <?php echo esc_attr($society_discount_applied || 'applied' === $society_discount_status ? 'is-applied' : ($society_discount_used || 'used' === $society_discount_status ? 'is-used' : 'is-ready')); ?>" aria-label="society discount">
					<div>
						<span>society member?</span>
						<strong><?php echo esc_html($society_discount_label); ?> one order this month</strong>
							<?php if (!$society_discount_access) : ?>
								<p>join free or paid society to unlock the monthly shop perk.</p>
							<?php elseif ($society_discount_used || 'used' === $society_discount_status) : ?>
								<p>you already used this month&apos;s society discount. a new one opens next month.</p>
							<?php elseif ($society_discount_applied || 'applied' === $society_discount_status) : ?>
								<p>applied. the discount will show in your order summary.</p>
							<?php elseif ('expired' === $society_discount_status) : ?>
								<p>that discount session refreshed. tap apply again to use this month&apos;s perk.</p>
							<?php else : ?>
								<p>apply it here, then finish checkout with your member discount.</p>
							<?php endif; ?>
					</div>
					<?php if (!$society_discount_access) : ?>
						<a class="bbb-checkout__societyDiscountAction" href="<?php echo esc_url($society_page_url . '#society-shop-discount'); ?>">unlock discount</a>
					<?php else : ?>
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
							<input type="hidden" name="action" value="bbb_apply_society_discount">
							<?php wp_nonce_field('bbb_apply_society_discount'); ?>
							<button class="bbb-checkout__societyDiscountAction" type="submit" <?php disabled($society_discount_used || $society_discount_applied || 'used' === $society_discount_status || 'applied' === $society_discount_status); ?>>
								<?php echo esc_html($society_discount_used || 'used' === $society_discount_status ? 'used this month' : ($society_discount_applied || 'applied' === $society_discount_status ? 'applied' : 'apply discount')); ?>
							</button>
						</form>
					<?php endif; ?>
				</aside>
			<?php endif; ?>
			<aside class="bbb-checkout__extras" aria-label="download notes">
				<a
					class="bbb-checkout__sample"
					href="<?php echo esc_url(get_template_directory_uri() . '/assets/6Inch_Printable_Test.pdf'); ?>"
					target="_blank"
					rel="noopener noreferrer"
					data-bbb-sample-link
				>
					<span>try before you buy</span>
					<strong>open the sample pdf</strong>
					<em>choose your kindle size and make sure the file feels right before checkout.</em>
				</a>
				<label class="bbb-checkout__samplePicker">
					<span>sample size</span>
					<select data-bbb-sample-select>
						<option value="<?php echo esc_url(get_template_directory_uri() . '/assets/6Inch_Printable_Test.pdf'); ?>">6 inch kindle</option>
						<option value="<?php echo esc_url(get_template_directory_uri() . '/assets/10thGen_Printable_Test.pdf'); ?>">10th gen kindle</option>
						<option value="<?php echo esc_url(get_template_directory_uri() . '/assets/11thGen_Printable_Test.pdf'); ?>">11th gen kindle</option>
						<option value="<?php echo esc_url(get_template_directory_uri() . '/assets/12thGen_Printable_Test.pdf'); ?>">12th gen kindle</option>
					</select>
				</label>
				<ul class="bbb-checkout__trust">
					<li>instant delivery after payment</li>
					<li>download links sent to your email</li>
					<li>re-download and print anytime</li>
				</ul>
			</aside>
		</div>
	</section>
	<div class="bbb-checkout__sampleModal" data-bbb-sample-modal aria-hidden="true">
		<div class="bbb-checkout__sampleViewer" role="dialog" aria-modal="true" aria-label="sample pdf preview">
			<div class="bbb-checkout__sampleBar">
				<a class="bbb-checkout__sampleOpen" href="<?php echo esc_url(get_template_directory_uri() . '/assets/6Inch_Printable_Test.pdf'); ?>" target="_blank" rel="noopener noreferrer" data-bbb-sample-open>
					open in browser
				</a>
				<button class="bbb-checkout__sampleClose" type="button" aria-label="close sample pdf" data-bbb-sample-close>
					<span aria-hidden="true">×</span>
				</button>
			</div>
			<iframe class="bbb-checkout__sampleFrame" title="sample pdf preview" data-bbb-sample-frame></iframe>
		</div>
	</div>
</main>

<script>
	document.addEventListener('DOMContentLoaded', function () {
		var panel = document.querySelector('.bbb-checkout__panel');
		var extras = document.querySelector('.bbb-checkout__extras');
		var discount = document.querySelector('.bbb-checkout__societyDiscount');
		var cart = document.querySelector('#edd_checkout_cart_form, .edd-blocks__cart');

		if (!panel || !extras || panel.querySelector('.bbb-checkout__sideRail')) {
			return;
		}

		var sideRail = document.createElement('aside');
		sideRail.className = 'bbb-checkout__sideRail';
		sideRail.setAttribute('aria-label', 'checkout summary');
		panel.appendChild(sideRail);
		if (cart) {
			sideRail.appendChild(cart);
		}
		if (discount) {
			sideRail.appendChild(discount);
		}
		sideRail.appendChild(extras);
	});

	document.addEventListener('click', function (event) {
		var applyButton = event.target.closest('.bbb-checkout__sideRail .edd-apply-discount');
		var formWrap = document.querySelector('#edd_checkout_form_wrap');
		var sideRail = document.querySelector('.bbb-checkout__sideRail');
		var cart = applyButton ? applyButton.closest('.edd-blocks__cart, #edd_checkout_cart_form') : null;

		if (!applyButton || !formWrap || !sideRail || !cart || !window.jQuery) {
			return;
		}

		event.preventDefault();
		formWrap.insertBefore(cart, formWrap.firstChild);
		window.jQuery(document.body).one('edd_discount_applied edd_discount_invalid edd_discount_failed', function () {
			sideRail.insertBefore(cart, sideRail.firstChild);
		});
		window.jQuery(applyButton).trigger('click');
		window.setTimeout(function () {
			if (!sideRail.contains(cart)) {
				sideRail.insertBefore(cart, sideRail.firstChild);
			}
		}, 2500);
	});

	document.addEventListener('keyup', function (event) {
		var discountInput = event.target.closest('.bbb-checkout__sideRail #edd-discount');
		var applyButton = discountInput ? document.querySelector('.bbb-checkout__sideRail .edd-apply-discount') : null;

		if ('Enter' !== event.key || !discountInput || !applyButton) {
			return;
		}

		event.preventDefault();
		applyButton.click();
	});

	document.addEventListener('DOMContentLoaded', function () {
		var vaultUrl = <?php echo wp_json_encode(esc_url($vault_upgrade_url)); ?>;
		var vaultButton = <?php echo wp_json_encode($vault_upgrade_button); ?>;
		var upgradeClass = 'bbb-checkout__vaultUpgradeRow';
		var upgradeInnerClass = 'bbb-checkout__vaultUpgrade';

		function cartHasVault(cart) {
			return Array.prototype.some.call(cart.querySelectorAll('.edd_checkout_cart_item_title, .edd_cart_item_name'), function (item) {
				return /vault/i.test(item.textContent || '');
			});
		}

		function buildUpgradeInner() {
			var wrap = document.createElement('div');
			wrap.className = upgradeInnerClass;
			wrap.innerHTML = '<div><strong>want all designs now &amp; future?</strong><span>replace this cart with vault access and get the full archive plus every future drop.</span></div><a href="' + vaultUrl + '">' + vaultButton + '</a>';
			return wrap;
		}

		function insertCheckoutVaultUpgrade() {
			var cart = document.querySelector('#edd_checkout_cart');
			var total = cart ? cart.querySelector('.edd_cart_total') : null;
			var totalRow = total ? (total.closest('tr') || total.closest('.edd-blocks-cart__row-footer')) : null;

			if (!cart || !totalRow) {
				return;
			}

			Array.prototype.forEach.call(cart.querySelectorAll('.' + upgradeClass), function (existing) {
				existing.remove();
			});

			if (cartHasVault(cart)) {
				return;
			}

			if (totalRow.previousElementSibling && totalRow.previousElementSibling.classList.contains(upgradeClass)) {
				return;
			}

			if ('TR' === totalRow.tagName) {
				var row = document.createElement('tr');
				var cell = document.createElement('th');
				row.className = 'edd_cart_footer_row ' + upgradeClass;
				cell.colSpan = total.closest('th, td') ? total.closest('th, td').colSpan || 3 : 3;
				cell.appendChild(buildUpgradeInner());
				row.appendChild(cell);
				totalRow.parentNode.insertBefore(row, totalRow);
				return;
			}

			var blockRow = document.createElement('div');
			blockRow.className = 'edd-blocks-cart__row edd-blocks-cart__row-footer ' + upgradeClass;
			blockRow.appendChild(buildUpgradeInner());
			totalRow.parentNode.insertBefore(blockRow, totalRow);
		}

		insertCheckoutVaultUpgrade();

		var cartRoot = document.querySelector('#edd_checkout_cart_form');
		if (cartRoot && 'MutationObserver' in window) {
			var observer = new MutationObserver(insertCheckoutVaultUpgrade);
			observer.observe(cartRoot, { childList: true, subtree: true });
		}
	});

	document.addEventListener('change', function (event) {
		var select = event.target.closest('[data-bbb-sample-select]');
		var link = document.querySelector('[data-bbb-sample-link]');
		var openLink = document.querySelector('[data-bbb-sample-open]');

		if (!select || !link || !openLink) {
			return;
		}

		link.href = select.value;
		openLink.href = select.value;
	});

	document.addEventListener('click', function (event) {
		var sampleLink = event.target.closest('[data-bbb-sample-link]');
		var close = event.target.closest('[data-bbb-sample-close]');
		var modal = document.querySelector('[data-bbb-sample-modal]');
		var frame = document.querySelector('[data-bbb-sample-frame]');
		var openLink = document.querySelector('[data-bbb-sample-open]');

		if (sampleLink && modal && frame && openLink) {
			event.preventDefault();
			frame.src = sampleLink.href;
			openLink.href = sampleLink.href;
			modal.setAttribute('aria-hidden', 'false');
			document.documentElement.classList.add('bbb-sample-pdf-open');
			return;
		}

		if (close && modal && frame) {
			frame.removeAttribute('src');
			modal.setAttribute('aria-hidden', 'true');
			document.documentElement.classList.remove('bbb-sample-pdf-open');
		}
	});

	document.addEventListener('keydown', function (event) {
		var modal = document.querySelector('[data-bbb-sample-modal]');
		var frame = document.querySelector('[data-bbb-sample-frame]');

		if ('Escape' !== event.key || !modal || 'false' !== modal.getAttribute('aria-hidden')) {
			return;
		}

		if (frame) {
			frame.removeAttribute('src');
		}
		modal.setAttribute('aria-hidden', 'true');
		document.documentElement.classList.remove('bbb-sample-pdf-open');
	});
</script>

<?php
get_footer();
