<?php
/**
 * Section: Features / USP
 * Template: template-parts/sections/features.php
 *
 * ACF fields:
 * - label_usp (Text)
 * - usp_title (Text)
 * - features (Repeater)
 *   - usp_icon (Image)
 *   - usp_title (Text)
 *   - usp_desc (Text)
 * - usp_btn (Text)
 * - url_btn (URL)
 */

$label   = get_field('label_usp');
$title   = get_field('usp_title');
$btn_txt = get_field('usp_btn');
$btn_url = get_field('url_btn');
?>

<div class="features">
	<div class="container">


		<?php if ($label) : ?>
			<div class="section-label">
				<p class="section-label__text"><?php echo esc_html($label); ?></p>
				<span class="section-label__line" aria-hidden="true"></span>
			</div>
		<?php endif; ?>

		<?php if ($title) : ?>
			<h2 class="section-title"><?php echo esc_html($title); ?></h2>
		<?php endif; ?>

		<?php if (have_rows('features')) : ?>
			<div class="features__grid">

				<?php while (have_rows('features')) : the_row(); ?>
					<?php
						$item_icon  = get_sub_field('usp_icon');
						$item_title = get_sub_field('usp_title');
						$item_desc  = get_sub_field('usp_desc');
					?>

					<div class="features__item">
						<?php if (!empty($item_icon)) : ?>
							<div class="features__icon">
								<?php
									$icon_id  = is_array($item_icon) && isset($item_icon['ID']) ? (int) $item_icon['ID'] : 0;
									$icon_alt = is_array($item_icon) && isset($item_icon['alt']) ? $item_icon['alt'] : '';

									if ($icon_id) {
										echo wp_get_attachment_image(
											$icon_id,
											'full',
											false,
											[
												'alt'     => esc_attr($icon_alt),
												'loading' => 'lazy',
											]
										);
									}
								?>
							</div>
						<?php endif; ?>

						<div class="features__content">
							<?php if ($item_title) : ?>
								<h3 class="features__title"><?php echo esc_html($item_title); ?></h3>
							<?php endif; ?>

							<?php if ($item_desc) : ?>
								<p class="features__desc"><?php echo esc_html($item_desc); ?></p>
							<?php endif; ?>
						</div>
					</div>

				<?php endwhile; ?>

			</div>
		<?php endif; ?>

		<?php if ($btn_txt && $btn_url) : ?>
			<div class="features__cta">
				<a class="btn btn--primary btn--fixed" href="<?php echo esc_url($btn_url); ?>">
					<span class="btn__text"><?php echo esc_html($btn_txt); ?></span>
					<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
				</a>
			</div>
		<?php endif; ?>

	</div>
</div>
