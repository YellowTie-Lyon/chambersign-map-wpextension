<?php
namespace ChamberSign\Locator\Front;

use ChamberSign\Locator\Admin\Marker_Icon;
use ChamberSign\Locator\Admin\Settings;
use ChamberSign\Locator\Ajax\Search;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enregistrement et chargement conditionnel des assets front (Leaflet,
 * CSS/JS du plugin, Google Font Poppins). Les assets ne sont chargés que
 * sur les pages qui affichent réellement le shortcode.
 */
class Assets {

	public const LEAFLET_VERSION       = '1.9.4';
	public const MARKERCLUSTER_VERSION = '1.5.3';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Enregistre (sans charger) tous les assets front.
	 */
	public function register_assets(): void {
		wp_register_style(
			'csol-leaflet',
			CSOL_PLUGIN_URL . 'public/vendor/leaflet/leaflet.css',
			array(),
			self::LEAFLET_VERSION
		);

		wp_register_script(
			'csol-leaflet',
			CSOL_PLUGIN_URL . 'public/vendor/leaflet/leaflet.js',
			array(),
			self::LEAFLET_VERSION,
			true
		);

		// MarkerCluster.Default.css (styles de bulle par défaut) n'est pas
		// chargé : les bulles sont un SVG généré à la volée (voir
		// buildClusterIcon() dans le JS), les classes .marker-cluster-* par
		// défaut ne sont jamais appliquées.
		wp_register_style(
			'csol-markercluster',
			CSOL_PLUGIN_URL . 'public/vendor/leaflet-markercluster/MarkerCluster.css',
			array(),
			self::MARKERCLUSTER_VERSION
		);

		wp_register_script(
			'csol-markercluster',
			CSOL_PLUGIN_URL . 'public/vendor/leaflet-markercluster/leaflet.markercluster.js',
			array( 'csol-leaflet' ),
			self::MARKERCLUSTER_VERSION,
			true
		);

		wp_register_style(
			'csol-google-font-poppins',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;800&display=swap',
			array(),
			CSOL_VERSION
		);

		wp_register_style(
			'csol-locator',
			CSOL_PLUGIN_URL . 'public/css/chambersign-locator.css',
			array( 'csol-leaflet', 'csol-markercluster', 'csol-google-font-poppins' ),
			CSOL_VERSION
		);

		wp_register_script(
			'csol-locator',
			CSOL_PLUGIN_URL . 'public/js/chambersign-locator.js',
			array( 'csol-leaflet', 'csol-markercluster' ),
			CSOL_VERSION,
			true
		);
	}

	/**
	 * Charge les assets et localise les paramètres pour le JS front.
	 * Appelé par le shortcode uniquement lorsqu'il est effectivement rendu.
	 */
	public function enqueue(): void {
		wp_enqueue_style( 'csol-locator' );
		wp_enqueue_script( 'csol-locator' );

		$settings = Settings::get_settings();
		$tile     = Settings::get_active_tile_provider();

		wp_localize_script(
			'csol-locator',
			'csolLocator',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'action'        => Search::ACTION,
				'nonce'         => wp_create_nonce( Search::ACTION ),
				'markerIconUrl' => Marker_Icon::get_icon_url(),
				'tile'          => $tile,
				'settings'      => array(
					'defaultLat'      => (float) $settings['default_lat'],
					'defaultLng'      => (float) $settings['default_lng'],
					'zoomFrance'      => (int) $settings['zoom_france'],
					'zoomRegion'      => (int) $settings['zoom_region'],
					'zoomDepartement' => (int) $settings['zoom_departement'],
					'zoomBureau'      => (int) $settings['zoom_bureau'],
				),
				'i18n'          => array(
					'noResults'    => __( 'Aucun bureau ne correspond à votre recherche. Vérifiez l\'orthographe ou essayez une recherche plus large (ville voisine, région).', 'chambersign-office-locator' ),
					'loading'      => __( 'Chargement…', 'chambersign-office-locator' ),
					'directions'   => __( 'Itinéraire', 'chambersign-office-locator' ),
					'viewOnMap'    => __( 'Voir sur la carte', 'chambersign-office-locator' ),
					'resetFilters' => __( 'Réinitialiser les filtres', 'chambersign-office-locator' ),
				),
			)
		);
	}
}
