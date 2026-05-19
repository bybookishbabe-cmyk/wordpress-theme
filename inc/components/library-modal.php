<?php
declare(strict_types=1);
?>
<div class="sss-lib__modal" id="sssLibraryModal" hidden>
	<div class="sss-lib__modalBackdrop" data-sss-modal-close></div>
	<div class="sss-lib__modalPanel" role="dialog" aria-modal="true" aria-labelledby="sssLibraryModalTitle">
		<button class="sss-lib__modalClose" type="button" data-sss-modal-close aria-label="<?php esc_attr_e('Close', 'bybookishbabe-shopify-port'); ?>">×</button>
		<img class="sss-lib__modalCover" data-sss-modal-cover alt="">
		<div>
			<p class="sss-lib__modalAuthor" data-sss-modal-author></p>
			<h2 class="sss-lib__modalTitle" id="sssLibraryModalTitle" data-sss-modal-title></h2>
		</div>
	</div>
</div>
