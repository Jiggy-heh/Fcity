<?php
/**
 * Section: Location
 * Template: template-parts/sections/location.php
 */

$bg        = get_field('bg_loc');
$label     = get_field('label_loc');
$title     = get_field('title_loc');
$bullets   = get_field('bullet_loc');

$btn_left  = get_field('przycisk_lewa_strona');
$btn_right = get_field('btn_right_loc');

$iframe    = get_field('iframe_mapa');

/* Background url (ACF image może zwracać array/ID/url) */
$bg_url = '';
if (is_array($bg) && !empty($bg['url'])) {
	$bg_url = $bg['url'];
} elseif (is_numeric($bg)) {
	$bg_url = wp_get_attachment_image_url((int) $bg, 'full');
} elseif (is_string($bg)) {
	$bg_url = $bg;
}

/* Allow iframe */
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

<section class="section section--no-top location" style="background-image:url('<?php echo esc_url($bg_url); ?>')">
	<div class="container location__grid">

		<!-- LEFT -->
		<div class="location__content">

			<?php if ($label) : ?>
				<div class="section-label">
					<p class="section-label__text"><?php echo esc_html($label); ?></p>
					<span class="section-label__line" aria-hidden="true"></span>
				</div>
			<?php endif; ?>

			<?php if ($title) : ?>
				<h2 class="section-title"><?php echo esc_html($title); ?></h2>
			<?php endif; ?>

			<?php if ($bullets) : ?>
				<div class="location__bullets">
					<?php echo wp_kses_post($bullets); ?>
				</div>
			<?php endif; ?>

			<?php if (have_rows('usp_loc')) : ?>
				<div class="location__usps">

					<?php while (have_rows('usp_loc')) : the_row(); ?>
						<?php
							$item_title = get_sub_field('title_loc_usp');
							$item_desc  = get_sub_field('desc_loc_usp');
						?>

						<div class="location__usp">
							<?php if ($item_title) : ?>
								<h3 class="location__usp-title"><?php echo esc_html($item_title); ?></h3>
							<?php endif; ?>

							<?php if ($item_desc) : ?>
								<p class="text-usp-desc location__usp-desc"><?php echo esc_html($item_desc); ?></p>
							<?php endif; ?>
						</div>

					<?php endwhile; ?>

				</div>
			<?php endif; ?>

			<div class="location__cta">
				<div class="location__cta-inner">

					<?php if ($btn_left) : ?>
						<a href="#kontakt" class="btn btn--fixed location__btn-left">
							<span class="btn__text"><?php echo esc_html($btn_left); ?></span>
							<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

					<?php if ($btn_right) : ?>
						<a href="#kontakt" class="btn btn--fixed location__btn-right">
							<span class="btn__text"><?php echo esc_html($btn_right); ?></span>
							<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
						</a>
					<?php endif; ?>

				</div>
			</div>

		</div>

		<!-- RIGHT -->
		<div class="location__map-wrap">

			<img class="location__line-long" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/loc1.svg" alt="" aria-hidden="true">
			<img class="location__line-short" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/loc2.svg" alt="" aria-hidden="true">

			<div class="location__map">
				<?php
				if ($iframe) {
					echo wp_kses($iframe, $allowed_iframe);
				}
				?>
			</div>

			<div class="location__map-bar" aria-hidden="true"></div>

		</div>

	</div>
</section>
