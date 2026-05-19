<?php
declare(strict_types=1);

$shortcode = (string) bbb_get_field('contact_form_shortcode', get_the_ID(), '[contact-form-7 id="contact-form" title="Contact form"]');
?>
<section class="contact page-width page-width--narrow">
	<div class="contact__form"><?php echo do_shortcode($shortcode); ?></div>
</section>
