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

$materials_label = $get_acf( 'house_materials_label' );
$materials_title = $get_acf( 'house_materials_title' );
$materials       = $get_acf( 'house_materials' );
$buildings_cards = $get_acf( 'house_buildings_cards' );
$cards_map       = [];

if ( ! empty( $buildings_cards ) && is_array( $buildings_cards ) ) {
	foreach ( $buildings_cards as $row ) {
		$building_number = isset( $row['building_number'] ) ? (string) $row['building_number'] : '';
		if ( '' === $building_number ) {
			continue;
		}

		$left_image  = $row['left_card_image'] ?? null;
		$right_image = $row['right_card_image'] ?? null;

		$left_image_url = '';
		if ( is_array( $left_image ) && ! empty( $left_image['url'] ) ) {
			$left_image_url = (string) $left_image['url'];
		} elseif ( is_numeric( $left_image ) ) {
			$left_image_url = (string) wp_get_attachment_image_url( (int) $left_image, 'large' );
		}

		$right_image_url = '';
		if ( is_array( $right_image ) && ! empty( $right_image['url'] ) ) {
			$right_image_url = (string) $right_image['url'];
		} elseif ( is_numeric( $right_image ) ) {
			$right_image_url = (string) wp_get_attachment_image_url( (int) $right_image, 'large' );
		}

		$cards_map[ $building_number ] = [
			'left'  => $left_image_url,
			'right' => $right_image_url,
		];
	}
}

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

if ( empty( $materials_label ) ) {
	$materials_label = 'Inwestycje';
}

if ( empty( $materials_title ) ) {
	$materials_title = 'Pobierz materiały';
}
?>
<section class="section houses is-step-1" id="<?php echo esc_attr( $section_id ); ?>" data-houses-endpoint="<?php echo esc_url( rest_url( 'flix-asari/v1/houses' ) ); ?>" data-houses-cards="<?php echo esc_attr( wp_json_encode( $cards_map ) ); ?>">
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

		<?php
		$houses_dev = current_user_can( 'manage_options' ) && isset( $_GET['houses_dev'] ) && '1' === $_GET['houses_dev'];
		?>

		<?php if ( $houses_dev ) : ?>
			<div class="houses__dev" data-houses-dev>
				<div class="houses__dev-row">
					<button type="button" class="houses__dev-toggle" data-houses-dev-toggle aria-pressed="false">
						Tryb łapania koordynatów: OFF
					</button>
					<p class="houses__dev-tip">Tip: klikaj punkty na makiecie, potem skopiuj points. ENTER kończy polygon.</p>
				</div>

				<div class="houses__dev-row">
					<label class="houses__dev-label">
						Aktualny dom (data-house):
						<input type="text" class="houses__dev-input" value="1" data-houses-dev-house>
					</label>

					<label class="houses__dev-label">
						Aktywna mapa:
						<select class="houses__dev-select" data-houses-dev-map>
							<option value="auto">auto (widoczna)</option>
							<option value="desktop">desktop</option>
							<option value="mobile">mobile</option>
						</select>
					</label>
				</div>

				<div class="houses__dev-row">
					<textarea class="houses__dev-output" rows="3" readonly data-houses-dev-output placeholder="Tu pojawi się string points..."></textarea>
				</div>

				<div class="houses__dev-row">
					<button type="button" class="houses__dev-btn" data-houses-dev-copy>Kopiuj</button>
					<button type="button" class="houses__dev-btn" data-houses-dev-clear>Wyczyść</button>
				</div>
			</div>
		<?php endif; ?>

		<div class="houses__stage" data-houses-stage>
			<div class="houses__frame">
				<?php if ( ! empty( $image_url ) ) : ?>
					<picture>
						<source media="(max-width: 991px)" srcset="https://flixcity.kreatorzybiznesu.pl/wp-content/uploads/2026/02/makieta_domow_mobile.jpg">
						<img class="houses__img" src="<?php echo esc_url( $image_url ); ?>" alt="">
					</picture>
				<?php endif; ?>

				<div class="houses__overlay" data-houses-overlay>
					<p class="houses__overlay-text">Kliknij i wybierz dom który Cię interesuje</p>
				</div>

				<svg class="houses__map houses__map--desktop" viewBox="0 0 1235 542" preserveAspectRatio="none" aria-label="Mapa domów (desktop)">
					<rect class="houses__map-hit" x="0" y="0" width="1235" height="542" fill="transparent"></rect>

					<polygon class="houses__shape" data-house="1" points="698,256 698,284 755,295 779,275 779,247 772,244 710,235" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="2" points="607,241 607,268 663,280 686,262 686,235 677,230 621,221" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="3" points="526,228 527,255 576,265 604,249 603,220 592,217 541,208" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="4" points="452,214 454,241 499,250 526,237 526,210 514,204 467,198" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="5" points="385,205 386,228 427,236 458,225 457,199 441,194 400,187" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="6" points="647,315 647,354 716,367 743,339 745,304 734,302 663,287" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="7" points="542,294 543,326 605,340 634,318 636,284 622,282 559,268" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="8" points="430,271 431,300 484,314 520,296 519,263 502,258 445,248" tabindex="0"></polygon>
				</svg>

				<svg class="houses__map houses__map--mobile" viewBox="0 0 1235 542" preserveAspectRatio="none" aria-label="Mapa domów (mobile)">
					<rect class="houses__map-hit" x="0" y="0" width="1235" height="542" fill="transparent"></rect>

					<polygon class="houses__shape" data-house="1" points="976,282 976,309 1137,322 1197,303 1201,275 1184,270 1010,260" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="2" points="760,245 721,264 726,294 874,304 946,288 951,260 917,255" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="3" points="535,234 501,252 497,279 637,288 709,273 713,246 675,240" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="4" points="331,222 284,240 293,267 416,275 497,263 501,236 458,228" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="5" points="140,213 98,230 106,257 216,263 306,252 301,224 263,219" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="6" points="874,312 836,340 832,376 1027,389 1108,364 1108,330 1082,324" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="7" points="586,295 539,318 543,351 717,362 802,342 806,312 760,307" tabindex="0"></polygon>
					<polygon class="houses__shape" data-house="8" points="272,275 225,295 233,327 382,339 480,319 484,288" tabindex="0"></polygon>
				</svg>

				<div class="houses__tooltip" data-houses-tooltip hidden>
					<p class="houses__tooltip-title"></p>
					<p class="houses__tooltip-status"></p>
				</div>
			</div>
		</div>


		<div class="houses__choose" data-houses-choose>
			<h3 class="houses__choose-title">Wybierz stronę lokalu</h3>
			<div class="houses__choose-grid">
				<button type="button" class="houses__choose-card" data-house-side-card="left">
					<span class="houses__choose-image-wrap">
						<img class="houses__choose-image" src="" alt="Lokal lewy" data-house-side-image="left">
					</span>
					<span class="houses__choose-area" data-house-side-area="left">Powierzchnia: —</span>
				</button>
				<button type="button" class="houses__choose-card" data-house-side-card="right">
					<span class="houses__choose-image-wrap">
						<img class="houses__choose-image" src="" alt="Lokal prawy" data-house-side-image="right">
					</span>
					<span class="houses__choose-area" data-house-side-area="right">Powierzchnia: —</span>
				</button>
			</div>
			<div class="houses__choose-buttons">
				<button type="button" class="btn btn--outline" data-house-side-button="left">
					<span class="btn__text">Wybierz lokal lewy</span>
					<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
				</button>
				<button type="button" class="btn btn--outline" data-house-side-button="right">
					<span class="btn__text">Wybierz lokal prawy</span>
					<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
				</button>
			</div>
		</div>

		<div class="houses__expanded" data-houses-expanded>
			<div class="houses__expanded-grid">
				<div class="houses__expanded-left">
					<h3 class="houses__expanded-title">Dane lokalu:</h3>
					<div class="houses__stats">
						<div class="houses__stat"><span class="houses__stat-label">Cena:</span> <span class="houses__stat-value" data-house-price>—</span></div>
						<div class="houses__stat"><span class="houses__stat-label">Powierzchnia:</span> <span class="houses__stat-value" data-house-area>—</span></div>
						<div class="houses__stat"><span class="houses__stat-label">Liczba pokoi:</span> <span class="houses__stat-value" data-house-rooms>—</span></div>
						<div class="houses__stat">
							<span class="houses__stat-label">Dostępność:</span>
							<span class="houses__stat-value houses__availability" data-house-status>—</span>
						</div>
						<div class="houses__stat"><span class="houses__stat-label">Działka:</span> <span class="houses__stat-value" data-house-plot>—</span></div>
					</div>
					<div class="houses__actions">
						<a class="btn btn--outline" href="#" data-house-plan>
							<span class="btn__text">Pobierz rzut nieruchomości</span>
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
						<h3 class="section-title houses__form-title">Nie czekaj i zapytaj o&nbsp;ten dom</h3>
						<p class="houses__form-desc">Wypełnij formularz a my skontaktujemy się z Tobą.</p>
					</div>
					<div class="fc-form fc-form--houses">
						<?php echo do_shortcode( '[contact-form-7 id="f30e671" title="Formularz Domy"]' ); ?>
					</div>

				</div>
			</div>
		</div>
		<?php if ( ! empty( $materials ) && is_array( $materials ) ) : ?>
			<div class="houses__materials">
				<div class="houses__materials-grid">
					<div class="houses__materials-left">
						<div class="section-label">
							<p class="section-label__text"><?php echo esc_html( $materials_label ); ?></p>
							<span class="section-label__line" aria-hidden="true"></span>
						</div>
						<h2 class="section-title"><?php echo esc_html( $materials_title ); ?></h2>
					</div>

					<div class="houses__materials-buttons">
						<?php foreach ( $materials as $row ) : ?>
							<?php
							$link = $row['material_link'] ?? null;

							if ( empty( $link ) || empty( $link['url'] ) ) {
								continue;
							}

							$url    = $link['url'];
							$text   = ! empty( $link['title'] ) ? $link['title'] : 'Pobierz materiał';
							$target = ! empty( $link['target'] ) ? $link['target'] : '_self';
							?>
							<a class="btn btn--outline houses__dev-btn" href="<?php echo esc_url( $url ); ?>" target="<?php echo esc_attr( $target ); ?>" rel="noopener">
								<span class="btn__text"><?php echo esc_html( $text ); ?></span>
								<span class="btn__icon btn__icon--hamburger" aria-hidden="true"></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		<?php endif; ?>		
	</div>
</section>
