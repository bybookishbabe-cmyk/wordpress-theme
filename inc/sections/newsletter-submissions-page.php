<?php
/**
 * Society newsletter submissions form.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$page_id = isset($args['page_id']) ? (int) $args['page_id'] : (int) get_the_ID();

$defaults = array(
	'kicker'           => 'the smut and sentiment society',
	'heading'          => 'get featured in the sunday newsletter',
	'subtext'          => "every sunday i feature one reader. got a hot take, a quote that lives rent free in your head, or a book you'd recommend to everyone you like? submit it below.",
	'submission_types' => "bookish hot take\nquote i can't stop thinking about\nbook recommendation\nunpopular opinion",
	'submit_label'     => 'submit to the sunday newsletter ->',
	'table_name'       => 'newsletter_submissions',
	'supabase_url'     => defined('SUPABASE_URL') ? (string) SUPABASE_URL : 'https://efmrfxsmgbeikfgtrxjv.supabase.co',
	'supabase_key'     => defined('SUPABASE_ANON_KEY') ? (string) SUPABASE_ANON_KEY : 'sb_publishable_iwjASe3QwixdDvHovaXZBQ_gbXU0Utk',
);

$kicker           = (string) bbb_get_field('kicker', $page_id, $defaults['kicker']);
$heading          = (string) bbb_get_field('heading', $page_id, $defaults['heading']);
$subtext          = (string) bbb_get_field('subtext', $page_id, $defaults['subtext']);
$submission_types = (string) bbb_get_field('submission_types', $page_id, $defaults['submission_types']);
$submit_label     = (string) bbb_get_field('submit_label', $page_id, $defaults['submit_label']);
$table_name       = (string) bbb_get_field('table_name', $page_id, $defaults['table_name']);
$supabase_url     = (string) bbb_get_field('supabase_url', $page_id, $defaults['supabase_url']);
$supabase_key     = (string) bbb_get_field('supabase_key', $page_id, $defaults['supabase_key']);

$type_options = array_values(
	array_filter(
		array_map('trim', preg_split('/\R/', $submission_types) ?: array()),
		static fn(string $option): bool => '' !== $option
	)
);

if (!$type_options) {
	$type_options = array_values(array_filter(array_map('trim', preg_split('/\R/', $defaults['submission_types']) ?: array())));
}

$section_id = 'NewsletterSubmissions-' . ($page_id > 0 ? (string) $page_id : 'route');
?>
<section
	class="bbb-submission-page"
	id="<?php echo esc_attr($section_id); ?>"
	data-newsletter-submissions
	data-supabase-url="<?php echo esc_attr($supabase_url); ?>"
	data-supabase-key="<?php echo esc_attr($supabase_key); ?>"
	data-table-name="<?php echo esc_attr($table_name); ?>"
>
	<div class="bbb-submission-page__wrap page-width">
		<header class="bbb-submission-page__hero">
			<?php if ('' !== trim($kicker)) : ?>
				<p class="bbb-submission-page__kicker"><?php echo esc_html($kicker); ?></p>
			<?php endif; ?>
			<h1 class="bbb-submission-page__title"><?php echo esc_html($heading); ?></h1>
			<p class="bbb-submission-page__sub"><?php echo esc_html($subtext); ?></p>
		</header>

		<div class="bbb-submission-page__panel">
			<form class="bbb-submission-form" data-submission-form novalidate>
				<div class="bbb-submission-form__field">
					<label for="<?php echo esc_attr($section_id); ?>-Name">your name (or how you want to be featured)</label>
					<input
						id="<?php echo esc_attr($section_id); ?>-Name"
						name="display_name"
						type="text"
						placeholder="e.g. sarah or @darkromancereads"
						maxlength="120"
						required
					>
				</div>

				<div class="bbb-submission-form__field">
					<label for="<?php echo esc_attr($section_id); ?>-Type">what are you submitting?</label>
					<div class="bbb-submission-form__selectWrap">
						<select id="<?php echo esc_attr($section_id); ?>-Type" name="submission_type" required>
							<?php foreach ($type_options as $option) : ?>
								<option value="<?php echo esc_attr($option); ?>"><?php echo esc_html($option); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="bbb-submission-form__field">
					<label for="<?php echo esc_attr($section_id); ?>-Body">your submission</label>
					<textarea
						id="<?php echo esc_attr($section_id); ?>-Body"
						name="submission_text"
						rows="6"
						placeholder="if the mmc isn't at least a little unhinged by chapter five, i've already lost interest."
						maxlength="1200"
						required
					></textarea>
				</div>

				<div class="bbb-submission-form__field">
					<label for="<?php echo esc_attr($section_id); ?>-Book">book title & author (if submitting a quote or rec)</label>
					<input
						id="<?php echo esc_attr($section_id); ?>-Book"
						name="book_title_author"
						type="text"
						placeholder="e.g. Haunting Adeline by H.D. Carlton"
						maxlength="220"
					>
				</div>

				<label class="bbb-submission-form__check">
					<input type="checkbox" name="can_feature_with_name" value="true">
					<span>yes, you can feature this in the newsletter with my name</span>
				</label>

				<div class="bbb-submission-form__actions">
					<button type="submit" class="bbb-submission-form__submit" data-submit-button>
						<span data-submit-label><?php echo esc_html($submit_label); ?></span>
					</button>
					<p class="bbb-submission-form__status" data-form-status aria-live="polite"></p>
				</div>
			</form>
		</div>
	</div>
</section>

<style>
	.bbb-submission-page{
		background:
			radial-gradient(circle at top left, rgba(239,137,191,.14), transparent 32%),
			radial-gradient(circle at 80% 12%, rgba(126,82,114,.16), transparent 26%),
			linear-gradient(180deg, #090909 0%, #0d0b0d 28%, #090909 100%);
		color:#f6f1ef;
		padding:4rem 0 6rem;
	}
	.bbb-submission-page__wrap{max-width:112rem;}
	.bbb-submission-page__hero{max-width:92rem;margin:0 0 3rem;position:relative;}
	.bbb-submission-page__kicker{
		margin:0 0 1rem;
		font-size:1.15rem;
		letter-spacing:.16em;
		text-transform:uppercase;
		color:rgba(255,255,255,.58);
	}
	.bbb-submission-page__title{
		margin:0;
		font-size:clamp(4rem, 7vw, 7.4rem);
		line-height:.94;
		font-style:italic;
		font-weight:500;
		text-wrap:balance;
		text-shadow:0 0 28px rgba(239,137,191,.08);
	}
	.bbb-submission-page__sub{
		max-width:96rem;
		margin:1.5rem 0 0;
		font-size:1.95rem;
		line-height:1.6;
		color:rgba(255,255,255,.78);
	}
	.bbb-submission-page__panel{
		margin-top:2.4rem;
		border:1px solid rgba(255,255,255,.12);
		border-radius:2.6rem;
		background:linear-gradient(180deg, rgba(44,39,41,.96) 0%, rgba(34,31,32,.98) 100%);
		box-shadow:0 2.4rem 6rem rgba(0,0,0,.24), inset 0 1px 0 rgba(255,255,255,.04);
		overflow:hidden;
		position:relative;
	}
	.bbb-submission-page__panel::before{
		content:"";
		position:absolute;
		inset:0;
		background:
			linear-gradient(135deg, rgba(239,137,191,.09), transparent 36%),
			radial-gradient(circle at right top, rgba(255,255,255,.03), transparent 28%);
		pointer-events:none;
	}
	.bbb-submission-form{display:grid;gap:2.2rem;padding:3rem;position:relative;z-index:1;}
	.bbb-submission-form__field label{
		display:block;
		margin:0 0 1rem;
		font-size:1.5rem;
		line-height:1.35;
		color:rgba(255,255,255,.9);
	}
	.bbb-submission-form__field input,
	.bbb-submission-form__field textarea,
	.bbb-submission-form__field select{
		width:100%;
		border:1px solid rgba(255,255,255,.1);
		border-radius:1.4rem;
		background:rgba(18,16,17,.68);
		color:#f8f2f0;
		font:inherit;
		line-height:1.45;
		padding:1.8rem 2rem;
		box-shadow:none;
	}
	.bbb-submission-form__field input::placeholder,
	.bbb-submission-form__field textarea::placeholder{color:rgba(255,255,255,.48);}
	.bbb-submission-form__field textarea{min-height:18rem;resize:vertical;}
	.bbb-submission-form__field input:focus,
	.bbb-submission-form__field textarea:focus,
	.bbb-submission-form__field select:focus{
		outline:none;
		border-color:#ef89bf;
		box-shadow:0 0 0 1px #ef89bf, 0 0 0 7px rgba(239,137,191,.08);
	}
	.bbb-submission-form__selectWrap{position:relative;}
	.bbb-submission-form__selectWrap::after{
		content:"";
		position:absolute;
		top:50%;
		right:1.8rem;
		width:1rem;
		height:1rem;
		border-right:2px solid rgba(255,255,255,.55);
		border-bottom:2px solid rgba(255,255,255,.55);
		transform:translateY(-70%) rotate(45deg);
		pointer-events:none;
	}
	.bbb-submission-form__field select{appearance:none;padding-right:4.8rem;}
	.bbb-submission-form__check{
		display:flex;
		align-items:flex-start;
		gap:1.2rem;
		font-size:1.55rem;
		line-height:1.55;
		color:rgba(255,255,255,.82);
		cursor:pointer;
	}
	.bbb-submission-form__check input{
		width:2.2rem;
		height:2.2rem;
		margin:.15rem 0 0;
		accent-color:#ef89bf;
	}
	.bbb-submission-form__actions{display:flex;flex-direction:column;gap:1.2rem;align-items:flex-start;}
	.bbb-submission-form__submit{
		width:100%;
		justify-content:center;
		border:1px solid rgba(239,137,191,.26);
		border-radius:1.6rem;
		background:linear-gradient(135deg, rgba(239,137,191,.12), rgba(255,255,255,.02));
		color:#ffffff;
		font:inherit;
		font-size:1.85rem;
		font-weight:600;
		line-height:1.2;
		padding:1.8rem 2.4rem;
		cursor:pointer;
		transition:background .22s ease, color .22s ease, border-color .22s ease, transform .22s ease;
	}
	.bbb-submission-form__submit:hover,
	.bbb-submission-form__submit:focus-visible{
		background:#ef89bf;
		color:#140d12;
		border-color:#ef89bf;
		transform:translateY(-1px);
		outline:none;
		box-shadow:0 1.2rem 2.4rem rgba(239,137,191,.18);
	}
	.bbb-submission-form__submit[disabled]{opacity:.65;cursor:wait;transform:none;}
	.bbb-submission-form__status{
		margin:0;
		font-size:1.45rem;
		line-height:1.5;
		color:rgba(255,255,255,.72);
	}
	.bbb-submission-form__status[data-state="success"]{color:#90f0b2;}
	.bbb-submission-form__status[data-state="error"]{color:#ff9ab5;}
	@media screen and (max-width: 749px){
		.bbb-submission-page{padding:2.8rem 0 4.6rem;}
		.bbb-submission-page__title{font-size:clamp(3.6rem, 11vw, 5.2rem);}
		.bbb-submission-page__sub{font-size:1.75rem;line-height:1.55;}
		.bbb-submission-form{padding:2.2rem 1.8rem;}
		.bbb-submission-page__panel{border-radius:2rem;}
		.bbb-submission-form__field input,
		.bbb-submission-form__field textarea,
		.bbb-submission-form__field select{padding:1.6rem 1.7rem;font-size:1.7rem;}
		.bbb-submission-form__submit{font-size:1.7rem;}
	}
</style>

<script>
	(function(){
		var root = document.querySelector('[data-newsletter-submissions]');
		if (!root) return;

		var form = root.querySelector('[data-submission-form]');
		if (!form) return;

		var statusEl = root.querySelector('[data-form-status]');
		var submitButton = root.querySelector('[data-submit-button]');
		var submitLabel = root.querySelector('[data-submit-label]');
		var nameInput = form.querySelector('[name="display_name"]');
		var typeInput = form.querySelector('[name="submission_type"]');
		var bodyInput = form.querySelector('[name="submission_text"]');
		var bookInput = form.querySelector('[name="book_title_author"]');
		var consentInput = form.querySelector('[name="can_feature_with_name"]');
		var supabaseUrl = root.dataset.supabaseUrl || '';
		var supabaseKey = root.dataset.supabaseKey || '';
		var tableName = root.dataset.tableName || 'newsletter_submissions';
		var idleLabel = <?php echo wp_json_encode($submit_label); ?>;

		function getSessionId(){
			try {
				var key = 'bbbSessionId';
				var existing = window.localStorage.getItem(key);
				if (existing) return existing;
				var created = 'bbb-' + Math.random().toString(36).slice(2) + Date.now().toString(36);
				window.localStorage.setItem(key, created);
				return created;
			} catch (error) {
				return 'bbb-' + Date.now().toString(36);
			}
		}

		function setStatus(message, state){
			if (!statusEl) return;
			statusEl.textContent = message || '';
			if (state) {
				statusEl.dataset.state = state;
			} else {
				statusEl.removeAttribute('data-state');
			}
		}

		form.addEventListener('submit', async function(event){
			event.preventDefault();
			setStatus('');

			var displayName = (nameInput && nameInput.value.trim()) || '';
			var submissionType = (typeInput && typeInput.value.trim()) || '';
			var submissionText = (bodyInput && bodyInput.value.trim()) || '';

			if (!displayName || !submissionType || !submissionText) {
				setStatus('please fill out your name, submission type, and submission before sending it through.', 'error');
				return;
			}

			if (!window.supabase || typeof window.supabase.createClient !== 'function') {
				setStatus('the submissions connection is missing right now. reload the page and try again.', 'error');
				return;
			}

			if (submitButton) {
				submitButton.setAttribute('disabled', 'disabled');
				submitButton.setAttribute('aria-busy', 'true');
			}
			if (submitLabel) submitLabel.textContent = 'sending...';

			try {
				var client = window.supabase.createClient(supabaseUrl, supabaseKey);
				var payload = {
					display_name: displayName,
					submission_type: submissionType,
					submission_text: submissionText,
					book_title_author: (bookInput && bookInput.value.trim()) || null,
					can_feature_with_name: !!(consentInput && consentInput.checked),
					page_path: window.location.pathname || null,
					session_id: getSessionId(),
					user_agent: navigator.userAgent || null
				};

				var response = await client.from(tableName).insert(payload);
				if (response.error) throw response.error;

				form.reset();
				setStatus("got it - you're in the pile for the sunday newsletter.", 'success');
			} catch (error) {
				console.error('[newsletter-submissions]', error);
				setStatus('something glitched while sending that through. try again in a sec.', 'error');
			} finally {
				if (submitButton) {
					submitButton.removeAttribute('disabled');
					submitButton.removeAttribute('aria-busy');
				}
				if (submitLabel) submitLabel.textContent = idleLabel;
			}
		});
	})();
</script>
