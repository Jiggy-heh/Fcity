<?php
/**
 * Section: Houses
 * Template: template-parts/sections/houses.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$get_acf = static function( $key ) use ( $post_id ) {
	if ( function_exists( 'get_sub_field' ) ) {
		$sub = get_sub_field( $key );
		if ( false !== $sub && null !== $sub && '' !== $sub ) {
			return $sub;
		}
	}

	if ( function_exists( 'get_field' ) ) {
		return get_field( $key, $post_id );
	}

	return null;
};

$section_id = $get_acf( 'house_section_id' );
$label      = $get_acf( 'house_label' );
$title      = $get_acf( 'house_title' );
$desc       = $get_acf( 'house_desc' );
$image      = $get_acf( 'house_img' );

if ( empty( $section_id ) ) {
	$section_id = 'wybierz-dom';
}

$image_url = '';
if ( is_array( $image ) && ! empty( $image['url'] ) ) {
	$image_url = $image['url'];
} elseif ( is_numeric( $image ) ) {
	$image_url = wp_get_attachment_image_url( (int) $image, 'full' );
}

if ( empty( $label ) ) {
	$label = 'Poznaj naszą ofertę';
}

if ( empty( $title ) ) {
	$title = 'Wybierz dom';
}

if ( empty( $desc ) ) {
	$desc = 'Kliknij i wybierz dom który Cię interesuje';
}
?>
<section class="section houses is-step-1" id="<?php echo esc_attr( $section_id ); ?>">
	<div class="container houses__inner">
		<div class="houses__head">
			<div class="houses__head-left">
				<div class="section-label houses__label">
					<p class="section-label__text"><?php echo esc_html( $label ); ?></p>
					<span class="section-label__line" aria-hidden="true"></span>
				</div>
				<h2 class="section-title houses__title"><?php echo esc_html( $title ); ?></h2>
				<p class="houses__desc"><?php echo esc_html( $desc ); ?></p>
			</div>
		</div>

		<div class="houses__stage" data-houses-stage>
			<div class="houses__frame">
				<?php if ( ! empty( $image_url ) ) : ?>
					<img class="houses__img" src="<?php echo esc_url( $image_url ); ?>" alt="">
				<?php endif; ?>
				<div class="houses__overlay" data-houses-overlay>
					<p class="houses__overlay-text">Kliknij i wybierz dom który Cię interesuje</p>
				</div>
				<svg class="houses__map" viewBox="0 0 1235 542" preserveAspectRatio="none" aria-label="Mapa domów">
					<polygon class="houses__shape" data-house="1" points="360,206 406,196 435,212 434,250 388,258 360,242" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="2" points="422,198 468,188 498,204 497,242 451,250 422,234" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="3" points="485,189 531,179 560,195 560,233 514,241 485,225" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="4" points="548,180 594,170 623,186 623,224 577,232 548,216" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="5" points="611,171 657,161 686,177 685,215 639,223 611,207" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="6" points="674,162 720,152 749,168 748,206 702,214 674,198" tabindex="0"></polygon>
				</svg>
				<div class="houses__tooltip" data-houses-tooltip hidden>
					<p class="houses__tooltip-title"></p>
					<p class="houses__tooltip-status"></p>
				</div>
			</div>
		</div>

		<div class="houses__expanded" data-houses-expanded>
			<div class="houses__expanded-grid">
				<div class="houses__expanded-left">
					<h3 class="houses__expanded-title">Dane lokalu:</h3>
					<div class="houses__stats">
						<div class="houses__stat"><span class="houses__stat-label">Cena:</span> <span class="houses__stat-value" data-house-price>—</span></div>
						<div class="houses__stat"><span class="houses__stat-label">Powierzchnia:</span> <span class="houses__stat-value" data-house-area>—</span></div>
						<div class="houses__stat"><span class="houses__stat-label">Ogród / działka:</span> <span class="houses__stat-value" data-house-plot>—</span></div>
					</div>
					<div class="houses__actions">
						<a class="btn btn--outline" href="#" data-house-plan>
							<span class="btn__text">Pobierz rzut nieruchomości</span>
							<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
						</a>
						<a class="btn btn--outline" href="#" data-house-dims>
							<span class="btn__text">Pobierz tabelę wymiarów</span>
							<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
						</a>
					</div>
				</div>
				<div class="houses__expanded-right">
					<div class="houses__model">
						<img class="houses__model-img" src="" alt="Model domu" data-house-model>
					</div>
				</div>
			</div>

			<div class="houses__form">
				<div class="houses__form-grid">
					<div class="houses__form-copy">
						<h3 class="section-title houses__form-title">Nie czekaj i zapytaj o ten dom</h3>
						<p class="houses__form-desc">Wypełnij formularz a my skontaktujemy się z Tobą.</p>
					</div>
					<div class="fc-form fc-form--houses">
						<form>
							<div class="fc-form__fields">
								<input class="fc-input" type="text" name="phone" placeholder="Twój numer telefonu">
								<input class="fc-input" type="text" name="fullname" placeholder="Twoje imię">
								<input class="fc-input" type="email" name="email" placeholder="Twój adres email">
								<input class="fc-input" type="text" name="city" placeholder="Twoje miasto">
							</div>
							<div class="fc-form__consents">
								<label><input class="fc-consent" type="checkbox"> <span>Zgoda na kontakt telefoniczny i emailowy.</span></label>
								<label><input class="fc-consent" type="checkbox"> <span>Akceptuję politykę prywatności.</span></label>
							</div>
							<div class="fc-form__actions">
								<button class="fc-submit" type="submit">Wyślij wiadomość</button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
