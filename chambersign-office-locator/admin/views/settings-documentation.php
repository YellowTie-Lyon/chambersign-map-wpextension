<?php
/**
 * Vue : introduction du bloc Documentation de la page Réglages.
 *
 * @package ChamberSign\Locator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p><?php esc_html_e( 'Affichez le localisateur de bureaux d\'enregistrement sur n\'importe quelle page ou article en utilisant le shortcode suivant :', 'chambersign-office-locator' ); ?></p>
<p>
	<code style="font-size: 14px; padding: 6px 10px; background: #EFEFEF; border-radius: 4px; display: inline-block;">[chambersign_locator]</code>
</p>
<p><?php esc_html_e( 'Ce shortcode affiche une carte OpenStreetMap et la liste des bureaux, avec une recherche par texte libre, région, département et produit. Il fonctionne dans l\'éditeur classique, l\'éditeur de blocs et dans un widget Shortcode Elementor.', 'chambersign-office-locator' ); ?></p>
