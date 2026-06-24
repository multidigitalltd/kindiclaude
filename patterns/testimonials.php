<?php
/**
 * Title: Testimonials
 * Slug: kindi/testimonials
 * Categories: kindi
 * Description: המלצות לקוחות — נמשכות מביקורות גוגל אם הוגדרו, אחרת ברירת מחדל.
 *
 * @package Kindi
 */

$kindi_google = function_exists( 'kindi_google_reviews' ) ? kindi_google_reviews() : array();

$kindi_tst = ! empty( $kindi_google['reviews'] ) ? $kindi_google['reviews'] : array(
	array( 'text' => 'חנות מדהימה! קניתי לבן שלי ילקוט ומחברות לכיתה א\' — השירות מקסים, המחירים נהדרים והמשלוח הגיע למחרת. ממליצה לכולם!', 'name' => 'שרה כהן', 'role' => 'אמא לארבעה • בני ברק', 'letter' => 'ש' ),
	array( 'text' => 'אני גננת שמזמינה מקינדר טויס כבר 5 שנים. המבחר של חומרי היצירה מצוין, המחירים הוגנים והם תמיד מוכנים להמליץ. מקצועיים אמיתיים.', 'name' => 'מירי פרץ', 'role' => 'גננת • ירושלים', 'letter' => 'מ' ),
	array( 'text' => 'אתר נוח להזמנה, מבחר ענק של משחקי קופסה ובובות. הילדים שלי מתים על המשחקים שקניתי לחנוכה. גם השירות הטלפוני מעולה!', 'name' => 'יוסי לוי', 'role' => 'אבא מאושר • פתח תקווה', 'letter' => 'י' ),
);

$kindi_has_google = ! empty( $kindi_google['reviews'] );
?>
<!-- wp:html -->
<section class="kindi-section">
	<div class="kindi-sechead">
		<div class="kindi-sechead__text">
			<span class="kindi-eyebrow"><?php echo $kindi_has_google ? '★ Google' : 'לקוחות מספרים'; ?></span>
			<h2 class="kindi-sec-title">למה <span class="kindi-hl">בוחרים בקינדי</span>?</h2>
		</div>
		<?php if ( $kindi_has_google ) : ?>
		<div class="kindi-grev__score">
			<strong><?php echo esc_html( number_format( (float) $kindi_google['rating'], 1 ) ); ?></strong>
			<span class="kindi-grev__stars"><?php for ( $s = 0; $s < 5; $s++ ) {
				echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} ?></span>
			<span class="kindi-grev__count"><?php echo esc_html( number_format_i18n( (int) $kindi_google['total'] ) ); ?>+ ביקורות בגוגל</span>
		</div>
		<?php endif; ?>
	</div>
	<div class="kindi-tst">
		<?php foreach ( array_slice( $kindi_tst, 0, 3 ) as $t ) : ?>
		<article class="kindi-tst__card">
			<span class="kindi-tst__quote" aria-hidden="true">”</span>
			<div class="kindi-tst__stars">
				<?php for ( $i = 0; $i < 5; $i++ ) {
					echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
				} ?>
			</div>
			<p class="kindi-tst__text"><?php echo esc_html( $t['text'] ); ?></p>
			<div class="kindi-tst__foot">
				<span class="kindi-tst__avatar"><?php echo esc_html( $t['letter'] ); ?></span>
				<span>
					<span class="kindi-tst__name"><?php echo esc_html( $t['name'] ); ?></span><br>
					<span class="kindi-tst__role"><?php echo esc_html( $t['role'] ?? 'לקוח/ה מ-Google' ); ?></span>
				</span>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
