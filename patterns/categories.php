<?php
/**
 * Title: Category Grid
 * Slug: kindi/categories
 * Categories: kindi
 * Description: גריד קטגוריות מובילות עם תמונות.
 *
 * @package Kindi
 */

$kindi_cats = array(
	array( 'img' => 'blocks.jpg', 'name' => 'צעצועים', 'count' => '+2,400 מוצרים', 'acc' => 'red' ),
	array( 'img' => 'boardgame.jpg', 'name' => 'משחקי קופסה', 'count' => '+850 מוצרים', 'acc' => 'navy' ),
	array( 'img' => 'back-to-school.jpg', 'name' => 'חזרה לביה"ס', 'count' => '+1,200 מוצרים', 'acc' => 'blue' ),
	array( 'img' => 'art-supplies.jpg', 'name' => 'יצירה ואומנות', 'count' => '+1,800 מוצרים', 'acc' => 'red' ),
	array( 'img' => 'promo-art.jpg', 'name' => 'ציוד לגננת', 'count' => '+900 מוצרים', 'acc' => 'navy' ),
	array( 'img' => 'puzzle.jpg', 'name' => 'פאזלים', 'count' => '+420 מוצרים', 'acc' => 'blue' ),
	array( 'img' => 'baby-plush.jpg', 'name' => 'תינוקות ופעוטות', 'count' => '+650 מוצרים', 'acc' => 'red' ),
	array( 'img' => 'gameboy.jpg', 'name' => 'משחקי למידה', 'count' => '+1,100 מוצרים', 'acc' => 'navy' ),
	array( 'img' => 'playmobil.jpg', 'name' => 'בובות ודמיון', 'count' => '+520 מוצרים', 'acc' => 'red' ),
	array( 'img' => 'promo-playmobil.jpg', 'name' => 'כלי תחבורה', 'count' => '+380 מוצרים', 'acc' => 'blue' ),
	array( 'img' => 'promo-back-to-school.jpg', 'name' => 'כתיבה ולמידה', 'count' => '+290 מוצרים', 'acc' => 'navy' ),
	array( 'img' => 'promo-blocks.jpg', 'name' => 'מתנות ופרסים', 'count' => '+760 מוצרים', 'acc' => 'red' ),
);
?>
<!-- wp:html -->
<section class="kindi-section" id="categories">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'קטגוריות מובילות',
		'title'     => 'בחרו את',
		'highlight' => 'העולם המתאים',
		'suffix'    => 'לכם',
		'desc'      => 'קולקציה מסודרת לפי עניין, גיל ומטרה — כדי שתמצאו בדיוק מה שאתם מחפשים',
	) ); ?>
	<div class="kindi-cats">
		<?php foreach ( $kindi_cats as $c ) : ?>
		<a class="kindi-cat kindi-cat--<?php echo esc_attr( $c['acc'] ); ?>" href="#">
			<span class="kindi-cat__media">
				<img src="<?php echo kindi_img( 'photos/' . $c['img'] ); ?>" alt="<?php echo esc_attr( $c['name'] ); ?>" loading="lazy" width="400" height="400">
				<span class="kindi-cat__tint"></span>
				<span class="kindi-cat__pill">לצפייה במוצרים ←</span>
			</span>
			<span class="kindi-cat__bar"></span>
			<span class="kindi-cat__name">
				<b><?php echo esc_html( $c['name'] ); ?></b>
				<span class="kindi-cat__count"><?php echo esc_html( $c['count'] ); ?></span>
			</span>
		</a>
		<?php endforeach; ?>
	</div>
</section>
<!-- /wp:html -->
