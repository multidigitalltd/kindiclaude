<?php
/**
 * Kindi icon library — inline SVGs ported 1:1 from the Lovable design
 * (src/components/icons/BrandIcons.tsx). Line-first, viewBox 24, navy stroke,
 * soft blue/red tints. Output is escaped-safe static markup.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Return an inline brand SVG icon.
 *
 * @param string $name  Icon key (e.g. 'truck', 'cart', 'star').
 * @param string $class Extra CSS classes for the <svg>.
 * @return string Safe SVG markup ('' if the icon is unknown).
 */
function kindi_icon( string $name, string $class = '' ): string {
	static $icons = null;
	if ( null === $icons ) {
		$icons = array(
		'accessibility' => array( 'stroke' => 'currentColor', 'body' => '<circle cx="11" cy="3.8" r="1.7" /> <path d="M11 5.8v4.7h4.2l2.3 5" /> <path d="M11 8.2h3.6" /> <circle cx="11" cy="16.2" r="5.2" /> <path d="M16.2 16.2h1.4" />' ),
		'apple' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 7c-2-1.5-6.5-1.5-8 1.5-2 3.5 0 10 3.5 12 1.5.7 3 0 4.5-.7 1.5.7 3 1.4 4.5.7 3.5-2 5.5-8.5 3.5-12-1.5-3-6-3-8-1.5z" fill="#FDE8EA" /> <path d="M12 7V5c0-1.2 1.2-2 2.5-2" /> <path d="M14 4c1.2 0 2 .8 2 2" />' ),
		'arrowleft' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M15 5 8 12l7 7" stroke="currentColor" stroke-width="1.4" />' ),
		'baby' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="9" r="5.5" fill="#E8F0FE" /> <circle cx="10" cy="9" r="0.7" fill="#1B2A52" stroke="none" /> <circle cx="14" cy="9" r="0.7" fill="#1B2A52" stroke="none" /> <path d="M10.5 11.5c.7.5 2.3.5 3 0" /> <path d="M6.5 7.5C6.5 5 9 4 12 4s5.5 1 5.5 3.5" /> <path d="M7 19c1.5-2.5 3-3 5-3s3.5.5 5 3" fill="#FDE8EA" />' ),
		'backpack' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M9 6c0-1.8 1.4-3 3-3s3 1.2 3 3" /> <rect x="4.5" y="6" width="15" height="15" rx="3" fill="#E8F0FE" /> <rect x="8" y="11" width="8" height="6" rx="1" fill="white" /> <path d="M4.5 10h15" /> <circle cx="12" cy="14" r="0.9" fill="#E63946" stroke="none" />' ),
		'ball' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="12" r="8.5" fill="white" /> <path d="M12 3.5v17M3.5 12h17M5.5 6.5c4 1 9 1 13 0M5.5 17.5c4-1 9-1 13 0" />' ),
		'blocks' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="2.5" y="12.5" width="8" height="8" rx="1" fill="#FDE8EA" /> <rect x="13.5" y="12.5" width="8" height="8" rx="1" fill="#E8F0FE" /> <rect x="8" y="3.5" width="8" height="8" rx="1" fill="#E8F0FE" /> <circle cx="6.5" cy="11.5" r="0.6" fill="#1B2A52" stroke="none" /> <circle cx="17.5" cy="11.5" r="0.6" fill="#1B2A52" stroke="none" /> <circle cx="10" cy="2.5" r="0.6" fill="#1B2A52" stroke="none" /> <circle cx="14" cy="2.5" r="0.6" fill="#1B2A52" stroke="none" />' ),
		'bottle' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="9" y="2.5" width="6" height="2" rx="0.6" fill="#FDE8EA" /> <rect x="8.5" y="4.5" width="7" height="2.5" rx="0.8" fill="#E63946" stroke="#1B2A52" /> <path d="M8.5 7h7v11a2.5 2.5 0 0 1-2.5 2.5h-2A2.5 2.5 0 0 1 8.5 18z" fill="#E8F0FE" /> <path d="M9 12h6M9 15h6" stroke-dasharray="1.5 1.5" />' ),
		'car' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M4 13h16l-1.5-4.5C18 7.5 17.5 7 16.5 7h-9C6.5 7 6 7.5 5.5 8.5z" fill="#FDE8EA" /> <rect x="3" y="13" width="18" height="5" rx="1.2" fill="#E8F0FE" /> <circle cx="7" cy="19" r="1.5" fill="white" stroke="#1B2A52" /> <circle cx="17" cy="19" r="1.5" fill="white" stroke="#1B2A52" />' ),
		'cart' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M2.5 3.5h2.5l1 2.5M6 6h15l-2.2 8.5H8.5z" fill="#E8F0FE" /> <circle cx="9.5" cy="19" r="1.4" fill="#E63946" stroke="#1B2A52" /> <circle cx="17" cy="19" r="1.4" fill="#E63946" stroke="#1B2A52" />' ),
		'check' => array( 'stroke' => '#1B2A52', 'body' => '<path d="m4.5 12 5 5L20 6.5" stroke="currentColor" stroke-width="1.5" />' ),
		'chevrondown' => array( 'stroke' => '#1B2A52', 'body' => '<path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.4" />' ),
		'clock' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="12" r="8.5" fill="#E8F0FE" /> <path d="M12 7v5l3.5 2" stroke="#E63946" stroke-width="1.4" />' ),
		'close' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="1.4" />' ),
		'crown' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M3 8.5 6.5 12 9 6.5 12 11l3-4.5L17.5 12 21 8.5V18H3z" fill="#E8F0FE" /> <path d="M3 17h18" stroke="#E63946" stroke-width="1.3" /> <circle cx="12" cy="14" r="0.9" fill="#FFD43B" stroke="none" />' ),
		'dice' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3 21 8v8l-9 5-9-5V8z" fill="#E8F0FE" /> <path d="m3 8 9 5 9-5M12 13v8" /> <circle cx="7" cy="11" r="0.8" fill="#E63946" stroke="none" /> <circle cx="17" cy="11" r="0.8" fill="#E63946" stroke="none" /> <circle cx="12" cy="17" r="0.8" fill="#E63946" stroke="none" />' ),
		'fire' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3c1 3 5 5 5 9a5 5 0 0 1-10 0c0-2 1-3 2-4 0 2 1 3 2 3 0-3-1-5 1-8z" fill="#FDE8EA" stroke="#E63946" /> <path d="M11 16a2 2 0 1 0 2 0c0-1-1-1.5-1-2.5-.5.5-1 1.5-1 2.5z" fill="#FFD43B" stroke="none" />' ),
		'gamepad' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M6 7h12a3 3 0 0 1 3 3v5a3 3 0 0 1-5.5 1.5L14 15h-4l-1.5 1.5A3 3 0 0 1 3 15v-5a3 3 0 0 1 3-3z" fill="#E8F0FE" /> <path d="M7 11v2M6 12h2" stroke="#1B2A52" stroke-width="1.4" /> <circle cx="16" cy="11" r="0.9" fill="#E63946" stroke="none" /> <circle cx="18" cy="13" r="0.9" fill="#2D6CDF" stroke="none" />' ),
		'gem' => array( 'stroke' => '#1B2A52', 'body' => '<path d="m4.5 9 3.5-5h8l3.5 5L12 21z" fill="#E8F0FE" /> <path d="m4.5 9 7.5 3 7.5-3M8 4l4 8 4-8" />' ),
		'gift' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="3" y="10" width="18" height="11" rx="1.5" fill="#FDE8EA" /> <rect x="2" y="7" width="20" height="4" rx="1" fill="#E8F0FE" /> <path d="M12 7v14" stroke="#E63946" stroke-width="1.4" /> <path d="M12 7c-2-3-5-2-5 0s3 1 5 0zM12 7c2-3 5-2 5 0s-3 1-5 0z" fill="white" />' ),
		'grid' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.2" fill="#E8F0FE" /> <rect x="13.5" y="3.5" width="7" height="7" rx="1.2" fill="#FDE8EA" /> <rect x="3.5" y="13.5" width="7" height="7" rx="1.2" fill="#FDE8EA" /> <rect x="13.5" y="13.5" width="7" height="7" rx="1.2" fill="#E8F0FE" />' ),
		'heart' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 20.5S3.5 14.5 3.5 8.5A4.5 4.5 0 0 1 12 6a4.5 4.5 0 0 1 8.5 2.5c0 6-8.5 12-8.5 12z" fill="#FDE8EA" />' ),
		'mail' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="3" y="5.5" width="18" height="13" rx="1.5" fill="#E8F0FE" /> <path d="m3 6.5 9 6.5 9-6.5" />' ),
		'menu' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M4 7h16M4 12h16M4 17h10" stroke="#1B2A52" stroke-width="1.4" />' ),
		'palette' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3C7 3 3 6.5 3 11.5S7 20 12 20c1.2 0 1.8-.7 1.8-1.5 0-1.4-1.3-1.4-1.3-2.7 0-.8.7-1.3 1.5-1.3H17c2.5 0 4-2 4-4.5C21 6.5 17 3 12 3z" fill="#E8F0FE" /> <circle cx="7" cy="11" r="1.1" fill="#E63946" stroke="none" /> <circle cx="10" cy="7.5" r="1.1" fill="#2D6CDF" stroke="none" /> <circle cx="14" cy="7.5" r="1.1" fill="#1B2A52" stroke="none" /> <circle cx="17" cy="11" r="1.1" fill="#FFD43B" stroke="none" />' ),
		'party' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M4 20 13 7l4 4z" fill="#FDE8EA" /> <path d="m13 7 1.5-2.5L17 4l-.5 2.5L19 8l-2 .5z" fill="#E8F0FE" /> <circle cx="7" cy="14" r="0.7" fill="#2D6CDF" stroke="none" /> <circle cx="10" cy="11" r="0.7" fill="#FFD43B" stroke="none" /> <circle cx="6" cy="18" r="0.7" fill="#E63946" stroke="none" />' ),
		'pencil' => array( 'stroke' => '#1B2A52', 'body' => '<path d="m4 20 2.5-.7L18.5 7.3l-1.8-1.8L4.5 17.5z" fill="#E8F0FE" /> <path d="m16 5.5 2-2 2.5 2.5-2 2z" fill="#FDE8EA" /> <path d="m4 20 .8-2.5 1.7 1.8z" fill="#1B2A52" stroke="none" />' ),
		'phone' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M5 3.5h4l2 4.5-2.5 1.5a11 11 0 0 0 5 5l1.5-2.5 4.5 2v4c0 1-.8 2-2 2C9.5 20 4 14.5 4 5.5c0-1.2 1-2 2-2z" fill="#E8F0FE" />' ),
		'pin' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3c4 0 7 3 7 7 0 5-7 11.5-7 11.5S5 15 5 10c0-4 3-7 7-7z" fill="#FDE8EA" /> <circle cx="12" cy="10" r="2.5" fill="white" />' ),
		'play' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="12" r="9" fill="#FDE8EA" /> <path d="M10 8.5v7l6-3.5z" fill="#E63946" />' ),
		'plus' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.5" />' ),
		'puzzle' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M3 4h7v4.5a1.5 1.5 0 0 0 1.5-1.5 1.5 1.5 0 0 1 3 0 1.5 1.5 0 0 0 1.5 1.5H21V15h-4.5a1.5 1.5 0 0 1-1.5-1.5 1.5 1.5 0 0 0-3 0 1.5 1.5 0 0 1-1.5 1.5V21H3v-4.5a1.5 1.5 0 0 1 1.5-1.5 1.5 1.5 0 0 0 0-3A1.5 1.5 0 0 1 3 10.5z" fill="#E8F0FE" />' ),
		'rocket' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3c3 2 5 5.5 5 9.5V17h-2.5L12 19.5 9.5 17H7v-4.5C7 8.5 9 5 12 3z" fill="#FDE8EA" /> <circle cx="12" cy="10" r="1.8" fill="white" /> <circle cx="12" cy="10" r="0.7" fill="#2D6CDF" stroke="none" /> <path d="M7 14 4.5 16.5 7 16zM17 14l2.5 2.5L17 16z" fill="#E8F0FE" /> <path d="M10.5 19.5c0 1 1.5 2 1.5 2s1.5-1 1.5-2" stroke="#FFD43B" stroke-width="1.3" />' ),
		'rotate' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M20.5 12a8.5 8.5 0 1 1-3-6.5" /> <path d="M20.5 3v4.5H16" stroke="#E63946" stroke-width="1.4" />' ),
		'search' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="10.5" cy="10.5" r="6.5" fill="#E8F0FE" /> <path d="m15.5 15.5 5 5" stroke="#E63946" stroke-width="1.5" />' ),
		'shield' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 2.5 4 5v6.5c0 5 3.5 8.5 8 10 4.5-1.5 8-5 8-10V5z" fill="#E8F0FE" /> <path d="m8.5 12 2.5 2.5L16 9.5" stroke="#E63946" stroke-width="1.5" />' ),
		'sparkles' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3 13.5 9 19.5 10.5 13.5 12 12 18 10.5 12 4.5 10.5 10.5 9z" fill="#E8F0FE" /> <path d="M18.5 3.5 19 5.5 21 6 19 6.5 18.5 8.5 18 6.5 16 6 18 5.5z" fill="#FFD43B" stroke="#E63946" stroke-width="1" /> <circle cx="5" cy="18" r="0.8" fill="#FFD43B" stroke="none" />' ),
		'star' => array( 'stroke' => '#E63946', 'body' => '<path d="m12 3 2.8 5.7 6.2.9-4.5 4.4 1 6.2L12 17.3 6.5 20.2l1-6.2L3 9.6l6.2-.9z" fill="#E63946" />' ),
		'store' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M3.5 9 5 4.5h14L20.5 9z" fill="#FDE8EA" /> <path d="M5 9v10.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V9" fill="#E8F0FE" /> <path d="M3.5 9h17" stroke="#E63946" stroke-width="1.3" /> <path d="M9.5 20.5v-4a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1v4" fill="white" /> <circle cx="13" cy="18" r="0.5" fill="#E63946" stroke="none" />' ),
		'bank' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3 3.5 7.5h17z" fill="#FDE8EA" /> <path d="M3.5 7.5h17" stroke="#E63946" stroke-width="1.3" /> <path d="M5.5 11v5.5M9.5 11v5.5M14.5 11v5.5M18.5 11v5.5" /> <rect x="3" y="18" width="18" height="2.5" rx="0.6" fill="#E8F0FE" /> <circle cx="12" cy="5.6" r="0.6" fill="#E63946" stroke="none" />' ),
		'doc' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M6 3h8l4 4v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1z" fill="#E8F0FE" /> <path d="M14 3v4h4" fill="#FDE8EA" /> <path d="M8.5 12h7M8.5 15h7M8.5 18h4.5" stroke="#E63946" stroke-width="1.2" />' ),
		'sun' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="12" r="4.5" fill="#FFD43B" stroke="#1B2A52" /> <path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3M5 5l2 2M17 17l2 2M5 19l2-2M17 7l2-2" stroke="#E63946" stroke-width="1.3" />' ),
		'tag' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M3.5 12.5V4h8.5l9 9-8.5 8.5z" fill="#E8F0FE" /> <circle cx="7.5" cy="8" r="1.3" fill="#E63946" stroke="#1B2A52" />' ),
		'teddy' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="6.5" cy="7" r="2" fill="#FDE8EA" /> <circle cx="17.5" cy="7" r="2" fill="#FDE8EA" /> <circle cx="12" cy="14" r="6.5" fill="#E8F0FE" /> <circle cx="10" cy="13" r="0.8" fill="#1B2A52" stroke="none" /> <circle cx="14" cy="13" r="0.8" fill="#1B2A52" stroke="none" /> <path d="M10.5 16c.7.8 2.3.8 3 0" /> <circle cx="12" cy="15" r="0.6" fill="#E63946" stroke="none" />' ),
		'truck' => array( 'stroke' => '#1B2A52', 'body' => '<rect x="1.5" y="7" width="12" height="9" rx="1.5" fill="#E8F0FE" /> <path d="M13.5 9.5h4l3 3v3.5h-7z" fill="#FDE8EA" /> <path d="M1.5 16h19" /> <circle cx="7" cy="17.5" r="1.8" fill="white" /> <circle cx="16.5" cy="17.5" r="1.8" fill="white" /> <path d="M13.5 7v9M17.5 9.5h-4M20.5 12.5h-3" /> <circle cx="20" cy="6" r="0.9" fill="#FFD43B" stroke="none" />' ),
		'user' => array( 'stroke' => '#1B2A52', 'body' => '<circle cx="12" cy="8" r="3.5" fill="#E8F0FE" /> <path d="M4.5 20c1-4 4-6 7.5-6s6.5 2 7.5 6" fill="#E8F0FE" />' ),
			'whatsapp' => array( 'stroke' => '#1B2A52', 'body' => '<path d="M12 3a9 9 0 0 0-7.7 13.7L3 21l4.5-1.2A9 9 0 1 0 12 3z" fill="#25D366" stroke="none" /> <path d="M8.5 8.2c0-.7.7-1.2 1.3-1.2h.6l1.3 2.6-1.3.7c0 1.4 1.3 2.7 2.7 2.7l.7-1.3 2.6 1.3v.6c0 .6-.5 1.3-1.2 1.3-3.5 0-6.7-3.2-6.7-6.7z" fill="white" stroke="none" />' ),
		);
	}

	if ( ! isset( $icons[ $name ] ) ) {
		return '';
	}

	$icon  = $icons[ $name ];
	$class = trim( 'kindi-icon ' . $class );

	return sprintf(
		'<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="%s" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
		esc_attr( $class ),
		esc_attr( $icon['stroke'] ),
		$icon['body']
	);
}
