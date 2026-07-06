<?php
namespace ChamberSign\Locator\Front;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes [chambersign_locator] (carte + liste + filtres, 2 colonnes)
 * et [chambersign_locator_map] (carte seule, centrée sur la France).
 */
class Shortcode {

	public const TAG     = 'chambersign_locator';
	public const TAG_MAP = 'chambersign_locator_map';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_shortcode( self::TAG_MAP, array( $this, 'render_map' ) );
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
	 * Rend uniquement la carte (sans recherche ni liste), centrée sur la
	 * France : idéal pour une page d'accueil ou un widget.
	 *
	 * Attribut : height (ex. [chambersign_locator_map height="480px"]).
	 *
	 * @param array<string, string>|string $atts Attributs du shortcode.
	 */
	public function render_map( $atts = array() ): string {
		( new Assets() )->enqueue();

		$atts = shortcode_atts(
			array( 'height' => '480px' ),
			$atts,
			self::TAG_MAP
		);

		$height = preg_match( '/^\d+(\.\d+)?(px|em|rem|vh|%)$/', $atts['height'] ) ? $atts['height'] : '480px';

		ob_start();
		require CSOL_PLUGIN_DIR . 'public/views/locator-map-template.php';

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
