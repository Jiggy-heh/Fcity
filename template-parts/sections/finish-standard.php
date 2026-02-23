<?php
/**
 * Section: Finish Standard
 * Template: template-parts/sections/finish-standard.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$data = isset( $args['data'] ) && is_array( $args['data'] ) ? $args['data'] : [];

$section_id  = isset( $data['section_id'] ) ? $data['section_id'] : 'standard-wykonczenia';
$label_text  = isset( $data['label_text'] ) ? $data['label_text'] : 'Wykończenie domów';
$title       = isset( $data['title'] ) ? $data['title'] : 'Standard wykończenia';
$intro       = isset( $data['intro'] ) ? $data['intro'] : '';
$checks      = isset( $data['checks'] ) && is_array( $data['checks'] ) ? $data['checks'] : [];
$bottom_text = isset( $data['bottom_text'] ) ? $data['bottom_text'] : '';
$button_text = isset( $data['button_text'] ) ? $data['button_text'] : 'Zapytaj o ofertę';
$button_url  = isset( $data['button_url'] ) ? $data['button_url'] : 'https://flixcity.kreatorzybiznesu.pl/inwestycje/swieciechowa/#kontakt';

/**
 * Budujemy 3 kolumny jak w gridzie:
 * 1,4,7... / 2,5,8... / 3,6,9...
 */
$columns = [
	[],
	[],
	[],
];

if ( ! empty( $checks ) ) {
	$i = 0;
	foreach ( $checks as $check ) {
		$check_text = isset( $check['check_text'] ) ? trim( (string) $check['check_text'] ) : '';
		if ( $check_text === '' ) {
			continue;
		}

		$columns[ $i % 3 ][] = $check_text;
		$i++;
	}
}
?>
<div class="container">
	<div class="finish-standard__head">
		<div class="section-label">
			<p class="section-label__text"><?php echo esc_html( $label_text ); ?></p>
			<span class="section-label__line" aria-hidden="true"></span>
		</div>

		<h2 class="section-title"><?php echo esc_html( $title ); ?></h2>

		<?php if ( ! empty( $intro ) ) : ?>
			<div class="finish-standard__intro wysiwyg"><?php echo wp_kses_post( $intro ); ?></div>
		<?php endif; ?>
	</div>

	<?php if ( ! empty( $columns[0] ) || ! empty( $columns[1] ) || ! empty( $columns[2] ) ) : ?>
		<div class="finish-standard__checks-wrap">
			<div class="finish-standard__checks" aria-label="Standard wykończenia">
				<?php foreach ( $columns as $col ) : ?>
					<div class="finish-standard__col">
						<?php foreach ( $col as $text ) : ?>
							<div class="finish-standard__item">
								<span class="finish-standard__icon" aria-hidden="true"></span>

								<span class="finish-standard__frame">
									<span class="finish-standard__item-text"><?php echo esc_html( $text ); ?></span>
								</span>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="finish-standard__nav" aria-label="Nawigacja standardu wykończenia">
				<button type="button" class="finish-standard__nav-btn" data-dir="prev" aria-label="Poprzedni">←</button>
				<button type="button" class="finish-standard__nav-btn" data-dir="next" aria-label="Następny">→</button>
			</div>
		</div>
	<?php endif; ?>

	<div class="finish-standard__bottom">
		<?php if ( ! empty( $bottom_text ) ) : ?>
			<div class="finish-standard__text wysiwyg"><?php echo wp_kses_post( $bottom_text ); ?></div>
		<?php endif; ?>

		<a class="btn btn--primary btn--fixed" href="<?php echo esc_url( $button_url ); ?>">
			<span class="btn__text"><?php echo esc_html( $button_text ); ?></span>
			<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
		</a>

	</div>
</div>
