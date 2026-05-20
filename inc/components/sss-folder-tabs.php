<?php
declare(strict_types=1);

$tabs = $args['tabs'] ?? array(
	array('label' => 'main page', 'icon' => '📁', 'slug' => 'society-library', 'sub' => 'dashboard'),
	array('label' => 'library', 'icon' => '📚', 'slug' => 'sss-library-page', 'sub' => 'books'),
	array('label' => 'member dashboard', 'icon' => '✨', 'slug' => 'member-dashboard', 'sub' => 'recs'),
	array('label' => 'printable inserts', 'icon' => '🖨️', 'slug' => 'sss-printable-kindle-inserts', 'sub' => 'drops'),
	array('label' => 'bookish templates', 'icon' => '⌨️', 'slug' => 'sss-canva-templates', 'sub' => 'canva'),
	array('label' => 'free extras', 'icon' => '🎁', 'slug' => 'sss-freebies', 'sub' => 'downloads'),
	array('label' => 'quote library', 'icon' => '"', 'slug' => 'sss-quote-wall', 'sub' => 'quotes'),
	array('label' => 'private shelf', 'icon' => '🔓', 'slug' => 'sss-private-shelf', 'sub' => 'members'),
);
?>
<nav class="sss-folder-tabs" aria-label="<?php esc_attr_e('Society navigation', 'bybookishbabe-shopify-port'); ?>">
	<div class="sss-folder-tabs__track">
		<?php foreach ($tabs as $tab) : ?>
			<a class="sss-folder-tabs__tab sss-tabs__tab" href="<?php echo esc_url(bbb_resolve_page_url((string) $tab['slug'])); ?>">
				<span class="sss-folder-tabs__icon sss-tabs__icon"><?php echo esc_html((string) $tab['icon']); ?></span>
				<span class="sss-folder-tabs__label sss-tabs__label"><?php echo esc_html((string) $tab['label']); ?></span>
				<?php if (!empty($tab['sub'])) : ?>
					<span class="sss-folder-tabs__sub sss-tabs__sub"><?php echo esc_html((string) $tab['sub']); ?></span>
				<?php endif; ?>
			</a>
		<?php endforeach; ?>
	</div>
</nav>
