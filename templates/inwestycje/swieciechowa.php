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

	<?php /*
	<section id="domy" class="section">
	<?php get_template_part( 'template-parts/sections/houses' ); ?>
	</section>
	*/ ?>

	<section id="galeria" class="section">
		<?php get_template_part( 'template-parts/sections/gallery' ); ?>
	</section>

	<section id="faq" class="section">
		<?php get_template_part( 'template-parts/sections/faq' ); ?>
	</section>

	<section id="kontakt" class="section section--no-bottom">
		<?php get_template_part( 'template-parts/sections/contact' ); ?>
	</section>

</main>
