<?php
/**
 * Désinstallation du plugin.
 *
 * Par défaut, les bureaux, produits et réglages sont CONSERVÉS : supprimer
 * puis réinstaller le plugin (par exemple pour le mettre à jour) ne doit
 * jamais faire perdre les données. La suppression définitive n'a lieu que
 * si l'administrateur a explicitement coché l'option correspondante dans
 * Réglages > Désinstallation.
 *
 * @package ChamberSign\Locator
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$csol_settings = get_option( 'csol_settings', array() );

if ( empty( $csol_settings['delete_data_on_uninstall'] ) ) {
	return;
}

/**
 * Supprime tous les bureaux d'enregistrement.
 */
function csol_uninstall_delete_bureaux(): void {
	$posts = get_posts(
		array(
			'post_type'      => 'csol_bureau',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	foreach ( $posts as $post_id ) {
		wp_delete_post( $post_id, true );
	}
}

/**
 * Supprime tous les termes de la taxonomie produit.
 */
function csol_uninstall_delete_produits(): void {
	$terms = get_terms(
		array(
			'taxonomy'   => 'produit',
			'hide_empty' => false,
			'fields'     => 'ids',
		)
	);

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'produit' );
		}
	}
}

csol_uninstall_delete_bureaux();
csol_uninstall_delete_produits();

delete_option( 'csol_settings' );
delete_option( 'csol_marker_icon_url' );
