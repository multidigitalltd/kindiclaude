<?php
/**
 * Theme content options — single source of truth for editable site content.
 *
 * All editable strings/links live in one autoloaded option (`kindi_options`),
 * keeping reads cheap (one query, object-cache friendly). Patterns read values
 * via kindi_opt(); the admin panel writes them. Defaults below double as the
 * out-of-the-box content, so the site looks complete before any editing.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Default content values (also the design's out-of-the-box copy).
 *
 * @return array<string,mixed>
 */
function kindi_default_options(): array {
	return array(
		// General.
		'phone'         => '03-5293383',
		'whatsapp'      => '972500000000',
		'free_shipping' => 299,
		'shipbar'       => 'משלוח מהיר חינם מעל 299 ₪ | מועדון הלקוחות — 10% הנחה על הקנייה הראשונה',
		'ticker'        => "משלוח חינם בהזמנה מעל 299 ₪\nמועדון קינדי — 5% חזרה על כל קנייה\nקולקציית חזרה לבית הספר 2026 נחתה\nתשלום מאובטח SSL + PCI\nשירות אישי 03-5293383",

		// Hero.
		'hero_badge'    => 'חדש בקינדר טויס • קולקציית 2026',
		'hero_title1'   => 'עולם של',
		'hero_hl'       => 'קסם, משחק ויצירה',
		'hero_title2'   => 'בלחיצה אחת',
		'hero_lead'     => 'אלפי צעצועים, משחקים, חומרי יצירה וציוד לבית הספר ולגן — משלוח מהיר ושירות אישי מהלב.',
		'hero_cta1'     => 'לכל המבצעים החמים',
		'hero_cta1_url' => '/shop/?on_sale=1',
		'hero_cta2'     => 'לכל המוצרים',
		'hero_cta2_url' => '/shop/',

		// Club zone.
		'club_title'    => 'הצטרפו לחברים שלנו וקבלו עולם של הטבות!',
		'club_lead'     => 'חברים נהנים מהנחות בלעדיות, צוברים נקודות על כל קנייה ומקבלים מתנות מיוחדות ביום ההולדת.',
		'club_benefits' => "5% חזרה על כל קנייה\nמתנה ביום הולדת\nמבצעים בלעדיים\nמשלוח חינם מהיר",
		'club_cta_url'  => '/my-account/',

		// Store.
		'store_address' => 'הרב יעקב לנדא 7, בני ברק',
		'store_phone'   => '03-5293383',
		'store_hours'   => "א'-ה' 9:00-21:00 • ו' 9:00-14:00",
		'store_waze'    => 'https://waze.com/ul?q=הרב יעקב לנדא 7 בני ברק',
		'news_title'    => 'קבלו 10% הנחה על הזמנה ראשונה!',
		'news_sub'      => 'הירשמו לניוזלטר וקבלו עדכונים על מבצעים, מוצרים חדשים והטבות בלעדיות.',

		// Footer / social.
		'about'         => 'החנות המובילה לצעצועים, מכשירי כתיבה, חומרי יצירה וציוד לגני ילדים ובתי ספר. שירות אישי, מחירים הוגנים ואלפי לקוחות מרוצים.',
		'email'         => 'info@kindertoys.co.il',
		'fb'            => '#',
		'ig'            => '#',
	);
}

/**
 * Read a theme content option (falls back to the default).
 *
 * @param string $key     Option key.
 * @param mixed  $default Optional explicit fallback.
 * @return mixed
 */
function kindi_opt( string $key, $default = '' ) {
	static $opts = null;
	if ( null === $opts ) {
		$opts = get_option( 'kindi_options', array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
	}

	if ( isset( $opts[ $key ] ) && '' !== $opts[ $key ] ) {
		return $opts[ $key ];
	}

	$defaults = kindi_default_options();

	return $defaults[ $key ] ?? $default;
}

/**
 * Split a textarea option into trimmed, non-empty lines.
 *
 * @param string $key Option key.
 * @return array<int,string>
 */
function kindi_opt_lines( string $key ): array {
	$value = (string) kindi_opt( $key );
	$lines = preg_split( '/\r\n|\r|\n/', $value ) ?: array();

	return array_values( array_filter( array_map( 'trim', $lines ), 'strlen' ) );
}
