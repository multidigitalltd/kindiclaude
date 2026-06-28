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
.kindi-shipbar{background:#1B2A52;color:#fff;font-size:.875rem;padding:.5rem 1rem;text-align:center;font-weight:500}
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
@media(min-width:1024px){.kindi-nav__inner{overflow-x:visible}.kindi-hero__grid{grid-template-columns:1.1fr 1fr}}
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
	// icons, nav and mega never flash unstyled; only defer heavier page-specific
	// stylesheets.
	$async = array( 'kindi-sections', 'kindi-animations', 'kindi-woocommerce' );
	if ( ! in_array( $handle, $async, true ) ) {
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
