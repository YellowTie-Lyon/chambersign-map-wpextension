<?php
/**
 * Vue : tableau de bord ChamberSign Locator.
 *
 * @package ChamberSign\Locator
 */

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$bureaux_count = wp_count_posts( Bureau::POST_TYPE );
$total_bureaux = isset( $bureaux_count->publish ) ? (int) $bureaux_count->publish : 0;
$total_produits = wp_count_terms( array( 'taxonomy' => Produit::TAXONOMY, 'hide_empty' => false ) );
?>
<div class="wrap csol-admin-wrap">
	<h1><?php esc_html_e( 'ChamberSign Locator', 'chambersign-office-locator' ); ?></h1>
	<p><?php esc_html_e( 'Gérez les bureaux d\'enregistrement affichés sur le localisateur du site.', 'chambersign-office-locator' ); ?></p>

	<div class="csol-dashboard-cards">
		<div class="csol-dashboard-card">
			<span class="csol-dashboard-card-number"><?php echo esc_html( $total_bureaux ); ?></span>
			<span class="csol-dashboard-card-label"><?php esc_html_e( 'Bureaux publiés', 'chambersign-office-locator' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . Bureau::POST_TYPE ) ); ?>"><?php esc_html_e( 'Voir les bureaux', 'chambersign-office-locator' ); ?></a>
		</div>
		<div class="csol-dashboard-card">
			<span class="csol-dashboard-card-number"><?php echo esc_html( is_wp_error( $total_produits ) ? 0 : $total_produits ); ?></span>
			<span class="csol-dashboard-card-label"><?php esc_html_e( 'Produits', 'chambersign-office-locator' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'edit-tags.php?taxonomy=' . Produit::TAXONOMY . '&post_type=' . Bureau::POST_TYPE ) ); ?>"><?php esc_html_e( 'Voir les produits', 'chambersign-office-locator' ); ?></a>
		</div>
		<div class="csol-dashboard-card">
			<span class="csol-dashboard-card-number">&rarr;</span>
			<span class="csol-dashboard-card-label"><?php esc_html_e( 'Importer des bureaux', 'chambersign-office-locator' ); ?></span>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=chambersign-locator-import' ) ); ?>"><?php esc_html_e( 'Importer un fichier', 'chambersign-office-locator' ); ?></a>
		</div>
	</div>

	<h2><?php esc_html_e( 'Utilisation', 'chambersign-office-locator' ); ?></h2>
	<p>
		<?php esc_html_e( 'Affichez le localisateur sur n\'importe quelle page ou article avec le shortcode :', 'chambersign-office-locator' ); ?>
		<code>[chambersign_locator]</code>
	</p>
	<p>
		<?php esc_html_e( 'Retrouvez la documentation complète et les paramètres de la carte dans la page Réglages.', 'chambersign-office-locator' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=chambersign-locator-settings' ) ); ?>"><?php esc_html_e( 'Accéder aux réglages', 'chambersign-office-locator' ); ?></a>
	</p>
</div>
