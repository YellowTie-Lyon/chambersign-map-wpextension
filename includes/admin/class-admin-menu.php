<?php
namespace ChamberSign\Locator\Admin;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menu d'administration "ChamberSign Locator" et ses sous-menus.
 */
class Admin_Menu {

	public const MENU_SLUG = 'chambersign-locator';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
	}

	/**
	 * Construit le menu principal et les sous-menus.
	 */
	public function register_menu(): void {
		add_menu_page(
			__( 'ChamberSign Locator', 'chambersign-office-locator' ),
			__( 'ChamberSign Locator', 'chambersign-office-locator' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' ),
			'dashicons-location-alt',
			25
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'ChamberSign Locator', 'chambersign-office-locator' ),
			__( 'Tableau de bord', 'chambersign-office-locator' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Bureaux d\'enregistrement', 'chambersign-office-locator' ),
			__( 'Bureaux d\'enregistrement', 'chambersign-office-locator' ),
			'manage_options',
			'edit.php?post_type=' . Bureau::POST_TYPE
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Produits', 'chambersign-office-locator' ),
			__( 'Produits', 'chambersign-office-locator' ),
			'manage_options',
			'edit-tags.php?taxonomy=' . Produit::TAXONOMY . '&post_type=' . Bureau::POST_TYPE
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Import de bureaux', 'chambersign-office-locator' ),
			__( 'Import', 'chambersign-office-locator' ),
			'manage_options',
			'chambersign-locator-import',
			array( $this, 'render_import_page' )
		);

		add_submenu_page(
			self::MENU_SLUG,
			__( 'Réglages', 'chambersign-office-locator' ),
			__( 'Réglages', 'chambersign-office-locator' ),
			'manage_options',
			'chambersign-locator-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Affiche la page tableau de bord.
	 */
	public function render_dashboard_page(): void {
		require CSOL_PLUGIN_DIR . 'admin/views/dashboard.php';
	}

	/**
	 * Affiche la page d'import.
	 */
	public function render_import_page(): void {
		require CSOL_PLUGIN_DIR . 'admin/views/import.php';
	}

	/**
	 * Affiche la page réglages.
	 */
	public function render_settings_page(): void {
		require CSOL_PLUGIN_DIR . 'admin/views/settings.php';
	}
}
