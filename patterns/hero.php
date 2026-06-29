<?php
/**
 * Title: Hero
 * Slug: kindi/hero
 * Categories: kindi, featured
 * Description: באנר ראשי — כותרת, קריאות לפעולה, דירוג ומסקוט קינדי.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<section class="kindi-section kindi-hero">
	<span class="kindi-hero__blob kindi-hero__blob--1" aria-hidden="true"></span>
	<span class="kindi-hero__blob kindi-hero__blob--2" aria-hidden="true"></span>
	<span class="kindi-hero__dots" aria-hidden="true"></span>

	<div class="kindi-hero__grid">
		<div class="kindi-hero__copy">
			<span class="kindi-hero__badge"><span class="kindi-hero__dot" aria-hidden="true"></span><?php echo esc_html( kindi_opt( 'hero_badge' ) ); ?></span>
			<h1 class="kindi-hero__title"><?php echo esc_html( kindi_opt( 'hero_title1' ) ); ?> <span class="kindi-hero__underline"><?php echo esc_html( kindi_opt( 'hero_hl' ) ); ?></span><br><span class="kindi-hero__blue"><?php echo esc_html( kindi_opt( 'hero_title2' ) ); ?></span></h1>
			<p class="kindi-hero__lead"><?php echo esc_html( kindi_opt( 'hero_lead' ) ); ?></p>

			<div class="kindi-hero__cta">
				<a class="kindi-btn kindi-btn--red" href="<?php echo esc_url( kindi_opt( 'hero_cta1_url' ) ); ?>"><?php echo esc_html( kindi_opt( 'hero_cta1' ) ); ?><?php echo kindi_icon( 'arrowleft', 'kindi-icon--sm kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a>
				<a class="kindi-btn kindi-btn--ghost" href="<?php echo esc_url( kindi_opt( 'hero_cta2_url' ) ); ?>"><?php echo kindi_icon( 'play', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( kindi_opt( 'hero_cta2' ) ); ?></a>
			</div>

			<div class="kindi-hero__trust">
				<span class="kindi-hero__rating">
					<?php for ( $i = 0; $i < 5; $i++ ) {
						echo kindi_icon( 'star', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} ?>
					<b>4.9</b> <small>(+50,000 הורים)</small>
				</span>
				<span class="kindi-hero__trust-item"><?php echo kindi_icon( 'truck', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>משלוח מחר עד הבית</span>
				<span class="kindi-hero__trust-item"><?php echo kindi_icon( 'sparkles', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>+10,000 מוצרים במלאי</span>
			</div>
		</div>

		<div class="kindi-hero__visual">
			<span class="kindi-spark animate-sparkle" style="top:4%;left:12%"><?php echo kindi_icon( 'sparkles', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-spark animate-sparkle" style="top:32%;right:-2%;animation-delay:0.8s"><?php echo kindi_icon( 'star', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-spark animate-sparkle" style="bottom:24%;left:4%;animation-delay:1.4s"><?php echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-ground-shadow animate-ground"></span>
			<img class="kindi-hero__mascot animate-mascot" src="<?php echo esc_url( kindi_mascot_src( 'hero_mascot', 'mascot/original.webp' ) ); ?>" width="520" height="520" fetchpriority="high" decoding="async" alt="קינדי מציג את עולם המוצרים בקינדר טויס — צעצועים, ילקוטים, יצירה ומשחקי קופסה">

			<span class="kindi-hero__pill kindi-hero__pill--top"><?php echo kindi_icon( 'sparkles', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><small>חדש השבוע</small><b>+120 מוצרים</b></span></span>
			<span class="kindi-hero__pill kindi-hero__pill--bottom"><?php echo kindi_icon( 'truck', 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><small>משלוח מחר</small><b>חינם מעל 299₪</b></span></span>
		</div>
	</div>
</section>
<!-- /wp:html -->
