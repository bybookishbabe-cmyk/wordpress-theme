<?php
/**
 * Quiz CTA shortcode.
 *
 * @package ByBookishBabeShopifyPort
 */

declare(strict_types=1);

function bbb_quiz_cta_url(string $quiz, string $url): string {
	if ('' !== trim($url)) {
		return $url;
	}

	$quiz = sanitize_title($quiz);
	$routes = array(
		'boyfriend'            => '/fictional-boyfriend-quiz/',
		'fictional-boyfriend'  => '/fictional-boyfriend-quiz/',
		'fictional-boyfriend-quiz' => '/fictional-boyfriend-quiz/',
		'mood'                 => '/reader-mood-quiz/',
		'reader-mood'          => '/reader-mood-quiz/',
		'reader-mood-quiz'     => '/reader-mood-quiz/',
		'trope'                => '/romance-trope-quiz/',
		'romance-trope'        => '/romance-trope-quiz/',
		'romance-trope-quiz'   => '/romance-trope-quiz/',
		'hub'                  => '/reader-quizzes/',
		'reader-quizzes'       => '/reader-quizzes/',
	);

	return home_url($routes[$quiz] ?? '/reader-quizzes/');
}

function bbb_quiz_cta_defaults(string $quiz): array {
	$quiz = sanitize_title($quiz);
	$defaults = array(
		'kicker' => 'reader quiz',
		'title'  => 'reader quizzes',
		'text'   => 'find the reading lane that wants you next.',
		'button' => 'take the quiz',
	);

	if (in_array($quiz, array('boyfriend', 'fictional-boyfriend', 'fictional-boyfriend-quiz'), true)) {
		return array(
			'kicker' => 'fictional boyfriend quiz',
			'title'  => 'who is your fictional boyfriend?',
			'text'   => 'choose your type. meet your man.',
			'button' => 'meet him',
		);
	}

	if (in_array($quiz, array('mood', 'reader-mood', 'reader-mood-quiz'), true)) {
		return array(
			'kicker' => 'reader mood quiz',
			'title'  => 'what should you read next?',
			'text'   => 'pick the mood. get the lane.',
			'button' => 'find my read',
		);
	}

	if (in_array($quiz, array('trope', 'romance-trope', 'romance-trope-quiz'), true)) {
		return array(
			'kicker' => 'romance trope quiz',
			'title'  => 'what romance trope are you?',
			'text'   => 'choose the tension. get diagnosed.',
			'button' => 'find my trope',
		);
	}

	return $defaults;
}

function bbb_quiz_cta_boyfriend_image(int $post_id = 0, string $name = ''): array {
	if ('' !== trim($name) && function_exists('bbb_fictionalman_shortcode_profile_from_name')) {
		$profile = bbb_fictionalman_shortcode_profile_from_name($name);
		if ($profile instanceof WP_Post) {
			$image = (string) get_the_post_thumbnail_url($profile, 'medium_large');
			if ('' !== $image) {
				return array('url' => $image, 'alt' => get_the_title($profile));
			}
		}
	}

	if (!function_exists('bbb_reader_quiz_boyfriend_profiles')) {
		return array('url' => '', 'alt' => '');
	}

	$profiles = array_values(
		array_filter(
			bbb_reader_quiz_boyfriend_profiles(),
			static fn(array $profile): bool => '' !== (string) ($profile['image'] ?? '')
		)
	);
	if (!$profiles) {
		return array('url' => '', 'alt' => '');
	}

	$index = $post_id > 0 ? $post_id % count($profiles) : 0;
	$profile = $profiles[$index];

	return array('url' => (string) $profile['image'], 'alt' => (string) ($profile['name'] ?? 'fictional boyfriend'));
}

function sss_quiz_cta_shortcode($atts): string {
	$raw_atts = (array) $atts;
	$quiz = (string) ($raw_atts['quiz'] ?? 'hub');
	$defaults = bbb_quiz_cta_defaults($quiz);
	$atts = shortcode_atts(
		array(
			'quiz'   => 'hub',
			'url'    => '',
			'name'   => '',
			'image'  => '',
			'kicker' => $defaults['kicker'],
			'title'  => $defaults['title'],
			'text'   => $defaults['text'],
			'button' => $defaults['button'],
		),
		$raw_atts,
		'sss_quiz_cta'
	);

	$url = bbb_quiz_cta_url((string) $atts['quiz'], (string) $atts['url']);
	$image = array('url' => trim((string) $atts['image']), 'alt' => (string) $atts['title']);
	if ('' === $image['url'] && in_array(sanitize_title((string) $atts['quiz']), array('boyfriend', 'fictional-boyfriend', 'fictional-boyfriend-quiz'), true)) {
		$image = bbb_quiz_cta_boyfriend_image((int) get_the_ID(), (string) $atts['name']);
	}

	ob_start();
	?>
<aside class="bbb-quiz-cta" aria-label="<?php echo esc_attr((string) $atts['title']); ?>">
  <?php if ('' !== $image['url']) : ?>
  <div class="bbb-quiz-cta__media" aria-hidden="true">
    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" loading="lazy">
  </div>
  <?php endif; ?>
  <div class="bbb-quiz-cta__copy">
    <p class="bbb-quiz-cta__kicker"><?php echo esc_html((string) $atts['kicker']); ?></p>
    <h3 class="bbb-quiz-cta__title"><?php echo esc_html((string) $atts['title']); ?></h3>
    <p class="bbb-quiz-cta__text"><?php echo esc_html((string) $atts['text']); ?></p>
  </div>
  <a class="bbb-quiz-cta__button" href="<?php echo esc_url($url); ?>"><?php echo esc_html((string) $atts['button']); ?> <span aria-hidden="true">&rarr;</span></a>
</aside>
	<?php
	return ob_get_clean();
}

add_shortcode('sss_quiz_cta', 'sss_quiz_cta_shortcode');
add_shortcode('quizcta', 'sss_quiz_cta_shortcode');
