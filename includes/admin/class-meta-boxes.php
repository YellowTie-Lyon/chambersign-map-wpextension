<?php
namespace ChamberSign\Locator\Admin;

use ChamberSign\Locator\Ajax\Geocode;
use ChamberSign\Locator\Cpt\Bureau;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meta box native pour l'édition des informations d'un bureau d'enregistrement.
 */
class Meta_Boxes {

	public const NONCE_ACTION = 'csol_save_bureau_meta';
	public const NONCE_NAME   = 'csol_bureau_meta_nonce';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		add_action( 'save_post_' . Bureau::POST_TYPE, array( $this, 'save_meta' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Charge assets uniquement sur l'écran d'édition du bureau.
	 *
	 * @param string $hook Écran admin courant.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || Bureau::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_style( 'csol-admin', CSOL_PLUGIN_URL . 'admin/css/admin.css', array(), CSOL_VERSION );
		wp_enqueue_script( 'csol-admin-geocode', CSOL_PLUGIN_URL . 'admin/js/geocode.js', array( 'jquery' ), CSOL_VERSION, true );

		wp_localize_script(
			'csol-admin-geocode',
			'csolAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( Geocode::ACTION ),
				'i18n'    => array(
					'searching' => __( 'Recherche en cours…', 'chambersign-office-locator' ),
					'notFound'  => __( 'Adresse introuvable. Vérifiez la saisie ou renseignez les coordonnées manuellement.', 'chambersign-office-locator' ),
					'error'     => __( 'Erreur lors de la géolocalisation.', 'chambersign-office-locator' ),
				),
			)
		);
	}

	/**
	 * Déclare la meta box.
	 */
	public function add_meta_boxes(): void {
		add_meta_box(
			'csol_bureau_details',
			__( 'Informations du bureau', 'chambersign-office-locator' ),
			array( $this, 'render_meta_box' ),
			Bureau::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Affiche la meta box.
	 *
	 * @param \WP_Post $post Article courant.
	 */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		$values = array();
		foreach ( array_keys( Bureau::get_meta_fields() ) as $field ) {
			$values[ $field ] = get_post_meta( $post->ID, Bureau::META_PREFIX . $field, true );
		}

		if ( '' === $values['actif'] ) {
			$values['actif'] = '1';
		}

		require CSOL_PLUGIN_DIR . 'admin/views/meta-box-bureau.php';
	}

	/**
	 * Sauvegarde les meta du bureau.
	 *
	 * @param int $post_id ID de l'article.
	 */
	public function save_meta( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array( 'region', 'departement', 'ville', 'code_postal', 'adresse', 'telephone', 'horaires' );

		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ 'csol_' . $field ] ) ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . $field, sanitize_text_field( wp_unslash( $_POST[ 'csol_' . $field ] ) ) );
			}
		}

		if ( isset( $_POST['csol_site_internet'] ) ) {
			update_post_meta( $post_id, Bureau::META_PREFIX . 'site_internet', esc_url_raw( wp_unslash( $_POST['csol_site_internet'] ) ) );
		}

		if ( isset( $_POST['csol_latitude'] ) ) {
			update_post_meta( $post_id, Bureau::META_PREFIX . 'latitude', Bureau::sanitize_coordinate( wp_unslash( $_POST['csol_latitude'] ) ) );
		}

		if ( isset( $_POST['csol_longitude'] ) ) {
			update_post_meta( $post_id, Bureau::META_PREFIX . 'longitude', Bureau::sanitize_coordinate( wp_unslash( $_POST['csol_longitude'] ) ) );
		}

		update_post_meta( $post_id, Bureau::META_PREFIX . 'actif', isset( $_POST['csol_actif'] ) ? '1' : '0' );
	}
}
