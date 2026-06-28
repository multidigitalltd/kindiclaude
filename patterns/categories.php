<?php
/**
 * Title: Category Grid (dynamic)
 * Slug: kindi/categories
 * Categories: kindi
 * Description: גריד קטגוריות — נמשך אוטומטית מקטגוריות המוצרים של WooCommerce (עם מטמון), עם נפילה לתצוגת ברירת מחדל.
 *
 * @package Kindi
 */

?>
<!-- wp:html -->
<?php echo do_shortcode( '[kindi_categories]' ); // phpcs:ignore WordPress.Security.EscapeOutput -- Shortcode output is escaped internally. ?>
<!-- /wp:html -->
