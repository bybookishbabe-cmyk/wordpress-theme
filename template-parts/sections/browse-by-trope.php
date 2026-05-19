<?php
/**
 * Browse by trope homepage section.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$defaults = array(
	'kicker'       => 'romance navigation',
	'title'        => 'browse by trope',
	'subtext'      => 'find your next obsession by trope.',
	'spice_url'    => '/romance-books-by-spice-level/',
	'spice_kicker' => 'new way to browse',
	'spice_text'   => 'want the exact spice level? browse romance by spice level →',
	'trope_cards'  => array(
		array('title' => 'sports romance', 'emoji' => '🏒', 'url' => '/sports-romance-books/'),
		array('title' => 'enemies to lovers', 'emoji' => '⚔️', 'url' => '/enemies-to-lovers/'),
		array('title' => 'slow burn', 'emoji' => '🕯️', 'url' => '/slow-burn-books/'),
		array('title' => 'dark romance + morally gray men', 'emoji' => '💀', 'url' => '/curated-romance-guides/the-best-dark-romance-books-with-morally-gray-men-that-will-ruin-you/'),
	),
);

$kicker       = bbb_get_field('bbb_tropes_kicker', 'option', $defaults['kicker']);
$title        = bbb_get_field('bbb_tropes_title', 'option', $defaults['title']);
$spice_url    = bbb_get_field('bbb_tropes_spice_url', 'option', $defaults['spice_url']);
$spice_kicker = bbb_get_field('bbb_tropes_spice_kicker', 'option', $defaults['spice_kicker']);
$spice_text   = bbb_get_field('bbb_tropes_spice_text', 'option', $defaults['spice_text']);
$trope_cards  = function_exists('get_field') ? get_field('bbb_trope_cards', 'option') : array();

if (empty($trope_cards) || !is_array($trope_cards)) {
	$trope_cards = $defaults['trope_cards'];
}
?>
<section class="bbb-tropes">
  <div class="bbb-tropes__inner">

    <a class="bbb-spiceCallout" href="<?php echo esc_url((string) $spice_url); ?>">
      <span class="bbb-spiceCallout__rain" aria-hidden="true">
        <span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span><span>🌶</span>
      </span>
      <span class="bbb-spiceCallout__kicker"><?php echo esc_html((string) $spice_kicker); ?></span>
      <span class="bbb-spiceCallout__text"><?php echo esc_html((string) $spice_text); ?></span>
    </a>

    <div class="bbb-tropes__row">

      <div class="bbb-tropes__titleWrap">

        <p class="bbb-tropes__kicker">
          <?php echo esc_html((string) $kicker); ?>
        </p>

        <h2 class="bbb-tropes__title">
          <?php echo esc_html((string) $title); ?>
        </h2>

      </div>

      <div class="bbb-tropes__grid">

        <?php foreach ($trope_cards as $trope_card) : ?>
          <?php
			$trope_title = (string) ($trope_card['title'] ?? '');
			$trope_emoji = (string) ($trope_card['emoji'] ?? '');
			$trope_url   = (string) ($trope_card['url'] ?? '');
			?>
        <a href="<?php echo esc_url($trope_url); ?>"
           class="bbb-trope-card"
           data-emoji="<?php echo esc_attr($trope_emoji); ?>">

          <div class="bbb-emoji-rain"></div>

          <div class="bbb-trope-card__label">
            trope
          </div>

          <div class="bbb-trope-card__title">
            <?php echo esc_html($trope_title); ?>
          </div>

          <div class="bbb-trope-card__arrow">
            see books →
          </div>

        </a>

        <?php endforeach; ?>

      </div>

    </div>
  </div>
</section>
