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
		<?php
		if ( $kindi_has_google ) :
			$kindi_grev_link = ! empty( $kindi_google['link'] ) ? (string) $kindi_google['link'] : '';
			$kindi_grev_tag  = $kindi_grev_link ? 'a' : 'div';
			?>
		<<?php echo $kindi_grev_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?> class="kindi-grev__score<?php echo $kindi_grev_link ? ' kindi-grev__score--link' : ''; ?>"<?php echo $kindi_grev_link ? ' href="' . esc_url( $kindi_grev_link ) . '" target="_blank" rel="noopener" title="לצפייה בכל הביקורות בגוגל"' : ''; ?>>
			<strong><?php echo esc_html( number_format( (float) $kindi_google['rating'], 1 ) ); ?></strong>
			<span class="kindi-grev__stars"><?php for ( $s = 0; $s < 5; $s++ ) {
				echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
			} ?></span>
			<span class="kindi-grev__count"><?php echo esc_html( number_format_i18n( (int) $kindi_google['total'] ) ); ?>+ ביקורות בגוגל</span>
		</<?php echo $kindi_grev_tag; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
		<?php endif; ?>
	</div>
	<div class="kindi-tst">
		<?php foreach ( $kindi_tst as $kindi_i => $t ) : ?>
		<article class="kindi-tst__card<?php echo $kindi_i >= 3 ? ' is-hidden' : ''; ?>">
			<span class="kindi-tst__quote" aria-hidden="true">”</span>
			<div class="kindi-tst__stars">
				<?php for ( $i = 0; $i < 5; $i++ ) {
					echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput
				} ?>
			</div>
			<p class="kindi-tst__text"><?php echo esc_html( $t['text'] ); ?></p>
			<div class="kindi-tst__foot">
				<?php if ( ! empty( $t['photo'] ) ) : ?>
				<img class="kindi-tst__avatar kindi-tst__avatar--img" src="<?php echo esc_url( $t['photo'] ); ?>" alt="" loading="lazy" decoding="async" width="44" height="44" referrerpolicy="no-referrer">
				<?php else : ?>
				<span class="kindi-tst__avatar"><?php echo esc_html( $t['letter'] ); ?></span>
				<?php endif; ?>
				<span>
					<span class="kindi-tst__name"><?php echo esc_html( $t['name'] ); ?></span><br>
					<span class="kindi-tst__role"><?php echo esc_html( $t['role'] ?? 'לקוח/ה מ-Google' ); ?></span>
				</span>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
	<?php if ( count( $kindi_tst ) > 3 ) : ?>
	<div class="kindi-tst-more">
		<button type="button" class="kindi-btn kindi-btn--ghost" data-kindi-more-reviews><?php echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>טען עוד ביקורות</button>
	</div>
	<?php endif; ?>
</section>
<!-- /wp:html -->
