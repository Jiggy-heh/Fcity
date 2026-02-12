<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$logo_url  = 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/logo_swieciechowa.svg';
$arrow_url = 'https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/btn_arrow.svg';
?>

<nav class="lp-nav" aria-label="Nawigacja LP">
	<div class="lp-nav__bar">
		<div class="container lp-nav__inner">

			<a class="lp-nav__brand" href="#top" aria-label="Flixcity Święciechowa">
				<img
					class="lp-nav__logo"
					src="<?php echo esc_url( $logo_url ); ?>"
					alt="FLIXCITY Święciechowa"
					loading="eager"
					decoding="async"
				/>
			</a>

			<ul class="lp-nav__links" role="list">
				<li><a href="#o-inwestycji">Inwestycja</a></li>
				<li><a href="#lokalizacja">Lokalizacja</a></li>
				<li><a href="#domy">Domy</a></li>
				<li><a href="#galeria">Galeria</a></li>
				<li><a href="#kontakt">Kontakt</a></li>
			</ul>

			<a class="lp-nav__cta" href="#kontakt">
				Zapytaj o ofertę
				<span class="lp-nav__cta-icon" aria-hidden="true">
					<img
						src="<?php echo esc_url( $arrow_url ); ?>"
						alt=""
						loading="lazy"
						decoding="async"
					/>
				</span>
			</a>

		</div>
	</div>
</nav>
