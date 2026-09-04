<?php
/**
 * Admin content panel — "קינדי" settings screen.
 *
 * A native, dependency-free control panel (Settings-style) for every editable
 * string and link on the site. Saves into the single `kindi_options` option.
 * Security: capability check + nonce on save; each field sanitised by type.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Tabs → sections → fields. Frequently-changed content lives in the "מבצעים
 * ותוכן" tab; rarely-touched section copy is tucked under "טקסטים והגדרות".
 *
 * @return array<string,array<string,mixed>>
 */
function kindi_settings_tabs(): array {
	$tabs = array(
		'promos'   => array(
			'label'    => 'מבצעים ותוכן',
			'sections' => array(
				'עמוד הבית — קטגוריות ומוצרים' => array(
					'home_cats_mode'       => array( 'type' => 'select', 'label' => 'קטגוריות בעמוד הבית', 'options' => array( 'auto' => 'אוטומטי (הפופולריות ביותר)', 'manual' => 'בחירה ידנית' ) ),
					'home_cats'            => array( 'type' => 'taxonomy_multi', 'label' => 'בחירת קטגוריות', 'help' => 'רלוונטי כשנבחר "בחירה ידנית". סמן את הקטגוריות שיופיעו בעמוד הבית.' ),
					'home_products_source' => array( 'type' => 'select', 'label' => 'אילו מוצרים להציג', 'options' => array( 'popularity' => 'הנמכרים ביותר', 'date' => 'החדשים ביותר', 'sale' => 'במבצע', 'best_selling' => 'רבי מכר', 'featured' => 'מוצרים מומלצים', 'category' => 'מקטגוריה מסוימת' ) ),
					'home_products_cat'    => array( 'type' => 'taxonomy_select', 'label' => 'קטגוריית מוצרים', 'help' => 'בשימוש רק כשנבחר "מקטגוריה מסוימת".' ),
					'home_products_count'  => array( 'type' => 'number', 'label' => 'כמות מוצרים להצגה' ),
				),
				'עמוד הבית — מסקוטים וכותרות' => array(
					'hero_mascot'         => array( 'type' => 'image', 'label' => 'מסקוט ההירו (ראשי)' ),
					'kindyzone_mascot'    => array( 'type' => 'image', 'label' => 'מסקוט קינדי-זון' ),
					'storeinfo_mascot'    => array( 'type' => 'image', 'label' => 'מסקוט פרטי החנות' ),
					'values_mascot'       => array( 'type' => 'image', 'label' => 'מסקוט רצועת הערכים' ),
					'section_heading_tag' => array( 'type' => 'select', 'label' => 'תגית כותרת הסקשנים', 'options' => array( 'h2' => 'H2 (מומלץ)', 'h3' => 'H3', 'h4' => 'H4' ), 'help' => 'תגית ה-HTML של כותרות הסקשנים בדף הבית — לשליטה במבנה ה-SEO.' ),
				),
				'רצועה עליונה' => array(
					'ticker'       => array( 'type' => 'textarea', 'label' => 'רצועת מבצעים עליונה', 'help' => 'שורה אחת לכל פריט' ),
					'header_promo' => array( 'type' => 'text', 'label' => 'פס כחול מעל החיפוש', 'help' => 'משלוח חינם / הטבת מועדון. השאירו ריק כדי להסתיר.' ),
				),
				'באנר מבצע גדול' => array(
					'promo1_img'   => array( 'type' => 'image', 'label' => 'תמונה' ),
					'promo1_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo1_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo1_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo1_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo1_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 2'    => array(
					'promo2_img'   => array( 'type' => 'image', 'label' => 'תמונה' ),
					'promo2_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo2_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo2_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo2_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo2_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר מבצע 3'    => array(
					'promo3_img'   => array( 'type' => 'image', 'label' => 'תמונה' ),
					'promo3_badge' => array( 'type' => 'text', 'label' => 'תגית' ),
					'promo3_title' => array( 'type' => 'text', 'label' => 'כותרת' ),
					'promo3_sub'   => array( 'type' => 'text', 'label' => 'תיאור' ),
					'promo3_cta'   => array( 'type' => 'text', 'label' => 'טקסט כפתור' ),
					'promo3_url'   => array( 'type' => 'url', 'label' => 'קישור' ),
				),
				'באנר ראשי (Hero)' => array(
					'hero_badge'    => array( 'type' => 'text', 'label' => 'תגית עליונה' ),
					'hero_title1'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 1' ),
					'hero_hl'       => array( 'type' => 'text', 'label' => 'כותרת — חלק מודגש (אדום)' ),
					'hero_title2'   => array( 'type' => 'text', 'label' => 'כותרת — חלק 2 (כחול)' ),
					'hero_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'hero_cta1'     => array( 'type' => 'text', 'label' => 'כפתור ראשי — טקסט' ),
					'hero_cta1_url' => array( 'type' => 'url', 'label' => 'כפתור ראשי — קישור' ),
					'hero_cta2'     => array( 'type' => 'text', 'label' => 'כפתור משני — טקסט' ),
					'hero_cta2_url' => array( 'type' => 'url', 'label' => 'כפתור משני — קישור' ),
					'hero_rating'      => array( 'type' => 'text', 'label' => 'שורת אמון — ציון (ריק = להסתיר את הדירוג)' ),
					'hero_rating_note' => array( 'type' => 'text', 'label' => 'שורת אמון — טקסט ליד הציון' ),
					'hero_trust1'      => array( 'type' => 'text', 'label' => 'שורת אמון — פריט 1 (ריק = להסתיר)' ),
					'hero_trust2'      => array( 'type' => 'text', 'label' => 'שורת אמון — פריט 2 (ריק = להסתיר)' ),
				),
			),
		),
		'texts'    => array(
			'label'    => 'טקסטים והגדרות',
			'sections' => array(
				'כללי'         => array(
					'phone'                => array( 'type' => 'text', 'label' => 'טלפון' ),
					'whatsapp'             => array( 'type' => 'text', 'label' => 'מספר וואטסאפ (בינלאומי, ללא +)', 'help' => 'לדוגמה: 972500000000' ),
					'whatsapp_hours'       => array( 'type' => 'text', 'label' => 'שעות שירות וואטסאפ (תצוגה)' ),
					'whatsapp_from'        => array( 'type' => 'number', 'label' => 'וואטסאפ — שעת פתיחה (0-23)', 'help' => 'משמש לסימון "זמינים עכשיו" בכפתור המוצר.' ),
					'whatsapp_to'          => array( 'type' => 'number', 'label' => 'וואטסאפ — שעת סגירה (0-23)' ),
					'whatsapp_product_msg' => array( 'type' => 'textarea', 'label' => 'הודעת וואטסאפ מעמוד מוצר', 'help' => 'אפשר להשתמש ב-{product} ו-{url}.' ),
					'free_shipping'        => array( 'type' => 'number', 'label' => 'סף משלוח חינם (₪)' ),
					'free_ship_exclude_cats' => array( 'type' => 'taxonomy_multi', 'label' => 'החרגת משלוח חינם — קטגוריות', 'help' => 'מוצרים בקטגוריות שתבחרו (וכל תת-הקטגוריות שלהן) לא יזכו במשלוח חינם, ללא קשר לסכום. אם לא תבחרו כלום — אין החרגה וכל המוצרים זכאים למשלוח חינם לפי הסף.' ),
					'gift_wrap_enable'     => array( 'type' => 'select', 'label' => 'אריזת מתנה וכרטיס ברכה (עמוד תשלום)', 'options' => array( '1' => 'מופעל', '0' => 'כבוי' ), 'help' => 'כיבוי מסתיר את קוביית "זו מתנה?" בעמוד התשלום.' ),
					'bundle_coupon'        => array( 'type' => 'text', 'label' => 'קוד קופון לחבילה "נקנה יחד"', 'help' => 'אופציונלי — אם יוגדר, הקופון יוחל אוטומטית בלחיצה על "הוספת הנבחרים לסל" בעמוד המוצר.' ),
				),
				'משלוח והחזרות (Schema)' => array(
					'ship_cost'     => array( 'type' => 'number', 'label' => 'עלות משלוח רגיל (₪)', 'help' => 'מוצג בנתונים המובנים של גוגל. 0 = משלוח חינם תמיד.' ),
					'ship_days_min' => array( 'type' => 'number', 'label' => 'זמן אספקה — מינימום (ימי עסקים)' ),
					'ship_days_max' => array( 'type' => 'number', 'label' => 'זמן אספקה — מקסימום (ימי עסקים)' ),
					'return_days'   => array( 'type' => 'number', 'label' => 'חלון החזרות (ימים)', 'help' => '0 = לא מתקבלות החזרות.' ),
					'google_category' => array( 'type' => 'text', 'label' => 'קטגוריית Google למוצרים (ברירת מחדל)', 'help' => 'מזהה או נתיב מלא מתוך טקסונומיית Google. ניתן לדרוס לכל מוצר עם השדה _google_product_category.' ),
				),
				'מועדון קינדי' => array(
					'club_title'    => array( 'type' => 'text', 'label' => 'כותרת' ),
					'club_title_hl' => array( 'type' => 'text', 'label' => 'כותרת — חלק מודגש (רקע אדום)' ),
					'club_lead'     => array( 'type' => 'textarea', 'label' => 'תיאור' ),
					'club_benefits' => array( 'type' => 'textarea', 'label' => 'הטבות', 'help' => 'שורה אחת לכל הטבה' ),
					'club_cta_url'  => array( 'type' => 'url', 'label' => 'קישור הצטרפות' ),
				),
				'עדכוני תבנית' => array(
					'update_token' => array( 'type' => 'text', 'label' => 'GitHub Token לעדכונים (אופציונלי)', 'help' => 'עדכוני התבנית מגיעים אוטומטית מהריפו הציבורי — אין צורך בטוקן. רק אם הריפו יהפוך פרטי: Fine-grained token עם הרשאת Contents: Read-only, כאן או כ-KINDI_UPDATE_TOKEN ב-wp-config.php.' ),
				),
				'Flashy' => array(
					'flashy_key'     => array( 'type' => 'text', 'label' => 'מפתח API', 'help' => 'מ-Flashy: הגדרות החשבון ← API. נדרש רק לצירוף נרשמי הניוזלטר כאנשי קשר; הביקורות עובדות דרך התוסף בלי מפתח.' ),
					'flashy_list'    => array( 'type' => 'text', 'label' => 'מזהה רשימת ניוזלטר (List ID)', 'help' => 'אחרי שמירת המפתח, רשימות החשבון יוצגו למטה — העתיקו מהן את המזהה.' ),
					'flashy_reviews' => array( 'type' => 'text', 'label' => 'מזהה אלמנט ביקורות בעמוד מוצר', 'help' => 'ה-Element ID מ-Flashy (למשל 1480). כשמוגדר — הביקורות של Flashy מוצגות בעמוד המוצר ולשונית הביקורות המובנית מוסתרת. ריק = חזרה לביקורות המובנות.' ),
					'_flashy_status' => array( 'type' => 'note', 'label' => 'סטטוס חיבור', 'help_cb' => 'kindi_flashy_status_html' ),
				),
				'Webhook — הזמנה שהושלמה (Pabbly)' => array(
					'webhook_enable' => array( 'type' => 'select', 'label' => 'הפעלת ה-Webhook', 'options' => array( '0' => 'כבוי', '1' => 'מופעל' ), 'help' => 'כשמופעל: בכל פעם שהזמנה עוברת לסטטוס "הושלמה", נשלחים פרטי ההזמנה (שם, טלפון, מוצרים עם קישור, מספר חשבונית) לכתובת ה-Webhook. כיבוי כאן עוצר הכל מיידית.' ),
					'webhook_url'    => array( 'type' => 'text', 'label' => 'כתובת ה-Webhook', 'help' => 'ה-URL של ה-Workflow ב-Pabbly (או Zapier/Make). חייב להתחיל ב-https://.' ),
					'_webhook_status' => array( 'type' => 'note', 'label' => 'סטטוס ובדיקה', 'help_cb' => 'kindi_webhook_status_html', 'help' => 'הלוג מציג את השליחות האחרונות (זמן, מספר הזמנה, קוד תשובת השרת). קוד 200 = נשלח בהצלחה.' ),
				),
				'שאלות ותשובות (פוטר)' => array(
					'faq_title'       => array( 'type' => 'text', 'label' => 'כותרת הסקשן' ),
					'faq_intro'       => array( 'type' => 'textarea', 'label' => 'פסקת פתיחה' ),
					'faq_items'       => array( 'type' => 'textarea', 'label' => 'שאלות ותשובות', 'help' => 'מפרידים בין שאלה לשאלה בשורה עם שלושה מקפים (---). בכל בלוק: השורה הראשונה היא השאלה, וכל השאר התשובה. כדי ליצור רווח/פסקה חדשה בתוך תשובה — פשוט השאירו שורה ריקה בין הפסקאות. ריק = הסקשן מוסתר.' ),
					'faq_outro_title' => array( 'type' => 'text', 'label' => 'כותרת סיום' ),
					'faq_outro'       => array( 'type' => 'textarea', 'label' => 'פסקת סיום' ),
				),
				'פרטי החנות'   => array(
					'store_address' => array( 'type' => 'text', 'label' => 'כתובת' ),
					'store_phone'   => array( 'type' => 'text', 'label' => 'טלפון בחנות' ),
					'store_hours'   => array( 'type' => 'text', 'label' => 'שעות פתיחה' ),
					'store_waze'    => array( 'type' => 'url', 'label' => 'קישור ניווט (Waze)' ),
					'news_title'    => array( 'type' => 'text', 'label' => 'ניוזלטר — כותרת' ),
					'news_sub'      => array( 'type' => 'textarea', 'label' => 'ניוזלטר — תיאור' ),
				),
				'באנר עוגיות (Cookies)' => array(
					'cookie_enable'      => array( 'type' => 'select', 'label' => 'הצגת הבאנר', 'options' => array( '1' => 'מופעל', '0' => 'כבוי' ) ),
					'cookie_text'        => array( 'type' => 'textarea', 'label' => 'טקסט ההודעה' ),
					'cookie_btn'         => array( 'type' => 'text', 'label' => 'טקסט כפתור האישור' ),
					'cookie_policy_text' => array( 'type' => 'text', 'label' => 'טקסט קישור המדיניות' ),
					'cookie_policy_url'  => array( 'type' => 'url', 'label' => 'קישור למדיניות פרטיות' ),
				),
				'מיילים של הזמנות — באנרים' => array(
					'email_proc_top'    => array( 'type' => 'image', 'label' => '"הזמנה בטיפול" — תמונה עליונה', 'help' => 'מוצגת מתחת לכותרת המייל, מעל פרטי ההזמנה. רוחב מומלץ: 600px. ריק = ללא תמונה.' ),
					'email_proc_bottom' => array( 'type' => 'image', 'label' => '"הזמנה בטיפול" — תמונה תחתונה', 'help' => 'מוצגת אחרי פרטי ההזמנה, מעל הפוטר.' ),
					'email_done_top'    => array( 'type' => 'image', 'label' => '"הזמנה הושלמה" — תמונה עליונה' ),
					'email_done_bottom' => array( 'type' => 'image', 'label' => '"הזמנה הושלמה" — תמונה תחתונה' ),
				),
				'מייל — חזרה למלאי' => array(
					'wl_email_subject' => array( 'type' => 'text', 'label' => 'נושא המייל', 'help' => 'אפשר: {name}, {product}, {url}' ),
					'wl_email_body'    => array( 'type' => 'textarea', 'label' => 'תוכן המייל', 'help' => 'אפשר: {name}, {product}, {url}. העיצוב (לוגו/צבעים) מתווסף אוטומטית.' ),
				),
				'עגלה שמורה — מיילים' => array(
					'cart_reminder_delay'   => array( 'type' => 'number', 'label' => 'תזכורת לאחר (שעות)', 'help' => 'כמה שעות אחרי השמירה תישלח תזכורת. ברירת מחדל: 24.' ),
					'_cart_vars_note'       => array( 'type' => 'note', 'label' => '', 'help' => 'משתנים דינמיים בנושא ובתוכן: {site} שם האתר · {name} שם הלקוח · {url} קישור לשחזור העגלה · {count} מספר הפריטים · {total} סכום העגלה. רשימת המוצרים מתווספת אוטומטית מתחת לתוכן.' ),
					'cart_email_subject'    => array( 'type' => 'text', 'label' => 'מייל מיידי — נושא', 'help' => 'אפשר: {site}, {name}, {url}, {count}, {total}' ),
					'cart_email_body'       => array( 'type' => 'textarea', 'label' => 'מייל מיידי — תוכן', 'help' => 'אפשר: {site}, {name}, {url}, {count}, {total}' ),
					'cart_reminder_subject' => array( 'type' => 'text', 'label' => 'תזכורת — נושא', 'help' => 'אפשר: {site}, {name}, {url}, {count}, {total}' ),
					'cart_reminder_body'    => array( 'type' => 'textarea', 'label' => 'תזכורת — תוכן', 'help' => 'אפשר: {site}, {name}, {url}, {count}, {total}' ),
				),
				'ביקורות גוגל' => array(
					'_reviews_note'  => array( 'type' => 'note', 'label' => '', 'help' => 'ביקורות גוגל האמיתיות נמשכות אוטומטית מהתוסף Rich Showcase for Google Reviews ומוצגות בעיצוב של קינדי. הביקורות הידניות שלמטה משמשות רק כגיבוי כשאין ביקורות מהתוסף.' ),
					'reviews_manual' => array( 'type' => 'textarea', 'label' => 'ביקורות ידניות (גיבוי)', 'help' => 'שורה לכל ביקורת: שם | דירוג (1-5) | טקסט.' ),
					'reviews_rating' => array( 'type' => 'text', 'label' => 'דירוג כללי', 'help' => 'לדוגמה: 4.9' ),
					'reviews_count'  => array( 'type' => 'number', 'label' => 'מספר ביקורות כולל', 'help' => 'יוצג כ"X+ ביקורות בגוגל". 0 = מספר הביקורות שהוזנו.' ),
					'reviews_link'   => array( 'type' => 'url', 'label' => 'קישור לכל הביקורות בגוגל' ),
				),
				'פוטר ורשתות'  => array(
					'about' => array( 'type' => 'textarea', 'label' => 'טקסט "אודות"' ),
					'email' => array( 'type' => 'text', 'label' => 'אימייל' ),
					'fb'    => array( 'type' => 'url', 'label' => 'קישור פייסבוק' ),
					'ig'    => array( 'type' => 'url', 'label' => 'קישור אינסטגרם' ),
					'yt'    => array( 'type' => 'url', 'label' => 'קישור יוטיוב' ),
				),
			),
		),
	);

	$toggle = array( '1' => 'מופעל', '0' => 'כבוי' );

	$tabs['pixel'] = array(
		'label'    => 'פיקסל ומעקב (Meta)',
		'sections' => array(
			'מזהה הפיקסל' => array(
				'_pixel_note'  => array( 'type' => 'note', 'label' => '', 'help' => 'הזינו את מזהה הפיקסל של Meta (Facebook) וכל קוד המעקב יוטמע אוטומטית — תחליף רזה ל-PixelYourSite. השאירו ריק כדי לכבות לחלוטין.' ),
				'fb_pixel_id'  => array( 'type' => 'text', 'label' => 'מזהה פיקסל (Pixel ID)', 'help' => 'מספר בלבד, לדוגמה: 1234567890123456.' ),
				'pixel_enable' => array( 'type' => 'select', 'label' => 'הפעלת הפיקסל', 'options' => $toggle ),
			),
			'אירועי מסחר (WooCommerce)' => array(
				'px_view_content'      => array( 'type' => 'select', 'label' => 'צפייה במוצר (ViewContent)', 'options' => $toggle ),
				'px_add_to_cart'       => array( 'type' => 'select', 'label' => 'הוספה לעגלה (AddToCart)', 'options' => $toggle ),
				'px_initiate_checkout' => array( 'type' => 'select', 'label' => 'התחלת תשלום (InitiateCheckout)', 'options' => $toggle ),
				'px_purchase'          => array( 'type' => 'select', 'label' => 'רכישה (Purchase)', 'options' => $toggle ),
				'px_view_category'     => array( 'type' => 'select', 'label' => 'צפייה בקטגוריה (ViewCategory)', 'options' => $toggle ),
			),
			'אירועים כלליים' => array(
				'px_search'    => array( 'type' => 'select', 'label' => 'חיפוש (Search)', 'options' => $toggle ),
				'px_404'       => array( 'type' => 'select', 'label' => 'עמוד 404', 'options' => $toggle ),
				'px_signup'    => array( 'type' => 'select', 'label' => 'הרשמת משתמש (CompleteRegistration)', 'options' => $toggle ),
				'px_login'     => array( 'type' => 'select', 'label' => 'התחברות משתמש', 'options' => $toggle ),
				'px_scroll'    => array( 'type' => 'select', 'label' => 'גלילה בעמוד (90%)', 'options' => $toggle ),
				'px_time'      => array( 'type' => 'select', 'label' => 'זמן בעמוד (30 שניות)', 'options' => $toggle ),
				'px_downloads' => array( 'type' => 'select', 'label' => 'הורדות קבצים', 'options' => $toggle ),
				'px_forms'     => array( 'type' => 'select', 'label' => 'שליחת טפסים (Lead)', 'options' => $toggle ),
				'px_comments'  => array( 'type' => 'select', 'label' => 'תגובות', 'options' => $toggle ),
			),
			'מעקב נתונים מקומי (הזמנות)' => array(
				'_report_note'        => array( 'type' => 'note', 'label' => '', 'help' => 'שומר על כל הזמנה את מקור התנועה, דף הנחיתה ופרמטרי ה-UTM (מגע ראשון). המידע מוצג בעמוד ההזמנה ובמייל "הזמנה חדשה" למנהל.' ),
				'px_native_reporting' => array( 'type' => 'select', 'label' => 'מעקב מקור הזמנות (UTM / דף נחיתה)', 'options' => $toggle ),
			),
		),
	);

	/**
	 * Filter the settings tabs/sections/fields (used to inject the ACF bridge).
	 *
	 * @param array<string,array<string,mixed>> $tabs Settings tabs.
	 */
	return apply_filters( 'kindi_settings_tabs', $tabs );
}

/**
 * Flat map of every field key → its definition (for saving/sanitising).
 *
 * @return array<string,array{type:string,label:string,help?:string}>
 */
function kindi_settings_all_fields(): array {
	$all = array();
	foreach ( kindi_settings_tabs() as $tab ) {
		foreach ( $tab['sections'] as $fields ) {
			$all += $fields;
		}
	}
	return $all;
}

/**
 * Sanitise a value according to its field type.
 *
 * @param string $type  Field type.
 * @param mixed  $value Raw value.
 * @return mixed
 */
function kindi_sanitize_field( string $type, $value ) {
	switch ( $type ) {
		case 'number':
		case 'taxonomy_select':
			return absint( $value );
		case 'url':
		case 'image':
			return esc_url_raw( trim( (string) $value ) );
		case 'textarea':
			return sanitize_textarea_field( (string) $value );
		case 'html':
			return wp_kses_post( (string) $value );
		case 'code':
			// Verbatim embed slot (inc/custom-code.php). Raw script tags may only
			// be saved by users trusted with unfiltered HTML; others get kses.
			return current_user_can( 'unfiltered_html' ) ? trim( (string) $value ) : wp_kses_post( (string) $value );
		default:
			return sanitize_text_field( (string) $value );
	}
}

/**
 * Product categories for select/checkbox fields (id => name).
 *
 * @return array<int,string>
 */
function kindi_admin_product_cats(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => 300,
			'orderby'    => 'name',
		)
	);
	$out = array();
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			$out[ (int) $term->term_id ] = $term->name;
		}
	}
	return $out;
}

/**
 * Register the admin menu page.
 *
 * @return void
 */
function kindi_settings_menu(): void {
	add_menu_page(
		__( 'קינדי — תוכן האתר', 'kindi' ),
		__( 'קינדי', 'kindi' ),
		'manage_options',
		'kindi-settings',
		'kindi_settings_render',
		'dashicons-store',
		59
	);
	// Rename the auto-created first submenu (same slug) from "קינדי" to "ניהול".
	add_submenu_page(
		'kindi-settings',
		__( 'קינדי — ניהול תוכן האתר', 'kindi' ),
		__( 'ניהול', 'kindi' ),
		'manage_options',
		'kindi-settings',
		'kindi_settings_render'
	);
}
add_action( 'admin_menu', 'kindi_settings_menu' );

/**
 * Handle save + render the settings screen.
 *
 * @return void
 */
function kindi_settings_render(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// Needed for the image-picker fields (promo banner images).
	wp_enqueue_media();

	$tabs    = kindi_settings_tabs();
	$current = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'promos'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $tabs[ $current ] ) ) {
		$current = 'promos';
	}
	$saved = false;

	if ( isset( $_POST['kindi_settings_submit'] ) && check_admin_referer( 'kindi_save_settings', 'kindi_nonce' ) ) {
		$clean = get_option( 'kindi_options', array() );
		if ( ! is_array( $clean ) ) {
			$clean = array();
		}

		// Only persist fields actually submitted (the active tab), so other
		// tabs' values are never wiped.
		foreach ( kindi_settings_all_fields() as $key => $field ) {
			if ( 'taxonomy_multi' === $field['type'] ) {
				if ( isset( $_POST['kindi__multi'][ $key ] ) ) {
					$vals          = isset( $_POST['kindi'][ $key ] ) ? (array) wp_unslash( $_POST['kindi'][ $key ] ) : array();
					$clean[ $key ] = array_values( array_filter( array_map( 'absint', $vals ) ) );
				}
				continue;
			}
			if ( 'cat_notices' === $field['type'] ) {
				if ( isset( $_POST['kindi__present'][ $key ] ) && function_exists( 'kindi_sanitize_cat_notices' ) ) {
					$rows          = isset( $_POST['kindi'][ $key ] ) ? (array) wp_unslash( $_POST['kindi'][ $key ] ) : array();
					$clean[ $key ] = kindi_sanitize_cat_notices( $rows );
				}
				continue;
			}
			if ( 'menu_toggles' === $field['type'] ) {
				if ( isset( $_POST['kindi__present'][ $key ] ) && function_exists( 'kindi_dashclean_sanitize' ) ) {
					$slugs         = isset( $_POST['kindi'][ $key ] ) ? (array) wp_unslash( $_POST['kindi'][ $key ] ) : array();
					$clean[ $key ] = kindi_dashclean_sanitize( $slugs );
				}
				continue;
			}
			if ( ! isset( $_POST['kindi'][ $key ] ) ) {
				continue;
			}
			$clean[ $key ] = kindi_sanitize_field( $field['type'], wp_unslash( $_POST['kindi'][ $key ] ) );
		}

		update_option( 'kindi_options', $clean );
		if ( function_exists( 'kindi_flush_store_cache' ) ) {
			kindi_flush_store_cache();
		}
		$saved = true;
	}

	echo '<div class="wrap kindi-settings"><h1>' . esc_html__( 'קינדי — ניהול תוכן האתר', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'קטגוריות ומוצרים נמשכים אוטומטית מ-WooCommerce. כאן עורכים את תוכן השיווק והטקסטים.', 'kindi' ) . '</p>';

	if ( $saved ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'התוכן נשמר.', 'kindi' ) . '</p></div>';
	}

	echo '<h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $slug => $tab ) {
		printf(
			'<a href="%s" class="nav-tab%s">%s</a>',
			esc_url( admin_url( 'admin.php?page=kindi-settings&tab=' . $slug ) ),
			$slug === $current ? ' nav-tab-active' : '',
			esc_html( $tab['label'] )
		);
	}
	echo '</h2>';

	echo '<form method="post" action="">';
	wp_nonce_field( 'kindi_save_settings', 'kindi_nonce' );

	foreach ( $tabs[ $current ]['sections'] as $section => $fields ) {
		echo '<h2 style="margin-top:2em">' . esc_html( $section ) . '</h2>';
		echo '<table class="form-table" role="presentation"><tbody>';

		foreach ( $fields as $key => $field ) {
			$value = kindi_opt( $key );
			$id    = 'kindi_' . $key;
			echo '<tr><th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label></th><td>';

			if ( 'textarea' === $field['type'] || 'html' === $field['type'] || 'code' === $field['type'] ) {
				$is_code = 'code' === $field['type'];
				printf(
					'<textarea id="%1$s" name="kindi[%2$s]" rows="%4$d" class="large-text%5$s" dir="%6$s"%7$s>%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_textarea( (string) $value ),
					'textarea' === $field['type'] ? 4 : 12,
					$is_code ? ' code' : '',
					$is_code ? 'ltr' : 'rtl',
					$is_code ? ' spellcheck="false"' : ''
				);
			} elseif ( 'select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="kindi[' . esc_attr( $key ) . ']">';
				foreach ( ( $field['options'] ?? array() ) as $opt_val => $opt_label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $opt_val ), selected( (string) $value, (string) $opt_val, false ), esc_html( $opt_label ) );
				}
				echo '</select>';
			} elseif ( 'taxonomy_select' === $field['type'] ) {
				echo '<select id="' . esc_attr( $id ) . '" name="kindi[' . esc_attr( $key ) . ']"><option value="0">— ללא —</option>';
				foreach ( kindi_admin_product_cats() as $tid => $tname ) {
					printf( '<option value="%d"%s>%s</option>', (int) $tid, selected( (int) $value, (int) $tid, false ), esc_html( $tname ) );
				}
				echo '</select>';
			} elseif ( 'image' === $field['type'] ) {
					$img = (string) $value;
					echo '<div class="kindi-imgfield">';
					printf(
						'<input type="text" id="%1$s" name="kindi[%2$s]" value="%3$s" class="regular-text kindi-imgfield__url" dir="ltr" placeholder="https://…">',
						esc_attr( $id ),
						esc_attr( $key ),
						esc_attr( $img )
					);
					echo ' <button type="button" class="button kindi-imgfield__pick">' . esc_html__( 'בחר/העלה תמונה', 'kindi' ) . '</button>';
					echo ' <button type="button" class="button-link kindi-imgfield__clear">' . esc_html__( 'הסר', 'kindi' ) . '</button>';
					echo '<div class="kindi-imgfield__preview">' . ( '' !== $img ? '<img src="' . esc_url( $img ) . '" alt="">' : '' ) . '</div>';
					echo '</div>';
				} elseif ( 'note' === $field['type'] ) {
					// A note carries no input; when it has a help_cb, render that
					// (dynamic status HTML) here — the static help below still prints.
					if ( isset( $field['help_cb'] ) && is_callable( $field['help_cb'] ) ) {
						echo wp_kses_post( (string) call_user_func( $field['help_cb'] ) );
					}
				} elseif ( 'taxonomy_multi' === $field['type'] ) {
				$selected_ids = is_array( $value ) ? array_map( 'intval', $value ) : array();
				echo '<input type="hidden" name="kindi__multi[' . esc_attr( $key ) . ']" value="1">';
				echo '<input type="search" class="kindi-catsearch regular-text" style="display:block;margin-bottom:8px;max-width:760px" placeholder="' . esc_attr__( 'חיפוש קטגוריה…', 'kindi' ) . '" aria-controls="' . esc_attr( $id ) . '-list" autocomplete="off">';
					echo '<div id="' . esc_attr( $id ) . '-list" class="kindi-catlist" style="max-height:220px;overflow:auto;border:1px solid #dcdcde;border-radius:6px;padding:8px;column-count:2">';
				foreach ( kindi_admin_product_cats() as $tid => $tname ) {
					printf(
						'<label style="display:block;margin:2px 0"><input type="checkbox" name="kindi[%1$s][]" value="%2$d"%3$s> %4$s</label>',
						esc_attr( $key ),
						(int) $tid,
						checked( in_array( (int) $tid, $selected_ids, true ), true, false ),
						esc_html( $tname )
					);
				}
				echo '</div>';
			} elseif ( 'cat_notices' === $field['type'] && function_exists( 'kindi_cat_notices_field_render' ) ) {
				kindi_cat_notices_field_render( $key, $value );
			} elseif ( 'menu_toggles' === $field['type'] && function_exists( 'kindi_dashclean_field_render' ) ) {
				kindi_dashclean_field_render( $key, $value );
			} else {
				// URLs render as text so relative paths (e.g. /shop/) are accepted.
				$input_type = 'number' === $field['type'] ? 'number' : 'text';
				printf(
					'<input type="%1$s" id="%2$s" name="kindi[%3$s]" value="%4$s" class="regular-text" dir="rtl">',
					esc_attr( $input_type ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			if ( ! empty( $field['help'] ) ) {
				echo '<p class="description">' . esc_html( $field['help'] ) . '</p>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	submit_button( __( 'שמירת תוכן', 'kindi' ), 'primary', 'kindi_settings_submit' );
	echo '</form>';

	/**
	 * Fires after the settings form — used for standalone tools (e.g. ACF import).
	 */
	do_action( 'kindi_settings_after_form' );

	?>
	<style>.kindi-imgfield__preview img{max-width:160px;max-height:90px;border-radius:8px;margin-top:8px;display:block;border:1px solid #dcdcde}</style>
	<script>
	( function () {
		function wire() {
			document.querySelectorAll( '.kindi-imgfield__pick' ).forEach( function ( btn ) {
				if ( btn.dataset.wired ) { return; }
				btn.dataset.wired = '1';
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					if ( typeof wp === 'undefined' || ! wp.media ) { return; }
					var wrap = btn.closest( '.kindi-imgfield' );
					var input = wrap.querySelector( '.kindi-imgfield__url' );
					var prev = wrap.querySelector( '.kindi-imgfield__preview' );
					var frame = wp.media( { title: 'בחירת תמונה', button: { text: 'שימוש בתמונה' }, multiple: false } );
					frame.on( 'select', function () {
						var a = frame.state().get( 'selection' ).first().toJSON();
						var url = ( a.sizes && a.sizes.large ) ? a.sizes.large.url : a.url;
						input.value = url;
						prev.innerHTML = '<img src="' + url + '" alt="">';
					} );
					frame.open();
				} );
			} );
			document.querySelectorAll( '.kindi-imgfield__clear' ).forEach( function ( btn ) {
				if ( btn.dataset.wired ) { return; }
				btn.dataset.wired = '1';
				btn.addEventListener( 'click', function ( e ) {
					e.preventDefault();
					var w = btn.closest( '.kindi-imgfield' );
					w.querySelector( '.kindi-imgfield__url' ).value = '';
					w.querySelector( '.kindi-imgfield__preview' ).innerHTML = '';
				} );
			} );
			document.querySelectorAll( '.kindi-catsearch' ).forEach( function ( s ) {
				if ( s.dataset.wired ) { return; }
				s.dataset.wired = '1';
				s.addEventListener( 'input', function () {
					var q = s.value.trim().toLowerCase();
					var list = document.getElementById( s.getAttribute( 'aria-controls' ) );
					if ( ! list ) { return; }
					list.querySelectorAll( 'label' ).forEach( function ( l ) {
						l.style.display = l.textContent.toLowerCase().indexOf( q ) > -1 ? '' : 'none';
					} );
				} );
			} );
		}
		if ( 'loading' !== document.readyState ) { wire(); } else { document.addEventListener( 'DOMContentLoaded', wire ); }
	}() );
	</script>
	<?php

	echo '</div>';
}
