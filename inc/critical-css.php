<?php
/**
 * Critical CSS + non-blocking stylesheet loading.
 *
 * Inlines a small above-the-fold critical stylesheet so first paint is instant,
 * then loads the theme's full stylesheets asynchronously (preload→onload swap)
 * so they no longer block rendering — improving LCP/FCP without FOUC of the
 * visible header/hero. Toggle with the `kindi_use_critical_css` filter.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Whether the optimisation is active.
 *
 * @return bool
 */
function kindi_critical_css_enabled(): bool {
	return (bool) apply_filters( 'kindi_use_critical_css', true );
}

/**
 * The hand-tuned above-the-fold critical CSS.
 *
 * @return string
 */
function kindi_critical_css(): string {
	return <<<'CSS'
*{box-sizing:border-box}html{direction:rtl}body{margin:0;background:#FAFBFC;color:#15233f;-webkit-font-smoothing:antialiased}
img{max-width:100%;height:auto}a{color:#E63946;text-decoration:none}
.skip-link{position:absolute;top:-200px;left:1rem}
.kindi-header{position:sticky;top:0;z-index:100}
.kindi-topbar{background:linear-gradient(to left,#E63946,#b81f2c 55%,#1B2A52);color:#fff;overflow:hidden}
.kindi-topbar__track{display:flex;flex-wrap:wrap;justify-content:center;padding-block:.5rem;padding-inline:1rem}
.kindi-topbar__item{display:inline-flex;align-items:center;gap:.5rem;padding-inline:1.25rem;font-size:13px;font-weight:600}
.kindi-bar{position:relative;z-index:3;background:#fff;border-bottom:1px solid #e7eaf0;max-width:1440px;margin-inline:auto;height:5rem;display:flex;align-items:center;gap:1rem;padding-inline:1rem}
.kindi-bar__logo img{height:3.25rem;width:auto}
.kindi-bar__actions{display:flex;align-items:center;gap:.5rem;margin-inline-start:auto}
.kindi-nav{background:#1B2A52;color:#fff;display:block}
.kindi-nav__inner{display:flex;overflow-x:auto;gap:.25rem;min-height:3rem;padding-inline:1rem;max-width:1440px;margin-inline:auto}
.kindi-main{max-width:1440px;margin-inline:auto;padding-inline:1rem}
.kindi-section{margin-block:3rem}
.kindi-hero{position:relative;overflow:hidden;border-radius:2rem;padding:2rem;border:1px solid rgba(27,42,82,.1);background:linear-gradient(to bottom left,#e7effe,#fff 50%,#fde9ec)}
.kindi-hero__grid{position:relative;display:grid;gap:1.5rem;align-items:center}
.kindi-hero__title{font-family:'Ploni Yad','PloniYad',system-ui,sans-serif;font-weight:900;line-height:1.05;color:#1B2A52;font-size:clamp(1.875rem,1.3rem + 3vw,3rem);margin:0}
.kindi-hero__lead{color:rgba(27,42,82,.75);max-width:32rem;line-height:1.7;font-weight:500}
.kindi-btn{display:inline-flex;align-items:center;gap:.5rem;font-weight:700;border-radius:1rem;padding:.75rem 1.5rem;text-decoration:none}
.kindi-btn--red{background:#E63946;color:#fff}
@media(min-width:1024px){.kindi-hero__grid{grid-template-columns:1.1fr 1fr}}
CSS;
}

/**
 * Print critical CSS in the head.
 *
 * @return void
 */
function kindi_print_critical_css(): void {
	if ( ! kindi_critical_css_enabled() || is_admin() ) {
		return;
	}
	echo '<style id="kindi-critical">' . kindi_critical_css() . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'wp_head', 'kindi_print_critical_css', 2 );

/**
 * Preload the hero mascot (the mobile LCP image) on the front page so it starts
 * downloading immediately instead of after HTML parsing reaches it.
 *
 * @return void
 */
function kindi_preload_lcp_image(): void {
	if ( ! is_front_page() || ! function_exists( 'kindi_mascot_src' ) ) {
		return;
	}
	$src = kindi_mascot_src( 'hero_mascot', 'mascot/kindy-hero.webp' );
	if ( '' === $src ) {
		return;
	}

	// Imagify rewrites the rendered <img> into an AVIF <picture>, so preloading
	// the original PNG/JPG would download the image TWICE. When a next-gen twin
	// exists (Imagify stores it alongside as <file>.avif / <file>.webp), preload
	// that instead; if the source is being rewritten but no twin is found yet,
	// skip the preload — better none than a wasted double download.
	$type = '';
	if ( defined( 'IMAGIFY_VERSION' ) && ! preg_match( '/\.(webp|avif)$/i', $src ) ) {
		$uploads = wp_get_upload_dir();
		if ( 0 !== strpos( $src, (string) $uploads['baseurl'] ) ) {
			return; // Non-uploads original that will be rewritten — can't verify a twin.
		}
		$path  = str_replace( (string) $uploads['baseurl'], (string) $uploads['basedir'], $src );
		$found = '';
		foreach ( array( 'avif', 'webp' ) as $ext ) {
			if ( file_exists( $path . '.' . $ext ) ) {
				$found = $ext;
				break;
			}
		}
		if ( '' === $found ) {
			return;
		}
		$src  .= '.' . $found;
		$type  = ' type="image/' . $found . '"';
	}

	// The hero mascot only shows at ≥1024px, so preload it there only —
	// otherwise mobile would download a hidden image and slow its LCP.
	printf( '<link rel="preload" as="image" href="%s" media="(min-width: 1024px)" fetchpriority="high"%s>' . "\n", esc_url( $src ), $type ); // phpcs:ignore WordPress.Security.EscapeOutput -- $type built from a fixed whitelist.
}
add_action( 'wp_head', 'kindi_preload_lcp_image', 1 );

/**
 * Convert the theme's stylesheet links to non-blocking (preload→onload swap).
 *
 * @param string $tag    Link tag.
 * @param string $handle Style handle.
 * @return string
 */
function kindi_async_styles( string $tag, string $handle ): string {
	if ( ! kindi_critical_css_enabled() || is_admin() ) {
		return $tag;
	}

	// Keep the global chrome (base + components) render-blocking so the header,
	// icons, nav and mega never flash unstyled; defer heavier page-specific
	// stylesheets — including WooCommerce's own (the main render-blocking cost on
	// mobile). They still apply via the preload→onload swap, just non-blocking.
	$async = array(
		'kindi-sections', 'kindi-animations', 'kindi-woocommerce',
		'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-general',
		'woocommerce-blocktheme', 'wc-blocks-style', 'wc-blocks-packages-style',
		'brands-styles', 'aos',
	);

	// Third-party plugin stylesheets are the remaining render-blocking cost on
	// mobile. Outside the WooCommerce funnel pages (cart/checkout/account, where
	// plugin UI must never flash unstyled), swap EVERY stylesheet that isn't
	// core chrome to non-blocking as well. Unknown handles simply keep working —
	// they just stop delaying first paint.
	$keep_blocking = array(
		'kindi-base', 'kindi-components', 'kindi-content', 'kindi-about',
		'wp-block-library', 'global-styles', 'admin-bar', 'dashicons',
	);
	$is_funnel = ( function_exists( 'is_cart' ) && is_cart() )
		|| ( function_exists( 'is_checkout' ) && is_checkout() )
		|| ( function_exists( 'is_account_page' ) && is_account_page() );

	$should_async = in_array( $handle, $async, true )
		|| ( ! $is_funnel && ! in_array( $handle, $keep_blocking, true ) );
	if ( ! $should_async ) {
		return $tag;
	}

	// Build the non-blocking variant + <noscript> fallback.
	$preload = str_replace( "rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $tag );
	if ( $preload === $tag ) {
		$preload = str_replace( 'rel="stylesheet"', 'rel="preload" as="style" onload="this.onload=null;this.rel=\'stylesheet\'"', $tag );
	}

	return $preload . '<noscript>' . $tag . '</noscript>' . "\n";
}
add_filter( 'style_loader_tag', 'kindi_async_styles', 20, 2 );
