<?php
/**
 * Title: Testimonials
 * Slug: kindi/testimonials
 * Categories: kindi
 * Description: המלצות לקוחות.
 *
 * @package Kindi
 */

$kindi_tst = array(
	array(
		'text'   => 'חנות מדהימה! קניתי לבן שלי ילקוט ומחברות לכיתה א\' — השירות מקסים, המחירים נהדרים והמשלוח הגיע למחרת. ממליצה לכולם!',
		'name'   => 'שרה כהן',
		'role'   => 'אמא לארבעה • בני ברק',
		'letter' => 'ש',
	),
	array(
		'text'   => 'אני גננת שמזמינה מקינדר טויס כבר 5 שנים. המבחר של חומרי היצירה מצוין, המחירים הוגנים והם תמיד מוכנים להמליץ. מקצועיים אמיתיים.',
		'name'   => 'מירי פרץ',
		'role'   => 'גננת • ירושלים',
		'letter' => 'מ',
	),
	array(
		'text'   => 'אתר נוח להזמנה, מבחר ענק של משחקי קופסה ובובות. הילדים שלי מתים על המשחקים שקניתי לחנוכה. גם השירות הטלפוני מעולה!',
		'name'   => 'יוסי לוי',
		'role'   => 'אבא מאושר • פתח תקווה',
		'letter' => 'י',
	),
);
?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'לקוחות מספרים',
		'title'     => 'למה',
		'highlight' => 'בוחרים בקינדי',
		'suffix'    => '?',
	) ); ?>
	<div class="kindi-tst">
		<?php foreach ( $kindi_tst as $t ) : ?>
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
					<span class="kindi-tst__role"><?php echo esc_html( $t['role'] ); ?></span>
				</span>
			</div>
		</article>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
