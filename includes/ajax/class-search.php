<?php
namespace ChamberSign\Locator\Ajax;

use ChamberSign\Locator\Cpt\Bureau;
use ChamberSign\Locator\Taxonomy\Produit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recherche AJAX des bureaux (texte libre, région, département, produit),
 * disponible pour les visiteurs non connectés (wp_ajax_nopriv).
 */
class Search {

	public const ACTION = 'csol_search_bureaux';

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'wp_ajax_' . self::ACTION, array( $this, 'handle' ) );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, array( $this, 'handle' ) );
	}

	/**
	 * Traite la requête de recherche et retourne les bureaux au format JSON.
	 */
	public function handle(): void {
		check_ajax_referer( self::ACTION, 'nonce' );

		$search      = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$region      = isset( $_POST['region'] ) ? sanitize_text_field( wp_unslash( $_POST['region'] ) ) : '';
		$departement = isset( $_POST['departement'] ) ? sanitize_text_field( wp_unslash( $_POST['departement'] ) ) : '';
		$produit     = isset( $_POST['produit'] ) ? sanitize_title( wp_unslash( $_POST['produit'] ) ) : '';

		$meta_query = array( 'relation' => 'AND' );

		if ( '' !== $region ) {
			$meta_query[] = array(
				'key'   => Bureau::META_PREFIX . 'region',
				'value' => $region,
			);
		}

		if ( '' !== $departement ) {
			$meta_query[] = array(
				'key'   => Bureau::META_PREFIX . 'departement',
				'value' => $departement,
			);
		}

		$meta_query[] = array(
			'relation' => 'OR',
			array(
				'key'     => Bureau::META_PREFIX . 'actif',
				'value'   => '0',
				'compare' => '!=',
			),
			array(
				'key'     => Bureau::META_PREFIX . 'actif',
				'compare' => 'NOT EXISTS',
			),
		);

		$args = array(
			'post_type'              => Bureau::POST_TYPE,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'update_post_term_cache' => true,
			'meta_query'             => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'                => 'title',
			'order'                  => 'ASC',
		);

		if ( '' !== $produit ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => Produit::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $produit,
				),
			);
		}

		$search_filter = null;

		if ( '' !== $search ) {
			// La recherche WordPress native (`s`) ne porte que sur le titre et le
			// contenu de l'article, or la ville/le code postal/l'adresse sont
			// des meta : on élargit donc le WHERE pour matcher aussi ces champs.
			$search_filter = function ( $where ) use ( $search ) {
				return $this->add_search_where( $where, $search );
			};
			add_filter( 'posts_where', $search_filter );
		}

		$query = new \WP_Query( $args );

		if ( null !== $search_filter ) {
			remove_filter( 'posts_where', $search_filter );
		}

		$bureaux = array();

		foreach ( $query->posts as $post ) {
			$bureaux[] = $this->format_bureau( $post );
		}

		wp_send_json_success(
			array(
				'bureaux' => $bureaux,
				'count'   => count( $bureaux ),
			)
		);
	}

	/**
	 * Élargit le WHERE de la requête pour matcher le titre du bureau ainsi
	 * que sa ville, son code postal, son adresse, sa région et son
	 * département (recherche texte libre "trouve ma ville").
	 *
	 * @param string $where  Clause WHERE actuelle.
	 * @param string $search Terme recherché.
	 */
	private function add_search_where( string $where, string $search ): string {
		global $wpdb;

		$like       = '%' . $wpdb->esc_like( $search ) . '%';
		$meta_keys  = array(
			Bureau::META_PREFIX . 'ville',
			Bureau::META_PREFIX . 'code_postal',
			Bureau::META_PREFIX . 'adresse',
			Bureau::META_PREFIX . 'region',
			Bureau::META_PREFIX . 'departement',
		);
		$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );

		$args = array_merge( array( $like ), $meta_keys, array( $like ) );

		$where .= $wpdb->prepare(
			" AND ( {$wpdb->posts}.post_title LIKE %s OR {$wpdb->posts}.ID IN (
				SELECT post_id FROM {$wpdb->postmeta}
				WHERE meta_key IN ( {$placeholders} ) AND meta_value LIKE %s
			) )",
			$args
		);

		return $where;
	}

	/**
	 * Formate un bureau pour la réponse JSON front.
	 *
	 * @param \WP_Post $post Bureau.
	 *
	 * @return array<string, mixed>
	 */
	private function format_bureau( \WP_Post $post ): array {
		$lat = (float) get_post_meta( $post->ID, Bureau::META_PREFIX . 'latitude', true );
		$lng = (float) get_post_meta( $post->ID, Bureau::META_PREFIX . 'longitude', true );

		$terms    = get_the_terms( $post->ID, Produit::TAXONOMY );
		$produits = array();

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				$produits[] = array(
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return array(
			'id'          => $post->ID,
			// Titre brut (non filtré) : get_the_title() applique wptexturize et
			// convertirait par ex. " - " en l'entité HTML "&#8211;", qui serait
			// alors ré-échappée telle quelle par le JS côté front (JSON, pas HTML).
			'title'       => $post->post_title,
			'region'      => get_post_meta( $post->ID, Bureau::META_PREFIX . 'region', true ),
			'departement' => get_post_meta( $post->ID, Bureau::META_PREFIX . 'departement', true ),
			'ville'       => get_post_meta( $post->ID, Bureau::META_PREFIX . 'ville', true ),
			'code_postal' => get_post_meta( $post->ID, Bureau::META_PREFIX . 'code_postal', true ),
			'adresse'     => get_post_meta( $post->ID, Bureau::META_PREFIX . 'adresse', true ),
			'telephone'   => get_post_meta( $post->ID, Bureau::META_PREFIX . 'telephone', true ),
			'site'        => get_post_meta( $post->ID, Bureau::META_PREFIX . 'site_internet', true ),
			'horaires'    => get_post_meta( $post->ID, Bureau::META_PREFIX . 'horaires', true ),
			'lat'         => $lat,
			'lng'         => $lng,
			'produits'    => $produits,
		);
	}
}
