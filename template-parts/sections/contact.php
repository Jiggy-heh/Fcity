<?php
/**
 * Section: Contact
 * Template: template-parts/sections/contact.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$section_id = trim( (string) get_field( 'section_id', $post_id ) );
$label      = get_field( 'label', $post_id );
$title      = get_field( 'title', $post_id );
$desc       = get_field( 'desc', $post_id );

$company_name   = get_field( 'company_name', $post_id );
$hours          = get_field( 'hours', $post_id );
$office_title   = get_field( 'office_title', $post_id );
$office_address = get_field( 'office_address', $post_id );
$email          = trim( (string) get_field( 'email', $post_id ) );
$phone          = trim( (string) get_field( 'phone', $post_id ) );

$bg_image = get_field( 'bg_image', $post_id );

$form_title     = get_field( 'form_title', $post_id );
$form_desc      = get_field( 'form_desc', $post_id );
$form_shortcode = get_field( 'form_shortcode', $post_id );

if ( '' === $section_id ) {
	$section_id = 'kontakt';
}

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

$mailto = '';
if ( '' !== $email ) {
	$mailto = 'mailto:' . sanitize_email( $email );
}

$phone_href = '';
if ( '' !== $phone ) {
	$phone_href = 'tel:' . preg_replace( '/[^\d\+]/', '', $phone );
}

$has_content = $title || $desc || $company_name || $hours || $office_title || $office_address || $mailto || $phone_href || $form_title || $form_desc || $form_shortcode || $bg_url;
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
			<?php if ( ! empty( $label ) ) : ?>
				<div class="section-label contact__label">
					<p class="section-label__text"><?php echo esc_html( $label ); ?></p>
					<span class="section-label__line" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $title ) ) : ?>
				<h2 class="contact__title"><?php echo esc_html( $title ); ?></h2>
			<?php endif; ?>

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

			<?php if ( $mailto || $phone_href ) : ?>
				<div class="contact__cta-row">
					<?php if ( $mailto ) : ?>
						<a class="contact__btn contact__btn--email" href="<?php echo esc_url( $mailto ); ?>"><?php echo esc_html( $email ); ?></a>
					<?php endif; ?>
					<?php if ( $phone_href ) : ?>
						<a class="contact__btn contact__btn--phone" href="<?php echo esc_url( $phone_href ); ?>"><?php echo esc_html( $phone ); ?></a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="contact__form">
			<div class="contact__form-box hero__form">
				<?php if ( ! empty( $form_title ) ) : ?>
					<h3 class="hero__form-title"><?php echo esc_html( $form_title ); ?></h3>
				<?php endif; ?>

				<?php if ( ! empty( $form_desc ) ) : ?>
					<div class="hero__form-desc"><?php echo wp_kses_post( $form_desc ); ?></div>
				<?php endif; ?>

				<?php if ( ! empty( $form_shortcode ) ) : ?>
					<div class="hero__form-cf7">
						<?php echo do_shortcode( wp_kses_post( $form_shortcode ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>
