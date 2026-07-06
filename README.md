# ChamberSign Office Locator

Extension WordPress sur mesure permettant aux visiteurs du site [ChamberSign](https://www.chambersign.fr/) de trouver le bureau d'enregistrement le plus proche pour retirer leur certificat électronique : carte interactive OpenStreetMap/Leaflet, recherche instantanée et gestion complète des bureaux depuis l'administration.

Développée par **YellowTie** pour **ChamberSign**.

## Fonctionnalités

- **Carte interactive** OpenStreetMap/Leaflet (aucune dépendance à Google Maps), avec regroupement automatique des bureaux proches (Leaflet.markercluster) : clic sur une bulle pour zoomer et révéler les bureaux qu'elle contient.
- **Recherche AJAX** en temps réel — texte libre (ville, code postal, adresse, bureau), région, produit — sans rechargement de page.
- **"Autour de moi"** : géolocalisation navigateur, tri des résultats par distance (persiste tant qu'elle est active, même en changeant de filtre).
- **Fiches bureaux compactes** : nom, adresse, téléphone, site web, produits proposés, bouton itinéraire.
- **Gestion complète en back-office** :
  - Custom Post Type *Bureaux d'enregistrement* (coordonnées GPS, contact, horaires, statut actif/inactif).
  - Taxonomie *Produits* (RGS, eIDAS, Signature Électronique, Cachet Serveur…) en relation many-to-many avec les bureaux.
  - Page Réglages (position/zooms de la carte, fond de carte, icône du marqueur) via l'API Settings de WordPress.
- **Import en masse** de bureaux depuis un fichier CSV ou XLSX, avec mise à jour des bureaux existants.
- **Géocodage** des adresses via OpenStreetMap Nominatim : au cas par cas depuis la fiche d'un bureau, ou en masse depuis l'écran d'import pour traiter de gros volumes.
- **Design** conforme à la charte graphique ChamberSign (couleurs, typographie Poppins), responsive mobile/tablette.
- Compatible **Elementor** via le widget Shortcode.

## Prérequis

- WordPress 6.0+
- PHP 8.0+
- Aucune dépendance payante ni service tiers payant

## Installation

1. Récupérez le code de ce dépôt (`git clone` ou téléchargement de l'archive).
2. Copiez l'ensemble des fichiers dans `wp-content/plugins/chambersign-office-locator/` de votre installation WordPress (le fichier `chambersign-office-locator.php` doit se trouver directement dans ce dossier).
3. Activez l'extension depuis **Extensions** dans l'administration WordPress.
4. Un nouveau menu **ChamberSign Locator** apparaît, avec les sous-menus *Bureaux d'enregistrement*, *Produits*, *Import* et *Réglages*.

## Utilisation

### Afficher le localisateur

Insérez le shortcode suivant sur n'importe quelle page ou article (éditeur classique, éditeur de blocs, ou widget Shortcode Elementor) :

```
[chambersign_locator]
```

### Afficher uniquement la carte (page d'accueil, widget)

Pour un aperçu compact sans recherche ni liste — carte seule centrée sur la France, bureaux en clusters cliquables :

```
[chambersign_locator_map]
[chambersign_locator_map height="500px"]
```

L'attribut `height` est optionnel (480px par défaut).

### Ajouter des bureaux

- Manuellement depuis **ChamberSign Locator > Bureaux d'enregistrement > Ajouter**.
- En masse depuis **ChamberSign Locator > Import**, via un fichier CSV ou XLSX contenant les colonnes : `Région`, `Nom du bureau`, `Département`, `Ville`, `Code postal`, `Adresse`, `Téléphone`, `Site internet`, `Horaires`, `Latitude`, `Longitude`, `Produits` (plusieurs produits séparés par une virgule ou un `|`). Un bureau existant portant le même nom est mis à jour plutôt que dupliqué.

### Géocoder les adresses

Si un bureau n'a pas de latitude/longitude, un bouton **Géocoder depuis l'adresse** est disponible sur sa fiche (utilise OpenStreetMap Nominatim). Pour un import en masse sans coordonnées, l'écran **Import** propose un géocodage automatique par lots, qui respecte la limite d'une requête par seconde imposée par Nominatim.

### Réglages de la carte

Depuis **ChamberSign Locator > Réglages** :
- Position par défaut de la carte (latitude/longitude) et niveaux de zoom appliqués (France, région, département, sélection d'un bureau).
- Fond de carte : OpenStreetMap standard, OpenStreetMap France (libellés en français), ou CartoDB Voyager/Positron/Dark Matter (tuiles libres, sans clé API).
- Icône personnalisée du marqueur (SVG, 100 Ko max) en remplacement du pin par défaut.

## Structure du projet

```
chambersign-office-locator.php   Fichier principal du plugin (bootstrap, autoload)
uninstall.php                    Nettoyage des données à la désinstallation
includes/
  class-plugin.php               Point d'entrée, câblage des composants
  cpt/                           Custom Post Type "Bureau d'enregistrement"
  taxonomy/                      Taxonomie "Produit"
  admin/                         Menus, réglages, meta box, import CSV/XLSX
  ajax/                          Points d'entrée AJAX (recherche, géocodage)
  front/                         Shortcode et chargement des assets front
  vendor/simplexlsx/             Lecture des fichiers XLSX (MIT)
admin/                           Vues et assets de l'administration
public/                          Vues, CSS et JS du front (+ Leaflet BSD, Leaflet.markercluster MIT)
languages/                       Fichiers de traduction
```

## Sécurité

- Toutes les entrées sont assainies (`sanitize_text_field`, `esc_url_raw`…) et toutes les sorties échappées (`esc_html`, `esc_attr`, `esc_url`…).
- Toutes les actions AJAX sont protégées par un nonce WordPress et une vérification de capacité.
- Aucune dépendance externe payante : Leaflet (BSD), Leaflet.markercluster (MIT) et SimpleXLSX (MIT) sont fournis avec le plugin.
- Désinstaller l'extension (Extensions > Supprimer) **conserve vos données par défaut** : bureaux, produits et réglages restent en base tant que la case correspondante n'est pas cochée dans Réglages > Désinstallation. Réinstaller le plugin ne fait donc jamais perdre vos bureaux.

## Licence

GPLv2 ou ultérieure — voir [readme.txt](readme.txt).

## Crédits

Développé par **YellowTie** pour **ChamberSign**.
