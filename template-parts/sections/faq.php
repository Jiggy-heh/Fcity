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

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_label = get_field( 'faq_label' );
$section_title = get_field( 'faq_title' );
$items         = get_field( 'faq' );

if ( empty( $section_label ) && empty( $section_title ) && empty( $items ) ) {
	return;
}

?>

<div class="faq" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container">

		<div class="faq__head">
			<div class="faq__head-left">

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
		</div>

		<?php if ( ! empty( $items ) && is_array( $items ) ) : ?>
			<div class="faq__list" data-faq>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php
					$question = isset( $item['faq_question'] ) ? $item['faq_question'] : '';
					$answer   = isset( $item['faq_answer'] ) ? $item['faq_answer'] : '';

					if ( empty( $question ) && empty( $answer ) ) {
						continue;
					}

					$item_id  = 'faq-item-' . ( $index + 1 );
					$panel_id = 'faq-panel-' . ( $index + 1 );
					?>
					<div class="faq__item" id="<?php echo esc_attr( $item_id ); ?>">
						<button
							class="faq__question"
							type="button"
							aria-expanded="false"
							aria-controls="<?php echo esc_attr( $panel_id ); ?>"
						>
							<span class="faq__left">
								<span class="faq__num"><?php echo esc_html( ( $index + 1 ) . '.' ); ?></span>
								<span class="faq__qtext"><?php echo esc_html( $question ); ?></span>
							</span>

							<span class="faq__arrow" aria-hidden="true"></span>
						</button>

						<div class="faq__answer" id="<?php echo esc_attr( $panel_id ); ?>" role="region" aria-label="<?php echo esc_attr( $question ); ?>">
							<?php if ( ! empty( $answer ) ) : ?>
								<div class="faq__acontent">
									<?php echo apply_filters( 'the_content', wp_kses_post( $answer ) ); ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<button class="faq__more" type="button" aria-expanded="false">Zobacz więcej</button>
		<?php endif; ?>

	</div>
</div>
