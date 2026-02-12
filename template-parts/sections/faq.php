<?php
/**
 * Section: FAQ
 * Template: template-parts/sections/core/faq.php
 *
 * ACF fields:
 * - faq_label (text)
 * - faq_title (text)
 * - faq (repeater)
 *    - faq_question (text)
 *    - faq_answer (wysiwyg)
 */

$label = get_field('faq_label');
$title = get_field('faq_title');
$items = get_field('faq');

if (empty($label) && empty($title) && empty($items)) {
	return;
}

$section_id = 'faq';
?>

<div class="faq">
	<div class="container">

		<div class="faq__head">
			<div class="faq__head-left">

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
		</div>

		<?php if (!empty($items) && is_array($items)) : ?>
			<div class="faq__list" data-faq>
				<?php foreach ($items as $i => $row) :
					$q = isset($row['faq_question']) ? $row['faq_question'] : '';
					$a = isset($row['faq_answer']) ? $row['faq_answer'] : '';

					if (empty($q) && empty($a)) {
						continue;
					}

					$item_id  = 'faq-item-' . ($i + 1);
					$panel_id = 'faq-panel-' . ($i + 1);
				?>
					<div class="faq__item" id="<?php echo esc_attr($item_id); ?>">
						<button
							class="faq__question"
							type="button"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr($panel_id); ?>"
						>
							<span class="faq__left">
								<span class="faq__num"><?php echo esc_html(($i + 1) . '.'); ?></span>
								<span class="faq__qtext"><?php echo esc_html($q); ?></span>
							</span>

							<span class="faq__arrow" aria-hidden="true"></span>
						</button>

						<div class="faq__answer" id="<?php echo esc_attr($panel_id); ?>" role="region" aria-label="<?php echo esc_attr($q); ?>">
							<?php if (!empty($a)) : ?>
								<div class="faq__acontent">
									<?php echo apply_filters('the_content', wp_kses_post($a)); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

	</div>
</div>
