<?php
/**
 * Plugin Name:       ChamberSign Office Locator
 * Plugin URI:        https://www.chambersign.fr/
 * Description:       Permet aux visiteurs de trouver le bureau d'enregistrement ChamberSign le plus proche pour retirer leur certificat. Carte OpenStreetMap/Leaflet, recherche AJAX, gestion des bureaux et des produits associés.
 * Version:           1.3.2
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            YellowTie
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       chambersign-office-locator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Sortie si accès direct.
}

define( 'CSOL_VERSION', '1.3.2' );
define( 'CSOL_PLUGIN_FILE', __FILE__ );
define( 'CSOL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSOL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CSOL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoload des classes du plugin (namespace ChamberSign\Locator).
 */
spl_autoload_register(
	function ( $class ) {
		if ( 'Shuchkin\\SimpleXLSX' === $class ) {
			require_once CSOL_PLUGIN_DIR . 'includes/vendor/simplexlsx/simplexlsx.class.php';
			return;
		}

		$prefix = 'ChamberSign\\Locator\\';

		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );
		$relative_path  = str_replace( '\\', '/', $relative_class );
		$segments       = explode( '/', $relative_path );
		$class_name     = array_pop( $segments );
		$file_name      = 'class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';

		$sub_dir = '';
		if ( ! empty( $segments ) ) {
			$sub_dir = strtolower( implode( '/', $segments ) ) . '/';
		}

		$file = CSOL_PLUGIN_DIR . 'includes/' . $sub_dir . $file_name;

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Initialise le plugin.
 */
function csol_run_plugin() {
	$plugin = ChamberSign\Locator\Plugin::instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'csol_run_plugin' );

/**
 * Activation : enregistre le CPT/taxonomie puis flush les rewrite rules.
 */
function csol_activate_plugin() {
	ChamberSign\Locator\Cpt\Bureau::register_post_type();
	ChamberSign\Locator\Taxonomy\Produit::register_taxonomy();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'csol_activate_plugin' );

/**
 * Désactivation : flush les rewrite rules.
 */
function csol_deactivate_plugin() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'csol_deactivate_plugin' );
