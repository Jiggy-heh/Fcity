<?php
/**
 * Flix_City functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Flix_City
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function flix_city_setup() {
	/*
		* Make theme available for translation.
		* Translations can be filed in the /languages/ directory.
		* If you're building a theme based on Flix_City, use a find and replace
		* to change 'flix_city' to the name of your theme in all the template files.
		*/
	load_theme_textdomain( 'flix_city', get_template_directory() . '/languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
		* Let WordPress manage the document title.
		* By adding theme support, we declare that this theme does not use a
		* hard-coded <title> tag in the document head, and expect WordPress to
		* provide it for us.
		*/
	add_theme_support( 'title-tag' );

	/*
		* Enable support for Post Thumbnails on posts and pages.
		*
		* @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		*/
	add_theme_support( 'post-thumbnails' );

	// This theme uses wp_nav_menu() in one location.
	register_nav_menus(
		array(
			'menu-1' => esc_html__( 'Primary', 'flix_city' ),
		)
	);

	/*
		* Switch default core markup for search form, comment form, and comments
		* to output valid HTML5.
		*/
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	// Set up the WordPress core custom background feature.
	add_theme_support(
		'custom-background',
		apply_filters(
			'flix_city_custom_background_args',
			array(
				'default-color' => 'ffffff',
				'default-image' => '',
			)
		)
	);

	// Add theme support for selective refresh for widgets.
	add_theme_support( 'customize-selective-refresh-widgets' );

	/**
	 * Add support for core custom logo.
	 *
	 * @link https://codex.wordpress.org/Theme_Logo
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 250,
			'width'       => 250,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);
}
add_action( 'after_setup_theme', 'flix_city_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function flix_city_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'flix_city_content_width', 640 );
}
add_action( 'after_setup_theme', 'flix_city_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function flix_city_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Sidebar', 'flix_city' ),
			'id'            => 'sidebar-1',
			'description'   => esc_html__( 'Add widgets here.', 'flix_city' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
}
add_action( 'widgets_init', 'flix_city_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function flix_city_scripts() {
	wp_enqueue_style( 'flix_city-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'flix_city-style', 'rtl', 'replace' );

	wp_enqueue_script( 'flix_city-navigation', get_template_directory_uri() . '/js/navigation.js', array(), _S_VERSION, true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'flix_city_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
	require get_template_directory() . '/inc/jetpack.php';
}

//*Moje ustawienia */

function flixcity_enqueue_assets() {

	/* ===============================
	   Fonts
	   =============================== */

	wp_enqueue_style(
		'flixcity-font-jost',
		'https://fonts.googleapis.com/css2?family=Jost:wght@300;400;600;700&display=swap',
		[],
		null
	);

	/* ===============================
	   Design system (global)
	   =============================== */

	wp_enqueue_style(
		'flixcity-config',
		get_template_directory_uri() . '/assets/css/config.css',
		['flixcity-font-jost'],
		'1.0.0'
	);

	/* Opcjonalny reset / helpers */
	if ( file_exists( get_template_directory() . '/assets/css/base.css' ) ) {
		wp_enqueue_style(
			'flixcity-base',
			get_template_directory_uri() . '/assets/css/base.css',
			['flixcity-config'],
			'1.0.0'
		);
	}

	/* ===============================
	   LP styles — tylko dla inwestycji
	   =============================== */

	if ( is_singular( 'inwestycje' ) ) {

		/* Components */
		$components = [
			'lp-nav',
			'buttons',
			'forms',
		];

		foreach ( $components as $component ) {
			$path = "/assets/css/components/{$component}.css";

			if ( file_exists( get_template_directory() . $path ) ) {
				wp_enqueue_style(
					"flixcity-component-{$component}",
					get_template_directory_uri() . $path,
					['flixcity-config'],
					'1.0.0'
				);
			}
		}

		/* Sections */
		$sections = [
			'hero',
			'about',
			'features',
			'location',
			'houses',
			'gallery',
			'faq',
			'contact',
		];

		foreach ( $sections as $section ) {
			$path = "/assets/css/sections/{$section}.css";

			if ( file_exists( get_template_directory() . $path ) ) {
				wp_enqueue_style(
					"flixcity-section-{$section}",
					get_template_directory_uri() . $path,
					['flixcity-config'],
					'1.0.0'
				);
			}
		}
	}

	/* ===============================
	   style.css — override LAST
	   =============================== */

	wp_enqueue_style(
		'flixcity-style',
		get_stylesheet_uri(),
		['flixcity-config'],
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'flixcity_enqueue_assets' );


//* Custom Post Type */
require get_template_directory() . '/inc/post-types/inwestycje.php';
require get_template_directory() . '/inc/acf/houses-layout.php';


//* Scroll menu */
function flixcity_enqueue_lp_scripts() {

	if ( is_singular( 'inwestycje' ) ) {
		wp_enqueue_script(
			'flixcity-lp-nav',
			get_template_directory_uri() . '/assets/js/lp-nav.js',
			[],
			'1.0.0',
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'flixcity_enqueue_lp_scripts' );


/**
 * Allow SVG upload (admin only)
 */
function flixcity_allow_svg_uploads( $mimes ) {

	if ( current_user_can( 'manage_options' ) ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'flixcity_allow_svg_uploads' );


//* css nav */
function flixcity_enqueue_lp_nav_css() {

	if ( is_singular( 'inwestycje' ) ) {

		wp_enqueue_style(
			'flixcity-lp-nav',
			get_template_directory_uri() . '/assets/css/lp-nav.css',
			[ 'flixcity-style' ],
			'1.0.0'
		);
	}
}
add_action( 'wp_enqueue_scripts', 'flixcity_enqueue_lp_nav_css', 30 );


//* Section scripts (Gallery + FAQ) — poprawnie przez hook (bez luzem wp_enqueue_script)
function flixcity_enqueue_section_scripts() {

	if ( ! is_singular( 'inwestycje' ) ) {
		return;
	}

	/* Gallery */
	if ( file_exists( get_template_directory() . '/assets/js/gallery.js' ) ) {
		wp_enqueue_script(
			'flix-gallery',
			get_template_directory_uri() . '/assets/js/gallery.js',
			[],
			'1.0.0',
			true
		);
	}

	/* FAQ */
	if ( file_exists( get_template_directory() . '/assets/js/faq.js' ) ) {
		wp_enqueue_script(
			'flixcity-faq',
			get_template_directory_uri() . '/assets/js/faq.js',
			[],
			'1.0.0',
			true
		);
	}

	/* Houses */
	if ( file_exists( get_template_directory() . '/assets/js/houses.js' ) ) {
		wp_enqueue_script(
			'flixcity-houses',
			get_template_directory_uri() . '/assets/js/houses.js',
			[],
			'1.0.0',
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'flixcity_enqueue_section_scripts', 40 );

