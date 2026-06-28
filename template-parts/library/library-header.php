<?php
declare(strict_types=1);

$kicker  = (string) get_option('sss_lib_kicker', 'official library');
$title   = (string) get_option('sss_lib_title', 'the romance library');
$subtext = (string) get_option('sss_lib_subtext', 'the official collection of romance books curated and catalogued by the smut and sentiment society.');
$read_next_url = function_exists('bbb_page_url') ? bbb_page_url('what-to-read-next') : home_url('/what-to-read-next/');
$society_layer = function_exists('bbb_society_private_layer_state') ? bbb_society_private_layer_state() : array('label' => 'the private layer', 'class' => 'private', 'url' => 'https://thesmutandsentimentsociety.substack.com/subscribe', 'cta' => 'enter the society');
?>
<div class="sss-lib__headWrap">
	<header class="sss-lib__head">
		<p class="sss-lib__kicker"><?php echo esc_html($kicker); ?></p>
		<h1 class="sss-lib__title"><?php echo esc_html($title); ?></h1>
		<p class="sss-lib__seoLine">made for romance readers by a romance reader.</p>
		<p class="sss-lib__sub"><?php echo esc_html($subtext); ?></p>
		<a class="sss-lib__kuLink" href="<?php echo esc_url($read_next_url); ?>">
			not sure where to start? find your read →
		</a>
	</header>

	<div class="sss-lib__societyInviteCard">
		<div class="sss-lib__societyInviteKicker sss-lib__societyInviteKicker--<?php echo esc_attr((string) $society_layer['class']); ?>"><?php echo esc_html((string) $society_layer['label']); ?></div>
		<div class="sss-lib__societyInviteTitle">join the society for the weekly recommendation</div>
		<div class="sss-lib__societyInviteSub">
			one curated romance. delivered every sunday.
			full breakdown, tension index, emotional damage rating, and private annotations.
		</div>
		<a href="<?php echo esc_url((string) $society_layer['url']); ?>" class="sss-lib__societyInviteBtn">
			<?php echo esc_html((string) $society_layer['cta']); ?>
		</a>
	</div>
</div>
