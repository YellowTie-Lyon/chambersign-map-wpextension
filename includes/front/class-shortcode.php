<?php
namespace ChamberSign\Locator\Front;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcode [chambersign_locator] : rend le localisateur de bureaux
 * (carte + liste + filtres) en 2 colonnes.
 */
class Shortcode {

	public const TAG = 'chambersign_locator';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
	}

	/**
	 * Rend le markup du localisateur et charge les assets nécessaires.
	 */
	public function render(): string {
		( new Assets() )->enqueue();

		$regions  = $this->get_distinct_meta_values( 'region' );
		$produits = get_terms(
			array(
				'taxonomy'   => Produit::TAXONOMY,
				'hide_empty' => true,
			)
		);

		if ( is_wp_error( $produits ) ) {
			$produits = array();
		}

		ob_start();
		require CSOL_PLUGIN_DIR . 'public/views/locator-template.php';

		return ob_get_clean();
	}

	/**
	 * Retourne la liste triée des valeurs distinctes d'une meta pour les
	 * bureaux actifs (utilisée pour peupler les filtres Région/Département).
	 *
	 * @param string $meta_key Nom court de la meta (sans préfixe).
	 *
	 * @return array<int, string>
	 */
	private function get_distinct_meta_values( string $meta_key ): array {
		global $wpdb;

		$values = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT pm.meta_value
				FROM {$wpdb->postmeta} pm
				INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				WHERE pm.meta_key = %s
				AND pm.meta_value != ''
				AND p.post_type = %s
				AND p.post_status = 'publish'
				ORDER BY pm.meta_value ASC",
				Bureau::META_PREFIX . $meta_key,
				Bureau::POST_TYPE
			)
		);

		return array_map( 'sanitize_text_field', $values );
	}
}
