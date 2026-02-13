<?php
/**
 * Section: Contact
 * Template: template-parts/sections/contact.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$section_id = 'kontakt';

/* ACF fields (wg Twoich nazw) */
$bg_image       = get_field( 'concat_img', $post_id );
$title          = get_field( 'concat_title', $post_id );
$desc           = get_field( 'contact_desc', $post_id );

$company_name   = get_field( 'contact_name', $post_id );
$hours          = get_field( 'contact_houres', $post_id );

$office_title   = get_field( 'concat_office', $post_id );
$office_address = get_field( 'concat_adress', $post_id );

$form_shortcode = get_field( 'concat_shortcode_form', $post_id );
$form_title     = get_field( 'hero_form_title', $post_id );
$form_desc      = get_field( 'hero_form_desc', $post_id );

/* Stałe CTA (tak jak chciałeś) */
$email = 'oferty@flixhome.pl';
$phone = '+48 883 990 877';

$bg_url = '';
if ( is_array( $bg_image ) && ! empty( $bg_image['url'] ) ) {
	$bg_url = $bg_image['url'];
} elseif ( is_numeric( $bg_image ) ) {
	$bg_url = wp_get_attachment_image_url( (int) $bg_image, 'full' );
} elseif ( is_string( $bg_image ) ) {
	$bg_url = $bg_image;
}

if ( empty( $title ) ) {
	$title = 'Skontaktuj się z nami';
}

$mailto = 'mailto:' . sanitize_email( $email );
$phone_href = 'tel:' . preg_replace( '/[^\d\+]/', '', $phone );

$has_content = $title || $desc || $company_name || $hours || $office_title || $office_address || $form_shortcode || $bg_url;
if ( ! $has_content ) {
	return;
}
?>

<section class="section section--no-top contact" id="<?php echo esc_attr( $section_id ); ?>">

	<?php if ( $bg_url ) : ?>
		<div class="contact__bg" aria-hidden="true">
			<img src="<?php echo esc_url( $bg_url ); ?>" alt="">
		</div>
	<?php endif; ?>
	<div class="contact__overlay" aria-hidden="true"></div>

	<div class="container contact__container">
		<div class="contact__content">

			<h2 class="contact__title"><?php echo esc_html( $title ); ?></h2>

			<?php if ( ! empty( $desc ) ) : ?>
				<div class="contact__desc"><?php echo wp_kses_post( $desc ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $company_name ) ) : ?>
				<p class="contact__company"><?php echo esc_html( $company_name ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $hours ) ) : ?>
				<div class="contact__hours"><?php echo wp_kses_post( $hours ); ?></div>
			<?php endif; ?>

			<?php if ( ! empty( $office_title ) ) : ?>
				<p class="contact__office-title"><?php echo esc_html( $office_title ); ?></p>
			<?php endif; ?>

			<?php if ( ! empty( $office_address ) ) : ?>
				<div class="contact__address"><?php echo wp_kses_post( $office_address ); ?></div>
			<?php endif; ?>

			<div class="contact__cta-row">
				<a class="contact__btn contact__btn--email" href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $email ); ?></a>
				<a class="contact__btn contact__btn--phone" href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a>
			</div>

		</div>

		<?php if ( ! empty( $form_shortcode ) ) : ?>
			<div class="contact__form">
				<div class="contact__form-box hero__form">
					<?php if ( ! empty( $form_title ) ) : ?>
						<h3 class="hero__form-title"><?php echo esc_html( $form_title ); ?></h3>
					<?php endif; ?>

					<?php if ( ! empty( $form_desc ) ) : ?>
						<div class="hero__form-desc">
							<?php echo wp_kses_post( $form_desc ); ?>
						</div>
					<?php endif; ?>

					<div class="hero__form-cf7">
						<?php echo do_shortcode( wp_kses_post( $form_shortcode ) ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

	</div>
</section>
