<?php
namespace ChamberSign\Locator\Admin;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;
use Shuchkin\SimpleXLSX;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Import CSV/XLSX des bureaux d'enregistrement, avec mise à jour des
 * bureaux existants (upsert par nom du bureau).
 */
class Import {

	public const NONCE_ACTION = 'csol_import_bureaux';
	public const NONCE_NAME   = 'csol_import_nonce';

	/**
	 * Correspondance entre en-têtes de colonnes (normalisés) et clés meta.
	 *
	 * @var array<string, string>
	 */
	private const COLUMN_MAP = array(
		'region'         => 'region',
		'nom du bureau'  => 'title',
		'nom'            => 'title',
		'departement'    => 'departement',
		'ville'          => 'ville',
		'code postal'    => 'code_postal',
		'adresse'        => 'adresse',
		'telephone'      => 'telephone',
		'tel'            => 'telephone',
		'site internet'  => 'site_internet',
		'site web'       => 'site_internet',
		'horaires'       => 'horaires',
		'latitude'       => 'latitude',
		'lat'            => 'latitude',
		'longitude'      => 'longitude',
		'lng'            => 'longitude',
		'lon'            => 'longitude',
		'produits'       => 'produits',
		'produit'        => 'produits',
	);

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'admin_post_csol_import_bureaux', array( $this, 'handle_import' ) );
	}

	/**
	 * Traite le formulaire d'import (CSV ou XLSX).
	 */
	public function handle_import(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Non autorisé.', 'chambersign-office-locator' ) );
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );

		$redirect = add_query_arg(
			array( 'page' => 'chambersign-locator-import' ),
			admin_url( 'admin.php' )
		);

		if ( empty( $_FILES['csol_import_file']['tmp_name'] )
			|| UPLOAD_ERR_OK !== $_FILES['csol_import_file']['error']
			|| ! is_uploaded_file( $_FILES['csol_import_file']['tmp_name'] )
		) {
			wp_safe_redirect( add_query_arg( 'csol_import', 'error', $redirect ) );
			exit;
		}

		$file_name = sanitize_file_name( wp_unslash( $_FILES['csol_import_file']['name'] ) );
		$filetype  = wp_check_filetype( $file_name, array( 'csv' => 'text/csv', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ) );
		$extension = strtolower( (string) $filetype['ext'] );
		$tmp_path  = $_FILES['csol_import_file']['tmp_name'];

		$rows = array();

		if ( 'csv' === $extension ) {
			$rows = $this->parse_csv( $tmp_path );
		} elseif ( 'xlsx' === $extension ) {
			$rows = $this->parse_xlsx( $tmp_path );
		} else {
			wp_safe_redirect( add_query_arg( 'csol_import', 'format', $redirect ) );
			exit;
		}

		if ( empty( $rows ) ) {
			wp_safe_redirect( add_query_arg( 'csol_import', 'empty', $redirect ) );
			exit;
		}

		$result = $this->import_rows( $rows );

		set_transient( 'csol_import_result_' . get_current_user_id(), $result, MINUTE_IN_SECONDS * 5 );

		wp_safe_redirect( add_query_arg( 'csol_import', 'success', $redirect ) );
		exit;
	}

	/**
	 * Parse un fichier CSV en tableau associatif par ligne.
	 *
	 * @param string $path Chemin du fichier temporaire.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function parse_csv( string $path ): array {
		$handle = fopen( $path, 'r' );
		if ( false === $handle ) {
			return array();
		}

		$rows    = array();
		$headers = array();
		$index   = 0;

		while ( false !== ( $data = fgetcsv( $handle, 0, ';' ) ) ) {
			if ( 1 === count( $data ) && false !== strpos( $data[0], ',' ) ) {
				$data = str_getcsv( $data[0], ',' );
			}

			if ( 0 === $index ) {
				$headers = array_map( array( $this, 'normalize_header' ), $data );
			} else {
				$rows[] = $this->map_row( $headers, $data );
			}

			$index++;
		}

		fclose( $handle );

		return $rows;
	}

	/**
	 * Parse un fichier XLSX via la librairie SimpleXLSX bundlée.
	 *
	 * @param string $path Chemin du fichier temporaire.
	 *
	 * @return array<int, array<string, string>>
	 */
	private function parse_xlsx( string $path ): array {
		$xlsx = SimpleXLSX::parse( $path );

		if ( false === $xlsx ) {
			return array();
		}

		$rows    = array();
		$headers = array();

		foreach ( $xlsx->rows() as $index => $data ) {
			if ( 0 === $index ) {
				$headers = array_map( array( $this, 'normalize_header' ), $data );
			} else {
				$rows[] = $this->map_row( $headers, $data );
			}
		}

		return $rows;
	}

	/**
	 * Normalise un en-tête de colonne (minuscule, sans accents).
	 */
	private function normalize_header( string $header ): string {
		// Excel enregistre souvent les CSV en "UTF-8 avec BOM" : ces 3 octets
		// se retrouvent collés au tout premier en-tête et l'empêchent de
		// correspondre à COLUMN_MAP (seule cette colonne était affectée).
		$header = preg_replace( '/^\xEF\xBB\xBF/', '', $header );
		$header = remove_accents( trim( $header ) );

		return strtolower( $header );
	}

	/**
	 * Associe une ligne de données à ses clés internes via COLUMN_MAP.
	 *
	 * @param array<int, string> $headers En-têtes normalisés.
	 * @param array<int, string> $data    Valeurs de la ligne.
	 *
	 * @return array<string, string>
	 */
	private function map_row( array $headers, array $data ): array {
		$row = array();

		foreach ( $headers as $col_index => $header ) {
			if ( ! isset( self::COLUMN_MAP[ $header ] ) ) {
				continue;
			}

			$key         = self::COLUMN_MAP[ $header ];
			$row[ $key ] = isset( $data[ $col_index ] ) ? trim( (string) $data[ $col_index ] ) : '';
		}

		return $row;
	}

	/**
	 * Importe les lignes en créant ou mettant à jour les bureaux (upsert
	 * par titre).
	 *
	 * @param array<int, array<string, string>> $rows Lignes mappées.
	 *
	 * @return array{created: int, updated: int, skipped: int}
	 */
	private function import_rows( array $rows ): array {
		$created = 0;
		$updated = 0;
		$skipped = 0;

		foreach ( $rows as $row ) {
			if ( empty( $row['title'] ) ) {
				$skipped++;
				continue;
			}

			$title       = sanitize_text_field( $row['title'] );
			$existing_id = $this->find_bureau_by_title( $title );

			$post_args = array(
				'post_title'  => $title,
				'post_type'   => Bureau::POST_TYPE,
				'post_status' => 'publish',
			);

			if ( $existing_id ) {
				$post_args['ID'] = $existing_id;
				$post_id         = wp_update_post( wp_slash( $post_args ), true );
				$updated++;
			} else {
				$post_id = wp_insert_post( wp_slash( $post_args ), true );
				$created++;
			}

			if ( is_wp_error( $post_id ) || ! $post_id ) {
				$skipped++;
				continue;
			}

			$text_fields = array( 'region', 'departement', 'ville', 'code_postal', 'adresse', 'telephone', 'horaires' );
			foreach ( $text_fields as $field ) {
				if ( isset( $row[ $field ] ) && '' !== $row[ $field ] ) {
					update_post_meta( $post_id, Bureau::META_PREFIX . $field, sanitize_text_field( $row[ $field ] ) );
				}
			}

			if ( ! empty( $row['site_internet'] ) ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'site_internet', esc_url_raw( $row['site_internet'] ) );
			}

			if ( isset( $row['latitude'] ) && '' !== $row['latitude'] ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'latitude', Bureau::sanitize_coordinate( $row['latitude'] ) );
			}

			if ( isset( $row['longitude'] ) && '' !== $row['longitude'] ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'longitude', Bureau::sanitize_coordinate( $row['longitude'] ) );
			}

			if ( ! get_post_meta( $post_id, Bureau::META_PREFIX . 'actif', true ) ) {
				update_post_meta( $post_id, Bureau::META_PREFIX . 'actif', '1' );
			}

			if ( ! empty( $row['produits'] ) ) {
				$produits = array_filter( array_map( 'trim', preg_split( '/[,|]/', $row['produits'] ) ) );
				wp_set_object_terms( $post_id, $produits, Produit::TAXONOMY, false );
			}
		}

		return array(
			'created' => $created,
			'updated' => $updated,
			'skipped' => $skipped,
		);
	}

	/**
	 * Recherche un bureau existant par titre exact.
	 */
	private function find_bureau_by_title( string $title ): int {
		$query = new \WP_Query(
			array(
				'post_type'              => Bureau::POST_TYPE,
				'title'                  => $title,
				'post_status'            => 'any',
				'posts_per_page'         => 1,
				'fields'                 => 'ids',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		return ! empty( $query->posts ) ? (int) $query->posts[0] : 0;
	}
}
