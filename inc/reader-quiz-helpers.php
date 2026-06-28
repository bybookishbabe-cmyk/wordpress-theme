<?php
/**
 * Data helpers for reader quizzes.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_reader_quiz_books(): array {
	if (!function_exists('bbb_books_like_all_visible_books') || !function_exists('bbb_books_like_book_data')) {
		return array();
	}

	$books = array();
	foreach (bbb_books_like_all_visible_books() as $book_post) {
		if (!$book_post instanceof WP_Post) {
			continue;
		}
		if (function_exists('bbb_book_is_private') && bbb_book_is_private($book_post->ID)) {
			continue;
		}

		$data          = bbb_books_like_book_data((int) $book_post->ID);
		$series_number = (int) ($data['series_number'] ?? 0);
		if ($series_number > 1 && empty($data['standalone'])) {
			continue;
		}

		$tropes = array();
		$trope_display = array();
		$trope_urls = array();
		foreach ((array) ($data['tropes'] ?? array()) as $trope) {
			$name = (string) ($trope['name'] ?? '');
			if ($name !== '') {
				$tropes[] = $name;
				$trope_display[] = function_exists('bbb_trope_label')
					? bbb_trope_label($name, $trope['emoji'] ?? $trope['sss_trope_emoji'] ?? '')
					: trim(((string) ($trope['emoji'] ?? $trope['sss_trope_emoji'] ?? '🖤')) . ' ' . $name);

				$handle = sanitize_title((string) ($trope['handle'] ?? $trope['slug'] ?? $name));
				if ($handle !== '') {
					$trope_urls[] = home_url('/' . $handle . '-books/');
				}
			}
		}

		$books[] = array(
			'handle'        => (string) ($data['handle'] ?? ''),
			'title'         => (string) ($data['title'] ?? ''),
			'author'        => (string) ($data['author'] ?? ''),
			'cover'         => (string) ($data['cover'] ?? ''),
			'amazon'        => (string) ($data['amazon'] ?? ''),
			'bookshop'      => (string) ($data['bookshop'] ?? ''),
			'newsletter'    => (string) ($data['newsletter'] ?? ''),
			'why'           => wp_strip_all_tags((string) ($data['why'] ?? '')),
			'mini'          => wp_strip_all_tags((string) ($data['mini'] ?? '')),
			'shelf'         => (string) ($data['shelf']['name'] ?? ''),
			'shelfSlug'     => (string) ($data['shelf']['slug'] ?? ''),
			'tropes'        => $tropes,
			'tropesDisplay' => $trope_display,
			'tropeUrls'     => $trope_urls,
			'spice'         => (int) ($data['spice'] ?? 0),
			'darkness'      => (int) ($data['darkness'] ?? 0),
			'tension'       => (int) ($data['tension'] ?? 0),
			'damage'        => (int) ($data['damage'] ?? 0),
			'yearning'      => (string) ($data['yearning'] ?? ''),
			'boyfriend'     => (string) ($data['boyfriend'] ?? ''),
			'boyfriendName' => (string) ($data['boyfriend_name'] ?? ''),
			'reread'        => (string) ($data['reread'] ?? ''),
			'ku'            => !empty($data['ku']) ? 'true' : 'false',
			'series'        => (string) ($data['series_handle'] ?? ''),
			'seriesName'    => (string) ($data['series_name'] ?? ''),
			'seriesNumber'  => (string) ($data['series_number'] ?? ''),
			'mostLike'      => array_values(array_filter(array_map('strval', (array) ($data['most_like_handles'] ?? array())))),
			'standalone'    => !empty($data['standalone']) ? 'true' : 'false',
			'darknessRaw'   => (string) ($data['darkness'] ?? ''),
			'url'           => home_url('/library/?book=' . rawurlencode((string) ($data['handle'] ?? ''))),
		);
	}

	return $books;
}

function bbb_reader_quiz_boyfriend_profiles(): array {
	if (!post_type_exists('bbb_boyfriend')) {
		return array();
	}

	$profiles = get_posts(
		array(
			'post_type'              => 'bbb_boyfriend',
			'post_status'            => 'publish',
			'posts_per_page'         => 60,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => true,
			'update_post_term_cache' => true,
		)
	);
	$out = array();

	foreach ($profiles as $profile) {
		if (!$profile instanceof WP_Post) {
			continue;
		}
		if (function_exists('bbb_content_is_hidden_from_public_browsing') && bbb_content_is_hidden_from_public_browsing((int) $profile->ID)) {
			continue;
		}
		if (function_exists('bbb_fictional_boyfriend_profile_ready') && !bbb_fictional_boyfriend_profile_ready((int) $profile->ID)) {
			continue;
		}

		$post_id = (int) $profile->ID;
		$book_id = function_exists('bbb_fictional_boyfriend_primary_book_id') ? bbb_fictional_boyfriend_primary_book_id($post_id) : 0;
		$book_cover = '';
		if ($book_id > 0) {
			$book_cover = (string) get_the_post_thumbnail_url($book_id, 'medium');
			if ('' === $book_cover && function_exists('bbb_get_book_cover_url')) {
				$book_cover = (string) bbb_get_book_cover_url($book_id);
			}
		}

		$traits = function_exists('bbb_fictional_boyfriend_traits') ? bbb_fictional_boyfriend_traits($post_id) : array();
		$tropes = function_exists('bbb_fictional_boyfriend_tropes') ? bbb_fictional_boyfriend_tropes($post_id) : array();
		$trait_labels = function_exists('bbb_fictional_boyfriend_trait_label')
			? array_map('bbb_fictional_boyfriend_trait_label', $traits)
			: array_map(static fn(string $trait): string => ucwords(str_replace('-', ' ', $trait)), $traits);
		$scores = function_exists('bbb_fictional_boyfriend_trait_scores') ? bbb_fictional_boyfriend_trait_scores($post_id) : array();
		$spice = function_exists('bbb_fictional_boyfriend_spice') ? bbb_fictional_boyfriend_spice($post_id) : 0;
		$hook = function_exists('bbb_fictional_boyfriend_seo_hook')
			? bbb_fictional_boyfriend_seo_hook($post_id)
			: trim(wp_strip_all_tags((string) get_the_excerpt($profile)));

		$out[] = array(
			'id'             => $post_id,
			'name'           => get_the_title($profile),
			'url'            => get_permalink($profile),
			'image'          => (string) get_the_post_thumbnail_url($post_id, 'large'),
			'imageFull'      => (string) get_the_post_thumbnail_url($post_id, 'full'),
			'bookId'         => $book_id,
			'bookTitle'      => $book_id > 0 ? get_the_title($book_id) : (string) get_post_meta($post_id, '_bbb_fb_source', true),
			'bookUrl'        => $book_id > 0 ? get_permalink($book_id) : '',
			'bookCover'      => $book_cover,
			'author'         => (string) get_post_meta($post_id, '_bbb_fb_author', true),
			'shelf'          => (string) get_post_meta($post_id, '_bbb_fb_shelf', true),
			'hook'           => $hook,
			'descriptor'     => function_exists('bbb_fictional_boyfriend_descriptor') ? bbb_fictional_boyfriend_descriptor($post_id) : '',
			'tropes'         => array_values($tropes),
			'traits'         => array_values($traits),
			'traitLabels'    => array_values($trait_labels),
			'scores'         => $scores,
			'spice'          => $spice,
			'peppers'        => function_exists('bbb_fictional_boyfriend_peppers') ? bbb_fictional_boyfriend_peppers($spice) : (string) $spice,
			'loveLanguage'   => (string) get_post_meta($post_id, '_bbb_fb_love_language', true),
			'wouldTextBack'  => (string) get_post_meta($post_id, '_bbb_fb_would_text_back', true),
			'readNextNote'   => (string) get_post_meta($post_id, '_bbb_fb_read_next_note', true),
			'pinterestLinks' => function_exists('bbb_fictional_boyfriend_pinterest_links') ? bbb_fictional_boyfriend_pinterest_links($post_id) : array(),
		);
	}

	return $out;
}

function bbb_reader_quiz_enqueue_assets(): void {
	$css_path = get_theme_file_path('assets/css/reader-quizzes.css');
	$js_path  = get_theme_file_path('assets/js/reader-quiz.js');
	$version  = wp_get_theme()->get('Version');
	$identity = function_exists('bbb_reader_current_identity') ? bbb_reader_current_identity() : null;
	$email    = is_array($identity) ? (string) ($identity['email'] ?? '') : '';
	$user_id  = is_array($identity) ? (int) ($identity['userId'] ?? 0) : 0;
	$account_key = $user_id > 0
		? 'user-' . $user_id
		: ('' !== $email ? 'email-' . md5(strtolower($email)) : '');

	wp_enqueue_style('bbb-reader-quizzes', get_theme_file_uri('assets/css/reader-quizzes.css'), array('bbb-sss-library'), file_exists($css_path) ? (string) filemtime($css_path) : $version);
	wp_enqueue_script('bbb-reader-quiz', get_theme_file_uri('assets/js/reader-quiz.js'), array('bbb-sss-library'), file_exists($js_path) ? (string) filemtime($js_path) : $version, true);
	wp_localize_script(
		'bbb-reader-quiz',
		'BBBReaderQuizPins',
		array(
			'ajaxUrl'   => admin_url('admin-ajax.php'),
			'nonce'     => wp_create_nonce('bbb_reader_quiz_pin_card'),
			'restUrl'   => esc_url_raw(rest_url('bbb/v1/reader-quiz-pin')),
			'restNonce' => wp_create_nonce('wp_rest'),
		)
	);
	wp_localize_script(
		'bbb-reader-quiz',
		'BBBReaderAccountApi',
		array(
			'endpoint'        => set_url_scheme(rest_url('bbb/v1/reader-account'), is_ssl() ? 'https' : 'http'),
			'emailEndpoint'   => set_url_scheme(rest_url('bbb/v1/reader-account/email-session'), is_ssl() ? 'https' : 'http'),
			'profileEndpoint' => set_url_scheme(rest_url('bbb/v1/reader-account/made-for-you'), is_ssl() ? 'https' : 'http'),
			'profileVersion'  => function_exists('bbb_reader_mfy_profile_version') ? bbb_reader_mfy_profile_version() : 'mfy-2026-06-11-reader-types',
			'accountKey'      => $account_key,
			'nonce'           => wp_create_nonce('wp_rest'),
		)
	);
}

function bbb_reader_quiz_save_pin_card_file(array $file) {
	$size = isset($file['size']) ? (int) $file['size'] : 0;
	$tmp  = isset($file['tmp_name']) ? (string) $file['tmp_name'] : '';

	if ($size <= 0 || $size > 3 * 1024 * 1024 || '' === $tmp || !is_uploaded_file($tmp)) {
		return new WP_Error('bbb_pin_card_size', 'card image was too large', array('status' => 400));
	}

	$check = wp_check_filetype_and_ext($tmp, isset($file['name']) ? (string) $file['name'] : 'my-fictional-bf.png');
	if ('image/png' !== ($check['type'] ?? '')) {
		return new WP_Error('bbb_pin_card_type', 'card image must be a png', array('status' => 400));
	}

	$upload_dir = wp_upload_dir();
	if (!empty($upload_dir['error'])) {
		return new WP_Error('bbb_pin_card_uploads', 'uploads are unavailable', array('status' => 500));
	}

	$subdir = trailingslashit((string) $upload_dir['basedir']) . 'fictional-boyfriend-pins';
	$suburl = trailingslashit((string) $upload_dir['baseurl']) . 'fictional-boyfriend-pins';
	if (!wp_mkdir_p($subdir)) {
		return new WP_Error('bbb_pin_card_folder', 'could not create pin folder', array('status' => 500));
	}

	$bytes = file_get_contents($tmp);
	if (false === $bytes) {
		return new WP_Error('bbb_pin_card_read', 'could not read card image', array('status' => 500));
	}

	$filename = 'my-fictional-bf-' . substr(wp_hash($bytes . microtime(true)), 0, 12) . '.png';
	$path     = trailingslashit($subdir) . $filename;

	if (false === file_put_contents($path, $bytes)) {
		return new WP_Error('bbb_pin_card_write', 'could not save card image', array('status' => 500));
	}

	return array('url' => trailingslashit($suburl) . $filename);
}

function bbb_reader_quiz_pin_card_upload(): void {
	if (!isset($_POST['nonce']) || !wp_verify_nonce((string) wp_unslash($_POST['nonce']), 'bbb_reader_quiz_pin_card')) {
		wp_send_json_error(array('message' => 'invalid pin request'), 403);
	}

	if (empty($_FILES['card']) || !is_array($_FILES['card'])) {
		wp_send_json_error(array('message' => 'missing card image'), 400);
	}

	$result = bbb_reader_quiz_save_pin_card_file($_FILES['card']);
	if (is_wp_error($result)) {
		$data = $result->get_error_data();
		wp_send_json_error(array('message' => $result->get_error_message()), (int) ($data['status'] ?? 500));
	}

	wp_send_json_success(
		array(
			'url' => $result['url'],
		)
	);
}
add_action('wp_ajax_bbb_reader_quiz_pin_card', 'bbb_reader_quiz_pin_card_upload');
add_action('wp_ajax_nopriv_bbb_reader_quiz_pin_card', 'bbb_reader_quiz_pin_card_upload');

function bbb_reader_quiz_register_pin_card_route(): void {
	register_rest_route(
		'bbb/v1',
		'/reader-quiz-pin',
		array(
			'methods'             => 'POST',
			'callback'            => 'bbb_reader_quiz_pin_card_rest_upload',
			'permission_callback' => '__return_true',
		)
	);
}
add_action('rest_api_init', 'bbb_reader_quiz_register_pin_card_route');

function bbb_reader_quiz_pin_card_rest_upload(WP_REST_Request $request) {
	$files = $request->get_file_params();
	if (empty($files['card']) || !is_array($files['card'])) {
		return new WP_Error('bbb_pin_card_missing', 'missing card image', array('status' => 400));
	}

	$result = bbb_reader_quiz_save_pin_card_file($files['card']);
	if (is_wp_error($result)) {
		return $result;
	}

	return rest_ensure_response($result);
}
