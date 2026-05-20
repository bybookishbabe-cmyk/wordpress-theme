<?php
/**
 * Template Name: Book Tracking Calendar
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$today      = current_time('timestamp');
$month_num  = (int) date_i18n('n', $today);
$year       = (int) date_i18n('Y', $today);
$month_name = strtolower((string) date_i18n('F', $today));
$days       = (int) date_i18n('t', $today);
$first_day  = (int) date_i18n('w', strtotime($year . '-' . $month_num . '-01 12:00:00'));
$books      = array();

foreach (sss_get_all_books() as $book_post) {
	if (!$book_post instanceof WP_Post) {
		continue;
	}

	$book = sss_book_data($book_post);
	if (empty($book['title'])) {
		continue;
	}

	$books[] = array(
		'handle' => (string) ($book['handle'] ?? $book_post->post_name),
		'title'  => strtolower((string) $book['title']),
		'author' => strtolower((string) ($book['author'] ?? '')),
		'cover'  => (string) ($book['cover'] ?? ''),
	);
}

get_header();
?>

<section class="sss-readcal" id="sss-readcal-wp">
	<div class="sss-readcal__wrap">
		<header class="sss-readcal__head">
			<a class="sss-readcal__back" href="<?php echo esc_url(bbb_page_url('smut-sentiment-society')); ?>">back to the society</a>
			<p class="sss-readcal__kicker">stay up to date on your latest reads</p>
			<p class="sss-readcal__month"><?php echo esc_html($month_name . ' ' . $year); ?></p>
			<h1 class="sss-readcal__title">track your reads</h1>
			<p class="sss-readcal__sub">click a day, choose the book you read, and let the cover live there.</p>
			<div class="sss-readcal__actions">
				<button type="button" class="sss-readcal__actionBtn" data-readcal-share>share your calendar</button>
				<button type="button" class="sss-readcal__actionBtn sss-readcal__actionBtn--ghost" data-readcal-save>save as png</button>
			</div>
		</header>

		<div class="sss-readcal__weekdays">
			<span>sun</span><span>mon</span><span>tue</span><span>wed</span><span>thu</span><span>fri</span><span>sat</span>
		</div>

		<div class="sss-readcal__grid" data-readcal-grid>
			<?php for ($day = 1; $day <= $days; $day++) : ?>
				<?php
				$date = sprintf('%d-%02d-%02d', $year, $month_num, $day);
				$style = 1 === $day ? ' style="grid-column-start:' . esc_attr((string) ($first_day + 1)) . '"' : '';
				?>
				<button
					type="button"
					class="sss-readcal__cell<?php echo 1 === $day ? ' sss-readcal__cell--first' : ''; ?>"
					data-readcal-day="<?php echo esc_attr($date); ?>"
					aria-label="<?php echo esc_attr('track a book for ' . $month_name . ' ' . $day); ?>"<?php echo $style; ?>>
					<span class="sss-readcal__num"><?php echo esc_html((string) $day); ?></span>
					<span class="sss-readcal__slot" data-readcal-slot></span>
				</button>
			<?php endfor; ?>
		</div>
	</div>

	<script type="application/json" id="sss-readcal-data-wp"><?php echo wp_json_encode($books, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?></script>

	<div class="sss-readcal__modal" data-readcal-modal hidden>
		<div class="sss-readcal__scrim" data-readcal-close></div>
		<div class="sss-readcal__panel" role="dialog" aria-modal="true" aria-labelledby="sss-readcal-title-wp">
			<button type="button" class="sss-readcal__close" data-readcal-close aria-label="close">×</button>
			<p class="sss-readcal__modalKicker">track your reads</p>
			<h2 class="sss-readcal__modalTitle" id="sss-readcal-title-wp">what did you read today?</h2>
			<p class="sss-readcal__modalSub" data-readcal-date></p>
			<p class="sss-readcal__modalHint">start typing, or choose from the books below.</p>
			<div class="sss-readcal__clear" data-readcal-clear hidden>
				<p class="sss-readcal__clearLabel">need a reset?</p>
				<p class="sss-readcal__clearCopy">clear the book from this day and leave the date blank again.</p>
				<button type="button" class="sss-readcal__remove" data-readcal-remove>clear this day</button>
			</div>
			<input type="search" class="sss-readcal__search" data-readcal-search placeholder="search your library by title or author" autocomplete="off">
			<div class="sss-readcal__results" data-readcal-results></div>
		</div>
	</div>
</section>

<style>
.sss-readcal{
	background:#0b0b0b;
	color:#f6f1eb;
	min-height:100vh;
	padding:34px 16px 48px;
	text-transform:lowercase;
}
.sss-readcal__wrap{
	max-width:1120px;
	margin:0 auto;
}
.sss-readcal__head{
	max-width:720px;
	margin-bottom:18px;
}
.sss-readcal__back{
	display:inline-flex;
	margin:0 0 22px;
	color:rgba(246,241,235,.68);
	font-size:12px;
	text-decoration:none;
}
.sss-readcal__back:hover{
	color:#ff8ac7;
}
.sss-readcal__kicker{
	margin:0 0 8px;
	font-size:11px;
	letter-spacing:.16em;
	text-transform:uppercase;
	color:rgba(246,241,235,.58);
}
.sss-readcal__month{
	margin:0 0 8px;
	font-family:"Cormorant Garamond", Cormorant, serif;
	font-size:34px;
	line-height:1;
}
.sss-readcal__title{
	margin:0;
	font-family:"Cormorant Garamond", Cormorant, serif;
	font-size:clamp(42px, 7vw, 76px);
	font-weight:500;
	line-height:.92;
	letter-spacing:0;
}
.sss-readcal__sub{
	margin:14px 0 0;
	color:rgba(246,241,235,.74);
	font-size:16px;
	line-height:1.65;
}
.sss-readcal__actions{
	display:flex;
	flex-wrap:wrap;
	gap:10px;
	margin-top:16px;
}
.sss-readcal__actionBtn{
	appearance:none;
	min-height:40px;
	padding:0 16px;
	border-radius:999px;
	border:1px solid rgba(255,138,199,.34);
	background:rgba(255,138,199,.12);
	color:#fff;
	font:inherit;
	font-size:12px;
	text-transform:lowercase;
	cursor:pointer;
}
.sss-readcal__actionBtn--ghost{
	background:transparent;
	border-color:rgba(255,255,255,.18);
}
.sss-readcal__weekdays,
.sss-readcal__grid{
	display:grid;
	grid-template-columns:repeat(7, minmax(0, 1fr));
	gap:10px;
}
.sss-readcal__weekdays{
	margin-bottom:8px;
}
.sss-readcal__weekdays span{
	font-size:10px;
	letter-spacing:.16em;
	text-transform:uppercase;
	color:rgba(246,241,235,.44);
	text-align:center;
}
.sss-readcal__cell{
	appearance:none;
	position:relative;
	min-height:116px;
	padding:10px;
	border-radius:18px;
	border:1px solid rgba(255,255,255,.08);
	background:linear-gradient(180deg, rgba(255,62,165,.08), rgba(255,255,255,.02));
	color:#fff;
	cursor:pointer;
	text-align:left;
	transition:border-color .18s ease, background .18s ease, box-shadow .18s ease, transform .18s ease;
}
.sss-readcal__cell:hover{
	border-color:rgba(255,62,165,.42);
	background:linear-gradient(180deg, rgba(255,62,165,.16), rgba(255,255,255,.04));
	box-shadow:0 0 0 1px rgba(255,62,165,.14), 0 0 24px rgba(255,62,165,.1);
	transform:translateY(-1px);
}
.sss-readcal__num{
	font-size:12px;
	color:rgba(246,241,235,.72);
}
.sss-readcal__slot{
	display:block;
	margin-top:10px;
}
.sss-readcal__cover{
	width:48px;
	height:68px;
	border-radius:10px;
	object-fit:cover;
	display:block;
	box-shadow:0 10px 24px rgba(0,0,0,.28);
}
.sss-readcal__bookTitle{
	display:block;
	margin-top:8px;
	font-size:11px;
	line-height:1.35;
	color:rgba(246,241,235,.8);
}
.sss-readcal__modal[hidden]{
	display:none !important;
}
.sss-readcal__modal{
	position:fixed;
	inset:0;
	z-index:10000;
}
.sss-readcal__scrim{
	position:absolute;
	inset:0;
	background:rgba(0,0,0,.72);
	backdrop-filter:blur(6px);
}
.sss-readcal__panel{
	position:relative;
	z-index:1;
	width:min(92vw, 760px);
	margin:10vh auto 0;
	padding:26px 24px 22px;
	max-height:80vh;
	overflow:auto;
	border-radius:26px;
	border:1px solid rgba(255,255,255,.12);
	background:linear-gradient(180deg, rgba(18,18,20,.98), rgba(10,10,12,.98));
	box-shadow:0 30px 80px rgba(0,0,0,.48);
	color:#f6f1eb;
}
.sss-readcal__close{
	appearance:none;
	position:absolute;
	top:14px;
	right:14px;
	display:flex;
	align-items:center;
	justify-content:center;
	width:34px;
	height:34px;
	padding:0;
	border-radius:999px;
	border:1px solid rgba(255,255,255,.12);
	background:rgba(255,255,255,.04);
	color:#fff;
	font:inherit;
	font-size:20px;
	line-height:1;
	cursor:pointer;
}
.sss-readcal__modalKicker,
.sss-readcal__clearLabel{
	margin:0 0 8px;
	font-size:11px;
	letter-spacing:.16em;
	text-transform:uppercase;
	color:rgba(246,241,235,.58);
}
.sss-readcal__modalTitle{
	margin:0;
	font-family:"Cormorant Garamond", Cormorant, serif;
	font-size:32px;
	line-height:1.02;
}
.sss-readcal__modalSub,
.sss-readcal__modalHint{
	margin:8px 0 0;
	font-size:12px;
	color:rgba(246,241,235,.66);
}
.sss-readcal__modalHint{
	color:rgba(255,138,199,.78);
}
.sss-readcal__search{
	width:100%;
	margin-top:16px;
	min-height:46px;
	padding:0 16px;
	border-radius:16px;
	border:1px solid rgba(255,255,255,.12);
	background:rgba(255,255,255,.04);
	color:#fff;
	font:inherit;
}
.sss-readcal__results{
	display:grid;
	grid-template-columns:repeat(4, minmax(0, 1fr));
	gap:12px;
	margin-top:16px;
	max-height:40vh;
	overflow:auto;
}
.sss-readcal__pick{
	appearance:none;
	border:1px solid rgba(255,255,255,.1);
	background:rgba(255,255,255,.03);
	border-radius:18px;
	padding:10px;
	color:#fff;
	text-align:left;
	cursor:pointer;
}
.sss-readcal__pick img{
	width:100%;
	aspect-ratio:3/4;
	object-fit:cover;
	border-radius:12px;
	display:block;
	background:rgba(255,255,255,.06);
}
.sss-readcal__pickTitle,
.sss-readcal__pickMeta{
	display:block;
	margin-top:8px;
	font-size:12px;
	line-height:1.4;
}
.sss-readcal__pickMeta{
	margin-top:4px;
	font-size:11px;
	color:rgba(246,241,235,.6);
}
.sss-readcal__clear{
	margin-top:18px;
	padding:14px 14px 16px;
	border-radius:18px;
	border:1px solid rgba(255,255,255,.08);
	background:rgba(255,255,255,.03);
}
.sss-readcal__clearCopy{
	margin:8px 0 0;
	font-size:12px;
	line-height:1.55;
	color:rgba(246,241,235,.68);
}
.sss-readcal__remove{
	margin-top:12px;
	appearance:none;
	border:1px solid rgba(255,255,255,.12);
	background:transparent;
	color:#ff8ac7;
	border-radius:999px;
	min-height:40px;
	padding:0 16px;
	font:inherit;
	text-transform:lowercase;
	cursor:pointer;
}
.sss-readcal__empty{
	grid-column:1/-1;
	font-size:13px;
	color:rgba(246,241,235,.62);
}
@media (max-width: 749px){
	.sss-readcal{
		padding:24px 12px 34px;
	}
	.sss-readcal__weekdays,
	.sss-readcal__grid{
		gap:6px;
	}
	.sss-readcal__cell{
		min-height:84px;
		padding:8px;
		border-radius:14px;
	}
	.sss-readcal__cover{
		width:34px;
		height:48px;
		border-radius:8px;
	}
	.sss-readcal__bookTitle{
		font-size:10px;
	}
	.sss-readcal__results{
		grid-template-columns:repeat(2, minmax(0, 1fr));
		max-height:34vh;
	}
	.sss-readcal__panel{
		width:min(94vw, 520px);
		margin:4vh auto 0;
		padding:18px 14px 16px;
		max-height:88vh;
	}
}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
(function(){
	var root = document.getElementById('sss-readcal-wp');
	if (!root) return;

	var dataEl = document.getElementById('sss-readcal-data-wp');
	var modal = root.querySelector('[data-readcal-modal]');
	var search = root.querySelector('[data-readcal-search]');
	var results = root.querySelector('[data-readcal-results]');
	var dateLabel = root.querySelector('[data-readcal-date]');
	var removeBtn = root.querySelector('[data-readcal-remove]');
	var clearWrap = root.querySelector('[data-readcal-clear]');
	var shareBtn = root.querySelector('[data-readcal-share]');
	var saveBtn = root.querySelector('[data-readcal-save]');
	var books = [];
	var activeDate = '';
	var storageKey = 'sssReadTrackerCalendar';

	try {
		books = JSON.parse(dataEl.textContent || '[]') || [];
	} catch (err) {
		books = [];
	}

	function getState(){
		try {
			return JSON.parse(localStorage.getItem(storageKey)) || {};
		} catch (err) {
			return {};
		}
	}

	function setState(next){
		localStorage.setItem(storageKey, JSON.stringify(next));
	}

	function renderCalendar(){
		var state = getState();
		root.querySelectorAll('[data-readcal-day]').forEach(function(cell){
			var slot = cell.querySelector('[data-readcal-slot]');
			var entry = state[cell.getAttribute('data-readcal-day')];
			slot.innerHTML = '';
			if (!entry) return;

			if (entry.cover) {
				var img = document.createElement('img');
				img.className = 'sss-readcal__cover';
				img.src = entry.cover;
				img.alt = entry.title || '';
				img.crossOrigin = 'anonymous';
				slot.appendChild(img);
			}

			var label = document.createElement('span');
			label.className = 'sss-readcal__bookTitle';
			label.textContent = entry.title || '';
			slot.appendChild(label);
		});
	}

	function renderResults(query){
		var state = getState();
		var current = activeDate ? state[activeDate] : null;
		var needle = String(query || '').toLowerCase();
		var matches = books.filter(function(book){
			if (!book || !book.title) return false;
			if (!needle) return true;
			return ((book.title || '') + ' ' + (book.author || '')).toLowerCase().indexOf(needle) > -1;
		});

		results.innerHTML = '';
		if (!books.length) {
			results.innerHTML = '<div class="sss-readcal__empty">your library books did not load here yet.</div>';
		} else if (!matches.length) {
			results.innerHTML = '<div class="sss-readcal__empty">no books matched that search. try a title or author instead.</div>';
		} else {
			matches.slice(0, 24).forEach(function(book){
				var button = document.createElement('button');
				button.type = 'button';
				button.className = 'sss-readcal__pick';

				if (book.cover) {
					var img = document.createElement('img');
					img.src = book.cover;
					img.alt = '';
					button.appendChild(img);
				}

				var title = document.createElement('span');
				title.className = 'sss-readcal__pickTitle';
				title.textContent = book.title || '';
				button.appendChild(title);

				var author = document.createElement('span');
				author.className = 'sss-readcal__pickMeta';
				author.textContent = book.author || '';
				button.appendChild(author);

				button.addEventListener('click', function(){
					var next = getState();
					next[activeDate] = {
						handle: book.handle || '',
						title: book.title || '',
						author: book.author || '',
						cover: book.cover || ''
					};
					setState(next);
					renderCalendar();
					closeModal();
				});
				results.appendChild(button);
			});
		}

		if (clearWrap) {
			clearWrap.hidden = !current;
		}
	}

	function openModal(date){
		activeDate = date;
		if (dateLabel) {
			dateLabel.textContent = new Date(date + 'T12:00:00').toLocaleDateString(undefined, {
				month: 'long',
				day: 'numeric',
				year: 'numeric'
			}).toLowerCase();
		}
		if (search) search.value = '';
		renderResults('');
		modal.hidden = false;
		document.body.style.overflow = 'hidden';
		if (search) search.focus();
	}

	function closeModal(){
		modal.hidden = true;
		document.body.style.overflow = '';
	}

	function setButtonFeedback(button, text){
		if (!button) return;
		var original = button.dataset.originalLabel || button.textContent;
		button.dataset.originalLabel = original;
		button.textContent = text;
		window.setTimeout(function(){
			button.textContent = original;
		}, 1800);
	}

	function exportCalendar(){
		if (typeof html2canvas === 'undefined') {
			alert('calendar export is still loading. try again in a second.');
			return Promise.reject(new Error('html2canvas missing'));
		}

		return html2canvas(root.querySelector('.sss-readcal__wrap'), {
			backgroundColor: '#0b0b0b',
			scale: 3,
			useCORS: true
		});
	}

	root.querySelectorAll('[data-readcal-day]').forEach(function(cell){
		cell.addEventListener('click', function(){
			openModal(cell.getAttribute('data-readcal-day'));
		});
	});

	root.querySelectorAll('[data-readcal-close]').forEach(function(closeEl){
		closeEl.addEventListener('click', closeModal);
	});

	if (search) {
		search.addEventListener('input', function(){
			renderResults(search.value || '');
		});
	}

	if (removeBtn) {
		removeBtn.addEventListener('click', function(){
			var next = getState();
			delete next[activeDate];
			setState(next);
			renderCalendar();
			closeModal();
		});
	}

	if (saveBtn) {
		saveBtn.addEventListener('click', function(){
			exportCalendar().then(function(canvas){
				var link = document.createElement('a');
				link.href = canvas.toDataURL('image/png');
				link.download = '<?php echo esc_js($month_name); ?>-read-tracker.png';
				link.click();
				setButtonFeedback(saveBtn, 'saved');
			}).catch(function(){});
		});
	}

	if (shareBtn) {
		shareBtn.addEventListener('click', function(){
			exportCalendar().then(function(canvas){
				canvas.toBlob(function(blob){
					if (!blob) return;
					var file = new File([blob], '<?php echo esc_js($month_name); ?>-read-tracker.png', { type: 'image/png' });
					if (navigator.share && navigator.canShare && navigator.canShare({ files: [file] })) {
						navigator.share({
							files: [file],
							title: '<?php echo esc_js($month_name); ?> read tracker',
							text: 'my society reading calendar'
						}).then(function(){
							setButtonFeedback(shareBtn, 'shared');
						}).catch(function(){});
					} else {
						var link = document.createElement('a');
						link.href = URL.createObjectURL(blob);
						link.download = file.name;
						link.click();
						window.setTimeout(function(){
							URL.revokeObjectURL(link.href);
						}, 1500);
						setButtonFeedback(shareBtn, 'png ready');
					}
				}, 'image/png');
			}).catch(function(){});
		});
	}

	document.addEventListener('keydown', function(event){
		if (event.key === 'Escape' && modal && !modal.hidden) {
			closeModal();
		}
	});

	renderCalendar();
})();
</script>

<?php
get_footer();
