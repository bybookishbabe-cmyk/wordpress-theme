<?php
/**
 * Template Name: Society Submissions
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$reader_identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
$has_society_member_access = is_array($reader_identity) && '' !== trim((string) ($reader_identity['email'] ?? ''));
$join_url = get_option('bbb_society_gate_member_url', 'https://thesmutandsentimentsociety.substack.com/subscribe');

get_header();

if (!$has_society_member_access) {
	?>
	<section class="bbb-submission-page bbb-submission-page--locked">
		<div class="bbb-submission-page__wrap page-width">
			<header class="bbb-submission-page__hero">
				<p class="bbb-submission-page__kicker">the smut and sentiment society</p>
				<h1 class="bbb-submission-page__title">get featured in the sunday newsletter</h1>
				<p class="bbb-submission-page__sub">
					send in your hot takes, favorite quotes, book recommendations, and reader-core thoughts once you are inside the society.
				</p>
			</header>

			<div class="bbb-submission-page__panel bbb-submission-page__panel--locked">
				<div class="bbb-submission-lock">
					<p class="bbb-submission-lock__eyebrow">member preview</p>
					<h2>join the society to submit</h2>
					<p>
						submissions are open to free and paid society members. join first, then come back to send the take,
						quote, or rec you want considered for the newsletter.
					</p>
					<a href="<?php echo esc_url($join_url); ?>" target="_blank" rel="noopener">join the society</a>
				</div>
			</div>
		</div>
	</section>

	<style>
		.bbb-submission-page--locked{
			background:
				radial-gradient(circle at top left, rgba(239,137,191,.14), transparent 32%),
				radial-gradient(circle at 80% 12%, rgba(126,82,114,.16), transparent 26%),
				linear-gradient(180deg, #090909 0%, #0d0b0d 28%, #090909 100%);
			color:#f6f1ef;
			padding:4rem 0 6rem;
		}
		.bbb-submission-page--locked .bbb-submission-page__wrap{max-width:112rem;}
		.bbb-submission-page--locked .bbb-submission-page__hero{max-width:92rem;margin:0 0 3rem;position:relative;}
		.bbb-submission-page--locked .bbb-submission-page__kicker{
			margin:0 0 1rem;
			font-size:1.15rem;
			letter-spacing:.16em;
			text-transform: lowercase;
			color:rgba(255,255,255,.58);
		}
		.bbb-submission-page--locked .bbb-submission-page__title{
			margin:0;
			font-size:clamp(4rem, 7vw, 7.4rem);
			line-height:.94;
			font-style:italic;
			font-weight:500;
			text-wrap:balance;
			text-shadow:0 0 28px rgba(239,137,191,.08);
		}
		.bbb-submission-page--locked .bbb-submission-page__sub{
			max-width:96rem;
			margin:1.5rem 0 0;
			font-size:1.95rem;
			line-height:1.6;
			color:rgba(255,255,255,.78);
		}
		.bbb-submission-page__panel--locked{
			margin-top:2.4rem;
			border:1px solid rgba(239,137,191,.28);
			border-radius:2.6rem;
			background:linear-gradient(180deg, rgba(44,39,41,.96) 0%, rgba(34,31,32,.98) 100%);
			box-shadow:0 2.4rem 6rem rgba(0,0,0,.24), inset 0 1px 0 rgba(255,255,255,.04);
			overflow:hidden;
			position:relative;
		}
		.bbb-submission-page__panel--locked::before{
			content:"";
			position:absolute;
			inset:0;
			background:
				linear-gradient(135deg, rgba(239,137,191,.09), transparent 36%),
				radial-gradient(circle at right top, rgba(255,255,255,.03), transparent 28%);
			pointer-events:none;
		}
		.bbb-submission-lock{
			position:relative;
			z-index:1;
			display:grid;
			gap:1.4rem;
			padding:3rem;
		}
		.bbb-submission-lock__eyebrow{
			margin:0;
			color:rgba(255,255,255,.64);
			font-size:1.15rem;
			letter-spacing:.16em;
			text-transform: lowercase;
		}
		.bbb-submission-lock h2{
			margin:0;
			color:#fff;
			font-family:Cormorant, "Cormorant Garamond", Georgia, serif;
			font-size:clamp(3.2rem, 5vw, 5.8rem);
			font-weight:400;
			line-height:1;
			text-transform:lowercase;
		}
		.bbb-submission-lock p:not(.bbb-submission-lock__eyebrow){
			max-width:74rem;
			margin:0;
			color:rgba(255,255,255,.76);
			font-size:1.7rem;
			line-height:1.6;
		}
		.bbb-submission-lock a{
			display:inline-flex;
			align-items:center;
			justify-content:center;
			width:min(34rem, 100%);
			min-height:4.8rem;
			margin-top:.6rem;
			border:1px solid rgba(239,137,191,.46);
			border-radius:999px;
			background:rgba(239,137,191,.16);
			color:#fff;
			font-size:1.25rem;
			font-weight:800;
			letter-spacing:.08em;
			line-height:1.2;
			text-decoration:none;
			text-transform: lowercase;
		}
		@media screen and (max-width: 749px){
			.bbb-submission-page--locked{padding:2.8rem 0 4.6rem;}
			.bbb-submission-page--locked .bbb-submission-page__title{font-size:clamp(3.6rem, 11vw, 5.2rem);}
			.bbb-submission-page--locked .bbb-submission-page__sub{font-size:1.75rem;line-height:1.55;}
			.bbb-submission-lock{padding:2.2rem 1.8rem;}
			.bbb-submission-page__panel--locked{border-radius:2rem;}
		}
	</style>
	<?php
	get_footer();
	return;
}

$rendered = false;
while (have_posts()) {
	the_post();
	bbb_render_section('newsletter-submissions-page', array('page_id' => get_the_ID()));
	$rendered = true;
}

if (!$rendered) {
	bbb_render_section('newsletter-submissions-page', array('page_id' => 0));
}

get_footer();
