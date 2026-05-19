<?php
declare(strict_types=1);

$rows = $args['rows'] ?? bbb_get_field('accordion_rows', get_the_ID(), array());
if (!$rows) {
	return;
}
?>
<section class="collapsible-content page-width page-width--narrow">
	<?php foreach ((array) $rows as $row) : ?>
		<details class="accordion">
			<summary><h3 class="accordion__title"><?php echo esc_html((string) ($row['heading'] ?? '')); ?></h3></summary>
			<div class="accordion__content rte"><?php echo wp_kses_post(wpautop((string) ($row['content'] ?? ''))); ?></div>
		</details>
	<?php endforeach; ?>
</section>
