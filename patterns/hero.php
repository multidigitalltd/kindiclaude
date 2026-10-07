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

			<?php
			// Trust row — panel-controlled (באנר ראשי); an empty value hides its item.
			$kindi_rating = (string) kindi_opt( 'hero_rating' );
			$kindi_trust1 = (string) kindi_opt( 'hero_trust1' );
			$kindi_trust2 = (string) kindi_opt( 'hero_trust2' );
			?>
			<div class="kindi-hero__trust">
				<?php if ( '' !== $kindi_rating ) : ?>
				<span class="kindi-hero__rating">
					<?php for ( $i = 0; $i < 5; $i++ ) {
						echo kindi_icon( 'star', 'kindi-icon--xs' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} ?>
					<b><?php echo esc_html( $kindi_rating ); ?></b> <small><?php echo esc_html( kindi_opt( 'hero_rating_note' ) ); ?></small>
				</span>
				<?php endif; ?>
				<?php if ( '' !== $kindi_trust1 ) : ?>
				<span class="kindi-hero__trust-item"><?php echo kindi_icon( 'truck', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $kindi_trust1 ); ?></span>
				<?php endif; ?>
				<?php if ( '' !== $kindi_trust2 ) : ?>
				<span class="kindi-hero__trust-item"><?php echo kindi_icon( 'sparkles', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><?php echo esc_html( $kindi_trust2 ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<div class="kindi-hero__visual">
			<span class="kindi-spark animate-sparkle" style="top:4%;left:12%"><?php echo kindi_icon( 'sparkles', 'kindi-icon--lg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-spark animate-sparkle" style="top:32%;right:-2%;animation-delay:0.8s"><?php echo kindi_icon( 'star', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-spark animate-sparkle" style="bottom:24%;left:4%;animation-delay:1.4s"><?php echo kindi_icon( 'star', 'kindi-icon--sm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<span class="kindi-ground-shadow animate-ground"></span>
			<?php echo kindi_mascot_img( 'hero_mascot', 'mascot/kindy-hero.webp', 'kindi-hero__mascot animate-mascot', '(max-width: 1023px) 300px, 520px', 'קינדי מציג את עולם המוצרים בקינדר טויס — צעצועים, ילקוטים, יצירה ומשחקי קופסה', array( 'fetchpriority' => 'high', 'decoding' => 'async' ) ); // phpcs:ignore WordPress.Security.EscapeOutput -- escaped within. ?>

			<span class="kindi-hero__pill kindi-hero__pill--top"><?php echo kindi_icon( 'sparkles', 'kindi-icon--md' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><small>חדש השבוע</small><b>+120 מוצרים</b></span></span>
			<span class="kindi-hero__pill kindi-hero__pill--bottom"><?php echo kindi_icon( 'truck', 'kindi-icon--lg kindi-icon--white' ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><small>משלוח מהיר</small><b>חינם מעל 299 ש"ח</b></span></span>
		</div>
	</div>
</section>
<!-- /wp:html -->
