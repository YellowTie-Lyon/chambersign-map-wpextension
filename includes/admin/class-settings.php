<?php
namespace ChamberSign\Locator\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Réglages du plugin (bloc Documentation + bloc Carte), via l'API
 * Settings de WordPress.
 */
class Settings {

	public const OPTION_NAME = 'csol_settings';
	public const OPTION_GROUP = 'csol_settings_group';
	public const PAGE_SLUG    = 'chambersign-locator-settings';

	/**
	 * Valeurs par défaut des réglages carte.
	 *
	 * @return array<string, float|int>
	 */
	public static function get_defaults(): array {
		return array(
			'default_lat'      => 46.603354,
			'default_lng'      => 1.888334,
			'zoom_france'      => 6,
			'zoom_region'      => 8,
			'zoom_departement' => 10,
			'zoom_bureau'      => 14,
		);
	}

	/**
	 * Retourne les réglages actuels, fusionnés avec les valeurs par défaut.
	 *
	 * @return array<string, float|int>
	 */
	public static function get_settings(): array {
		$saved = get_option( self::OPTION_NAME, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::get_defaults() );
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
			'zoom_france'      => __( 'Zoom France', 'chambersign-office-locator' ),
			'zoom_region'      => __( 'Zoom Région', 'chambersign-office-locator' ),
			'zoom_departement' => __( 'Zoom Département', 'chambersign-office-locator' ),
			'zoom_bureau'      => __( 'Zoom Bureau', 'chambersign-office-locator' ),
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
	}

	/**
	 * Sanitize les réglages avant sauvegarde.
	 *
	 * @param array<string, mixed> $input Valeurs soumises.
	 *
	 * @return array<string, float|int>
	 */
	public function sanitize_settings( $input ): array {
		$defaults = self::get_defaults();
		$output   = array();

		$output['default_lat'] = isset( $input['default_lat'] ) ? (float) $input['default_lat'] : $defaults['default_lat'];
		$output['default_lng'] = isset( $input['default_lng'] ) ? (float) $input['default_lng'] : $defaults['default_lng'];

		foreach ( array( 'zoom_france', 'zoom_region', 'zoom_departement', 'zoom_bureau' ) as $zoom_key ) {
			$value                 = isset( $input[ $zoom_key ] ) ? absint( $input[ $zoom_key ] ) : $defaults[ $zoom_key ];
			$output[ $zoom_key ]   = max( 0, min( 19, $value ) );
		}

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
}
