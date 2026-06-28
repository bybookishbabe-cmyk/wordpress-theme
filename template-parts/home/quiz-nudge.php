<?php
/**
 * Homepage quiz nudge.
 *
 * @package ByBookishBabeShopifyPort
 */

$bbb_quiz_boyfriends = new WP_Query(
	array(
		'post_type'              => 'bbb_boyfriend',
		'post_status'            => 'publish',
		'posts_per_page'         => 2,
		'meta_key'               => '_thumbnail_id',
		'orderby'                => 'menu_order date',
		'order'                  => 'DESC',
		'no_found_rows'          => true,
		'ignore_sticky_posts'    => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

?>
<style>
	#bbb-quiz-nudge-home {
		margin-top: 34px;
		padding-top: 18px;
		padding-bottom: 34px;
		overflow: visible;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap {
		display: flex;
		justify-content: center;
		align-items: stretch;
		gap: 0;
		min-height: 0;
		padding: 0;
		border: 0;
		background: transparent;
		box-shadow: none;
		overflow: visible;
		cursor: default;
		isolation: isolate;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap::before {
		display: none;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card {
		--bbb-tool-rotate: 0deg;
		--bbb-tool-y: 0px;
		--bbb-tool-accent: #f4f4f4;
		--bbb-tool-accent-soft: rgba(255, 255, 255, 0.12);
		--bbb-tool-accent-line: rgba(255, 255, 255, 0.48);
		--bbb-tool-panel: #111111;
		--bbb-tool-wash: rgba(255, 255, 255, 0.08);
		position: relative;
		z-index: 1;
		flex: 0 1 370px;
		min-height: 250px;
		display: grid;
		align-content: space-between;
		gap: 18px;
		padding: 20px 22px;
		border: 1px solid rgba(255, 255, 255, 0.18);
		border-radius: 16px;
		background:
			radial-gradient(360px 210px at 12% 0%, var(--bbb-tool-accent-soft), transparent 72%),
			linear-gradient(180deg, var(--bbb-tool-wash), rgba(255, 255, 255, 0.03)),
			var(--bbb-tool-panel);
		color: #fff;
		text-decoration: none;
		text-transform: lowercase;
		overflow: hidden;
		box-shadow: 0 24px 56px rgba(0, 0, 0, 0.38);
		transform: translateY(var(--bbb-tool-y)) rotate(var(--bbb-tool-rotate));
		animation: bbbToolCardSlide 0.72s cubic-bezier(0.18, 0.82, 0.22, 1) both;
		transition: border-color 0.18s ease, box-shadow 0.18s ease, transform 0.24s cubic-bezier(0.18, 0.82, 0.22, 1);
		will-change: transform;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card + .bbb-quiz-nudge__card {
		margin-left: -28px;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend {
		--bbb-tool-rotate: -3.2deg;
		--bbb-tool-y: 18px;
		--bbb-tool-accent: #f4f4f4;
		--bbb-tool-accent-soft: rgba(255, 255, 255, 0.11);
		--bbb-tool-accent-line: rgba(255, 255, 255, 0.46);
		--bbb-tool-panel: #050505;
		--bbb-tool-wash: rgba(255, 255, 255, 0.07);
		z-index: 1;
		animation-delay: 0.04s;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo {
		--bbb-tool-rotate: 1deg;
		--bbb-tool-y: -18px;
		--bbb-tool-accent: #ffffff;
		--bbb-tool-accent-soft: rgba(255, 255, 255, 0.14);
		--bbb-tool-accent-line: rgba(255, 255, 255, 0.62);
		--bbb-tool-panel: #181818;
		--bbb-tool-wash: rgba(255, 255, 255, 0.1);
		z-index: 3;
		border-color: rgba(255, 255, 255, 0.55);
		box-shadow: 0 30px 70px rgba(0, 0, 0, 0.48), 0 0 0 1px rgba(255, 255, 255, 0.14);
		animation-delay: 0.12s;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice {
		--bbb-tool-rotate: 3.1deg;
		--bbb-tool-y: 18px;
		--bbb-tool-accent: #d8d8d8;
		--bbb-tool-accent-soft: rgba(216, 216, 216, 0.12);
		--bbb-tool-accent-line: rgba(216, 216, 216, 0.5);
		--bbb-tool-panel: #242424;
		--bbb-tool-wash: rgba(255, 255, 255, 0.06);
		z-index: 2;
		animation-delay: 0.2s;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend .bbb-quiz-nudge__cardBody {
		padding-right: 28px;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice .bbb-quiz-nudge__cardBody {
		padding-left: 28px;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card:hover,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card:focus-visible,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card.is-scroll-active {
		border-color: var(--bbb-tool-accent-line);
		box-shadow: 0 34px 78px rgba(0, 0, 0, 0.54), 0 0 0 1px var(--bbb-tool-accent-soft);
		transform: translateY(calc(var(--bbb-tool-y) - 12px)) rotate(0deg) scale(1.025);
		outline: 0;
		z-index: 20;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:hover,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:focus-visible,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend.is-scroll-active {
		transform: translateY(calc(var(--bbb-tool-y) - 14px)) rotate(-1.2deg) scale(1.03);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:hover,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:focus-visible,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo.is-scroll-active {
		transform: translateY(calc(var(--bbb-tool-y) - 16px)) rotate(0deg) scale(1.028);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:hover,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:focus-visible,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice.is-scroll-active {
		transform: translateY(calc(var(--bbb-tool-y) - 14px)) rotate(1.2deg) scale(1.03);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap:has(.bbb-quiz-nudge__card:hover) .bbb-quiz-nudge__card:not(:hover),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap:has(.bbb-quiz-nudge__card:focus-visible) .bbb-quiz-nudge__card:not(:focus-visible) {
		filter: brightness(0.84);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap.has-scroll-active .bbb-quiz-nudge__card:not(.is-scroll-active):not(:hover):not(:focus-visible) {
		filter: brightness(0.74);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card.is-scroll-active {
		--bbb-tool-accent: #ff8ac7;
		--bbb-tool-accent-soft: rgba(255, 138, 199, 0.2);
		--bbb-tool-accent-line: rgba(255, 138, 199, 0.72);
		border-color: rgba(255, 138, 199, 0.72);
		background:
			radial-gradient(360px 210px at 12% 0%, rgba(255, 138, 199, 0.22), transparent 72%),
			linear-gradient(180deg, rgba(255, 255, 255, 0.11), rgba(255, 255, 255, 0.035)),
			var(--bbb-tool-panel);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card.is-scroll-active .bbb-quiz-nudge__pill,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card.is-scroll-active .bbb-quiz-nudge__cta {
		color: #ffd7eb;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__cardTop,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__cardBody {
		position: relative;
		z-index: 1;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__cardTop {
		min-height: 138px;
		display: grid;
		place-items: center;
		overflow: visible;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__pill {
		display: inline-flex;
		width: fit-content;
		margin: 0 0 12px;
		padding: 6px 10px;
		border: 1px solid var(--bbb-tool-accent-line);
		border-radius: 999px;
		background: var(--bbb-tool-accent-soft);
		color: #f5f5f5;
		font-family: "DM Sans", Arial, sans-serif;
		font-size: 11px;
		font-weight: 800;
		letter-spacing: 0.08em;
		line-height: 1;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card h3 {
		margin: 0;
		color: #fff;
		font-family: "Libre Baskerville", Georgia, serif;
		font-size: clamp(1.35rem, 2vw, 1.68rem);
		font-weight: 400;
		line-height: 1.18;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card p {
		margin: 10px 0 0;
		color: rgba(255, 255, 255, 0.82);
		font-size: 14px;
		line-height: 1.48;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__cta {
		display: block;
		margin-top: 16px;
		color: #f5f5f5;
		font-family: "DM Sans", Arial, sans-serif;
		font-size: 13px;
		font-weight: 800;
		letter-spacing: 0.04em;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__men {
		position: relative;
		top: auto;
		right: auto;
		display: grid;
		grid-template-columns: repeat(2, 96px);
		align-items: end;
		justify-content: center;
		width: 214px;
		max-width: 100%;
		min-height: 146px;
		margin: 0 auto;
		isolation: isolate;
		transform: none;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__man {
		box-sizing: border-box;
		width: 112px;
		aspect-ratio: 4 / 5;
		border: 1px solid rgba(255, 255, 255, 0.26);
		border-radius: 13px;
		overflow: hidden;
		background: rgba(255, 255, 255, 0.08);
		box-shadow: 0 18px 32px rgba(0, 0, 0, 0.45);
		transform: rotate(-7deg);
		transition: transform 0.24s ease, box-shadow 0.24s ease;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__man:nth-child(2) {
		z-index: 2;
		margin-left: -6px;
		transform: translateY(14px) rotate(8deg);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:hover .bbb-quiz-nudge__man:first-child,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:focus-visible .bbb-quiz-nudge__man:first-child,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend.is-scroll-active .bbb-quiz-nudge__man:first-child {
		box-shadow: 0 22px 38px rgba(0, 0, 0, 0.56);
		transform: translateX(-14px) translateY(-4px) rotate(-13deg);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:hover .bbb-quiz-nudge__man:nth-child(2),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend:focus-visible .bbb-quiz-nudge__man:nth-child(2),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend.is-scroll-active .bbb-quiz-nudge__man:nth-child(2) {
		box-shadow: 0 22px 38px rgba(0, 0, 0, 0.56);
		transform: translateX(14px) translateY(10px) rotate(13deg);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__manImg {
		width: 100%;
		height: 100%;
		display: block;
		object-fit: cover;
		object-position: center top;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoCard {
		display: grid;
		gap: 6px;
		width: min(164px, 50vw);
		padding: 10px;
		border: 1px solid rgba(255, 255, 255, 0.24);
		border-radius: 14px;
		background:
			linear-gradient(180deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0.035)),
			rgba(0, 0, 0, 0.3);
		box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.12);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoLetters,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid {
		display: grid;
		grid-template-columns: repeat(5, 1fr);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoLetters {
		gap: 4px;
		color: #f7efe0;
		font: 900 10px/1 "DM Sans", Arial, sans-serif;
		letter-spacing: 0.08em;
		text-align: center;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid {
		gap: 4px;
		aspect-ratio: 1;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span {
		border: 1px solid rgba(255, 255, 255, 0.24);
		border-radius: 5px;
		background: rgba(255, 255, 255, 0.09);
		color: #f7efe0;
		display: grid;
		place-items: center;
		font: 900 7px/1 "DM Sans", Arial, sans-serif;
		transition: background 0.18s ease, color 0.18s ease, transform 0.18s ease;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(1),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(7),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(13),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(19),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(25) {
		background: #e8e8e8;
		border-color: rgba(255, 255, 255, 0.78);
		color: #0b0b0b;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__bingoGrid span:nth-child(13) {
		background: #ffffff;
		border-color: rgba(255, 255, 255, 0.92);
		color: #050505;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:hover .bbb-quiz-nudge__bingoGrid span:nth-child(5),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:hover .bbb-quiz-nudge__bingoGrid span:nth-child(9),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:hover .bbb-quiz-nudge__bingoGrid span:nth-child(17),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:hover .bbb-quiz-nudge__bingoGrid span:nth-child(21),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:focus-visible .bbb-quiz-nudge__bingoGrid span:nth-child(5),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:focus-visible .bbb-quiz-nudge__bingoGrid span:nth-child(9),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:focus-visible .bbb-quiz-nudge__bingoGrid span:nth-child(17),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo:focus-visible .bbb-quiz-nudge__bingoGrid span:nth-child(21),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo.is-scroll-active .bbb-quiz-nudge__bingoGrid span:nth-child(5),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo.is-scroll-active .bbb-quiz-nudge__bingoGrid span:nth-child(9),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo.is-scroll-active .bbb-quiz-nudge__bingoGrid span:nth-child(17),
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo.is-scroll-active .bbb-quiz-nudge__bingoGrid span:nth-child(21) {
		background: #f5f5f5;
		color: #050505;
		transform: scale(1.08);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__spiceScale {
		display: grid;
		gap: 9px;
		width: min(230px, 80%);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__spiceScaleTop {
		display: flex;
		justify-content: space-between;
		color: rgba(255, 255, 255, 0.66);
		font: 800 10px/1 "DM Sans", Arial, sans-serif;
		letter-spacing: 0.08em;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__spiceTrack {
		position: relative;
		height: 12px;
		border: 1px solid rgba(255, 255, 255, 0.18);
		border-radius: 999px;
		background: linear-gradient(90deg, #f7f7f7 0%, #c9c9c9 36%, #747474 68%, #1c1c1c 100%);
		box-shadow: 0 12px 28px rgba(0, 0, 0, 0.28);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__spiceTrack::after {
		content: "";
		position: absolute;
		right: 16%;
		top: 50%;
		width: 18px;
		height: 18px;
		border: 2px solid #fff3d4;
		border-radius: 999px;
		background: #1c1c1c;
		box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.16);
		transform: translateY(-50%);
		transition: right 0.26s ease, background 0.26s ease, box-shadow 0.26s ease;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:hover .bbb-quiz-nudge__spiceTrack::after,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:focus-visible .bbb-quiz-nudge__spiceTrack::after,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice.is-scroll-active .bbb-quiz-nudge__spiceTrack::after {
		right: 4%;
		background: #050505;
		box-shadow: 0 0 0 5px rgba(255, 255, 255, 0.22);
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__spiceTicks {
		display: grid;
		grid-template-columns: repeat(5, 1fr);
		gap: 8px;
		color: #d8d8d8;
		font-size: 18px;
		line-height: 1;
		text-align: center;
		transition: letter-spacing 0.24s ease;
	}

	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:hover .bbb-quiz-nudge__spiceTicks,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice:focus-visible .bbb-quiz-nudge__spiceTicks,
	#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice.is-scroll-active .bbb-quiz-nudge__spiceTicks {
		letter-spacing: 0.08em;
	}

	@keyframes bbbToolCardSlide {
		from {
			opacity: 0;
			transform: translateY(calc(var(--bbb-tool-y) + 34px)) rotate(calc(var(--bbb-tool-rotate) - 5deg)) scale(0.94);
		}
		to {
			opacity: 1;
			transform: translateY(var(--bbb-tool-y)) rotate(var(--bbb-tool-rotate)) scale(1);
		}
	}

	@media (max-width: 900px) {
		#bbb-quiz-nudge-home {
			padding-bottom: 72px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__wrap {
			display: flex;
			flex-direction: column;
			align-items: center;
			gap: 0;
			padding-top: 12px;
			padding-bottom: 70px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card {
			width: min(100%, 380px);
			flex: 0 0 auto;
			min-height: 0;
			margin-left: 0;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card + .bbb-quiz-nudge__card {
			margin-left: 0;
			margin-top: -10px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend {
			--bbb-tool-rotate: -2.2deg;
			--bbb-tool-y: 0px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card--bingo {
			--bbb-tool-rotate: 1deg;
			--bbb-tool-y: 0px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice {
			--bbb-tool-rotate: 2.1deg;
			--bbb-tool-y: 0px;
		}

		#bbb-quiz-nudge-home .bbb-quiz-nudge__card--boyfriend .bbb-quiz-nudge__cardBody,
		#bbb-quiz-nudge-home .bbb-quiz-nudge__card--spice .bbb-quiz-nudge__cardBody {
			padding-left: 0;
			padding-right: 0;
		}
	}

	@media (prefers-reduced-motion: reduce) {
		#bbb-quiz-nudge-home .bbb-quiz-nudge__card,
		#bbb-quiz-nudge-home .bbb-quiz-nudge__card:hover,
		#bbb-quiz-nudge-home .bbb-quiz-nudge__card:focus-visible {
			animation: none;
			transition: none;
		}
	}
</style>
<section id="bbb-quiz-nudge-home" class="bbb-quiz-nudge">
	<div class="bbb-quiz-nudge__wrap">
		<a class="bbb-quiz-nudge__card bbb-quiz-nudge__card--boyfriend" href="<?php echo esc_url(home_url('/fictional-boyfriend-quiz/')); ?>">
			<div class="bbb-quiz-nudge__cardTop">
			<?php if ($bbb_quiz_boyfriends->have_posts()) : ?>
				<div class="bbb-quiz-nudge__men" aria-hidden="true">
					<?php
					while ($bbb_quiz_boyfriends->have_posts()) :
						$bbb_quiz_boyfriends->the_post();
						?>
						<div class="bbb-quiz-nudge__man">
							<?php
							the_post_thumbnail(
								'medium_large',
								array(
									'class'    => 'bbb-quiz-nudge__manImg',
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							?>
						</div>
					<?php endwhile; ?>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
			</div>
			<div class="bbb-quiz-nudge__cardBody">
				<span class="bbb-quiz-nudge__pill">reader quiz</span>
				<h3>fictional boyfriend quiz</h3>
				<p>meet the fictional man who is probably your problem.</p>
				<span class="bbb-quiz-nudge__cta">take the quiz →</span>
			</div>
		</a>

		<a class="bbb-quiz-nudge__card bbb-quiz-nudge__card--bingo" href="<?php echo esc_url(home_url('/romance-reading-bingo/')); ?>">
			<div class="bbb-quiz-nudge__cardTop">
				<span class="bbb-quiz-nudge__bingoCard" aria-hidden="true">
					<span class="bbb-quiz-nudge__bingoLetters">
						<span>b</span><span>i</span><span>n</span><span>g</span><span>o</span>
					</span>
					<span class="bbb-quiz-nudge__bingoGrid">
						<?php for ($bbb_bingo_cell = 1; $bbb_bingo_cell <= 25; $bbb_bingo_cell++) : ?>
							<span><?php echo 13 === $bbb_bingo_cell ? esc_html('free') : ''; ?></span>
						<?php endfor; ?>
					</span>
				</span>
			</div>
			<div class="bbb-quiz-nudge__cardBody">
				<span class="bbb-quiz-nudge__pill">summer edition</span>
				<h3>romance reading bingo</h3>
				<p>mark your tropes, book boyfriends, and new releases.</p>
				<span class="bbb-quiz-nudge__cta">find your reader type →</span>
			</div>
		</a>

		<a class="bbb-quiz-nudge__card bbb-quiz-nudge__card--spice" href="<?php echo esc_url(home_url('/romance-books-by-spice-level/')); ?>">
			<div class="bbb-quiz-nudge__cardTop">
				<span class="bbb-quiz-nudge__spiceScale" aria-hidden="true">
					<span class="bbb-quiz-nudge__spiceScaleTop"><span>soft</span><span>feral</span></span>
					<span class="bbb-quiz-nudge__spiceTrack"></span>
					<span class="bbb-quiz-nudge__spiceTicks">
						<span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span>
					</span>
				</span>
			</div>
			<div class="bbb-quiz-nudge__cardBody">
				<span class="bbb-quiz-nudge__pill">browse by heat</span>
				<h3>spice level browser</h3>
				<p>go straight to the romance books that match your heat level.</p>
				<span class="bbb-quiz-nudge__cta">browse spice →</span>
			</div>
		</a>
	</div>
</section>
<script>
	(function () {
		var section = document.getElementById('bbb-quiz-nudge-home');
		if (!section) {
			return;
		}

		var wrap = section.querySelector('.bbb-quiz-nudge__wrap');
		var cards = Array.prototype.slice.call(section.querySelectorAll('.bbb-quiz-nudge__card'));
		var ticking = false;

		if (!wrap || !cards.length) {
			return;
		}

		function setActiveCard() {
			ticking = false;

			var viewportCenter = window.innerHeight / 2;
			var sectionRect = section.getBoundingClientRect();
			var active = null;
			var activeDistance = Infinity;

			if (sectionRect.bottom < 0 || sectionRect.top > window.innerHeight) {
				wrap.classList.remove('has-scroll-active');
				cards.forEach(function (card) {
					card.classList.remove('is-scroll-active');
				});
				return;
			}

			cards.forEach(function (card) {
				var rect = card.getBoundingClientRect();
				var center = rect.top + (rect.height / 2);
				var distance = Math.abs(center - viewportCenter);

				if (distance < activeDistance) {
					activeDistance = distance;
					active = card;
				}
			});

			wrap.classList.toggle('has-scroll-active', !!active);
			cards.forEach(function (card) {
				card.classList.toggle('is-scroll-active', card === active);
			});
		}

		function requestActiveCard() {
			if (ticking) {
				return;
			}

			ticking = true;
			window.requestAnimationFrame(setActiveCard);
		}

		setActiveCard();
		window.addEventListener('scroll', requestActiveCard, { passive: true });
		window.addEventListener('resize', requestActiveCard);
	})();
</script>
