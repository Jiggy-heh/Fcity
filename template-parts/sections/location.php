<?php
/**
 * Section: Location
 * Template: template-parts/sections/location.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$background_image = get_field( 'bg_loc' );
$section_label    = get_field( 'label_loc' );
$section_title    = get_field( 'title_loc' );
$section_bullets  = get_field( 'bullet_loc' );
$button_left      = get_field( 'przycisk_lewa_strona' );
$button_right     = get_field( 'btn_right_loc' );
$map_iframe       = get_field( 'iframe_mapa' );

$background_url = '';
if ( is_array( $background_image ) && ! empty( $background_image['url'] ) ) {
	$background_url = $background_image['url'];
} elseif ( is_numeric( $background_image ) ) {
	$background_url = wp_get_attachment_image_url( (int) $background_image, 'full' );
} elseif ( is_string( $background_image ) ) {
	$background_url = $background_image;
}

$allowed_iframe = [
	'iframe' => [
		'src'             => true,
		'width'           => true,
		'height'          => true,
		'style'           => true,
		'frameborder'     => true,
		'allow'           => true,
		'allowfullscreen' => true,
		'loading'         => true,
		'referrerpolicy'  => true,
		'title'           => true,
	],
];
?>

<section class="section section--no-top location" style="background-image:url('<?php echo esc_url( $background_url ); ?>')">

	<img class="location__shape" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/shape_location.svg" alt="" aria-hidden="true">
	
	<div class="location__overlay" aria-hidden="true"></div>

	<div class="container location__grid">

		<div class="location__content">

			<?php if ( ! empty( $section_label ) ) : ?>
				<div class="section-label">
					<p class="section-label__text"><?php echo esc_html( $section_label ); ?></p>
					<span class="section-label__line" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $section_title ) ) : ?>
				<h2 class="section-title"><?php echo esc_html( $section_title ); ?></h2>
			<?php endif; ?>

			<?php if ( ! empty( $section_bullets ) ) : ?>
				<div class="location__bullets">
					<?php echo wp_kses_post( $section_bullets ); ?>
				</div>
			<?php endif; ?>

			<?php if ( have_rows( 'usp_loc' ) ) : ?>
				<div class="location__usps">

					<?php while ( have_rows( 'usp_loc' ) ) : the_row(); ?>
						<?php
						$item_title = get_sub_field( 'title_loc_usp' );
						$item_desc  = get_sub_field( 'desc_loc_usp' );
						?>

						<div class="location__usp">
							<?php if ( ! empty( $item_title ) ) : ?>
								<h3 class="location__usp-title"><?php echo esc_html( $item_title ); ?></h3>
							<?php endif; ?>

							<?php if ( ! empty( $item_desc ) ) : ?>
								<p class="text-usp-desc location__usp-desc"><?php echo esc_html( $item_desc ); ?></p>
							<?php endif; ?>
						</div>

					<?php endwhile; ?>

				</div>
			<?php endif; ?>

			<div class="location__cta">
				<div class="location__cta-inner">

					<?php if ( ! empty( $button_left ) ) : ?>
						<a href="#kontakt" class="btn btn--fixed location__btn-left">
							<span class="btn__text"><?php echo esc_html( $button_left ); ?></span>
							<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

					<?php if ( ! empty( $button_right ) ) : ?>
						<a href="#wybierz-dom" class="btn btn--fixed location__btn-right">
							<span class="btn__text"><?php echo esc_html( $button_right ); ?></span>
							<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

				</div>
			</div>

		</div>

		<div class="location__map-wrap">
			<img class="location__line-long" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/loc1.svg" alt="" aria-hidden="true">
			<img class="location__line-short" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/loc2.svg" alt="" aria-hidden="true">

			<div class="location__map">
				<?php if ( ! empty( $map_iframe ) ) : ?>
					<?php echo wp_kses( $map_iframe, $allowed_iframe ); ?>
				<?php endif; ?>
			</div>

			<div class="location__map-bar" aria-hidden="true"></div>
		</div>

	</div>
</section>

