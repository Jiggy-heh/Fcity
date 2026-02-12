<?php
/**
 * Section: Gallery
 * Template: template-parts/sections/gallery.php
 */

$post_id = get_queried_object_id();
if (!$post_id) {
	$post_id = get_the_ID();
}

$label  = get_field('gallery_label', $post_id);
$title  = get_field('gallery_title', $post_id);
$images = get_field('gallery_images', $post_id);

if (empty($images) || !is_array($images)) {
	return;
}
?>

<div class="gallery">
	<div class="container">

		<div class="gallery__head">

			<div class="gallery__head-left">
				<?php if (!empty($label)) : ?>
					<div class="section-label">
						<p class="section-label__text"><?php echo esc_html($label); ?></p>
						<span class="section-label__line" aria-hidden="true"></span>
					</div>
				<?php endif; ?>

				<?php if (!empty($title)) : ?>
					<h2 class="section-title"><?php echo esc_html($title); ?></h2>
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
			<?php foreach ($images as $i => $img) : ?>
				<?php
					$img_id  = is_array($img) && !empty($img['ID']) ? (int) $img['ID'] : 0;
					$full    = $img_id ? wp_get_attachment_image_url($img_id, 'full') : '';
					$thumb   = $img_id ? wp_get_attachment_image_url($img_id, 'large') : $full;
					$alt     = is_array($img) && isset($img['alt']) ? $img['alt'] : '';

					if (!$thumb) {
						continue;
					}
				?>

				<a class="gallery__item" href="<?php echo esc_url($full ? $full : $thumb); ?>" data-index="<?php echo esc_attr($i); ?>">
					<img src="<?php echo esc_url($thumb); ?>" alt="<?php echo esc_attr($alt); ?>" loading="lazy">
				</a>
			<?php endforeach; ?>
		</div>

	</div>
</div>
