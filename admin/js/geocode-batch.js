/**
 * Géocodage en masse des bureaux sans coordonnées GPS, depuis l'écran
 * d'import. Traite les bureaux par petits lots (voir Geocode::BATCH_SIZE
 * côté PHP) pour rester sous les limites de temps d'exécution PHP tout en
 * respectant la politique d'usage de Nominatim (1 requête/seconde).
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var button = document.getElementById( 'csol_geocode_batch_button' );
		if ( ! button ) {
			return;
		}

		var statusEl = document.getElementById( 'csol_geocode_batch_status' );
		var stopped = false;

		function runBatch() {
			if ( stopped ) {
				return;
			}

			var formData = new FormData();
			formData.append( 'action', csolAdminBatch.action );
			formData.append( 'nonce', csolAdminBatch.nonce );

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
						button.disabled = false;
						return;
					}

					var data = response.data;
					statusEl.textContent = csolAdminBatch.i18n.progress
						.replace( '%1$d', data.remaining )
						.replace( '%2$d', data.succeeded )
						.replace( '%3$d', data.failed );

					if ( data.remaining > 0 && data.processed > 0 ) {
						runBatch();
					} else {
						statusEl.textContent = csolAdminBatch.i18n.done.replace( '%1$d', data.remaining );
						button.disabled = false;
					}
				} )
				.catch( function () {
					statusEl.textContent = csolAdminBatch.i18n.error;
					button.disabled = false;
				} );
		}

		button.addEventListener( 'click', function () {
			stopped = false;
			button.disabled = true;
			statusEl.textContent = csolAdminBatch.i18n.starting;
			runBatch();
		} );
	} );
} )();
