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
		'gift_wrap_enable' => '1', // Gift-wrap + greeting-card box on checkout.

		// Product schema — shipping & returns (Google Merchant Listing fields).
		'ship_cost'     => 29,   // Flat domestic shipping rate (₪); 0 = always free.
		'ship_days_min' => 2,    // Transit time lower bound (business days).
		'ship_days_max' => 4,    // Transit time upper bound (business days).
		'return_days'   => 14,   // Return window (days); 0 = no returns accepted.

		// Product feed (Google Merchant / Meta catalog).
		'google_category' => '', // Default Google product category (ID or full path); per-product meta wins.

		// Meta (Facebook) Pixel — native, lean replacement for PixelYourSite.
		'fb_pixel_id'         => '',
		'pixel_enable'        => '1',
		'px_view_content'     => '1',
		'px_add_to_cart'      => '1',
		'px_initiate_checkout' => '1',
		'px_purchase'         => '1',
		'px_view_category'    => '1',
		'px_search'           => '1',
		'px_404'              => '1',
		'px_signup'           => '1',
		'px_login'            => '1',
		'px_scroll'           => '1',
		'px_time'             => '1',
		'px_downloads'        => '1',
		'px_forms'            => '1',
		'px_comments'         => '1',
		'px_native_reporting' => '1',
		'ticker'        => "משלוח חינם מעל ₪299 (למעט ריהוט)\nמועדון קינדי — 5% חזרה על כל קנייה\nקולקציית חזרה לבית הספר 2026 נחתה\nתשלום מאובטח SSL + PCI\nשירות אישי 03-5293383",
		'header_promo'  => 'משלוח מהיר חינם מעל 299 ₪ • מועדון הלקוחות — 10% הנחה על הקנייה הראשונה',
		'bundle_coupon' => '',

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
		'hero_rating'      => '4.9',
		'hero_rating_note' => '(+50,000 הורים)',
		'hero_trust1'      => 'משלוח מחר עד הבית',
		'hero_trust2'      => '+10,000 מוצרים במלאי',

		// Footer FAQ (site-wide accordion above the footer). Items format: blocks
		// separated by an empty line — first line is the question, the rest is the
		// answer. Empty items = the whole section is hidden.
		'faq_title'       => 'שאלות ותשובות',
		'faq_intro'       => 'ריכזנו כאן את השאלות שאתם הכי שואלים — משלוחים, תשלום, מועדון והחזרות. לא מצאתם תשובה? דברו איתנו ונשמח לעזור.',
		'faq_items'       => "תוך כמה זמן ההזמנה מגיעה?\nמשלוח עד הבית מגיע תוך 2-4 ימי עסקים. באזורים רחוקים, קיבוצים ויישובים — עד 6 ימי עסקים.\n---\nכמה עולה המשלוח?\nמשלוח עד הבית עולה ₪29, ובהזמנה מעל ₪299 המשלוח חינם (למעט מוצרי ריהוט).\n---\nאפשר לאסוף עצמאית מהחנות?\nבוודאי! איסוף עצמי מהחנות — חינם, בתיאום מראש.\n---\nהאם התשלום באתר מאובטח?\nכן. כל ההזמנות מאובטחות בתקן PCI המחמיר ביותר והתשלום מוצפן SSL.\n---\nמה זה מועדון קינדי ומה מקבלים בו?\nחברי המועדון נהנים מנקודות על כל קנייה, הנחות ומתנות. ההצטרפות חינם — מצטרפים בלחיצה בדף הבית.\n---\nאיך מבטלים עסקה או מחזירים מוצר?\nממלאים את טופס ביטול העסקה באתר או פונים אלינו, ואנחנו מטפלים בהתאם לתקנון ולחוק הגנת הצרכן.\n---\nקניתי מתנה — יש עטיפת מתנה?\nכן! בדף התשלום אפשר להוסיף עטיפת מתנה להזמנה.\n---\nאיך יוצרים קשר עם שירות הלקוחות?\nבוואטסאפ מכל עמוד באתר, בטלפון או בטופס יצירת הקשר — עונים מהר, מהלב.",
		'faq_outro_title' => 'מילה אחרונה, מהלב',
		'update_token'    => '',

		// Order-completed webhook (Pabbly / Zapier / Make). Off until switched on.
		'webhook_enable'  => '0',
		'webhook_url'     => '',

		// Flashy — newsletter contact push + product-reviews element.
		'flashy_key'     => '',
		'flashy_list'    => '',
		'flashy_reviews' => '1480',

		// Order-email banner images — a separate pair per email.
		'email_proc_top'    => '',
		'email_proc_bottom' => '',
		'email_done_top'    => '',
		'email_done_bottom' => '',
		'faq_outro'       => 'קינדר טויס היא חנות משפחתית עם אלפי מוצרים, שירות אישי ואהבה אמיתית לילדים ולמשחק. אנחנו כאן לכל שאלה — תמיד.',

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


		// Cookie consent banner.
		'cookie_enable'      => '1',
		'cookie_text'        => 'אתר זה עושה שימוש בעוגיות (cookies) כדי לשפר את חוויית הגלישה, להתאים תוכן ולנתח תנועה. המשך הגלישה מהווה הסכמה לשימוש בעוגיות.',
		'cookie_btn'         => 'הבנתי, מאשר/ת',
		'cookie_policy_url'  => '/privacy-policy/',
		'cookie_policy_text' => 'מדיניות הפרטיות',

		// Newsletter → external mailing list (webhook).

		// Back-in-stock (waitlist) email.
		'wl_email_subject' => '{product} חזר למלאי!',
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

		// Legacy term-meta key for the category bottom description — the ACF
		// import tool is gone (its one-time job is done), but archive-desc.php
		// still reads this key at runtime as a fallback for term meta that was
		// created by ACF (the data outlives the removed plugin).
		'acf_key_archive_desc' => 'תאור_תחתון_לארכיון',

		// כפתור וואטסאפ צף בפינת המסך.
		'whatsapp_float_enable' => '1',

		// מינימום הזמנה למשלוח (₪, 0 = כבוי) — איסוף עצמי תמיד אפשרי.
		'min_order_amount'  => '50',
		'min_order_enforce' => '1',
		'min_order_msg'    => 'שימו לב: לא ניתן להשלים הזמנה למשלוח בסכום נמוך מ־{amount}. אנא חזרו לסל והוסיפו מוצרים, או בחרו באיסוף עצמי מהחנות – הרב יעקב לנדא 7, בני ברק.',

		// פופאפ שמירת עגלה — נפתח פעם אחת אחרי הוספת 2 מוצרים ומעלה לסל.
		'savecart_popup_enable' => '1',

		// פופאפ הודעת חגים (משלוחים) — נפתח אחרי הוספה לסל ומוצג בעמוד התשלום.
		'holiday_enable' => '1',
		'holiday_title'  => 'הודעה חשובה – משלוחים לקראת החגים',
		'holiday_text'   => "לקוחות יקרים,\n\nכל הזמנה שתתבצע עד לתאריך 07.09.2026 תסופק עד לתאריך 16.09.2026.\n\nשימו לב: בעקבות עומס החגים, ייתכנו עיכובים במערכת המשלוחים. אנו עושים כל שביכולתנו לספק את ההזמנות במועד, ומודים לכם על ההבנה.\n\nבברכת שנה טובה,\nצוות קינדר טויס",

		// Footer / social.
		'about'         => 'החנות המובילה לצעצועים, מכשירי כתיבה, חומרי יצירה וציוד לגני ילדים ובתי ספר. שירות אישי, מחירים הוגנים ואלפי לקוחות מרוצים.',
		'email'         => 'office@kindertoys.co.il',
		'fb'            => '#',
		'ig'            => '#',
		'yt'            => '#',
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
