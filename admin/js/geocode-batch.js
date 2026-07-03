/**
 * Géocodage en masse des bureaux depuis l'écran d'import : soit uniquement
 * ceux sans coordonnées GPS, soit un re-géocodage forcé de tous les bureaux
 * publiés. Traite par petits lots (voir Geocode::BATCH_SIZE côté PHP) pour
 * rester sous les limites de temps d'exécution PHP tout en respectant la
 * politique d'usage de Nominatim (1 requête/seconde).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var missingButton = document.getElementById( 'csol_geocode_batch_button' );
		var forceButton = document.getElementById( 'csol_geocode_force_button' );
		var statusEl = document.getElementById( 'csol_geocode_batch_status' );

		if ( ( ! missingButton && ! forceButton ) || ! statusEl ) {
			return;
		}

		function setButtonsDisabled( disabled ) {
			if ( missingButton ) {
				missingButton.disabled = disabled;
			}
			if ( forceButton ) {
				forceButton.disabled = disabled;
			}
		}

		function runBatch( force ) {
			var formData = new FormData();
			formData.append( 'action', csolAdminBatch.action );
			formData.append( 'nonce', csolAdminBatch.nonce );
			if ( force ) {
				formData.append( 'force', '1' );
			}

			fetch( csolAdminBatch.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: formData,
			} )
				.then( function ( response ) {
					return response.json();
				} )
				.then( function ( response ) {
					if ( ! response.success ) {
						statusEl.textContent = csolAdminBatch.i18n.error;
						setButtonsDisabled( false );
						return;
					}

					var data = response.data;
					statusEl.textContent = csolAdminBatch.i18n.progress
						.replace( '%1$d', data.remaining )
						.replace( '%2$d', data.succeeded )
						.replace( '%3$d', data.failed );

					if ( data.remaining > 0 && data.processed > 0 ) {
						runBatch( force );
					} else {
						statusEl.textContent = csolAdminBatch.i18n.done.replace( '%1$d', data.remaining );
						setButtonsDisabled( false );
					}
				} )
				.catch( function () {
					statusEl.textContent = csolAdminBatch.i18n.error;
					setButtonsDisabled( false );
				} );
		}

		if ( missingButton ) {
			missingButton.addEventListener( 'click', function () {
				setButtonsDisabled( true );
				statusEl.textContent = csolAdminBatch.i18n.starting;
				runBatch( false );
			} );
		}

		if ( forceButton ) {
			forceButton.addEventListener( 'click', function () {
				if ( ! window.confirm( csolAdminBatch.i18n.confirmForce ) ) {
					return;
				}
				setButtonsDisabled( true );
				statusEl.textContent = csolAdminBatch.i18n.starting;
				runBatch( true );
			} );
		}
	} );
} )();
