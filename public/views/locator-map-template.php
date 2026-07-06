<?php
/**
 * Vue front : carte seule (sans recherche ni liste), centrée sur la
 * France, shortcode [chambersign_locator_map].
 *
 * @var string $height Hauteur CSS de la carte (ex. "480px").
 * @package ChamberSign\Locator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

static $csol_map_instance = 0;
$csol_map_instance++;
$csol_id = 'csol-locator-map-' . $csol_map_instance;
?>
<div id="<?php echo esc_attr( $csol_id ); ?>" class="csol-locator csol-locator-map-only" data-csol-instance="<?php echo esc_attr( $csol_id ); ?>">
	<div
		class="csol-map"
		id="<?php echo esc_attr( $csol_id ); ?>-map"
		style="height: <?php echo esc_attr( $height ); ?>;"
		role="application"
		aria-label="<?php esc_attr_e( 'Carte des bureaux d\'enregistrement', 'chambersign-office-locator' ); ?>"
	></div>
</div>
