<?php
declare(strict_types=1);
?>
<section class="sss-memberdash">
	<div class="sss-memberdash__wrap">
		<div class="sss-memberdash__intro">
			<div class="sss-memberdash__sparkles" aria-hidden="true"><?php echo str_repeat('<span>✦</span>', 12); ?></div>
			<p class="sss-memberdash__kicker">classified for members</p>
			<h1 class="sss-memberdash__title">welcome back to the society.</h1>
			<p class="sss-memberdash__sub">your recommendations, your books, your monthly drop.</p>
		</div>
		<div class="sss-memberdash__grid">
			<article class="sss-memberdash__card">
				<p class="sss-memberdash__cardKicker">start here</p>
				<h2 class="sss-memberdash__cardTitle">step 1. favorite books first</h2>
				<div class="sss-memberdash__stepLine">
					<p>favorite the books you love in the library.</p>
					<a href="<?php echo esc_url(bbb_resolve_page_url('sss-library-page')); ?>" class="sss-memberdash__btn sss-memberdash__btn--ghost">favorite books in the library</a>
				</div>
				<div class="sss-memberdash__stepLine">
					<p>step 2. open member dashboard...</p>
					<a href="<?php echo esc_url(bbb_resolve_page_url('member-dashboard')); ?>" class="sss-memberdash__btn">open member dashboard</a>
				</div>
			</article>
			<article class="sss-memberdash__card">
				<p class="sss-memberdash__cardKicker">this month inside the society</p>
				<h2 class="sss-memberdash__cardTitle">the current obsession, all in one place</h2>
				<div class="sss-memberdash__stackLinks">
					<button type="button" data-memberdash-target="moodboard">current drop atmosphere</button>
					<button type="button" data-memberdash-target="ritual">member ritual for the month</button>
					<button type="button" data-memberdash-target="reset">monthly reset vibes</button>
				</div>
			</article>
			<article class="sss-memberdash__card">
				<p class="sss-memberdash__cardKicker">member archive</p>
				<h2 class="sss-memberdash__cardTitle">for kindle, pinterest, and bookish printable lovers</h2>
				<div class="sss-memberdash__stackLinks">
					<a href="<?php echo esc_url(bbb_resolve_page_url('sss-printable-kindle-inserts')); ?>">open inserts</a>
					<a href="<?php echo esc_url(bbb_resolve_page_url('sss-canva-templates')); ?>">open templates</a>
					<a href="<?php echo esc_url(bbb_resolve_page_url('sss-quote-wall')); ?>">visit the quote library</a>
				</div>
			</article>
		</div>
	</div>
</section>
