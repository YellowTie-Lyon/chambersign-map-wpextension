<?php
namespace ChamberSign\Locator;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;
use ChamberSign\Locator\Admin\Admin_Menu;
use ChamberSign\Locator\Admin\Settings;
use ChamberSign\Locator\Admin\Meta_Boxes;
use ChamberSign\Locator\Admin\Import;
use ChamberSign\Locator\Admin\List_Columns;
use ChamberSign\Locator\Ajax\Search;
use ChamberSign\Locator\Ajax\Geocode;
use ChamberSign\Locator\Front\Shortcode;
use ChamberSign\Locator\Front\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Classe principale du plugin. Instancie et relie tous les composants.
 */
final class Plugin {

	/**
	 * Instance unique.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Retourne l'instance unique du plugin.
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructeur privé (singleton).
	 */
	private function __construct() {}

	/**
	 * Démarre le plugin : enregistre tous les hooks des composants.
	 */
	public function run(): void {
		load_plugin_textdomain( 'chambersign-office-locator', false, dirname( CSOL_PLUGIN_BASENAME ) . '/languages' );

		( new Bureau() )->register_hooks();
		( new Produit() )->register_hooks();

		if ( is_admin() ) {
			( new Admin_Menu() )->register_hooks();
			( new Settings() )->register_hooks();
			( new Meta_Boxes() )->register_hooks();
			( new Import() )->register_hooks();
			( new List_Columns() )->register_hooks();
		}

		( new Search() )->register_hooks();
		( new Geocode() )->register_hooks();
		( new Shortcode() )->register_hooks();
		( new Assets() )->register_hooks();
	}
}
