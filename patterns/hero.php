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
<!-- wp:group {"className":"kindi-hero","style":{"border":{"radius":"1.5rem"},"spacing":{"padding":{"top":"2.5rem","bottom":"2.5rem","left":"2rem","right":"2rem"}}},"layout":{"type":"constrained","contentSize":"1440px"}} -->
<div class="wp-block-group kindi-hero" style="border-radius:1.5rem;padding-top:2.5rem;padding-right:2rem;padding-bottom:2.5rem;padding-left:2rem">
	<!-- wp:columns {"verticalAlignment":"center"} -->
	<div class="wp-block-columns are-vertically-aligned-center">

		<!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
		<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%">

			<!-- wp:group {"className":"kindi-hero__badge","style":{"border":{"radius":"999px"},"spacing":{"padding":{"top":"0.375rem","bottom":"0.375rem","left":"1rem","right":"1rem"}}},"backgroundColor":"white","layout":{"type":"flex","verticalAlignment":"center"}} -->
			<div class="wp-block-group kindi-hero__badge has-white-background-color has-background" style="border-radius:999px;padding-top:0.375rem;padding-right:1rem;padding-bottom:0.375rem;padding-left:1rem">
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.75rem","fontWeight":"700"}},"textColor":"brand-navy"} -->
				<p class="has-brand-navy-color has-text-color" style="font-size:0.75rem;font-weight:700">חדש בקינדר טויס • קולקציית 2026</p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:heading {"level":1,"className":"kindi-hero__title","style":{"typography":{"fontFamily":"var:preset|font-family|display","fontWeight":"900","lineHeight":"1.05"}}} -->
			<h1 class="wp-block-heading kindi-hero__title" style="font-weight:900;line-height:1.05">עולם של <span class="has-brand-red-color has-text-color">קסם, משחק ויצירה</span><br><span class="has-brand-blue-color has-text-color">בלחיצה אחת</span></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"500"},"color":{"text":"#1b2a52bf"}}} -->
			<p style="color:#1b2a52bf;font-weight:500">אלפי צעצועים, משחקים, חומרי יצירה וציוד לבית הספר ולגן — משלוח מהיר ושירות אישי מהלב.</p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"style":{"spacing":{"blockGap":"0.75rem"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"backgroundColor":"brand-red","style":{"border":{"radius":"1rem"}}} -->
				<div class="wp-block-button"><a class="wp-block-button__link has-brand-red-background-color has-background wp-element-button" style="border-radius:1rem">לכל המבצעים החמים</a></div>
				<!-- /wp:button -->
				<!-- wp:button {"textColor":"brand-navy","className":"is-style-outline","style":{"border":{"radius":"1rem"}}} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-brand-navy-color has-text-color wp-element-button" style="border-radius:1rem">לכל המוצרים</a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->

			<!-- wp:paragraph {"style":{"typography":{"fontSize":"0.8125rem","fontWeight":"600"}},"textColor":"brand-navy"} -->
			<p class="has-brand-navy-color has-text-color" style="font-size:0.8125rem;font-weight:600">★★★★★ 4.9 (+50,000 הורים) &nbsp;·&nbsp; משלוח מחר עד הבית &nbsp;·&nbsp; +10,000 מוצרים במלאי</p>
			<!-- /wp:paragraph -->

		</div>
		<!-- /wp:column -->

		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:image {"className":"kindi-hero__mascot animate-mascot","sizeSlug":"large","linkDestination":"none"} -->
			<figure class="wp-block-image size-large kindi-hero__mascot animate-mascot"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/kindy-hero.png' ); ?>" alt="קינדי מציג את עולם המוצרים בקינדר טויס — צעצועים, ילקוטים, יצירה ומשחקי קופסה"/></figure>
			<!-- /wp:image -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->
</div>
<!-- /wp:group -->
