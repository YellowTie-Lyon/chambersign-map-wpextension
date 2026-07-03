<?php
namespace ChamberSign\Locator\Taxonomy;

use ChamberSign\Locator\Cpt\Bureau;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomie "produit" (Certificat RGS, eIDAS, ...), en relation many-to-many
 * avec le CPT csol_bureau (bureau d'enregistrement).
 */
class Produit {

	public const TAXONOMY = 'produit';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
	}

	/**
	 * Déclare la taxonomie.
	 */
	public static function register_taxonomy(): void {
		$labels = array(
			'name'          => _x( 'Produits', 'Taxonomy general name', 'chambersign-office-locator' ),
			'singular_name' => _x( 'Produit', 'Taxonomy singular name', 'chambersign-office-locator' ),
			'menu_name'     => __( 'Produits', 'chambersign-office-locator' ),
			'search_items'  => __( 'Rechercher un produit', 'chambersign-office-locator' ),
			'all_items'     => __( 'Tous les produits', 'chambersign-office-locator' ),
			'edit_item'     => __( 'Modifier le produit', 'chambersign-office-locator' ),
			'add_new_item'  => __( 'Ajouter un produit', 'chambersign-office-locator' ),
			'new_item_name' => __( 'Nom du nouveau produit', 'chambersign-office-locator' ),
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'public'            => true,
			'show_ui'           => true,
			'show_in_menu'      => false, // Affiché via le menu personnalisé ChamberSign Locator.
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'show_in_nav_menus' => false,
			'rewrite'           => array( 'slug' => 'produit-chambersign' ),
		);

		register_taxonomy( self::TAXONOMY, array( Bureau::POST_TYPE ), $args );
	}
}
