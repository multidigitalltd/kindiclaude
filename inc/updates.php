<?php
/**
 * In-dashboard theme updates, served from the theme's GitHub releases.
 *
 * Every release of the (private) repo carries the built kindi.zip as an
 * asset; when its version is newer than the installed one, WordPress shows
 * the standard "עדכון זמין / עדכן עכשיו" on the Themes and Updates screens.
 * Access needs a fine-grained GitHub token with read-only Contents permission
 * on the repo — defined as KINDI_UPDATE_TOKEN in wp-config.php (preferred) or
 * pasted in the panel (עדכוני תבנית). Without a token the whole module is a
 * no-op. The remote check is cached for 6 hours; "בדוק שוב" on the Updates
 * screen refreshes it.
 *
 * @package Kindi
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

const KINDI_UPDATE_REPO = 'multidigitalltd/kindiclaude';

/**
 * The GitHub token used for update checks (constant wins over the panel).
 *
 * @return string
 */
function kindi_update_token(): string {
	if ( defined( 'KINDI_UPDATE_TOKEN' ) && is_string( KINDI_UPDATE_TOKEN ) && '' !== KINDI_UPDATE_TOKEN ) {
		return KINDI_UPDATE_TOKEN;
	}
	return trim( (string) kindi_opt( 'update_token' ) );
}

/**
 * Latest release info: version + package (asset API URL), cached 6h.
 *
 * @return array{version:string,package:string,url:string}|null Null when no
 *         token, no release, or the release has no kindi.zip asset.
 */
function kindi_update_remote(): ?array {
	if ( '' === kindi_update_token() ) {
		return null;
	}

	$cached = get_site_transient( 'kindi_update_remote' );
	if ( is_array( $cached ) ) {
		return $cached ? $cached : null;
	}

	$resp = wp_remote_get(
		'https://api.github.com/repos/' . KINDI_UPDATE_REPO . '/releases/latest',
		array(
			'timeout' => 10,
			'headers' => array(
				'Accept'               => 'application/vnd.github+json',
				'Authorization'        => 'Bearer ' . kindi_update_token(),
				'X-GitHub-Api-Version' => '2022-11-28',
			),
		)
	);

	$out = array();
	if ( ! is_wp_error( $resp ) && 200 === wp_remote_retrieve_response_code( $resp ) ) {
		$data    = json_decode( wp_remote_retrieve_body( $resp ), true );
		$version = isset( $data['tag_name'] ) ? ltrim( (string) $data['tag_name'], 'v' ) : '';
		$package = '';
		foreach ( (array) ( $data['assets'] ?? array() ) as $asset ) {
			if ( 'kindi.zip' === (string) ( $asset['name'] ?? '' ) ) {
				$package = (string) ( $asset['url'] ?? '' ); // Asset API URL — downloads as octet-stream (see kindi_update_download_args).
				break;
			}
		}
		if ( '' !== $version && '' !== $package ) {
			$out = array(
				'version' => $version,
				'package' => $package,
				'url'     => (string) ( $data['html_url'] ?? '' ),
			);
		}
	}

	// An empty array is cached too, so a missing release doesn't re-hit the API
	// on every admin load.
	set_site_transient( 'kindi_update_remote', $out, 6 * HOUR_IN_SECONDS );

	return $out ? $out : null;
}

/**
 * Offer the release as a theme update when it is newer than the installed one.
 *
 * @param object|mixed $transient The update_themes transient value.
 * @return object|mixed
 */
function kindi_update_inject( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	$slug    = get_template();
	$current = (string) wp_get_theme( $slug )->get( 'Version' );
	$remote  = kindi_update_remote();

	if ( $remote && '' !== $current && version_compare( $remote['version'], $current, '>' ) ) {
		$transient->response[ $slug ] = array(
			'theme'       => $slug,
			'new_version' => $remote['version'],
			'url'         => $remote['url'],
			'package'     => $remote['package'],
		);
	}

	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'kindi_update_inject' );

/**
 * Auth + octet-stream headers when WordPress downloads the release asset
 * (private repo assets are only served with both).
 *
 * @param array<string,mixed> $args Request args.
 * @param string              $url  Request URL.
 * @return array<string,mixed>
 */
function kindi_update_download_args( array $args, string $url ): array {
	if ( 0 === strpos( $url, 'https://api.github.com/repos/' . KINDI_UPDATE_REPO . '/releases/assets/' ) ) {
		$args['headers'] = array_merge(
			(array) ( $args['headers'] ?? array() ),
			array(
				'Accept'        => 'application/octet-stream',
				'Authorization' => 'Bearer ' . kindi_update_token(),
			)
		);
	}
	return $args;
}
add_filter( 'http_request_args', 'kindi_update_download_args', 10, 2 );

/**
 * "בדוק שוב" on the Updates screen busts the 6-hour remote cache.
 *
 * @return void
 */
function kindi_update_force_check(): void {
	if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only cache bust.
		delete_site_transient( 'kindi_update_remote' );
	}
}
add_action( 'load-update-core.php', 'kindi_update_force_check' );
