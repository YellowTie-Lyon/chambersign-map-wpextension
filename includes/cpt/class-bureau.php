<?php
namespace ChamberSign\Locator\Cpt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Post Type "bureau d'enregistrement".
 *
 * Le nom technique du post type (csol_bureau) reste sous 20 caractères :
 * WordPress refuse silencieusement (WP_Error, sans erreur fatale) tout
 * post_type dont le nom dépasse cette limite, ce qui empêcherait le CPT
 * de s'enregistrer (le libellé "bureau_enregistrement" seul fait 21
 * caractères et dépasse déjà la limite).
 */
class Bureau {

	public const POST_TYPE = 'csol_bureau';

	/**
	 * Préfixe utilisé pour toutes les meta du bureau.
	 */
	public const META_PREFIX = 'csol_';

	/**
	 * Liste des champs meta du bureau : clé => libellé.
	 *
	 * @return array<string, string>
	 */
	public static function get_meta_fields(): array {
		return array(
			'region'          => __( 'Région', 'chambersign-office-locator' ),
			'departement'     => __( 'Département', 'chambersign-office-locator' ),
			'ville'           => __( 'Ville', 'chambersign-office-locator' ),
			'code_postal'     => __( 'Code postal', 'chambersign-office-locator' ),
			'adresse'         => __( 'Adresse complète', 'chambersign-office-locator' ),
			'telephone'       => __( 'Téléphone', 'chambersign-office-locator' ),
			'site_internet'   => __( 'Site internet', 'chambersign-office-locator' ),
			'horaires'        => __( 'Horaires', 'chambersign-office-locator' ),
			'latitude'        => __( 'Latitude', 'chambersign-office-locator' ),
			'longitude'       => __( 'Longitude', 'chambersign-office-locator' ),
			'actif'           => __( 'Actif', 'chambersign-office-locator' ),
		);
	}

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Déclare le Custom Post Type.
	 */
	public static function register_post_type(): void {
		$labels = array(
			'name'                  => _x( 'Bureaux d\'enregistrement', 'Post type general name', 'chambersign-office-locator' ),
			'singular_name'         => _x( 'Bureau d\'enregistrement', 'Post type singular name', 'chambersign-office-locator' ),
			'menu_name'             => __( 'Bureaux d\'enregistrement', 'chambersign-office-locator' ),
			'add_new'               => __( 'Ajouter un bureau', 'chambersign-office-locator' ),
			'add_new_item'          => __( 'Ajouter un bureau d\'enregistrement', 'chambersign-office-locator' ),
			'edit_item'             => __( 'Modifier le bureau', 'chambersign-office-locator' ),
			'new_item'              => __( 'Nouveau bureau', 'chambersign-office-locator' ),
			'view_item'             => __( 'Voir le bureau', 'chambersign-office-locator' ),
			'search_items'          => __( 'Rechercher un bureau', 'chambersign-office-locator' ),
			'not_found'             => __( 'Aucun bureau trouvé', 'chambersign-office-locator' ),
			'not_found_in_trash'    => __( 'Aucun bureau dans la corbeille', 'chambersign-office-locator' ),
			'all_items'             => __( 'Tous les bureaux', 'chambersign-office-locator' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false, // Affiché via le menu personnalisé ChamberSign Locator.
			'show_in_admin_bar'  => true,
			'show_in_rest'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'bureau-enregistrement' ),
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-location-alt',
			'supports'           => array( 'title' ),
		);

		register_post_type( self::POST_TYPE, $args );
	}

	/**
	 * Déclare les champs meta pour l'API REST / sanitization centralisée.
	 */
	public function register_meta(): void {
		$string_fields = array( 'region', 'departement', 'ville', 'code_postal', 'adresse', 'telephone', 'site_internet', 'horaires' );

		foreach ( $string_fields as $field ) {
			register_post_meta(
				self::POST_TYPE,
				self::META_PREFIX . $field,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'sanitize_text_field',
					'auth_callback'     => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}

		register_post_meta(
			self::POST_TYPE,
			self::META_PREFIX . 'latitude',
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_coordinate' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_PREFIX . 'longitude',
			array(
				'type'              => 'number',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => array( __CLASS__, 'sanitize_coordinate' ),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::META_PREFIX . 'actif',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'show_in_rest'      => true,
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * Sanitize une coordonnée GPS (latitude ou longitude).
	 *
	 * @param mixed $value Valeur brute.
	 */
	public static function sanitize_coordinate( $value ): float {
		return (float) str_replace( ',', '.', (string) $value );
	}
}
