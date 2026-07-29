<?php
/**
 * Returns & exchanges policy — the [kindi_returns] shortcode.
 *
 * Renders the returns/exchange/cancellation policy as a branded block: an intro
 * card, a native-<details> accordion (reusing the FAQ styles), and a worked
 * example. The free-shipping threshold is pulled from the panel so the page and
 * the cart never disagree.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Render the returns policy.
 *
 * @return string
 */
function kindi_returns_shortcode(): string {
	$free = (int) ( function_exists( 'kindi_opt' ) ? kindi_opt( 'free_shipping', 299 ) : 299 );
	$icon = static function ( string $name, string $classes = 'kindi-icon--sm' ): string {
		return function_exists( 'kindi_icon' ) ? kindi_icon( $name, $classes ) : '';
	};

	ob_start();
	?>
	<div class="kindi-returns">

		<div class="kindi-returns__intro">
			<h2><?php esc_html_e( 'החזרות, החלפות וביטול עסקה', 'kindi' ); ?></h2>
			<p><?php esc_html_e( 'אנו עושים את מירב המאמצים שתהיו מרוצים מהקנייה שלכם.', 'kindi' ); ?></p>
			<p><?php esc_html_e( 'ובדיוק כמו במשפחה — אם משהו לא יושב טוב, אנחנו כאן לתקן ולהסתדר ביחד.', 'kindi' ); ?></p>
			<p class="kindi-returns__store"><?php esc_html_e( 'קינדר טויס · הרב יעקב לנדא 7, בני ברק · ', 'kindi' ); ?><a href="<?php echo esc_url( home_url( '/' ) ); ?>">kindertoys.co.il</a></p>
			<div class="kindi-returns__ship">
				<?php echo $icon( 'truck', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<span><?php printf( esc_html__( 'משלוח חינם בכל הזמנה מעל %d ₪. מתחת לסכום זה — חלה עלות משלוח רגילה.', 'kindi' ), $free ); ?></span>
			</div>
		</div>

		<div class="kindi-faq__list kindi-returns__acc">

			<details class="kindi-faq__item">
				<summary><span class="kindi-returns__q"><span class="kindi-returns__num">1</span><?php esc_html_e( 'מהו זמן ההחזרה או ההחלפה?', 'kindi' ); ?></span><span class="kindi-faq__plus" aria-hidden="true">+</span></summary>
				<div class="kindi-faq__answer">
					<p><?php esc_html_e( 'ניתן להחזיר או להחליף מוצרים רגילים עד 14 ימים מיום קבלתם, כל עוד הם:', 'kindi' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'סגורים', 'kindi' ); ?></li>
						<li><?php esc_html_e( 'חדשים', 'kindi' ); ?></li>
						<li><?php esc_html_e( 'באריזתם המקורית', 'kindi' ); ?></li>
					</ul>
				</div>
			</details>

			<details class="kindi-faq__item">
				<summary><span class="kindi-returns__q"><span class="kindi-returns__num">2</span><?php esc_html_e( 'איך מבצעים החזרה או החלפה?', 'kindi' ); ?></span><span class="kindi-faq__plus" aria-hidden="true">+</span></summary>
				<div class="kindi-faq__answer">
					<p><?php esc_html_e( 'בשתי דרכים:', 'kindi' ); ?></p>
					<p class="kindi-returns__sub"><?php esc_html_e( 'החלפה / החזרה בחנות', 'kindi' ); ?></p>
					<p><?php esc_html_e( 'הגעה לסניף: הרב יעקב לנדא 7, בני ברק. מומלץ לוודא מראש את זמינות המוצר המבוקש.', 'kindi' ); ?></p>
					<p class="kindi-returns__ok"><?php echo $icon( 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php esc_html_e( 'החלפה בחנות מתבצעת ללא כל עלות.', 'kindi' ); ?></p>
					<p class="kindi-returns__sub"><?php esc_html_e( 'החלפה / החזרה באמצעות שליח', 'kindi' ); ?></p>
					<p><?php esc_html_e( 'מתאמים מול שירות הלקוחות. עלות המשלוח תיקבע לפי סוג וגודל המוצר (ראו פירוט העלויות בסעיף הבא).', 'kindi' ); ?></p>
				</div>
			</details>

			<details class="kindi-faq__item">
				<summary><span class="kindi-returns__q"><span class="kindi-returns__num">3</span><?php esc_html_e( 'מהן העלויות בהחזרה או החלפה בשליח?', 'kindi' ); ?></span><span class="kindi-faq__plus" aria-hidden="true">+</span></summary>
				<div class="kindi-faq__answer">
					<ul>
						<li><?php esc_html_e( 'החזרה וקבלת החזר כספי (ללא החלפה): 5% דמי ביטול מערך המוצר, בתוספת 35 ₪ דמי משלוח החזרה.', 'kindi' ); ?></li>
						<li><?php esc_html_e( 'החלפת מוצר או דגם: ללא דמי ביטול — המוצר החלופי נשלח במשלוח גוביינא בעלות 60 ₪.', 'kindi' ); ?></li>
					</ul>
					<div class="kindi-returns__note">
						<?php printf( esc_html__( 'שימו לב: אם ההזמנה המקורית זכתה במשלוח חינם (מעל %d ₪) ולאחר ההחזרה סכום ההזמנה יורד מתחת ל-%d ₪ — מתבטלת הזכאות למשלוח החינם ונגבית בדיעבד עלות המשלוח.', 'kindi' ), $free, $free ); ?>
					</div>
				</div>
			</details>

			<details class="kindi-faq__item">
				<summary><span class="kindi-returns__q"><span class="kindi-returns__num">4</span><?php esc_html_e( 'אילו מוצרים אינם ניתנים להחזרה או החלפה?', 'kindi' ); ?></span><span class="kindi-faq__plus" aria-hidden="true">+</span></summary>
				<div class="kindi-faq__answer">
					<p><?php esc_html_e( 'בהתאם למדיניות הספקים והרגולציה, המוצרים הבאים אינם ניתנים להחזרה או החלפה:', 'kindi' ); ?></p>
					<ul>
						<li><?php esc_html_e( 'מוצרים במבצעים מיוחדים', 'kindi' ); ?></li>
						<li><?php esc_html_e( 'מוצרי עונה לאחר זמן', 'kindi' ); ?></li>
						<li><?php esc_html_e( 'ציוד וריהוט (כולל מתקני חצר)', 'kindi' ); ?></li>
					</ul>
				</div>
			</details>

		</div>

		<div class="kindi-returns__example">
			<strong><?php esc_html_e( 'דוגמה להמחשה', 'kindi' ); ?></strong>
			<p class="kindi-returns__sub"><?php printf( esc_html__( 'החזרה שמורידה את ההזמנה מתחת ל-%d ₪', 'kindi' ), $free ); ?></p>
			<p><?php esc_html_e( 'הזמנה מקורית 350 ₪ (זכתה במשלוח חינם). הלקוח מחזיר מוצר בשווי 80 ₪, ונותרו 270 ₪.', 'kindi' ); ?></p>
			<p><?php printf( esc_html__( 'חיוב: 5%% דמי ביטול על ה-80 ₪ + 35 ₪ משלוח החזרה + תוספת משלוח על ההזמנה שנותרה (כי ירדה מתחת ל-%d ₪).', 'kindi' ), $free ); ?></p>
		</div>

	</div>
	<?php
	return (string) ob_get_clean();
}
add_shortcode( 'kindi_returns', 'kindi_returns_shortcode' );
