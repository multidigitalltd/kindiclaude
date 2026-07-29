<?php
/**
 * TEMPORARY tool — Kindi ▸ "עמודי אלמנטור". Elementor was uninstalled but some
 * pages still carry its data/flags, so the block editor opens them empty and
 * "uneditable". This lists those pages and converts each to a normal page:
 * pulls the text out of _elementor_data into post_content (only when the content
 * is empty, never overwriting) and removes the _elementor_edit_mode flag so
 * Gutenberg treats it normally. The Elementor meta is kept as a backup.
 *
 * Remove after the pages are converted.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Register the tool under the Kindi menu.
 *
 * @return void
 */
function kindi_elx_menu(): void {
	add_submenu_page(
		'kindi-settings',
		__( 'עמודי אלמנטור', 'kindi' ),
		__( 'עמודי אלמנטור', 'kindi' ),
		'manage_options',
		'kindi-elementor',
		'kindi_elx_page'
	);
}
add_action( 'admin_menu', 'kindi_elx_menu' );

/**
 * Page IDs that still carry Elementor's builder flag.
 *
 * @return int[]
 */
function kindi_elx_ids(): array {
	return array_map(
		'intval',
		get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'numberposts'    => -1,
				'fields'         => 'ids',
				'meta_key'       => '_elementor_edit_mode', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
		)
	);
}

/**
 * Recursively pull readable content out of an Elementor element tree, as block
 * markup (headings, paragraphs, raw HTML for rich text).
 *
 * @param array<int,mixed> $elements Elementor elements.
 * @return string
 */
function kindi_elx_extract( array $elements ): string {
	$out = '';
	foreach ( $elements as $el ) {
		if ( ! is_array( $el ) ) {
			continue;
		}
		$settings = isset( $el['settings'] ) && is_array( $el['settings'] ) ? $el['settings'] : array();
		$widget   = (string) ( $el['widgetType'] ?? '' );

		if ( 'heading' === $widget && ! empty( $settings['title'] ) ) {
			$tag = (string) ( $settings['header_size'] ?? 'h2' );
			$tag = in_array( $tag, array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), true ) ? $tag : 'h2';
			$lvl = (int) substr( $tag, 1 );
			$out .= '<!-- wp:heading' . ( 2 !== $lvl ? ' {"level":' . $lvl . '}' : '' ) . " -->\n<" . $tag . '>' . wp_kses_post( (string) $settings['title'] ) . '</' . $tag . ">\n<!-- /wp:heading -->\n\n";
		} elseif ( 'text-editor' === $widget && ! empty( $settings['editor'] ) ) {
			$out .= "<!-- wp:html -->\n" . wp_kses_post( (string) $settings['editor'] ) . "\n<!-- /wp:html -->\n\n";
		} elseif ( ! empty( $settings['text'] ) && is_string( $settings['text'] ) ) {
			$out .= "<!-- wp:paragraph -->\n<p>" . wp_kses_post( $settings['text'] ) . "</p>\n<!-- /wp:paragraph -->\n\n";
		}

		if ( ! empty( $el['elements'] ) && is_array( $el['elements'] ) ) {
			$out .= kindi_elx_extract( $el['elements'] );
		}
	}
	return $out;
}

/**
 * Block markup extracted from a page's Elementor data (empty when none).
 *
 * @param int $id Page ID.
 * @return string
 */
function kindi_elx_content_for( int $id ): string {
	$raw = (string) get_post_meta( $id, '_elementor_data', true );
	if ( '' === $raw ) {
		return '';
	}
	$data = json_decode( $raw, true );
	return is_array( $data ) ? trim( kindi_elx_extract( $data ) ) : '';
}

/**
 * Render the tool.
 *
 * @return void
 */
function kindi_elx_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['converted'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'העמוד הומר לעמוד רגיל וניתן לעריכה כעת.', 'kindi' ) . '</p></div>';
	}

	$ids = kindi_elx_ids();

	echo '<div class="wrap"><h1>' . esc_html__( 'עמודים שנבנו באלמנטור', 'kindi' ) . '</h1>';
	echo '<p class="description">' . esc_html__( 'המרה ממלאת את תוכן העמוד מנתוני אלמנטור (רק אם התוכן ריק — לא דורסת) ומסירה את דגל הבנאי כדי שהעמוד ייפתח לעריכה רגילה. נתוני אלמנטור נשמרים כגיבוי.', 'kindi' ) . '</p>';

	if ( ! $ids ) {
		echo '<p>' . esc_html__( 'לא נמצאו עמודים עם שאריות אלמנטור. אם עמוד עדיין לא נערך — כנראה שהסיבה אחרת (ראו בדיקת Console/Cloudflare).', 'kindi' ) . '</p></div>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>'
		. '<th>' . esc_html__( 'עמוד', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'תוכן נוכחי', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'טקסט באלמנטור', 'kindi' ) . '</th>'
		. '<th>' . esc_html__( 'פעולה', 'kindi' ) . '</th>'
		. '</tr></thead><tbody>';

	foreach ( $ids as $id ) {
		$post = get_post( $id );
		if ( ! $post instanceof WP_Post ) {
			continue;
		}
		$has_content = '' !== trim( (string) $post->post_content );
		$extracted   = kindi_elx_content_for( $id );

		echo '<tr>';
		printf( '<td><a href="%s"><strong>%s</strong></a></td>', esc_url( (string) get_edit_post_link( $id ) ), esc_html( get_the_title( $id ) ) );
		echo '<td>' . ( $has_content ? esc_html__( 'קיים תוכן', 'kindi' ) : '<span style="color:#b32d2e">' . esc_html__( 'ריק', 'kindi' ) . '</span>' ) . '</td>';
		echo '<td>' . ( '' !== $extracted ? esc_html__( 'נמצא — יחולץ', 'kindi' ) : esc_html__( 'אין / לא זוהה', 'kindi' ) ) . '</td>';
		echo '<td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin:0">';
		echo '<input type="hidden" name="action" value="kindi_elx_convert">';
		echo '<input type="hidden" name="page_id" value="' . esc_attr( (string) $id ) . '">';
		wp_nonce_field( 'kindi_elx_convert_' . $id );
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'המר לעמוד רגיל', 'kindi' ) . '</button>';
		echo '</form></td></tr>';
	}

	echo '</tbody></table></div>';
}

/**
 * Convert one page: fill empty content from Elementor data, drop the builder
 * flag (keeps _elementor_data as a backup).
 *
 * @return void
 */
function kindi_elx_convert(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'אין הרשאה.', 'kindi' ) );
	}
	$id = isset( $_POST['page_id'] ) ? absint( $_POST['page_id'] ) : 0;
	check_admin_referer( 'kindi_elx_convert_' . $id );

	if ( $id ) {
		$post = get_post( $id );
		if ( $post instanceof WP_Post ) {
			if ( '' === trim( (string) $post->post_content ) ) {
				$content = kindi_elx_content_for( $id );
				if ( '' !== $content ) {
					wp_update_post(
						array(
							'ID'           => $id,
							'post_content' => $content,
						)
					);
				}
			}
			// Remove the builder flag so Gutenberg treats it as a normal page.
			delete_post_meta( $id, '_elementor_edit_mode' );
		}
	}

	wp_safe_redirect( add_query_arg( array( 'page' => 'kindi-elementor', 'converted' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}
add_action( 'admin_post_kindi_elx_convert', 'kindi_elx_convert' );
