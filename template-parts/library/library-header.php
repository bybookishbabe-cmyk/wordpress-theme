<?php
declare(strict_types=1);

$kicker  = (string) get_option('sss_lib_kicker', 'official library');
$title   = (string) get_option('sss_lib_title', 'the romance library');
$subtext = (string) get_option('sss_lib_subtext', 'the official collection of romance books curated and catalogued by the smut and sentiment society.');
?>
<div class="sss-lib__headWrap">
	<header class="sss-lib__head">
		<p class="sss-lib__kicker"><?php echo esc_html($kicker); ?></p>
		<h1 class="sss-lib__title"><?php echo esc_html($title); ?></h1>
		<p class="sss-lib__seoLine">made for romance readers by a romance reader.</p>
		<p class="sss-lib__sub"><?php echo esc_html($subtext); ?></p>
		<a class="sss-lib__kuLink" href="https://amzn.to/4uZ8Y3a" target="_blank" rel="noopener sponsored">
			on a kindle kick? try kindle unlimited →
		</a>
	</header>

	<div class="sss-lib__societyInviteCard">
		<div class="sss-lib__societyInviteKicker">the private layer</div>
		<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
		<div class="sss-lib__societyInviteSub">
			one curated romance. delivered every sunday.
			full breakdown, tension index, emotional damage rating, and private annotations.
		</div>
		<a href="https://thesmutandsentimentsociety.substack.com/subscribe" class="sss-lib__societyInviteBtn">
			enter the society
		</a>
	</div>
</div>
