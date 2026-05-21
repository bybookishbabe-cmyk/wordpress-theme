<?php
/**
 * Checkout page template.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

bbb_enqueue_css('bbb-edd-checkout', 'assets/css/edd-checkout.css', array('bbb-base'));

get_header();
?>

<main class="bbb-checkout" id="main">
	<section class="bbb-checkout__hero">
		<div class="bbb-checkout__inner">
			<p class="bbb-checkout__kicker">checkout</p>
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
					<li>choose only the kindle size you need</li>
				</ul>
			</aside>
		</div>
	</section>
</main>

<script>
	document.addEventListener('change', function (event) {
		var select = event.target.closest('[data-bbb-sample-select]');
		var link = document.querySelector('[data-bbb-sample-link]');

		if (!select || !link) {
			return;
		}

		link.href = select.value;
	});
</script>

<?php
get_footer();
