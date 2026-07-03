<?php
namespace ChamberSign\Locator\Ajax;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Géocodage d'une adresse via l'API Nominatim (OpenStreetMap), utilisé
 * uniquement côté admin pour préremplir latitude/longitude.
 */
class Geocode {

	public const ACTION = 'csol_geocode';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Traite la requête de géocodage (réservée aux utilisateurs connectés
	 * pouvant éditer des bureaux).
	 */
	public function handle(): void {
		check_ajax_referer( self::ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Non autorisé.', 'chambersign-office-locator' ) ), 403 );
		}

		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';

		if ( '' === $query ) {
			wp_send_json_error( array( 'message' => __( 'Adresse vide.', 'chambersign-office-locator' ) ) );
		}

		$url = add_query_arg(
			array(
				'q'      => $query,
				'format' => 'json',
				'limit'  => 1,
			),
			'https://nominatim.openstreetmap.org/search'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 10,
				'headers' => array(
					'User-Agent' => 'ChamberSign-Office-Locator/' . CSOL_VERSION . ' (' . home_url() . ')',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array( 'message' => $response->get_error_message() ) );
		}

		$body    = wp_remote_retrieve_body( $response );
		$results = json_decode( $body, true );

		if ( empty( $results ) || ! isset( $results[0]['lat'], $results[0]['lon'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Adresse introuvable.', 'chambersign-office-locator' ) ) );
		}

		wp_send_json_success(
			array(
				'lat'          => (float) $results[0]['lat'],
				'lon'          => (float) $results[0]['lon'],
				'display_name' => isset( $results[0]['display_name'] ) ? sanitize_text_field( $results[0]['display_name'] ) : '',
			)
		);
	}
}
