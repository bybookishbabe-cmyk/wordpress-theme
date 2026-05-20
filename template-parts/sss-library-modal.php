<?php
/**
 * Blog library modal bridge.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

if (!is_singular('post')) {
	return;
}
?>
<div id="bbbBookPreview" class="sss-lib__modal" hidden aria-hidden="true" style="display:none;">
	<div class="sss-lib__backdrop" data-close></div>
	<div class="sss-lib__dialog bbbBookPreview__inner" role="dialog" aria-modal="true" aria-label="book breakdown">
		<button id="bbbPreviewShare" class="sss-lib__mshare bbbBookPreview__share" type="button" aria-label="share this book">📲</button>
		<button id="bbbPreviewClose" class="sss-lib__x" type="button" data-close aria-label="close">×</button>

		<div class="sss-lib__mhead">
			<div class="sss-lib__mkicker">book breakdown</div>
			<div class="sss-lib__mseries" data-mseries hidden></div>
			<div class="sss-lib__mseriesOrder" data-mseries-order></div>
			<div class="sss-lib__mstandalone" data-mstandalone></div>
			<div class="sss-lib__mtitle" data-mtitle></div>
			<div class="sss-lib__mauthor" data-mauthor></div>
		</div>

		<div class="sss-lib__mbody">
			<div class="sss-lib__mcoverWrap">
				<div class="sss-lib__mcoverFrame">
					<span class="sss-lib__heart sss-lib__heart--modal" data-modal-heart role="button" aria-label="save to your bookshelf">
						<span class="sss-lib__heartIcon" data-heart-icon aria-hidden="true">♡</span>
						<span class="sss-lib__heartLabel" data-heart-label>save</span>
					</span>
					<div class="sss-lib__floatSpice sss-lib__mspice" data-mspice hidden></div>
					<img class="sss-lib__mcover" alt="" loading="lazy" data-mcover>
				</div>
			</div>

			<div class="sss-lib__mcontent">
				<div class="sss-lib__mmini" data-mmini></div>
				<div class="sss-lib__mcta">
					<a class="sss-lib__mbtn sss-lib__mbtn--amazon" href="#" target="_blank" rel="noopener" data-amazon-btn>ku/amazon</a>
					<a class="sss-lib__mbtn sss-lib__mbtn--bookshop" href="#" target="_blank" rel="noopener" data-bookshop-btn>support indie bookstore</a>
				</div>
				<div class="sss-lib__mku" data-mku></div>
			</div>

			<div class="sss-lib__mbelow">
				<div class="sss-lib__mmeta">
					<div class="sss-lib__mtropes" data-mtropes></div>
					<div class="sss-lib__mratings">
						<div class="sss-lib__mtension" data-mtension></div>
						<div class="sss-lib__mdamage" data-mdamage></div>
						<div class="sss-lib__myearning" data-myearning></div>
						<div class="sss-lib__mboyfriend" data-mboyfriend></div>
						<div class="sss-lib__mreread" data-mreread></div>
					</div>
					<div class="sss-lib__mwhy" data-mwhy></div>
				</div>
				<div class="sss-lib__mdisclaimer">
					some links may be affiliate links, so thank you for supporting the recs. &lt;3
				</div>
			</div>
		</div>
	</div>
</div>
