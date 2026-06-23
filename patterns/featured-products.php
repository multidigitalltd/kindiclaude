<?php
/**
 * Title: Featured Products
 * Slug: kindi/featured-products
 * Categories: kindi
 * Description: גריד מוצרים נבחרים מ-WooCommerce.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<section class="kindi-section">
	<?php echo kindi_section_head( array( // phpcs:ignore WordPress.Security.EscapeOutput
		'eyebrow'   => 'נבחרים בשבילכם',
		'title'     => 'המוצרים',
		'highlight' => 'החמים שלנו',
	) ); ?>
</section>
<!-- /wp:html -->

<!-- wp:shortcode -->
[kindi_hot_products]
<!-- /wp:shortcode -->
