<?php
/**
 * Shopify-style "leave a note" footer contact section.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$sent   = isset($_GET['bbb_note']) ? sanitize_key((string) wp_unslash($_GET['bbb_note'])) : '';
$action = admin_url('admin-post.php');
?>
<section id="bbb-contact-footer" class="bbb-contact" data-bbb-contact>
	<div class="bbb-contact__wrap">
		<button class="bbb-contact__toggle" type="button" aria-expanded="<?php echo 'sent' === $sent || 'error' === $sent ? 'true' : 'false'; ?>" aria-controls="bbb-contact-panel-footer" data-contact-toggle>
			<span class="bbb-contact__toggleCopy">
				<span class="bbb-contact__kicker">leave a note</span>
				<span class="bbb-contact__title">
					<span class="bbb-contact__script">dear</span>
					<span class="bbb-contact__main">reader</span>
				</span>
				<span class="bbb-contact__sub">questions, collabs, or you just want to say hi - i'm listening.</span>
			</span>
			<span class="bbb-contact__chev" aria-hidden="true">▾</span>
		</button>

		<div class="bbb-contact__panel" id="bbb-contact-panel-footer" data-contact-panel>
			<p class="bbb-contact__email">
				<span class="bbb-contact__emailLabel">or email:</span>
				<a class="bbb-contact__emailLink" href="mailto:bybookishbabe@gmail.com">bybookishbabe@gmail.com</a>
			</p>

			<?php if ('sent' === $sent) : ?>
				<div class="bbb-contact__notice bbb-contact__notice--success" role="status">
					note received. i'll be in touch soon.
				</div>
			<?php elseif ('error' === $sent) : ?>
				<div class="bbb-contact__notice bbb-contact__notice--error" role="alert">
					something's off - check the fields and try again.
				</div>
			<?php endif; ?>

			<form class="bbb-contact__form" method="post" action="<?php echo esc_url($action); ?>">
				<input type="hidden" name="action" value="bbb_footer_contact">
				<?php wp_nonce_field('bbb_footer_contact'); ?>

				<div class="bbb-contact__grid">
					<div class="bbb-field">
						<label class="bbb-field__label" for="ContactFormName-footer">name</label>
						<input class="bbb-field__input" id="ContactFormName-footer" type="text" name="contact_name" autocomplete="name" placeholder="your name" required>
					</div>

					<div class="bbb-field">
						<label class="bbb-field__label" for="ContactFormEmail-footer">email</label>
						<input class="bbb-field__input" id="ContactFormEmail-footer" type="email" name="contact_email" autocomplete="email" placeholder="your email" required>
					</div>
				</div>

				<div class="bbb-field">
					<label class="bbb-field__label" for="ContactFormSubject-footer">subject</label>
					<input class="bbb-field__input" id="ContactFormSubject-footer" type="text" name="contact_subject" placeholder="what's this about?">
				</div>

				<div class="bbb-field">
					<label class="bbb-field__label" for="ContactFormMessage-footer">message</label>
					<textarea class="bbb-field__textarea" id="ContactFormMessage-footer" name="contact_message" rows="5" placeholder="leave your note here..." required></textarea>
					<p class="bbb-contact__helper">bonus points if you tell me what you're reading right now.</p>
				</div>

				<div class="bbb-contact__actions">
					<button class="bbb-contact__btn" type="submit">send note</button>
					<p class="bbb-contact__alt">i reply within 1-2 business days.</p>
				</div>
			</form>
		</div>
	</div>
</section>

<script>
	(function(){
		var root = document.getElementById('bbb-contact-footer');
		if (!root) return;
		var toggle = root.querySelector('[data-contact-toggle]');
		if (!toggle) return;
		toggle.addEventListener('click', function(){
			var expanded = toggle.getAttribute('aria-expanded') === 'true';
			toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
		});
	})();
</script>
