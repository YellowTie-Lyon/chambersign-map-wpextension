<?php
namespace ChamberSign\Locator\Admin;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Colonnes personnalisées de la liste des bureaux dans l'admin.
 */
class List_Columns {

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_filter( 'manage_' . Bureau::POST_TYPE . '_posts_columns', array( $this, 'add_columns' ) );
		add_action( 'manage_' . Bureau::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/**
	 * Ajoute les colonnes ville, région, produits et statut.
	 *
	 * @param array<string, string> $columns Colonnes existantes.
	 *
	 * @return array<string, string>
	 */
	public function add_columns( array $columns ): array {
		$new_columns = array();

		foreach ( $columns as $key => $label ) {
			$new_columns[ $key ] = $label;

			if ( 'title' === $key ) {
				$new_columns['csol_ville']    = __( 'Ville', 'chambersign-office-locator' );
				$new_columns['csol_region']   = __( 'Région', 'chambersign-office-locator' );
				$new_columns['csol_produits'] = __( 'Produits', 'chambersign-office-locator' );
				$new_columns['csol_actif']    = __( 'Statut', 'chambersign-office-locator' );
			}
		}

		return $new_columns;
	}

	/**
	 * Affiche le contenu des colonnes personnalisées.
	 *
	 * @param string $column  Clé de la colonne.
	 * @param int    $post_id ID de l'article.
	 */
	public function render_column( string $column, int $post_id ): void {
		switch ( $column ) {
			case 'csol_ville':
				echo esc_html( get_post_meta( $post_id, Bureau::META_PREFIX . 'ville', true ) );
				break;

			case 'csol_region':
				echo esc_html( get_post_meta( $post_id, Bureau::META_PREFIX . 'region', true ) );
				break;

			case 'csol_produits':
				$terms = get_the_terms( $post_id, Produit::TAXONOMY );
				if ( is_array( $terms ) && ! empty( $terms ) ) {
					echo esc_html( implode( ', ', wp_list_pluck( $terms, 'name' ) ) );
				} else {
					echo '&#8212;';
				}
				break;

			case 'csol_actif':
				$actif = get_post_meta( $post_id, Bureau::META_PREFIX . 'actif', true );
				if ( '0' === $actif ) {
					echo '<span class="csol-badge csol-badge-inactive">' . esc_html__( 'Inactif', 'chambersign-office-locator' ) . '</span>';
				} else {
					echo '<span class="csol-badge csol-badge-active">' . esc_html__( 'Actif', 'chambersign-office-locator' ) . '</span>';
				}
				break;
		}
	}
}
