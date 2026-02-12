<?php
/**
 * Section: Hero
 * Template: template-parts/sections/hero.php
 *
 * ACF fields (Group: Hero):
 * - hero_bg (Image)
 * - hero_title (Text)
 * - hero_subtitle (Text)
 * - hero_description (WYSIWYG)
 * - hero_highlight (Text)
 *
 * - usp (Repeater)
 *   - hero_icon (Image)
 *   - title_usp (Text)
 *   - desc_usp (Text) [opcjonalnie jeśli dodasz]
 *
 * - hero_form_title (Text)
 * - hero_form_desc (WYSIWYG)
 * - hero_form_shortcode (Text)
 *
 * - hero_scroll_target (Text)  e.g. "lokalizacja" albo "#lokalizacja"
 * - hero_scroll_label (Text)   e.g. "DOWIEDZ SIĘ WIĘCEJ"
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

/* Content */
$bg        = get_field( 'hero_bg', $post_id );
$title     = get_field( 'hero_title', $post_id );
$subtitle  = get_field( 'hero_subtitle', $post_id );
$desc      = get_field( 'hero_description', $post_id ); // WYSIWYG
$highlight = get_field( 'hero_highlight', $post_id );

/* Form */
$form_title     = get_field( 'hero_form_title', $post_id );
$form_desc      = get_field( 'hero_form_desc', $post_id ); // WYSIWYG
$form_shortcode = get_field( 'hero_form_shortcode', $post_id );

/* Scroll */
$scroll_target = get_field( 'hero_scroll_target', $post_id );
$scroll_label  = get_field( 'hero_scroll_label', $post_id );

/* Background URL */
$bg_url = '';
if ( is_array( $bg ) && ! empty( $bg['url'] ) ) {
	$bg_url = $bg['url'];
} elseif ( is_numeric( $bg ) ) {
	$bg_url = wp_get_attachment_image_url( (int) $bg, 'full' );
}

/* Fallback */
if ( empty( $title ) ) {
	$title = get_the_title( $post_id );
}

/* Normalize scroll target */
$scroll_href = '';
if ( ! empty( $scroll_target ) ) {
	$scroll_href = trim( $scroll_target );
	if ( $scroll_href !== '' && $scroll_href[0] !== '#' ) {
		$scroll_href = '#' . $scroll_href;
	}
} else {
	$scroll_href = '#lokalizacja';
}

if ( empty( $scroll_label ) ) {
	$scroll_label = 'DOWIEDZ SIĘ WIĘCEJ';
}
?>

<div class="hero">

	<?php if ( $bg_url ) : ?>
		<div class="hero__bg" style="background-image:url('<?php echo esc_url( $bg_url ); ?>');" aria-hidden="true"></div>
	<?php else : ?>
		<div class="hero__bg" aria-hidden="true"></div>
	<?php endif; ?>

	<div class="hero__content">
		<div class="hero__grid">

			<!-- LEFT -->
			<div class="hero__left">

				<?php if ( $title ) : ?>
					<h1 class="hero__title"><?php echo esc_html( $title ); ?></h1>
				<?php endif; ?>

				<?php if ( $subtitle ) : ?>
					<p class="hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $desc ) : ?>
					<div class="hero__description">
						<?php echo wp_kses_post( $desc ); ?>
					</div>
				<?php endif; ?>

				<?php if ( $highlight ) : ?>
					<p class="hero__highlight"><?php echo esc_html( $highlight ); ?></p>
				<?php endif; ?>

				<?php if ( have_rows( 'usp', $post_id ) ) : ?>
					<div class="hero__usp" role="list">
						<?php while ( have_rows( 'usp', $post_id ) ) : the_row(); ?>

							<?php
							$usp_icon = get_sub_field( 'hero_icon' );
							$usp_title = get_sub_field( 'title_usp' );

							// opcjonalne (jeśli kiedyś dodasz)
							$usp_desc = '';
							if ( function_exists( 'get_sub_field' ) ) {
								$tmp_desc = get_sub_field( 'desc_usp' );
								if ( $tmp_desc ) {
									$usp_desc = $tmp_desc;
								}
							}

							$usp_icon_url = '';
							if ( is_array( $usp_icon ) && ! empty( $usp_icon['url'] ) ) {
								$usp_icon_url = $usp_icon['url'];
							} elseif ( is_numeric( $usp_icon ) ) {
								$usp_icon_url = wp_get_attachment_image_url( (int) $usp_icon, 'full' );
							}

							// Skip totally empty rows
							if ( empty( $usp_title ) && empty( $usp_desc ) && empty( $usp_icon_url ) ) {
								continue;
							}

							$usp_text = (string) $usp_title;
							if ( $usp_desc ) {
								$usp_text .= "\n" . $usp_desc;
							}
							?>

							<div class="hero__usp-item" role="listitem">
								<?php if ( $usp_icon_url ) : ?>
									<span class="hero__usp-icon" aria-hidden="true">
										<img src="<?php echo esc_url( $usp_icon_url ); ?>" alt="">
									</span>
								<?php endif; ?>

								<?php if ( $usp_text ) : ?>
									<span class="hero__usp-text"><?php echo esc_html( $usp_text ); ?></span>
								<?php endif; ?>
							</div>

						<?php endwhile; ?>
					</div>
				<?php endif; ?>

			</div>

			<!-- RIGHT (FORM BOX) -->
			<div class="hero__right" id="kontakt">
				<div class="hero__form">

					<?php if ( $form_title ) : ?>
						<h3 class="hero__form-title"><?php echo esc_html( $form_title ); ?></h3>
					<?php endif; ?>

					<?php if ( $form_desc ) : ?>
						<div class="hero__form-desc">
							<?php echo wp_kses_post( $form_desc ); ?>
						</div>
					<?php endif; ?>

					<?php if ( $form_shortcode ) : ?>
						<div class="hero__form-cf7">
							<?php echo do_shortcode( wp_kses_post( $form_shortcode ) ); ?>
						</div>
					<?php endif; ?>

				</div>
			</div>

		</div>
	</div>

	<a class="hero__more" href="<?php echo esc_url( $scroll_href ); ?>" aria-label="<?php echo esc_attr( $scroll_label ); ?>">
		<span class="hero__more-label"><?php echo esc_html( $scroll_label ); ?></span>
		<span class="hero__more-icon" aria-hidden="true"></span>
	</a>

</div>
