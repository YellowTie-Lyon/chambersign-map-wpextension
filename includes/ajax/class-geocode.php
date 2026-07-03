<?php
namespace ChamberSign\Locator\Ajax;

use ChamberSign\Locator\Cpt\Bureau;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Géocodage via l'API Nominatim (OpenStreetMap), utilisé côté admin pour
 * préremplir latitude/longitude : au cas par cas depuis la meta box, ou
 * en masse depuis l'écran d'import pour les bureaux sans coordonnées.
 */
class Geocode {

	public const ACTION       = 'csol_geocode';
	public const BATCH_ACTION = 'csol_geocode_batch';

	/**
	 * Nombre de bureaux traités par appel AJAX de géocodage en masse.
	 * Nominatim impose 1 requête/seconde : ce lot reste sous la plupart
	 * des limites de temps d'exécution PHP.
	 */
	private const BATCH_SIZE = 5;

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_' . self::BATCH_ACTION, array( $this, 'handle_batch' ) );
	}

	/**
	 * Géocode une adresse ponctuelle (bouton dans la meta box du bureau).
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

		$result = $this->lookup( $query );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * Géocode par lots les bureaux publiés sans coordonnées GPS, pour
	 * traiter de gros imports sans dépasser le temps d'exécution PHP.
	 * Appelé en boucle par le JS admin jusqu'à ce que "remaining" soit à 0.
	 */
	public function handle_batch(): void {
		check_ajax_referer( self::ACTION, 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Non autorisé.', 'chambersign-office-locator' ) ), 403 );
		}

		$force   = ! empty( $_POST['force'] );
		$missing = self::get_bureaux_missing_coordinates( $force );
		$batch   = array_slice( $missing, 0, self::BATCH_SIZE );

		$succeeded = 0;
		$failed    = 0;
		$first     = true;

		foreach ( $batch as $post_id ) {
			if ( ! $first ) {
				// Respecte la politique d'usage de Nominatim (1 requête/seconde).
				sleep( 1 );
			}
			$first = false;

			$adresse     = get_post_meta( $post_id, Bureau::META_PREFIX . 'adresse', true );
			$code_postal = get_post_meta( $post_id, Bureau::META_PREFIX . 'code_postal', true );
			$ville       = get_post_meta( $post_id, Bureau::META_PREFIX . 'ville', true );
			$query       = trim( implode( ', ', array_filter( array( $adresse, $code_postal, $ville ) ) ) );

			if ( '' === $query ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'geocode_failed', '1' );
				$failed++;
				continue;
			}

			$result = $this->lookup( $query );

			if ( is_wp_error( $result ) ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'geocode_failed', '1' );
				$failed++;
				continue;
			}

			update_post_meta( $post_id, Bureau::META_PREFIX . 'latitude', $result['lat'] );
			update_post_meta( $post_id, Bureau::META_PREFIX . 'longitude', $result['lon'] );
			delete_post_meta( $post_id, Bureau::META_PREFIX . 'geocode_failed' );
			$succeeded++;
		}

		wp_send_json_success(
			array(
				'processed' => count( $batch ),
				'succeeded' => $succeeded,
				'failed'    => $failed,
				'remaining' => max( 0, count( $missing ) - count( $batch ) ),
			)
		);
	}

	/**
	 * Interroge Nominatim pour une requête texte donnée.
	 *
	 * @param string $query Adresse à géocoder.
	 *
	 * @return array{lat: float, lon: float, display_name: string}|\WP_Error
	 */
	private function lookup( string $query ) {
		$url = add_query_arg(
			array(
				'q'            => $query,
				'format'       => 'json',
				'limit'        => 1,
				// Les bureaux ChamberSign sont tous en France : sans cette
				// contrainte, une adresse ambiguë ou mal formée (ex. "31-35
				// Quai Louis Blanc") peut faire remonter un résultat homonyme
				// n'importe où dans le monde plutôt qu'un échec propre.
				'countrycodes' => 'fr',
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
			return $response;
		}

		$body    = wp_remote_retrieve_body( $response );
		$results = json_decode( $body, true );

		if ( empty( $results ) || ! isset( $results[0]['lat'], $results[0]['lon'] ) ) {
			return new \WP_Error( 'csol_geocode_not_found', __( 'Adresse introuvable.', 'chambersign-office-locator' ) );
		}

		return array(
			'lat'          => (float) $results[0]['lat'],
			'lon'          => (float) $results[0]['lon'],
			'display_name' => isset( $results[0]['display_name'] ) ? sanitize_text_field( $results[0]['display_name'] ) : '',
		);
	}

	/**
	 * Retourne les IDs des bureaux publiés à géocoder.
	 *
	 * Par défaut, seuls les bureaux sans coordonnées GPS valides (hors ceux
	 * déjà marqués en échec de géocodage). En mode $force, retourne TOUS les
	 * bureaux publiés, y compris ceux ayant déjà des coordonnées : utile
	 * pour re-géocoder après une correction de la requête (ex. restriction
	 * pays ajoutée après coup, coordonnées existantes potentiellement fausses).
	 *
	 * @param bool $force Ignore les coordonnées et le statut d'échec existants.
	 *
	 * @return array<int, int>
	 */
	public static function get_bureaux_missing_coordinates( bool $force = false ): array {
		$ids = get_posts(
			array(
				'post_type'              => Bureau::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);

		if ( $force ) {
			return $ids;
		}

		$missing = array();

		foreach ( $ids as $post_id ) {
			if ( '1' === get_post_meta( $post_id, Bureau::META_PREFIX . 'geocode_failed', true ) ) {
				continue;
			}

			$lat = (float) get_post_meta( $post_id, Bureau::META_PREFIX . 'latitude', true );
			$lng = (float) get_post_meta( $post_id, Bureau::META_PREFIX . 'longitude', true );

			if ( 0.0 === $lat && 0.0 === $lng ) {
				$missing[] = $post_id;
			}
		}

		return $missing;
	}
}
