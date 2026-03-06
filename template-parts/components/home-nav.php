<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$home_url     = home_url( '/' );
$swieciechowa = home_url( '/inwestycje/swieciechowa/' );
?>

<nav class="home-nav" aria-label="Nawigacja strony głównej">
	<div class="home-nav__bar">
		<div class="container home-nav__inner">
			<a class="home-nav__brand" href="<?php echo esc_url( $home_url ); ?>" aria-label="FlixCity - Strona główna">
				<?php
				$custom_logo_id = (int) get_theme_mod( 'custom_logo' );
				if ( $custom_logo_id ) {
					echo wp_get_attachment_image( $custom_logo_id, 'full', false, [ 'class' => 'custom-logo', 'alt' => esc_attr( get_bloginfo( 'name' ) ) ] );
				} else {
					?>
					<span class="home-nav__brand-text"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
					<?php
				}
				?>
			</a>

			<button class="home-nav__toggle btn btn--outline" type="button" aria-expanded="false" aria-controls="home-nav-menu">
				<span class="btn__text">Menu</span>
				<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
			</button>

			<ul class="home-nav__links" id="home-nav-menu" role="list">
				<li><a href="<?php echo esc_url( $home_url ); ?>" aria-current="page">Strona główna</a></li>
				<li><a href="<?php echo esc_url( $home_url . '#about' ); ?>">O deweloperze</a></li>
				<li><a href="<?php echo esc_url( $swieciechowa ); ?>">Osiedle Święciechowa</a></li>
				<li><span class="home-nav__coming-soon" aria-disabled="true">Przęsocin <em>Wkrótce</em></span></li>
				<li><a href="<?php echo esc_url( $home_url . '#contact' ); ?>">Kontakt</a></li>
			</ul>

			<a class="home-nav__cta" href="<?php echo esc_url( $home_url . '#contact' ); ?>">
				Zapytaj o ofertę
				<span class="home-nav__cta-icon" aria-hidden="true">→</span>
			</a>
		</div>
	</div>
</nav>
