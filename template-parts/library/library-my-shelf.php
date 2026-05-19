<?php declare(strict_types=1); ?>
<div class="sss-lib__myshelf" id="sssMyShelfSection">
	<div class="sss-lib__archiveHead">
		<div class="sss-lib__archiveKicker">your shelf</div>
		<h2 class="sss-lib__archiveTitle">build your own bookshelf</h2>
		<div class="sss-lib__archiveSub">save the ones you want to come back to, compare, and keep close.</div>
		<a class="sss-lib__myshelfPageLink" href="<?php echo esc_url(home_url('/my-bookshelf/')); ?>" aria-label="open your synced bookshelf">
			<span aria-hidden="true">📚</span>
			<span>open synced bookshelf</span>
		</a>
	</div>
	<div class="sss-lib__myshelfActions">
		<div class="sss-lib__myshelfPrompt">
			want your shelf, plus what to read next each week?
			<button type="button" class="sss-lib__myshelfPromptLink" data-bbb-shelf-open onclick="if(window.BBBShelfSignup&&window.BBBShelfSignup.open){window.BBBShelfSignup.open();}return false;">
				enter email.
			</button>
		</div>
		<button type="button" id="sssExportNotes" class="sss-lib__exportBtn">copy list</button>
		<button type="button" id="sssEmailShelf" class="sss-lib__exportBtn sss-lib__exportBtn--secondary">email to self</button>
	</div>
	<div class="sss-lib__grid" id="sssMyShelfGrid"></div>
</div>
