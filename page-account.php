<?php
/**
 * Template Name: Reader Account
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

$my_bookshelf_css_path = get_theme_file_path('assets/css/my-bookshelf.css');
wp_enqueue_style('bbb-my-bookshelf', get_theme_file_uri('assets/css/my-bookshelf.css'), array('bbb-sss-library'), file_exists($my_bookshelf_css_path) ? (string) filemtime($my_bookshelf_css_path) : wp_get_theme()->get('Version'));

$is_logged_in = is_user_logged_in();
$user         = $is_logged_in ? wp_get_current_user() : null;
$is_society   = ($user instanceof WP_User && function_exists('bbb_reader_access_tier') && 'society' === bbb_reader_access_tier((int) $user->ID));
$account_data = ($is_logged_in && $user instanceof WP_User && function_exists('bbb_reader_account_response'))
	? bbb_reader_account_response($user)
	: array();
$books        = isset($account_data['books']) && is_array($account_data['books']) ? $account_data['books'] : array();
$tier         = $is_society ? 'society' : (string) ($account_data['accessTier'] ?? 'free');
$synced       = !empty($account_data['supabaseReady']);
$sync_error   = isset($account_data['supabaseError']) && is_array($account_data['supabaseError']) ? $account_data['supabaseError'] : array();
$sync_status  = (int) ($sync_error['status'] ?? 0);
$sync_title   = $synced ? 'account sync is active.' : 'account sync is not active.';
$sync_copy    = $synced ? 'Your tier and bookshelf can now be read from Supabase.' : 'Add SUPABASE_SERVICE_ROLE_KEY on WordPress to sync this account server-side.';
if (!$synced && 401 === $sync_status) {
	$sync_title = 'Supabase rejected the server key.';
	$sync_copy  = 'The key is present in wp-config.php, but Supabase returned 401 Invalid API key. Replace it with the current service_role or secret key for this project.';
}

get_header();
?>

<main id="MainContent" class="content-for-layout focus-none" role="main" tabindex="-1">
	<section class="bbb-account-shelf">
		<div class="bbb-account-shelf__wrap">
			<div class="bbb-account-shelf__hero">
				<p class="bbb-account-shelf__kicker">reader account</p>
				<div class="bbb-account-shelf__memberBadge<?php echo 'society' === $tier ? ' bbb-account-shelf__memberBadge--secret' : ''; ?>">
					<span aria-hidden="true"><?php echo esc_html('society' === $tier ? '♥' : '*'); ?></span>
					<span><?php echo esc_html('society' === $tier ? 'paid society member' : ($is_logged_in ? 'free reader account' : 'visitor')); ?></span>
				</div>
				<h1 class="bbb-account-shelf__title">account</h1>
				<p class="bbb-account-shelf__sub">
					<?php echo esc_html($is_logged_in ? 'Your WordPress login is connected to your reader tier and bookshelf.' : 'Log in or create an account to keep your bookshelf synced across devices.'); ?>
				</p>

				<div class="bbb-account-shelf__actions">
					<?php if ($is_logged_in) : ?>
						<a class="bbb-account-shelf__button" href="<?php echo esc_url(home_url('/my-bookshelf/')); ?>">open my bookshelf</a>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(get_edit_user_link((int) $user->ID)); ?>">edit profile</a>
						<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_logout_url(home_url('/'))); ?>">log out</a>
					<?php else : ?>
						<a class="bbb-account-shelf__button" href="<?php echo esc_url(wp_login_url(home_url('/account/'))); ?>">log in</a>
						<?php if (get_option('users_can_register')) : ?>
							<a class="bbb-account-shelf__button bbb-account-shelf__button--ghost" href="<?php echo esc_url(wp_registration_url()); ?>">create account</a>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ($is_logged_in && $user instanceof WP_User) : ?>
				<div class="bbb-account-shelf__status">
					<div class="bbb-account-shelf__statusMain">
						<span class="bbb-account-shelf__statusIcon" aria-hidden="true">*</span>
						<div>
							<strong><?php echo esc_html($sync_title); ?></strong>
							<span><?php echo esc_html($sync_copy); ?></span>
						</div>
					</div>
				</div>

				<div class="bbb-account-shelf__perk">
					<p class="bbb-account-shelf__perkKicker"><?php echo esc_html('society' === $tier ? 'paid society shelf' : 'free shelf'); ?></p>
					<h2><?php echo esc_html((string) $user->display_name ?: 'reader profile'); ?></h2>
					<p>
						<?php echo esc_html((string) $user->user_email); ?><br>
						<?php echo esc_html(count($books) . (1 === count($books) ? ' saved book connected' : ' saved books connected')); ?>
					</p>
					<a href="<?php echo esc_url(home_url('society' === $tier ? '/sss-library-page/' : '/smut-sentiment-society/')); ?>">
						<?php echo esc_html('society' === $tier ? 'open the society library ->' : 'see society access ->'); ?>
					</a>
				</div>
			<?php else : ?>
				<div class="bbb-account-shelf__empty">
					<div class="bbb-account-shelf__emptyIcon" aria-hidden="true">*</div>
					<h2>your account is waiting.</h2>
					<p>Create a WordPress account with the same email you use for Society access, then your tier and shelf can follow you.</p>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
