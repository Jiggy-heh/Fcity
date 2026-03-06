<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$post_id = get_the_ID();

$title        = get_field( 'home_hero_title', $post_id );
$subtitle     = get_field( 'home_hero_subtitle', $post_id );
$desc         = get_field( 'home_hero_description', $post_id );
$highlight    = get_field( 'home_hero_highlight', $post_id );
$scroll_label = get_field( 'home_hero_scroll_label', $post_id );
$video_file   = get_field( 'home_hero_video', $post_id );
$hero_usp     = get_field( 'home_hero_usp', $post_id );

// Backward-safe fallback (legacy content), only if new homepage fields are empty.
if ( empty( $title ) ) {
	$title = get_field( 'hero_title', $post_id );
}
if ( empty( $subtitle ) ) {
	$subtitle = get_field( 'hero_subtitle', $post_id );
}
if ( empty( $desc ) ) {
	$desc = get_field( 'hero_description', $post_id );
}
if ( empty( $highlight ) ) {
	$highlight = get_field( 'hero_highlight', $post_id );
}
if ( empty( $scroll_label ) ) {
	$scroll_label = get_field( 'hero_scroll_label', $post_id );
}
if ( empty( $video_file ) ) {
	$video_file = get_field( 'hero_video', $post_id );
}

$video_url = '';
if ( is_array( $video_file ) && ! empty( $video_file['url'] ) ) {
	$video_url = $video_file['url'];
} elseif ( is_numeric( $video_file ) ) {
	$video_url = wp_get_attachment_url( (int) $video_file );
} elseif ( is_string( $video_file ) ) {
	$video_url = $video_file;
}

if ( empty( $title ) ) {
	$title = get_the_title( $post_id );
}

if ( empty( $scroll_label ) ) {
	$scroll_label = 'DOWIEDZ SIĘ WIĘCEJ';
}

$usp_rows = [];
if ( is_array( $hero_usp ) && ! empty( $hero_usp ) ) {
	$usp_rows = $hero_usp;
} elseif ( have_rows( 'usp', $post_id ) ) {
	while ( have_rows( 'usp', $post_id ) ) {
		the_row();
		$usp_rows[] = [
			'icon' => get_sub_field( 'hero_icon' ),
			'text' => get_sub_field( 'title_usp' ),
		];
	}
}
?>

<div class="hero hero--home">
	<div class="hero__bg hero__bg--video" aria-hidden="true">
		<?php if ( $video_url ) : ?>
			<video class="hero__video" autoplay muted loop playsinline>
				<source src="<?php echo esc_url( $video_url ); ?>" type="video/mp4">
			</video>
		<?php endif; ?>
	</div>

	<svg class="hero__shape" viewBox="0 0 1440 57" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
		<path d="M0 24.5C200 6.5 474 -4.5 720 8.5C966 21.5 1240 58 1440 49V57H0V24.5Z" fill="white"/>
	</svg>

	<div class="hero__content">
		<div class="hero__grid hero__grid--home">
			<div class="hero__left">
				<?php if ( $title ) : ?>
					<h1 class="hero__title"><?php echo esc_html( $title ); ?></h1>
				<?php endif; ?>

				<?php if ( $subtitle ) : ?>
					<p class="hero__subtitle"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>

				<?php if ( $desc ) : ?>
					<div class="hero__description"><?php echo wp_kses_post( $desc ); ?></div>
				<?php endif; ?>

				<?php if ( $highlight ) : ?>
					<p class="hero__highlight"><?php echo esc_html( $highlight ); ?></p>
				<?php endif; ?>

				<?php if ( ! empty( $usp_rows ) ) : ?>
					<div class="hero__usp" role="list">
						<?php foreach ( $usp_rows as $usp_row ) : ?>
							<?php
							$usp_icon = isset( $usp_row['icon'] ) ? $usp_row['icon'] : null;
							$usp_text = isset( $usp_row['text'] ) ? $usp_row['text'] : '';
							$usp_icon_url = '';
							if ( is_array( $usp_icon ) && ! empty( $usp_icon['url'] ) ) {
								$usp_icon_url = $usp_icon['url'];
							} elseif ( is_numeric( $usp_icon ) ) {
								$usp_icon_url = wp_get_attachment_image_url( (int) $usp_icon, 'full' );
							}
							?>
							<div class="hero__usp-item" role="listitem">
								<?php if ( $usp_icon_url ) : ?>
									<span class="hero__usp-icon" aria-hidden="true"><img src="<?php echo esc_url( $usp_icon_url ); ?>" alt=""></span>
								<?php endif; ?>
								<?php if ( $usp_text ) : ?>
									<span class="hero__usp-text"><?php echo esc_html( $usp_text ); ?></span>
								<?php endif; ?>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>

				<a class="btn btn--primary btn--fixed hero__home-cta" href="#investments">
					<span class="btn__text">Nasze inwestycje</span>
					<span class="btn__icon btn__icon--arrow" aria-hidden="true"></span>
				</a>
			</div>
		</div>
	</div>

	<a class="hero__more" href="#about" aria-label="<?php echo esc_attr( $scroll_label ); ?>">
		<span class="hero__more-label"><?php echo esc_html( $scroll_label ); ?></span>
		<span class="hero__more-icon" aria-hidden="true"></span>
	</a>
</div>
