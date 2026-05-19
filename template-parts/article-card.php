<?php
/**
 * List article card for blog archives.
 *
 * @package ByBookishBabeShopifyPort
 */
?>

<article class="article-card-wrapper article-card-wrapper--list underline-links-hover">
	<a href="<?php the_permalink(); ?>" class="article-card article-card--list full-unstyled-link">
		<div class="article-card__content article-card__content--list">

			<div class="article-card__meta article-card__meta--list">
				<span class="article-card__info article-card__info--list">
					<time datetime="<?php echo esc_attr(get_the_date('Y-m-d')); ?>">
						<?php echo esc_html(get_the_date('F j, Y')); ?>
					</time>
				</span>
			</div>

			<h3 class="article-card__heading article-card__heading--list">
				<?php echo esc_html(get_the_title()); ?>
			</h3>

		</div>
	</a>
</article>
