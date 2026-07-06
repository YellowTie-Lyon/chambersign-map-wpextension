<?php
namespace ChamberSign\Locator\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Réglages du plugin (Documentation, Carte, Apparence), via l'API
 * Settings de WordPress.
 */
class Settings {

	public const OPTION_NAME  = 'csol_settings';
	public const OPTION_GROUP = 'csol_settings_group';
	public const PAGE_SLUG    = 'chambersign-locator-settings';

	/**
	 * Fonds de carte proposés : URL du tuilage (compatible Leaflet.tileLayer)
	 * et attribution associée. Tous libres d'accès, sans clé API.
	 *
	 * @return array<string, array{label: string, url: string, attribution: string}>
	 */
	public static function get_tile_providers(): array {
		return array(
			'osm'            => array(
				'label'       => __( 'OpenStreetMap (standard)', 'chambersign-office-locator' ),
				'url'         => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
			),
			'osm_france'     => array(
				'label'       => __( 'OpenStreetMap France (libellés en français)', 'chambersign-office-locator' ),
				'url'         => 'https://{s}.tile.openstreetmap.fr/osmfr/{z}/{x}/{y}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &mdash; rendu <a href="https://www.openstreetmap.fr/">OSM France</a>',
			),
			'carto_voyager'  => array(
				'label'       => __( 'CartoDB Voyager (couleurs douces)', 'chambersign-office-locator' ),
				'url'         => 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			),
			'carto_positron' => array(
				'label'       => __( 'CartoDB Positron (clair, épuré)', 'chambersign-office-locator' ),
				'url'         => 'https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			),
			'carto_dark'     => array(
				'label'       => __( 'CartoDB Dark Matter (sombre)', 'chambersign-office-locator' ),
				'url'         => 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png',
				'attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
			),
		);
	}

	/**
	 * Valeurs par défaut des réglages.
	 *
	 * @return array<string, float|int|string>
	 */
	public static function get_defaults(): array {
		return array(
			'default_lat'      => 46.603354,
			'default_lng'      => 1.888334,
			'zoom_france'      => 6,
			'zoom_region'      => 8,
			'zoom_departement' => 10,
			'zoom_bureau'      => 14,
			'tile_provider'    => 'osm',
			'delete_data_on_uninstall' => false,
		);
	}

	/**
	 * Retourne les réglages actuels, fusionnés avec les valeurs par défaut.
	 *
	 * @return array<string, float|int|string>
	 */
	public static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::get_defaults() );
	}

	/**
	 * Retourne l'URL et l'attribution du fond de carte actuellement choisi.
	 *
	 * @return array{url: string, attribution: string}
	 */
	public static function get_active_tile_provider(): array {
		$settings  = self::get_settings();
		$providers = self::get_tile_providers();
		$key       = isset( $providers[ $settings['tile_provider'] ] ) ? $settings['tile_provider'] : 'osm';

		return array(
			'url'         => $providers[ $key ]['url'],
			'attribution' => $providers[ $key ]['attribution'],
		);
	}

	/**
	 * Enregistre les hooks WordPress.
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Déclare le réglage, les sections et les champs.
	 */
	public function register_settings(): void {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => self::get_defaults(),
			)
		);

		add_settings_section(
			'csol_section_documentation',
			__( 'Documentation', 'chambersign-office-locator' ),
			array( $this, 'render_documentation_section' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'csol_section_carte',
			__( 'Carte', 'chambersign-office-locator' ),
			array( $this, 'render_carte_section_intro' ),
			self::PAGE_SLUG
		);

		$fields = array(
			'default_lat'      => __( 'Latitude par défaut', 'chambersign-office-locator' ),
			'default_lng'      => __( 'Longitude par défaut', 'chambersign-office-locator' ),
			'zoom_france'      => __( 'Zoom France (vue initiale)', 'chambersign-office-locator' ),
			'zoom_region'      => __( 'Zoom Région (plusieurs résultats)', 'chambersign-office-locator' ),
			'zoom_departement' => __( 'Zoom Département (un seul résultat)', 'chambersign-office-locator' ),
			'zoom_bureau'      => __( 'Zoom Bureau (bureau sélectionné)', 'chambersign-office-locator' ),
		);

		foreach ( $fields as $key => $label ) {
			add_settings_field(
				'csol_field_' . $key,
				$label,
				array( $this, 'render_field' ),
				self::PAGE_SLUG,
				'csol_section_carte',
				array( 'key' => $key )
			);
		}

		add_settings_section(
			'csol_section_apparence',
			__( 'Apparence', 'chambersign-office-locator' ),
			array( $this, 'render_apparence_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'csol_field_tile_provider',
			__( 'Fond de carte', 'chambersign-office-locator' ),
			array( $this, 'render_tile_provider_field' ),
			self::PAGE_SLUG,
			'csol_section_apparence'
		);

		add_settings_section(
			'csol_section_uninstall',
			__( 'Désinstallation', 'chambersign-office-locator' ),
			array( $this, 'render_uninstall_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'csol_field_delete_data_on_uninstall',
			__( 'Supprimer les données', 'chambersign-office-locator' ),
			array( $this, 'render_delete_data_field' ),
			self::PAGE_SLUG,
			'csol_section_uninstall'
		);
	}

	/**
	 * Sanitize les réglages avant sauvegarde.
	 *
	 * @param array<string, mixed> $input Valeurs soumises.
	 *
	 * @return array<string, float|int|string>
	 */
	public function sanitize_settings( $input ): array {
		$defaults = self::get_defaults();
		$output   = array();

		$output['default_lat'] = isset( $input['default_lat'] ) ? (float) $input['default_lat'] : $defaults['default_lat'];
		$output['default_lng'] = isset( $input['default_lng'] ) ? (float) $input['default_lng'] : $defaults['default_lng'];

		foreach ( array( 'zoom_france', 'zoom_region', 'zoom_departement', 'zoom_bureau' ) as $zoom_key ) {
			$value               = isset( $input[ $zoom_key ] ) ? absint( $input[ $zoom_key ] ) : $defaults[ $zoom_key ];
			$output[ $zoom_key ] = max( 0, min( 19, $value ) );
		}

		$providers                = self::get_tile_providers();
		$tile_provider            = isset( $input['tile_provider'] ) ? sanitize_key( $input['tile_provider'] ) : $defaults['tile_provider'];
		$output['tile_provider']  = isset( $providers[ $tile_provider ] ) ? $tile_provider : $defaults['tile_provider'];

		$output['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

		return $output;
	}

	/**
	 * Affiche l'introduction du bloc Documentation (shortcode + explication).
	 */
	public function render_documentation_section(): void {
		require CSOL_PLUGIN_DIR . 'admin/views/settings-documentation.php';
	}

	/**
	 * Affiche l'introduction du bloc Carte.
	 */
	public function render_carte_section_intro(): void {
		echo '<p>' . esc_html__( 'Ces paramètres définissent le centrage et le niveau de zoom initiaux de la carte, ainsi que les niveaux de zoom appliqués lors d\'une sélection.', 'chambersign-office-locator' ) . '</p>';
	}

	/**
	 * Affiche l'introduction du bloc Apparence.
	 */
	public function render_apparence_section_intro(): void {
		echo '<p>' . esc_html__( 'Fond de carte et icône du marqueur affichés sur le localisateur.', 'chambersign-office-locator' ) . '</p>';
	}

	/**
	 * Affiche un champ numérique de réglage.
	 *
	 * @param array{key: string} $args Arguments du champ.
	 */
	public function render_field( array $args ): void {
		$key      = $args['key'];
		$settings = self::get_settings();
		$value    = $settings[ $key ];
		$step     = in_array( $key, array( 'default_lat', 'default_lng' ), true ) ? 'any' : '1';
		?>
		<input
			type="number"
			step="<?php echo esc_attr( $step ); ?>"
			id="csol_field_<?php echo esc_attr( $key ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
		/>
		<?php
	}

	/**
	 * Affiche le sélecteur de fond de carte.
	 */
	public function render_tile_provider_field(): void {
		$settings = self::get_settings();
		$current  = $settings['tile_provider'];
		?>
		<select id="csol_field_tile_provider" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[tile_provider]">
			<?php foreach ( self::get_tile_providers() as $key => $provider ) : ?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $current, $key ); ?>>
					<?php echo esc_html( $provider['label'] ); ?>
				</option>
			<?php endforeach; ?>
		</select>
		<p class="description"><?php esc_html_e( 'Fonds de carte libres d\'accès, sans clé API ni service payant.', 'chambersign-office-locator' ); ?></p>
		<?php
	}

	/**
	 * Affiche l'avertissement du bloc Désinstallation.
	 */
	public function render_uninstall_section_intro(): void {
		?>
		<p>
			<?php esc_html_e( 'Par défaut, désinstaller l\'extension (Extensions > Supprimer) conserve tous vos bureaux, produits et réglages : vous pouvez la réinstaller sans rien reperdre. Cochez l\'option ci-dessous uniquement si vous voulez que la désinstallation efface définitivement ces données.', 'chambersign-office-locator' ); ?>
		</p>
		<?php
	}

	/**
	 * Affiche la case à cocher de suppression des données à la désinstallation.
	 */
	public function render_delete_data_field(): void {
		$settings = self::get_settings();
		$checked  = ! empty( $settings['delete_data_on_uninstall'] );
		?>
		<label>
			<input
				type="checkbox"
				id="csol_field_delete_data_on_uninstall"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[delete_data_on_uninstall]"
				value="1"
				<?php checked( $checked ); ?>
			/>
			<?php esc_html_e( 'Supprimer définitivement les bureaux, produits et réglages lorsque l\'extension est désinstallée.', 'chambersign-office-locator' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Décoché par défaut. Cette action est irréversible et n\'a aucun rapport avec le fait de désactiver l\'extension.', 'chambersign-office-locator' ); ?></p>
		<?php
	}
}
