<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_template_part( 'template-parts/components/lp-nav' );
?>

<main id="top">

	<section id="inwestycja" class="section section--no-top">
		<?php get_template_part( 'template-parts/sections/hero' ); ?>
	</section>

	<section id="o-inwestycji" class="section">
		<?php get_template_part( 'template-parts/sections/about' ); ?>
	</section>

	<section id="atut" class="section">
		<?php get_template_part( 'template-parts/sections/features' ); ?>
	</section>

	<section id="lokalizacja" class="section">
		<?php get_template_part( 'template-parts/sections/location' ); ?>
	</section>

	<?php
	$finish_standard_section = null;
	$fc_sections             = function_exists( 'get_field' ) ? get_field( 'fc_sections' ) : [];

	if ( is_array( $fc_sections ) ) {
		foreach ( $fc_sections as $fc_section ) {
			if ( isset( $fc_section['acf_fc_layout'] ) && 'standard_wykonczenia' === $fc_section['acf_fc_layout'] ) {
				$finish_standard_section = $fc_section;
				break;
			}
		}
	}

	$rendered_houses = false;
	if ( function_exists( 'have_rows' ) && have_rows( 'fc_sections' ) ) :
		while ( have_rows( 'fc_sections' ) ) : the_row();
			$layout = get_row_layout();


			$template = get_theme_file_path( "template-parts/sections/{$layout}.php" );

			if ( file_exists( $template ) ) {
				get_template_part( "template-parts/sections/{$layout}" );
			}

			if ( 'houses' === $layout ) {
				$rendered_houses = true;
			}
		endwhile;
	endif;

	if ( ! $rendered_houses ) {
		get_template_part( 'template-parts/sections/houses' );
	}
	?>

	<?php if ( ! empty( $finish_standard_section ) ) : ?>
	<div class="apla-sec">
		<img class="apla-sec__bg" src="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/apla_sec.svg" alt="" aria-hidden="true">

		<section id="<?php echo esc_attr( ! empty( $finish_standard_section['section_id'] ) ? $finish_standard_section['section_id'] : 'standard-wykonczenia' ); ?>" class="section finish-standard">
			<?php get_template_part( 'template-parts/sections/finish-standard', null, [ 'data' => $finish_standard_section ] ); ?>
		</section>

		<section id="galeria" class="section">
			<?php get_template_part( 'template-parts/sections/gallery' ); ?>
		</section>

		<section id="faq" class="section">
			<?php get_template_part( 'template-parts/sections/faq' ); ?>
		</section>

	</div>
	<?php endif; ?>

	<section id="kontakt" class="section section--no-bottom">
		<?php get_template_part( 'template-parts/sections/contact' ); ?>
	</section>

	<?php get_template_part( 'template-parts/sections/footer-bar' ); ?>


</main>
