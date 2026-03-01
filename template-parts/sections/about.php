<?php
/**
 * Section: About
 * Template: template-parts/sections/about.php
 *
 * ACF fields:
 * - img_left (Image)
 * - desc_label (Text)
 * - desc_title_right (Text)
 * - desc_desc_right (WYSIWYG)
 * - desc_button (Text)
 * - desc_url (URL)
 * - desc_title_left (Text)
 * - desc_desc_left (WYSIWYG)
 * - img_right (Image)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

/**
 * Getter działa:
 * - w Flexible Content / Repeater: get_sub_field()
 * - w normalnym widoku posta: get_field()
 */
$get_acf = function( $key ) use ( $post_id ) {

	// 1) Flexible / repeater
	if ( function_exists( 'get_sub_field' ) ) {
		$sub = get_sub_field( $key );

		// ACF zwraca false gdy nie ma kontekstu / pola
		if ( $sub !== false && $sub !== null ) {
			return $sub;
		}
	}

	// 2) Normalne pola na poście
	if ( function_exists( 'get_field' ) ) {
		return get_field( $key, $post_id );
	}

	return null;
};

$img_left        = $get_acf( 'img_left' );
$label           = $get_acf( 'desc_label' );
$title_right     = $get_acf( 'desc_title_right' );
$desc_right      = $get_acf( 'desc_desc_right' );

$button_text     = $get_acf( 'desc_button' );
$button_url      = $get_acf( 'desc_url' );

$title_left      = $get_acf( 'desc_title_left' );
$desc_left       = $get_acf( 'desc_desc_left' );
$img_right       = $get_acf( 'img_right' );

$img_left_id  = is_array( $img_left )  && ! empty( $img_left['ID'] )  ? (int) $img_left['ID']  : ( is_numeric( $img_left )  ? (int) $img_left  : 0 );
$img_right_id = is_array( $img_right ) && ! empty( $img_right['ID'] ) ? (int) $img_right['ID'] : ( is_numeric( $img_right ) ? (int) $img_right : 0 );

/* Fallback CTA text */
if ( empty( $button_text ) ) {
	$button_text = 'Sprawdź dostępne domy';
}

/* CTA href:
   - jeśli ACF URL puste => fallback na #domy
*/
$button_href = '#domy';

if ( is_string( $button_url ) && trim( $button_url ) !== '' ) {
	$button_href = trim( $button_url );
}
?>

<div class="about">

	<div class="about__inner">

		<!-- ROW 1 -->
		<div class="about__row about__row--top">

			<div class="about__col about__col--media about__col--media-left">
				<div class="about__media about__media--left">
					<?php if ( $img_left_id ) : ?>
						<?php echo wp_get_attachment_image( $img_left_id, 'full', false, [ 'class' => 'about__img about__img--left', 'alt' => '' ] ); ?>
					<?php endif; ?>
				</div>
			</div>

			<div class="about__col about__col--right">

				<?php if ( ! empty( $label ) ) : ?>
					<div class="section-label">
						<span class="section-label__text"><?php echo esc_html( $label ); ?></span>
						<span class="section-label__line" aria-hidden="true"></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $title_right ) ) : ?>
					<h2 class="section-title"><?php echo esc_html( flix_normalize_text( $title_right ) ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $desc_right ) ) : ?>
					<div class="about__desc about__desc--right">
						<?php echo wp_kses_post( $desc_right ); ?>
					</div>
				<?php endif; ?>

				<a class="btn btn--primary btn--fixed about__btn" href="<?php echo esc_url( $button_href ); ?>">
					<span class="btn__text"><?php echo esc_html( $button_text ); ?></span>
					<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
				</a>


			</div>

		</div>

		<!-- ROW 2 -->
		<div class="about__row about__row--bottom">

			<div class="about__col about__col--left">

				<?php if ( ! empty( $title_left ) ) : ?>
					<h2 class="section-title"><?php echo esc_html( $title_left ); ?></h2>
				<?php endif; ?>

				<?php if ( ! empty( $desc_left ) ) : ?>
					<div class="about__desc about__desc--left">
						<?php echo wp_kses_post( $desc_left ); ?>
					</div>
				<?php endif; ?>

			</div>

			<div class="about__col about__col--media about__col--media-right">
				<div class="about__media about__media--right">
					<?php if ( $img_right_id ) : ?>
						<?php echo wp_get_attachment_image( $img_right_id, 'full', false, [ 'class' => 'about__img about__img--right', 'alt' => '' ] ); ?>
					<?php endif; ?>
				</div>
			</div>

		</div>

	</div>
</div>
