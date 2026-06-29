<?php
/**
 * Title: Values Strip
 * Slug: kindi/values
 * Categories: kindi
 * Description: הבטחות המותג — אבטחה, מבחר, שירות.
 *
 * @package Kindi
 */

$kindi_values = array(
	array( 'icon' => 'shield', 'acc' => 'navy', 'title' => 'רכישה מאובטחת', 'body' => 'אתר מאובטח בתקני SSL מהמחמירים בעולם — פרטיכם ופרטי האשראי שמורים אצלנו.' ),
	array( 'icon' => 'sparkles', 'acc' => 'red', 'title' => 'הבית של החוויות', 'body' => 'הכל תחת גג אחד — משחקים, יצירה, מכשירי כתיבה וציוד לגננות. תמיד בסטוק.' ),
	array( 'icon' => 'heart', 'acc' => 'blue', 'title' => 'משפחת קינדרטויס', 'body' => 'ייעוץ אישי בוואטסאפ ובטלפון, התאמה חווייתית לפי גיל — מרגישים בבית.' ),
	array( 'icon' => 'crown', 'acc' => 'red', 'title' => 'שירות VIP', 'body' => 'משלוחים מהירים והחזרות נינוחות לכל חלקי הארץ — איתכם מההזמנה ועד הפתיחה.' ),
);
?>
<!-- wp:html -->
<section class="kindi-section">
	<div class="kindi-values__head">
		<div class="kindi-sechead__text">
			<span class="kindi-eyebrow"><?php echo kindi_icon( 'sparkles', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>למה דווקא אצלנו</span>
			<h2 class="kindi-sec-title">הבטחת קינדרטויס — <span class="kindi-hl">בכל הזמנה.</span></h2>
		</div>
		<img class="kindi-values__mascot animate-mascot" src="<?php echo esc_url( kindi_mascot_src( 'values_mascot', 'mascot/wave.webp' ) ); ?>" alt="" loading="lazy" decoding="async">
	</div>
	<div class="kindi-values">
		<?php foreach ( $kindi_values as $v ) : ?>
		<article class="kindi-value kindi-value--<?php echo esc_attr( $v['acc'] ); ?>">
			<span class="kindi-value__bar" aria-hidden="true"></span>
			<div class="kindi-value__top">
				<span class="kindi-value__ic"><?php echo kindi_icon( $v['icon'], 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<span class="kindi-value__chip">הבטחה</span>
			</div>
			<h3 class="kindi-value__title"><?php echo esc_html( $v['title'] ); ?></h3>
			<p class="kindi-value__body"><?php echo esc_html( $v['body'] ); ?></p>
		</article>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
