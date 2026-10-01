<?php
/**
 * Title: About Page
 * Slug: kindi/about
 * Categories: kindi
 * Inserter: false
 * Description: עמוד "אודות" — סיפור המותג, נתונים, ערכים ו-CTA.
 *
 * @package Kindi
 */

// The site's single contact address (same one the footer shows).
$kindi_email = (string) kindi_opt( 'email', 'office@kindertoys.co.il' );

$kindi_stats = array(
	array( 'num' => '+10', 'label' => 'שנים של מקצועיות' ),
	array( 'num' => '+200', 'label' => 'מותגים מובילים' ),
	array( 'num' => '+130', 'label' => 'קטגוריות תוכן' ),
	array( 'num' => '+5,372', 'label' => 'מוצרים במלאי' ),
);

$kindi_contact = array(
	array( 'icon' => 'pin',   'label' => 'כתובת החנות', 'value' => (string) kindi_opt( 'store_address' ) ),
	array( 'icon' => 'phone', 'label' => 'טלפון',        'value' => (string) kindi_opt( 'store_phone' ) . ' • שירות אישי וייעוץ' ),
	array( 'icon' => 'clock', 'label' => 'שעות פתיחה',   'value' => (string) kindi_opt( 'store_hours' ) ),
	array( 'icon' => 'mail',  'label' => 'אימייל',       'value' => $kindi_email, 'href' => 'mailto:' . $kindi_email ),
);

$kindi_brands = array( 'LEGO', 'Crayola', 'Fisher-Price', 'Playmobil', 'Melissa & Doug', 'Ravensburger', 'Hasbro' );

$kindi_why = array(
	array( 'icon' => 'truck',    'acc' => 'blue', 'title' => 'אספקה מהירה', 'body' => 'משלוח מהיר עד הבית, חינם בהזמנה מעל 299 ₪ — תוך 1-2 ימי עסקים.' ),
	array( 'icon' => 'heart',    'acc' => 'red',  'title' => 'ייעוץ אישי', 'body' => 'צוות שירות זמין בטלפון ובוואטסאפ, עם התאמה חווייתית לפי גיל.' ),
	array( 'icon' => 'shield',   'acc' => 'navy', 'title' => 'רכישה מאובטחת', 'body' => 'אתר מאובטח בתקני SSL מהמחמירים — פרטיכם ופרטי האשראי שמורים.' ),
	array( 'icon' => 'rotate',   'acc' => 'red',  'title' => 'החזרות בנוחות', 'body' => 'לא מרוצים? 30 יום להחזרה באריזה מקורית, החזר כספי מלא.' ),
);

$kindi_quality = array(
	array( 'icon' => 'shield', 'acc' => 'navy', 'title' => 'רכישה מאובטחת', 'body' => 'הצפנת SSL ותקני אבטחת מידע מחמירים בכל שלב בקנייה.' ),
	array( 'icon' => 'star',   'acc' => 'red',  'title' => 'בדיקות איכות', 'body' => 'כל מוצר נבחר בקפידה — מותגים מובילים ואיכות שעומדת בסטנדרט.' ),
	array( 'icon' => 'check',  'acc' => 'blue', 'title' => 'תו תקן ישראלי', 'body' => 'צעצועים ומוצרים העומדים בדרישות הבטיחות והתקן הישראלי.' ),
);
?>
<!-- wp:html -->
<section class="kindi-section kindi-about-hero">
	<div class="kindi-about-hero__grid">
		<div class="kindi-about-hero__visual">
			<?php echo kindi_mascot_img( 'hero_mascot', 'mascot/kindy-hero.webp', 'kindi-about-hero__mascot animate-mascot', '(max-width: 1023px) 300px, 340px', 'קינדי — המסקוט של קינדר טויס', array( 'fetchpriority' => 'high', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within. ?>
		</div>
		<div class="kindi-about-hero__copy">
			<span class="kindi-eyebrow"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>אלפי מוצרים • יצירה • משחק ולמידה</span>
			<h1 class="kindi-about-hero__title">הסיפור של <span>Kinder Toys</span></h1>
			<p class="kindi-about-hero__lead"><?php echo esc_html( (string) kindi_opt( 'about' ) ); ?> אנחנו מאמינים שכל ילד יכול ליהנות, ליצור וללמוד — ומביאים לכם את המבחר הגדול, השירות האישי והמחירים ההוגנים תחת גג אחד.</p>
		</div>
	</div>
</section>

<section class="kindi-about-stats" aria-label="נתונים">
	<?php foreach ( $kindi_stats as $s ) : ?>
	<div class="kindi-about-stat">
		<span class="kindi-about-stat__num"><?php echo esc_html( $s['num'] ); ?></span>
		<span class="kindi-about-stat__label"><?php echo esc_html( $s['label'] ); ?></span>
	</div>
	<?php endforeach; ?>
</section>

<section class="kindi-section kindi-about-story">
	<div class="kindi-about-story__grid">
		<div class="kindi-about-contact">
			<?php foreach ( $kindi_contact as $c ) : ?>
			<?php $kindi_tag = ! empty( $c['href'] ) ? 'a' : 'div'; ?>
			<<?php echo $kindi_tag; // phpcs:ignore WordPress.Security.EscapeOutput -- 'a'/'div' literal. ?> class="kindi-about-contact__item"<?php echo ! empty( $c['href'] ) ? ' href="' . esc_url( $c['href'] ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
				<span class="kindi-about-contact__t"><strong><?php echo esc_html( $c['label'] ); ?></strong><span><?php echo esc_html( $c['value'] ); ?></span></span>
				<span class="kindi-about-contact__ic"><?php echo kindi_icon( $c['icon'], 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</<?php echo $kindi_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
			<?php endforeach; ?>
		</div>

		<div class="kindi-about-story__tabs" role="radiogroup" aria-label="בחירת נושא: הסיפור, הערכים והחזון">
			<input type="radio" name="kabout" id="kabt1" class="kindi-about-story__radio" checked>
			<input type="radio" name="kabout" id="kabt2" class="kindi-about-story__radio">
			<input type="radio" name="kabout" id="kabt3" class="kindi-about-story__radio">
			<div class="kindi-about-story__nav">
				<label for="kabt1">הסיפור שלנו</label>
				<label for="kabt2">הערכים שלנו</label>
				<label for="kabt3">החזון שלנו</label>
			</div>
			<div class="kindi-about-story__body">
				<div class="kindi-about-story__panel" data-p="1">
					<p>כבר למעלה מעשור אנחנו ב-Kinder Toys מביאים את עולם המשחק, היצירה והלמידה אל אלפי בתים ברחבי הארץ. התחלנו כחנות שכונתית קטנה עם חלום גדול — להנגיש לכל משפחה צעצועים איכותיים, חומרי יצירה וציוד לבית הספר במחירים הוגנים ובשירות אישי.</p>
					<p>היום, עם מבחר של אלפי מוצרים ומאות מותגים מובילים, אנחנו ממשיכים להאמין באותו דבר: משחק הוא הדרך הטובה ביותר של ילדים ללמוד, לגדול ולהתחבר — הרחק מהמסכים, קרוב לחברים ולמשפחה.</p>
					<div class="kindi-about-chips">
						<?php foreach ( $kindi_brands as $b ) : ?>
						<span class="kindi-about-chip"><?php echo esc_html( $b ); ?></span>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="kindi-about-story__panel" data-p="2">
					<p>בכל החלטה שאנחנו מקבלים מנחים אותנו הערכים שלנו: <strong>איכות</strong> — רק מוצרים שהיינו שמחים לתת לילדים שלנו; <strong>שקיפות</strong> — מחירים הוגנים בלי אותיות קטנות; ו<strong>שירות</strong> — יחס אישי וחם, מההזמנה ועד הפתיחה.</p>
					<p>אנחנו רואים בכל לקוח חלק מהמשפחה של קינדרטויס, ומחויבים ללוות אתכם עד שאתם — והילדים — מרוצים באמת.</p>
				</div>
				<div class="kindi-about-story__panel" data-p="3">
					<p>החזון שלנו הוא להיות הבית של החוויות לכל משפחה בישראל — המקום שאליו פונים כשרוצים לתת מתנה שמשמחת, ליצור רגע של איכות, או פשוט להביא קצת קסם הביתה.</p>
					<p>אנחנו ממשיכים לגדול, להוסיף מותגים וקטגוריות, ולשפר את חוויית הקנייה — כדי שתמיד תמצאו בדיוק את מה שחיפשתם, בקלות ובאהבה.</p>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /wp:html -->

<!-- wp:kindi/featured-products /-->

<!-- wp:html -->
<section class="kindi-section">
	<div class="kindi-sechead">
		<div class="kindi-sechead__text">
			<span class="kindi-eyebrow"><?php echo kindi_icon( 'heart', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>למה דווקא אצלנו</span>
			<h2 class="kindi-sec-title">למה קינדר טויס?</h2>
		</div>
	</div>
	<div class="kindi-values">
		<?php foreach ( $kindi_why as $v ) : ?>
		<article class="kindi-value kindi-value--<?php echo esc_attr( $v['acc'] ); ?>">
			<span class="kindi-value__bar" aria-hidden="true"></span>
			<div class="kindi-value__top"><span class="kindi-value__ic"><?php echo kindi_icon( $v['icon'], 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
			<h3 class="kindi-value__title"><?php echo esc_html( $v['title'] ); ?></h3>
			<p class="kindi-value__body"><?php echo esc_html( $v['body'] ); ?></p>
		</article>
		<?php endforeach; ?>
	</div>
</section>

<section class="kindi-section">
	<div class="kindi-sechead">
		<div class="kindi-sechead__text">
			<span class="kindi-eyebrow"><?php echo kindi_icon( 'shield', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>אלפי לקוחות מרוצים</span>
			<h2 class="kindi-sec-title">מתחייבים ל<span class="kindi-hl">איכות ובטיחות</span></h2>
		</div>
	</div>
	<div class="kindi-values kindi-about-3">
		<?php foreach ( $kindi_quality as $v ) : ?>
		<article class="kindi-value kindi-value--<?php echo esc_attr( $v['acc'] ); ?>">
			<span class="kindi-value__bar" aria-hidden="true"></span>
			<div class="kindi-value__top"><span class="kindi-value__ic"><?php echo kindi_icon( $v['icon'], 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span></div>
			<h3 class="kindi-value__title"><?php echo esc_html( $v['title'] ); ?></h3>
			<p class="kindi-value__body"><?php echo esc_html( $v['body'] ); ?></p>
		</article>
		<?php endforeach; ?>
	</div>
</section>

<section class="kindi-about-cta">
	<span class="kindi-about-cta__badge"><?php echo kindi_icon( 'sparkles', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>קינדי מחכה לכם</span>
	<h2 class="kindi-about-cta__title">מוכנים לרגעים של <span>כיף?</span></h2>
	<p class="kindi-about-cta__lead">אלפי משחקים, יצירות וצעצועים מחכים לכם — בואו לגלות את המבחר, או דברו איתנו לכל שאלה.</p>
	<div class="kindi-about-cta__btns">
		<a class="kindi-about-cta__btn kindi-about-cta__btn--red" href="<?php echo esc_url( class_exists( 'WooCommerce' ) && function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">לכל המבצעים החמים<?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
		<a class="kindi-about-cta__btn kindi-about-cta__btn--ghost" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo kindi_icon( 'phone', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>שירות לקוחות</a>
	</div>
</section>
<!-- /wp:html -->
