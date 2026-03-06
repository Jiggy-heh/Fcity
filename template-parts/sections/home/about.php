<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$img_left    = get_field( 'home_about_image', $post_id );
$label       = get_field( 'home_about_label', $post_id );
$title_right = get_field( 'home_about_title', $post_id );
$desc_right  = get_field( 'home_about_description', $post_id );
$button_text = get_field( 'home_about_button_text', $post_id );
$button_url  = get_field( 'home_about_button_url', $post_id );

// Backward-safe fallback (legacy content), only if new homepage fields are empty.
if ( empty( $img_left ) ) {
	$img_left = get_field( 'img_left', $post_id );
}
if ( empty( $label ) ) {
	$label = get_field( 'desc_label', $post_id );
}
if ( empty( $title_right ) ) {
	$title_right = get_field( 'desc_title_right', $post_id );
}
if ( empty( $desc_right ) ) {
	$desc_right = get_field( 'desc_desc_right', $post_id );
}
if ( empty( $button_text ) ) {
	$button_text = get_field( 'desc_button', $post_id );
}
if ( empty( $button_url ) ) {
	$button_url = get_field( 'desc_url', $post_id );
}

$img_left_id = is_array( $img_left ) && ! empty( $img_left['ID'] ) ? (int) $img_left['ID'] : ( is_numeric( $img_left ) ? (int) $img_left : 0 );
$button_href = ! empty( $button_url ) ? trim( (string) $button_url ) : '#investments';

if ( empty( $button_text ) ) {
	$button_text = 'Sprawdź dostępne domy';
}
?>

<div class="about about--home">
	<div class="about__inner">
		<div class="about__row about__row--top">
			<div class="about__col about__col--media about__col--media-left">
				<div class="about__media about__media--left">
					<?php if ( $img_left_id ) : ?>
						<span class="about__img-wrap about__img-wrap--left" aria-hidden="true">
							<?php echo wp_get_attachment_image( $img_left_id, 'full', false, [ 'class' => 'about__img about__img--left', 'alt' => '' ] ); ?>
						</span>
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
	</div>
</div>
