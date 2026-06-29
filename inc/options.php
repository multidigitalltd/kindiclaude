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
		'whatsapp_hours'       => "א'-ה' 9:00-21:00 • ו' 9:00-14:00",
		'whatsapp_from'        => 9,
		'whatsapp_to'          => 21,
		'whatsapp_product_msg' => "היי, יש לי שאלה על המוצר:\n{product}\n{url}",
		'free_shipping' => 299,

		// Product schema — shipping & returns (Google Merchant Listing fields).
		'ship_cost'     => 29,   // Flat domestic shipping rate (₪); 0 = always free.
		'ship_days_min' => 1,    // Transit time lower bound (business days).
		'ship_days_max' => 4,    // Transit time upper bound (business days).
		'return_days'   => 14,   // Return window (days); 0 = no returns accepted.

		// Product feed (Google Merchant / Meta catalog).
		'google_category' => '', // Default Google product category (ID or full path); per-product meta wins.
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

		// Homepage categories + products control.
		'home_cats_mode'       => 'auto',
		'home_cats'            => array(),
		'home_products_source' => 'popularity',
		'home_products_cat'    => '',
		'home_products_count'  => 10,

		// Homepage mascots (control-panel uploads; blank = bundled default) + heading tag.
		'hero_mascot'         => '',
		'kindyzone_mascot'    => '',
		'storeinfo_mascot'    => '',
		'values_mascot'       => '',
		'section_heading_tag' => 'h2',
		'phone'               => '03-5293383',

		// Promo banners (change often).
		'promo1_badge' => 'מוגבל בזמן',
		'promo1_title' => 'חזרה לבית הספר — עד 40%- על הכל!',
		'promo1_sub'   => 'ילקוטים, קלמרים, מחברות, עפרונות ועוד מאות מוצרים במחירי השקה',
		'promo1_cta'   => 'לכל המבצעים',
		'promo1_url'   => '/shop/?on_sale=1',
		'promo1_img'   => '',
		'promo2_badge' => 'חדש בקינדי',
		'promo2_title' => 'גמבוי כשר X3',
		'promo2_sub'   => '222 משחקים',
		'promo2_cta'   => 'לרכישה',
		'promo2_url'   => '/shop/',
		'promo2_img'   => '',
		'promo3_badge' => 'מחיר מיוחד',
		'promo3_title' => 'לגו בכל הסדרות',
		'promo3_sub'   => 'החל מ-19.90 ₪',
		'promo3_cta'   => 'לקולקציה',
		'promo3_url'   => '/shop/',
		'promo3_img'   => '',

		// Club zone.
		'club_title'    => 'הצטרפו לחברים שלנו וקבלו',
		'club_title_hl' => 'עולם של הטבות!',
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

		// Accessibility statement.
		'a11y_statement' => '',

		// Cookie consent banner.
		'cookie_enable'      => '1',
		'cookie_text'        => 'אתר זה עושה שימוש בעוגיות (cookies) כדי לשפר את חוויית הגלישה, להתאים תוכן ולנתח תנועה. המשך הגלישה מהווה הסכמה לשימוש בעוגיות.',
		'cookie_btn'         => 'הבנתי, מאשר/ת',
		'cookie_policy_url'  => '/privacy-policy/',
		'cookie_policy_text' => 'מדיניות הפרטיות',

		// Newsletter → external mailing list (webhook).
		'newsletter_webhook' => '',
		'newsletter_field'   => 'email',
		'newsletter_secret'  => '',

		// Back-in-stock (waitlist) email.
		'wl_email_subject' => '{product} חזר למלאי! 🎉',
		'wl_email_body'    => "היי {name},\n\nהמוצר \"{product}\" שחיכית לו חזר למלאי!\nכדאי למהר — הכמות מוגבלת.\n\nצוות קינדר טויס",

		// Saved cart — emails + reminder timing.
		'cart_reminder_delay'   => 24,
		'cart_email_subject'    => 'שמרנו לך את העגלה 🛒',
		'cart_email_body'       => "היי,\n\nשמרנו את העגלה שלך ב-{site} כדי שתוכל/י לחזור אליה בכל רגע.\nאפשר גם לשתף את הקישור עם מישהו אחר.",
		'cart_reminder_subject' => 'העגלה שלך עדיין מחכה 💝',
		'cart_reminder_body'    => "היי,\n\nרק תזכורת קטנה — העגלה ששמרת ב-{site} עדיין כאן.\nהפריטים אזלו בעבר במהירות, אז כדאי להשלים את ההזמנה.",

		// Google reviews — real reviews come from the plugin DB; these are backup.
		'reviews_manual' => "מיכל א. | 5 | שירות מעולה ומשלוח מהיר, הילדים מאושרים!\nיוסי ב. | 5 | מבחר ענק ומחירים הוגנים. ממליץ בחום.\nרינת ל. | 5 | חוויית קנייה מצוינת ושירות אישי מהלב.",
		'reviews_rating' => '4.9',
		'reviews_count'  => 0,
		'reviews_link'   => '',

		// Google reviews — optional Places API fallback (leave empty to use showcase).
		'google_place_id'  => '',
		'google_api_key'   => '',

		// Custom-field (ACF) source-key mapping for toy fields.
		// Pre-mapped to the site's existing ACF field names so data is adopted
		// out of the box; override from the panel if a field is renamed.
		'acf_key_age'          => 'age_fit',
		'acf_key_brand'        => 'brand',
		'acf_key_skills'       => 'skills',
		'acf_key_players'      => 'users',
		'acf_key_play_time'    => '',
		'acf_key_pieces'       => '',
		'acf_key_archive_desc' => 'תאור_תחתון_לארכיון',

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
