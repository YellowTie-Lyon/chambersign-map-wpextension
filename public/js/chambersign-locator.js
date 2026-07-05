/**
 * ChamberSign Office Locator - front-end
 * Carte Leaflet/OpenStreetMap + recherche AJAX + interactions carte/liste.
 * JavaScript natif, sans dépendance autre que Leaflet.
 */
( function () {
	'use strict';

	if ( typeof csolLocator === 'undefined' || typeof L === 'undefined' ) {
		return;
	}

	// Icône du marqueur. IMPORTANT : on n'utilise jamais L.Icon.Default
	// (mergeOptions inclus) — sa méthode _getIconUrl préfixe toujours l'URL
	// avec un "imagePath" auto-détecté depuis le CSS de Leaflet, même quand
	// on lui donne une URL absolue ou une icône personnalisée. Résultat :
	// "{cheminLeaflet}/images/{notreURL}", une URL cassée. Un simple L.icon()
	// n'a pas ce comportement et est utilisé explicitement sur chaque
	// marqueur. On évite aussi L.divIcon() pour le point par défaut : dans
	// certains environnements il a été observé positionner les marqueurs de
	// façon incorrecte, alors que L.icon() (utilisé pour l'icône SVG
	// personnalisée) fonctionne de façon fiable — on utilise donc le même
	// mécanisme pour les deux cas plutôt que de creuser plus avant.
	var DEFAULT_DOT_ICON = 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxOCIgaGVpZ2h0PSIxOCI+PGNpcmNsZSBjeD0iOSIgY3k9IjkiIHI9IjYiIGZpbGw9IiNEMjEwMzQiIHN0cm9rZT0iI2ZmZmZmZiIgc3Ryb2tlLXdpZHRoPSIyLjUiLz48L3N2Zz4=';

	var markerIcon = L.icon( csolLocator.markerIconUrl
		? {
			iconUrl: csolLocator.markerIconUrl,
			iconRetinaUrl: csolLocator.markerIconUrl,
			iconSize: [ 32, 32 ],
			iconAnchor: [ 16, 32 ],
			popupAnchor: [ 0, -30 ],
		}
		: {
			iconUrl: DEFAULT_DOT_ICON,
			iconRetinaUrl: DEFAULT_DOT_ICON,
			iconSize: [ 18, 18 ],
			iconAnchor: [ 9, 9 ],
			popupAnchor: [ 0, -10 ],
		}
	);

	// Icône "Ma position" (bouton Autour de moi) : même mécanisme L.icon(),
	// bleu pour se distinguer des marqueurs bureaux.
	var USER_LOCATION_ICON = L.icon( {
		iconUrl: 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMCIgaGVpZ2h0PSIyMCI+PGNpcmNsZSBjeD0iMTAiIGN5PSIxMCIgcj0iNyIgZmlsbD0iIzFBNzNFOCIgc3Ryb2tlPSIjZmZmZmZmIiBzdHJva2Utd2lkdGg9IjMiLz48L3N2Zz4=',
		iconSize: [ 20, 20 ],
		iconAnchor: [ 10, 10 ],
		popupAnchor: [ 0, -12 ],
	} );

	/**
	 * Distance à vol d'oiseau entre deux points (formule de Haversine), en km.
	 */
	function haversineDistanceKm( lat1, lng1, lat2, lng2 ) {
		var toRad = function ( deg ) {
			return deg * Math.PI / 180;
		};
		var earthRadiusKm = 6371;
		var dLat = toRad( lat2 - lat1 );
		var dLng = toRad( lng2 - lng1 );
		var a = Math.sin( dLat / 2 ) * Math.sin( dLat / 2 ) +
			Math.cos( toRad( lat1 ) ) * Math.cos( toRad( lat2 ) ) *
			Math.sin( dLng / 2 ) * Math.sin( dLng / 2 );
		var c = 2 * Math.atan2( Math.sqrt( a ), Math.sqrt( 1 - a ) );
		return earthRadiusKm * c;
	}

	/**
	 * Formate une distance en km pour l'affichage (1 décimale sous 10 km).
	 */
	function formatDistanceKm( km ) {
		if ( km < 10 ) {
			return km.toFixed( 1 ).replace( '.', ',' ) + ' km';
		}
		return Math.round( km ) + ' km';
	}

	/**
	 * Construit l'icône d'une bulle de regroupement (nombre de bureaux
	 * qu'elle contient) sous forme de SVG généré à la volée, affiché via
	 * L.icon() — pas L.divIcon(), pour la même raison que ci-dessus.
	 */
	function buildClusterIcon( count ) {
		var size = count < 10 ? 34 : ( count < 25 ? 42 : 50 );
		var fontSize = count < 10 ? 13 : ( count < 25 ? 15 : 17 );
		var center = size / 2;
		var radius = center - 2;

		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' + size + '" height="' + size + '">' +
			'<circle cx="' + center + '" cy="' + center + '" r="' + radius + '" fill="#D21034" fill-opacity="0.9" stroke="#ffffff" stroke-width="2"/>' +
			'<text x="' + center + '" y="' + center + '" text-anchor="middle" dominant-baseline="central" font-family="Poppins, Arial, sans-serif" font-size="' + fontSize + '" font-weight="700" fill="#ffffff">' + count + '</text>' +
			'</svg>';

		var dataUri = 'data:image/svg+xml;base64,' + window.btoa( svg );

		return L.icon( {
			iconUrl: dataUri,
			iconSize: [ size, size ],
			iconAnchor: [ center, center ],
		} );
	}

	/**
	 * Débounce simple pour la recherche texte.
	 */
	function debounce( fn, wait ) {
		var timeout;
		return function () {
			var args = arguments;
			clearTimeout( timeout );
			timeout = setTimeout( function () {
				fn.apply( null, args );
			}, wait );
		};
	}

	function escapeHtml( value ) {
		var div = document.createElement( 'div' );
		div.textContent = value === null || value === undefined ? '' : String( value );
		return div.innerHTML;
	}

	/**
	 * Échappe une valeur pour une insertion sûre dans un attribut HTML
	 * entre guillemets doubles (échappe aussi les guillemets).
	 */
	function escapeAttr( value ) {
		return escapeHtml( value ).replace( /"/g, '&quot;' ).replace( /'/g, '&#39;' );
	}

	/**
	 * Raccourcit une URL de site web à son nom d'hôte (sans "www.") pour un
	 * affichage compact dans les cartes bureau.
	 */
	function siteLabel( url ) {
		try {
			return new URL( url ).hostname.replace( /^www\./, '' );
		} catch ( e ) {
			return url;
		}
	}

	/**
	 * Construit une instance du localisateur pour un conteneur donné.
	 */
	function CsolLocatorInstance( root ) {
		this.root = root;
		this.id = root.id;
		this.settings = csolLocator.settings;

		this.mapEl = document.getElementById( this.id + '-map' );
		this.listEl = document.getElementById( this.id + '-list' );
		this.countEl = document.getElementById( this.id + '-count' );
		this.searchInput = root.querySelector( '.csol-search-input' );
		this.regionSelect = root.querySelector( '.csol-filter-region' );
		this.produitSelect = root.querySelector( '.csol-filter-produit' );
		this.geolocButton = root.querySelector( '.csol-geoloc-button' );
		this.geolocStatusEl = document.getElementById( this.id + '-geoloc-status' );

		this.markers = {};
		this.activeCardId = null;
		this.lastBureaux = [];
		this.userLocation = null;
		this.userMarker = null;

		this.initMap();
		this.bindFilters();
		this.bindGeoloc();
		this.search();
	}

	CsolLocatorInstance.prototype.initMap = function () {
		this.map = L.map( this.mapEl ).setView(
			[ this.settings.defaultLat, this.settings.defaultLng ],
			this.settings.zoomFrance
		);

		var tile = csolLocator.tile || {};

		L.tileLayer( tile.url || 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: tile.attribution || '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
		} ).addTo( this.map );

		var hasClustering = typeof L.markerClusterGroup === 'function';

		this.markersLayer = hasClustering
			? L.markerClusterGroup( {
				showCoverageOnHover: false,
				spiderfyOnMaxZoom: true,
				iconCreateFunction: function ( cluster ) {
					return buildClusterIcon( cluster.getChildCount() );
				},
			} )
			: L.layerGroup();

		this.map.addLayer( this.markersLayer );
	};

	CsolLocatorInstance.prototype.bindFilters = function () {
		var self = this;
		var triggerSearch = debounce( function () {
			self.search();
		}, 350 );

		if ( this.searchInput ) {
			this.searchInput.addEventListener( 'input', triggerSearch );
		}
		if ( this.regionSelect ) {
			this.regionSelect.addEventListener( 'change', function () {
				self.search();
			} );
		}
		if ( this.produitSelect ) {
			this.produitSelect.addEventListener( 'change', function () {
				self.search();
			} );
		}
	};

	CsolLocatorInstance.prototype.bindGeoloc = function () {
		var self = this;

		if ( ! this.geolocButton ) {
			return;
		}

		this.geolocButton.addEventListener( 'click', function () {
			if ( self.userLocation ) {
				self.deactivateGeolocation();
			} else {
				self.activateGeolocation();
			}
		} );
	};

	CsolLocatorInstance.prototype.activateGeolocation = function () {
		var self = this;

		if ( ! navigator.geolocation ) {
			this.geolocStatusEl.textContent = csolLocator.i18n.geolocUnsupported;
			return;
		}

		this.geolocButton.disabled = true;
		this.geolocStatusEl.textContent = csolLocator.i18n.geolocSearching;

		navigator.geolocation.getCurrentPosition(
			function ( position ) {
				self.userLocation = {
					lat: position.coords.latitude,
					lng: position.coords.longitude,
				};
				self.geolocButton.disabled = false;
				self.geolocButton.classList.add( 'is-active' );
				self.geolocStatusEl.textContent = csolLocator.i18n.geolocActive;
				self.placeUserMarker();
				self.render( self.lastBureaux );
			},
			function () {
				self.geolocButton.disabled = false;
				self.geolocStatusEl.textContent = csolLocator.i18n.geolocDenied;
			},
			{ enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 }
		);
	};

	CsolLocatorInstance.prototype.deactivateGeolocation = function () {
		this.userLocation = null;
		this.geolocButton.classList.remove( 'is-active' );
		this.geolocStatusEl.textContent = '';

		if ( this.userMarker ) {
			this.map.removeLayer( this.userMarker );
			this.userMarker = null;
		}

		this.render( this.lastBureaux );
	};

	CsolLocatorInstance.prototype.placeUserMarker = function () {
		if ( this.userMarker ) {
			this.map.removeLayer( this.userMarker );
		}

		// Marqueur "ma position" ajouté directement à la carte, hors du
		// groupe de clustering : il ne doit jamais être regroupé avec les
		// bureaux.
		this.userMarker = L.marker( [ this.userLocation.lat, this.userLocation.lng ], {
			icon: USER_LOCATION_ICON,
			zIndexOffset: 1000,
			keyboard: false,
		} ).addTo( this.map );
		this.userMarker.bindPopup( escapeHtml( csolLocator.i18n.youAreHere ) );
	};

	CsolLocatorInstance.prototype.sortByDistance = function ( bureaux ) {
		var self = this;

		bureaux.forEach( function ( bureau ) {
			bureau._distanceKm = ( bureau.lat && bureau.lng )
				? haversineDistanceKm( self.userLocation.lat, self.userLocation.lng, bureau.lat, bureau.lng )
				: null;
		} );

		return bureaux.slice().sort( function ( a, b ) {
			if ( null === a._distanceKm ) {
				return 1;
			}
			if ( null === b._distanceKm ) {
				return -1;
			}
			return a._distanceKm - b._distanceKm;
		} );
	};

	CsolLocatorInstance.prototype.search = function () {
		var self = this;

		this.listEl.innerHTML = '<p class="csol-list-loading">' + escapeHtml( csolLocator.i18n.loading ) + '</p>';

		var formData = new FormData();
		formData.append( 'action', csolLocator.action );
		formData.append( 'nonce', csolLocator.nonce );
		formData.append( 'search', this.searchInput ? this.searchInput.value : '' );
		formData.append( 'region', this.regionSelect ? this.regionSelect.value : '' );
		formData.append( 'produit', this.produitSelect ? this.produitSelect.value : '' );

		fetch( csolLocator.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: formData,
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( response ) {
				if ( response.success ) {
					self.render( response.data.bureaux );
				} else {
					self.renderEmpty();
				}
			} )
			.catch( function () {
				self.renderEmpty();
			} );
	};

	CsolLocatorInstance.prototype.render = function ( bureaux ) {
		this.lastBureaux = bureaux;
		this.markersLayer.clearLayers();
		this.markers = {};
		this.activeCardId = null;

		if ( this.userLocation ) {
			bureaux = this.sortByDistance( bureaux );
		} else {
			// Nettoie une distance calculée lors d'un précédent rendu avec
			// géolocalisation active, pour ne pas l'afficher par erreur.
			bureaux.forEach( function ( bureau ) {
				bureau._distanceKm = null;
			} );
		}

		this.countEl.textContent = bureaux.length + ' ' + ( bureaux.length > 1 ? 'bureaux' : 'bureau' );

		if ( ! bureaux.length ) {
			this.renderEmpty();
			return;
		}

		var self = this;
		var listHtml = '';
		var bounds = [];

		bureaux.forEach( function ( bureau ) {
			listHtml += self.buildCardHtml( bureau );

			if ( bureau.lat && bureau.lng ) {
				var marker = L.marker( [ bureau.lat, bureau.lng ], { icon: markerIcon } ).addTo( self.markersLayer );
				marker.bindPopup( self.buildPopupHtml( bureau ) );
				marker.on( 'click', function () {
					self.highlightCard( bureau.id );
				} );
				self.markers[ bureau.id ] = marker;
				bounds.push( [ bureau.lat, bureau.lng ] );
			}
		} );

		this.listEl.innerHTML = listHtml;

		if ( this.userLocation ) {
			// En mode "Autour de moi", on centre/zoome sur la position de
			// l'utilisateur plutôt que d'englober tous les résultats : avec
			// 144 bureaux à travers la France, un fitBounds sur l'ensemble
			// zoomerait beaucoup trop large (et serait faussé par le moindre
			// bureau mal géocodé). La liste reste triée par distance.
			this.map.flyTo( [ this.userLocation.lat, this.userLocation.lng ], this.settings.zoomDepartement );
		} else if ( bounds.length > 1 ) {
			this.map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: this.settings.zoomRegion } );
		} else if ( 1 === bounds.length ) {
			this.map.setView( bounds[ 0 ], this.settings.zoomDepartement );
		}

		this.bindCardEvents();
	};

	CsolLocatorInstance.prototype.renderEmpty = function () {
		var hasActiveFilters = ( this.searchInput && this.searchInput.value )
			|| ( this.regionSelect && this.regionSelect.value )
			|| ( this.produitSelect && this.produitSelect.value );

		var html = '<p class="csol-list-empty">' + escapeHtml( csolLocator.i18n.noResults ) + '</p>';

		if ( hasActiveFilters ) {
			html += '<button type="button" class="csol-btn csol-btn-secondary csol-reset-filters">' + escapeHtml( csolLocator.i18n.resetFilters ) + '</button>';
		}

		this.listEl.innerHTML = html;
		this.countEl.textContent = '';

		var resetButton = this.listEl.querySelector( '.csol-reset-filters' );
		if ( resetButton ) {
			var self = this;
			resetButton.addEventListener( 'click', function () {
				self.resetFilters();
			} );
		}
	};

	CsolLocatorInstance.prototype.resetFilters = function () {
		if ( this.searchInput ) {
			this.searchInput.value = '';
		}
		if ( this.regionSelect ) {
			this.regionSelect.value = '';
		}
		if ( this.produitSelect ) {
			this.produitSelect.value = '';
		}
		this.search();
	};

	CsolLocatorInstance.prototype.buildPopupHtml = function ( bureau ) {
		var html = '<div class="csol-popup">';
		html += '<strong>' + escapeHtml( bureau.title ) + '</strong><br>';
		if ( bureau.adresse ) {
			html += escapeHtml( bureau.adresse ) + '<br>';
		}
		if ( bureau.ville ) {
			html += escapeHtml( bureau.code_postal ) + ' ' + escapeHtml( bureau.ville );
		}
		html += '</div>';
		return html;
	};

	CsolLocatorInstance.prototype.buildCardHtml = function ( bureau ) {
		var produitsHtml = '';
		if ( bureau.produits && bureau.produits.length ) {
			produitsHtml = '<div class="csol-card-produits">';
			bureau.produits.forEach( function ( produit ) {
				produitsHtml += '<span class="csol-badge-produit">' + escapeHtml( produit.name ) + '</span>';
			} );
			produitsHtml += '</div>';
		}

		var siteHtml = '';
		if ( bureau.site ) {
			siteHtml = '<a href="' + escapeAttr( bureau.site ) + '" class="csol-card-link" target="_blank" rel="noopener noreferrer">' + escapeHtml( siteLabel( bureau.site ) ) + '</a>';
		}

		var telHtml = '';
		if ( bureau.telephone ) {
			telHtml = '<a href="tel:' + escapeAttr( bureau.telephone.replace( /\s+/g, '' ) ) + '" class="csol-card-tel">' + escapeHtml( bureau.telephone ) + '</a>';
		}

		var contactHtml = '';
		if ( telHtml || siteHtml ) {
			contactHtml = '<p class="csol-card-contact">' + telHtml + siteHtml + '</p>';
		}

		var distanceHtml = ( 'number' === typeof bureau._distanceKm )
			? ' <span class="csol-card-distance">&middot; ' + escapeHtml( formatDistanceKm( bureau._distanceKm ) ) + '</span>'
			: '';

		return (
			'<article class="csol-card" id="' + this.id + '-card-' + bureau.id + '" data-bureau-id="' + bureau.id + '">' +
				'<h3 class="csol-card-title">' + escapeHtml( bureau.title ) + '</h3>' +
				'<p class="csol-card-location">' + escapeHtml( bureau.ville ) + ( bureau.region ? ' &middot; ' + escapeHtml( bureau.region ) : '' ) + distanceHtml + '</p>' +
				( bureau.adresse ? '<p class="csol-card-adresse">' + escapeHtml( bureau.adresse ) + '</p>' : '' ) +
				contactHtml +
				produitsHtml +
				'<div class="csol-card-actions">' +
					'<button type="button" class="csol-btn csol-btn-primary csol-card-view" data-bureau-id="' + bureau.id + '">' + escapeHtml( csolLocator.i18n.viewOnMap ) + '</button>' +
				'</div>' +
			'</article>'
		);
	};

	CsolLocatorInstance.prototype.bindCardEvents = function () {
		var self = this;

		this.listEl.querySelectorAll( '.csol-card-view' ).forEach( function ( button ) {
			button.addEventListener( 'click', function () {
				var bureauId = button.getAttribute( 'data-bureau-id' );
				self.focusBureau( bureauId );
			} );
		} );
	};

	CsolLocatorInstance.prototype.focusBureau = function ( bureauId ) {
		var marker = this.markers[ bureauId ];
		if ( ! marker ) {
			return;
		}

		var self = this;

		if ( typeof this.markersLayer.zoomToShowLayer === 'function' ) {
			// Le marqueur peut être caché dans une bulle de regroupement :
			// zoome/déplace la carte jusqu'à ce qu'il soit visible seul,
			// avant d'ouvrir sa popup (comportement fourni par le plugin de
			// clustering, gère aussi le cas non-clusterisé).
			this.markersLayer.zoomToShowLayer( marker, function () {
				marker.openPopup();
				self.highlightCard( bureauId );
			} );
			return;
		}

		this.map.flyTo( marker.getLatLng(), this.settings.zoomBureau );
		marker.openPopup();
		this.highlightCard( bureauId );
	};

	CsolLocatorInstance.prototype.highlightCard = function ( bureauId ) {
		if ( this.activeCardId ) {
			var previous = document.getElementById( this.id + '-card-' + this.activeCardId );
			if ( previous ) {
				previous.classList.remove( 'csol-card-active' );
			}
		}

		var card = document.getElementById( this.id + '-card-' + bureauId );
		if ( card ) {
			card.classList.add( 'csol-card-active' );
			card.scrollIntoView( { behavior: 'smooth', block: 'nearest' } );
		}

		this.activeCardId = bureauId;
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		document.querySelectorAll( '.csol-locator' ).forEach( function ( root ) {
			new CsolLocatorInstance( root );
		} );
	} );
} )();
