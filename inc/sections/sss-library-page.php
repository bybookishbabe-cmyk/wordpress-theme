<?php
declare(strict_types=1);

$books = bbb_get_all_books_json(false);
?>
<section class="sss-lib sss-lib--society" data-sss-lib="society">
	<div class="sss-lib__wrap">
		<header class="sss-lib__head">
			<p class="sss-lib__kicker"><?php echo esc_html((string) bbb_get_field('kicker', get_the_ID(), 'classified for members')); ?></p>
			<h1 class="sss-lib__title"><?php echo esc_html((string) bbb_get_field('title', get_the_ID(), 'the society library')); ?></h1>
			<p class="sss-lib__sub"><?php echo esc_html((string) bbb_get_field('subtext', get_the_ID(), "the books that ruined us - and we'd let them do it again.")); ?></p>
		</header>
		<div class="sss-lib__finder" id="sssReadFinder">
			<h3 class="sss-lib__finderTitle">find your next read</h3>
			<div class="sss-lib__finderForm">
				<select id="sssFinderShelf" class="sss-lib__finderSelect"><option value="">any shelf</option></select>
				<select id="sssFinderTrope" class="sss-lib__finderSelect"><option value="">any trope</option></select>
				<select id="sssFinderSpice" class="sss-lib__finderSelect">
					<option value="">any spice</option><option value="1">🌶</option><option value="2">🌶🌶</option><option value="3">🌶🌶🌶</option><option value="4">🌶🌶🌶🌶</option><option value="5">🌶🌶🌶🌶🌶</option>
				</select>
				<button id="sssFinderSubmit" class="sss-lib__finderBtn" type="button">find a book</button>
			</div>
			<div class="sss-lib__finderResult" id="sssFinderResult" hidden>
				<img class="sss-lib__finderCover" id="sssFinderCover" src="" alt="">
				<div class="sss-lib__finderInfo">
					<p class="sss-lib__finderBookTitle" id="sssFinderBookTitle"></p>
					<p class="sss-lib__finderAuthor" id="sssFinderAuthor"></p>
					<p class="sss-lib__finderWhy" id="sssFinderWhy"></p>
				</div>
			</div>
		</div>
		<div class="sss-lib__votes" id="sssBfVotes">
			<h3>this month's fictional boyfriend vote</h3>
			<input type="text" id="sssBfVoteInput" placeholder="nominate a boyfriend">
			<button id="sssBfVoteSubmit" type="button">vote</button>
			<div class="sss-lib__voteResults" id="sssBfResults" hidden>
				<h4 class="sss-lib__voteWinner" id="sssBfWinner"></h4>
				<ul class="sss-lib__voteList" id="sssBfList"></ul>
			</div>
		</div>
		<?php bbb_render_component('full-archive'); ?>
	</div>
	<script type="application/json" id="sssFinderData"><?php echo wp_json_encode($books); ?></script>
	<?php bbb_render_component('library-modal'); ?>
</section>
