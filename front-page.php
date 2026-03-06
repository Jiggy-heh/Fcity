<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

<main id="top">
	<?php get_template_part( 'template-parts/components/home-nav' ); ?>

	<section class="section section--no-top section--bleed" id="home">
		<?php get_template_part( 'template-parts/sections/home/hero' ); ?>
	</section>

	<section class="section" id="about">
		<?php get_template_part( 'template-parts/sections/home/about' ); ?>
	</section>

	<section class="section" id="features">
		<?php get_template_part( 'template-parts/sections/home/features' ); ?>
	</section>

	<section class="section" id="investments">
		<?php get_template_part( 'template-parts/sections/home/investments' ); ?>
	</section>

	<section class="section section--no-bottom" id="contact">
		<?php get_template_part( 'template-parts/sections/home/contact' ); ?>
	</section>
</main>

<?php
get_footer();
