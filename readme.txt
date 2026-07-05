=== ChamberSign Office Locator ===
Contributors: yellowtie
Tags: locator, map, leaflet, openstreetmap, certificat
Requires at least: 6.0
Tested up to: 6.6
Requires PHP: 8.0
Stable tag: 1.3.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Localisateur de bureaux d'enregistrement ChamberSign, avec carte OpenStreetMap/Leaflet, recherche AJAX et gestion des bureaux/produits.

== Description ==

ChamberSign Office Locator permet aux visiteurs du site ChamberSign de trouver le bureau d'enregistrement le plus proche pour retirer leur certificat électronique.

Fonctionnalités :

* Custom Post Type "Bureaux d'enregistrement" avec coordonnées GPS, coordonnées de contact et statut actif/inactif.
* Taxonomie "Produits" (RGS, eIDAS, Signature Électronique, Cachet Serveur…) en relation many-to-many avec les bureaux.
* Carte interactive OpenStreetMap (Leaflet), sans dépendance à Google Maps, avec regroupement automatique des bureaux proches (Leaflet.markercluster).
* Recherche AJAX (texte libre, région, produit) sans rechargement de page, avec géolocalisation "Autour de moi" (tri par distance).
* Import/mise à jour en masse des bureaux depuis un fichier CSV ou XLSX.
* Aide au géocodage (Nominatim/OpenStreetMap) dans l'administration.
* Réglages de la carte (position, zooms par défaut, fond de carte, icône du marqueur) via l'API Settings de WordPress.
* Design conforme à la charte graphique ChamberSign (couleurs, typographie Poppins), responsive mobile/tablette.
* Compatible Elementor via le widget Shortcode.

== Installation ==

1. Copiez le dossier `chambersign-office-locator` dans `wp-content/plugins/`, ou installez le fichier ZIP depuis Extensions > Ajouter.
2. Activez le plugin.
3. Dans le menu "ChamberSign Locator", ajoutez vos bureaux d'enregistrement (ou importez-les via l'écran Import).
4. Assignez les produits proposés par chaque bureau.
5. Ajustez éventuellement les réglages de la carte dans "ChamberSign Locator > Réglages".
6. Insérez le shortcode `[chambersign_locator]` sur la page de votre choix (éditeur classique, éditeur de blocs, ou widget Shortcode Elementor).

== Shortcode ==

`[chambersign_locator]`

Aucun attribut requis : le shortcode affiche la carte et la liste des bureaux actifs, avec les filtres de recherche.

== Import CSV/XLSX ==

Colonnes reconnues : Région, Nom du bureau, Département, Ville, Code postal, Adresse, Téléphone, Site internet, Horaires, Latitude, Longitude, Produits (séparés par une virgule ou un "|").

Un bureau existant portant le même nom est mis à jour ; sinon un nouveau bureau est créé.

== Changelog ==

= 1.3.2 =
* Correction : le bouton "Autour de moi" n'était pas aligné visuellement avec le reste de la barre de filtres (hauteur différente à cause d'un conflit de spécificité CSS).
* Correction : activer "Autour de moi" ne zoomait plus sur la position de l'utilisateur car la carte tentait d'englober tous les résultats (jusqu'à l'autre bout du monde en cas de bureau mal géocodé). Elle centre désormais la vue directement sur l'utilisateur.
* Suppression du bouton "Itinéraire" des cartes bureau (ne reste que "Voir sur la carte").

= 1.3.1 =
* Ajustement d'affichage : la colonne liste (à droite) fait maintenant exactement la même hauteur que la carte, quel que soit le nombre de résultats.

= 1.3.0 =
* Refonte de la barre de filtres : suppression du filtre Département (redondant avec la recherche texte, qui couvre déjà ville/code postal/département) et ajout d'un bouton "Autour de moi" (géolocalisation navigateur). Les résultats sont alors triés par distance, avec la distance affichée sur chaque bureau, et restent triés ainsi tant que la géolocalisation est active, même en changeant de filtre.

= 1.2.0 =
* Ajout du regroupement automatique des bureaux proches sur la carte (Leaflet.markercluster) : bulle avec le nombre de bureaux, clic pour zoomer et les révéler.

= 1.1.5 =
* Correction : le marqueur par défaut (point pulsant, introduit en 1.1.3) construit avec L.divIcon() pouvait se retrouver mal positionné sur la carte dans certains environnements, alors que la même donnée restait correcte partout ailleurs (fiche bureau, popup). Le point par défaut est reconstruit avec L.icon() — le même mécanisme, déjà fiable, que l'icône SVG personnalisée — et n'est plus animé (simple point rouge fixe, comme demandé).

= 1.1.4 =
* Correction : le géocodage automatique n'était pas restreint à la France. Sur des adresses ambiguës ou mal formées, Nominatim pouvait faire correspondre le bureau à un pays homonyme (souvent une ancienne colonie francophone), plaçant le marqueur très loin de son emplacement réel.
* Ajout d'un bouton "Re-géocoder tous les bureaux" (Import) pour recalculer les coordonnées de bureaux déjà géocodés (utile après ce correctif si des positions semblent fausses).

= 1.1.3 =
* Correction : l'icône du marqueur (par défaut comme personnalisée) pouvait générer une URL cassée à cause d'un comportement interne de Leaflet (L.Icon.Default préfixe toujours l'URL avec un chemin auto-détecté). Les marqueurs utilisent maintenant une icône construite explicitement (L.icon/L.divIcon), sans ce problème.
* Nouveau marqueur par défaut : point rouge pulsant (aux couleurs ChamberSign), sans aucune image à charger.

= 1.1.2 =
* IMPORTANT : la désinstallation (Extensions > Supprimer) supprimait automatiquement et définitivement tous les bureaux, produits et réglages. Elle conserve désormais les données par défaut ; la suppression devient une option à cocher explicitement dans Réglages > Désinstallation.

= 1.1.1 =
* Correction : la recherche texte libre ne portait que sur le nom du bureau. Elle porte désormais aussi sur la ville, le code postal, l'adresse, la région et le département.
* Ajout d'un bouton "Réinitialiser les filtres" quand la recherche ne renvoie aucun résultat.

= 1.1.0 =
* Ajout d'un sélecteur de fond de carte (OpenStreetMap, CartoDB Voyager/Positron/Dark Matter).
* Ajout de l'upload d'une icône de marqueur personnalisée (SVG) dans les réglages.
* Cartes bureau plus compactes dans la liste des résultats.

= 1.0.0 =
* Version initiale.

== Crédits ==

Développé par YellowTie pour ChamberSign.
