<?php
namespace ChamberSign\Locator\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icône SVG personnalisée du marqueur, affichée sur la carte à la place
 * du pin par défaut. Upload et suppression réservés aux administrateurs
 * (manage_options) : le principal contrôle de sécurité est la capacité
 * requise, la sanitization du SVG ci-dessous est une défense en profondeur.
 */
class Marker_Icon {

	public const OPTION_NAME    = 'csol_marker_icon_url';
	public const NONCE_ACTION   = 'csol_marker_icon_action';
	public const NONCE_NAME     = 'csol_marker_icon_nonce';
	public const MAX_FILE_SIZE  = 102400; // 100 Ko.
	private const UPLOAD_SUBDIR = 'chambersign-locator';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'admin_post_csol_upload_marker_icon', array( $this, 'handle_upload' ) );
		add_action( 'admin_post_csol_remove_marker_icon', array( $this, 'handle_remove' ) );
	}

	/**
	 * Retourne l'URL de l'icône personnalisée, ou une chaîne vide si aucune
	 * n'est définie (le front utilise alors le pin Leaflet par défaut).
	 */
	public static function get_icon_url(): string {
		$url = get_option( self::OPTION_NAME, '' );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Traite l'upload d'un fichier SVG comme icône de marqueur.
	 */
	public function handle_upload(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non autorisé.', 'chambersign-office-locator' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$redirect = admin_url( 'admin.php?page=' . Settings::PAGE_SLUG );

		if ( empty( $_FILES['csol_marker_icon']['tmp_name'] )
			|| UPLOAD_ERR_OK !== $_FILES['csol_marker_icon']['error']
			|| ! is_uploaded_file( $_FILES['csol_marker_icon']['tmp_name'] )
		) {
			wp_safe_redirect( add_query_arg( 'csol_icon', 'error', $redirect ) );
			exit;
		}

		$file_name = sanitize_file_name( wp_unslash( $_FILES['csol_marker_icon']['name'] ) );
		$filetype  = wp_check_filetype( $file_name, array( 'svg' => 'image/svg+xml' ) );

		if ( 'svg' !== $filetype['ext'] ) {
			wp_safe_redirect( add_query_arg( 'csol_icon', 'format', $redirect ) );
			exit;
		}

		$tmp_path = $_FILES['csol_marker_icon']['tmp_name'];

		if ( filesize( $tmp_path ) > self::MAX_FILE_SIZE ) {
			wp_safe_redirect( add_query_arg( 'csol_icon', 'size', $redirect ) );
			exit;
		}

		$svg = file_get_contents( $tmp_path );

		if ( false === $svg || false === stripos( $svg, '<svg' ) ) {
			wp_safe_redirect( add_query_arg( 'csol_icon', 'format', $redirect ) );
			exit;
		}

		$svg = $this->sanitize_svg( $svg );
		file_put_contents( $tmp_path, $svg );

		require_once ABSPATH . 'wp-admin/includes/file.php';

		$uploaded = wp_handle_upload(
			$_FILES['csol_marker_icon'],
			array(
				'test_form' => false,
				'mimes'     => array( 'svg' => 'image/svg+xml' ),
				'unique_filename_callback' => function ( $dir, $name, $ext ) {
					return 'marker-icon-' . time() . $ext;
				},
			)
		);

		if ( isset( $uploaded['error'] ) ) {
			wp_safe_redirect( add_query_arg( 'csol_icon', 'error', $redirect ) );
			exit;
		}

		$this->delete_current_file();
		update_option( self::OPTION_NAME, esc_url_raw( $uploaded['url'] ) );

		wp_safe_redirect( add_query_arg( 'csol_icon', 'success', $redirect ) );
		exit;
	}

	/**
	 * Supprime l'icône personnalisée et revient au pin par défaut.
	 */
	public function handle_remove(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non autorisé.', 'chambersign-office-locator' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$this->delete_current_file();
		delete_option( self::OPTION_NAME );

		wp_safe_redirect( add_query_arg( 'csol_icon', 'removed', admin_url( 'admin.php?page=' . Settings::PAGE_SLUG ) ) );
		exit;
	}

	/**
	 * Supprime le fichier actuellement enregistré, s'il existe.
	 */
	private function delete_current_file(): void {
		$current_url = self::get_icon_url();

		if ( '' === $current_url ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		if ( 0 !== strpos( $current_url, $upload_dir['baseurl'] ) ) {
			return;
		}

		$path = $upload_dir['basedir'] . substr( $current_url, strlen( $upload_dir['baseurl'] ) );

		if ( file_exists( $path ) ) {
			wp_delete_file( $path );
		}
	}

	/**
	 * Retire les vecteurs XSS connus d'un SVG (script, gestionnaires
	 * d'événements, URLs javascript:, foreignObject, iframe).
	 */
	private function sanitize_svg( string $svg ): string {
		$svg = preg_replace( '#<script\b[^>]*>.*?</script>#is', '', $svg );
		$svg = preg_replace( '#<foreignObject\b[^>]*>.*?</foreignObject>#is', '', $svg );
		$svg = preg_replace( '#<iframe\b[^>]*>.*?</iframe>#is', '', $svg );
		$svg = preg_replace( '#\son\w+\s*=\s*"[^"]*"#i', '', $svg );
		$svg = preg_replace( "#\son\w+\s*=\s*'[^']*'#i", '', $svg );
		$svg = preg_replace( '#(href\s*=\s*)"javascript:[^"]*"#i', '$1""', $svg );
		$svg = preg_replace( "#(href\s*=\s*)'javascript:[^']*'#i", "$1''", $svg );

		return $svg;
	}
}
