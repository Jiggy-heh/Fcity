<?php
/**
 * Section: Gallery
 * Template: template-parts/sections/gallery.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_queried_object_id();
if ( ! $post_id ) {
	$post_id = get_the_ID();
}

$section_label = get_field( 'gallery_label', $post_id );
$section_title = get_field( 'gallery_title', $post_id );
$images        = get_field( 'gallery_images', $post_id );

if ( empty( $images ) || ! is_array( $images ) ) {
	return;
}
?>

<div class="gallery">
	<div class="container">

		<div class="gallery__head">

			<div class="gallery__head-left">
				<?php if ( ! empty( $section_label ) ) : ?>
					<div class="section-label">
						<p class="section-label__text"><?php echo esc_html( $section_label ); ?></p>
						<span class="section-label__line" aria-hidden="true"></span>
					</div>
				<?php endif; ?>

				<?php if ( ! empty( $section_title ) ) : ?>
					<h2 class="section-title"><?php echo esc_html( $section_title ); ?></h2>
				<?php endif; ?>
			</div>

			<div class="gallery__nav" aria-label="Nawigacja galerii">
				<button type="button" class="gallery__nav-btn gallery__nav-btn--prev" aria-label="Poprzednie zdjęcie">
					<span aria-hidden="true">‹</span>
				</button>
				<button type="button" class="gallery__nav-btn gallery__nav-btn--next" aria-label="Następne zdjęcie">
					<span aria-hidden="true">›</span>
				</button>
			</div>

		</div>

		<div class="gallery__grid" data-flx-gallery>
			<?php foreach ( $images as $index => $image ) : ?>
				<?php
				$image_id = is_array( $image ) && ! empty( $image['ID'] ) ? (int) $image['ID'] : 0;
				$full_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';
				$thumb_url = $image_id ? wp_get_attachment_image_url( $image_id, 'large' ) : $full_url;
				$image_alt = is_array( $image ) && isset( $image['alt'] ) ? (string) $image['alt'] : '';

				if ( ! $thumb_url ) {
					continue;
				}
				?>

				<a class="gallery__item" href="<?php echo esc_url( $full_url ? $full_url : $thumb_url ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
					<img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $image_alt ); ?>" loading="lazy">
				</a>
			<?php endforeach; ?>
		</div>

	</div>
</div>
