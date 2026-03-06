<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_label = get_field( 'home_investments_label' );
$section_title = get_field( 'home_investments_title' );
$section_desc  = get_field( 'home_investments_desc' );
$card_one_img  = get_field( 'home_investments_image_swieciechowa' );
$card_two_img  = get_field( 'home_investments_image_przesocin' );

if ( empty( $section_label ) ) {
	$section_label = 'Nasze inwestycje';
}

if ( empty( $section_title ) ) {
	$section_title = 'Tworzymy miejsca, w których chce się mieszkać';
}

if ( empty( $section_desc ) ) {
	$section_desc = 'Poznaj aktualne inwestycje FlixCity. Realizujemy projekty z myślą o funkcjonalnym układzie, nowoczesnej architekturze i komforcie codziennego życia.';
}

$card_one_url = '';
if ( is_array( $card_one_img ) && ! empty( $card_one_img['url'] ) ) {
	$card_one_url = $card_one_img['url'];
} elseif ( is_numeric( $card_one_img ) ) {
	$card_one_url = wp_get_attachment_image_url( (int) $card_one_img, 'full' );
}

$card_two_url = '';
if ( is_array( $card_two_img ) && ! empty( $card_two_img['url'] ) ) {
	$card_two_url = $card_two_img['url'];
} elseif ( is_numeric( $card_two_img ) ) {
	$card_two_url = wp_get_attachment_image_url( (int) $card_two_img, 'full' );
}

$fallback_image = get_theme_file_uri( '/screenshot.png' );
$swieciechowa_link = home_url( '/inwestycje/swieciechowa/' );
?>

<div class="home-investments">
	<div class="container">
		<div class="section-label">
			<p class="section-label__text"><?php echo esc_html( $section_label ); ?></p>
			<span class="section-label__line" aria-hidden="true"></span>
		</div>

		<h2 class="section-title"><?php echo esc_html( $section_title ); ?></h2>
		<p class="about__desc home-investments__desc"><?php echo esc_html( $section_desc ); ?></p>

		<div class="home-investments__grid">
			<a class="home-investments__card" href="<?php echo esc_url( $swieciechowa_link ); ?>">
				<img class="home-investments__image" src="<?php echo esc_url( $card_one_url ? $card_one_url : $fallback_image ); ?>" alt="Osiedle Święciechowa" loading="lazy">
				<span class="home-investments__overlay" aria-hidden="true"></span>
				<span class="home-investments__content">
					<span class="home-investments__title">Święciechowa</span>
					<span class="home-investments__cta">Zobacz inwestycję</span>
				</span>
			</a>

			<div class="home-investments__card home-investments__card--coming" aria-label="Przęsocin - inwestycja w przygotowaniu">
				<img class="home-investments__image" src="<?php echo esc_url( $card_two_url ? $card_two_url : $fallback_image ); ?>" alt="Przęsocin" loading="lazy">
				<span class="home-investments__overlay" aria-hidden="true"></span>
				<span class="home-investments__content">
					<span class="home-investments__title">Przęsocin</span>
					<span class="home-investments__status">Wkrótce</span>
				</span>
			</div>
		</div>
	</div>
</div>
