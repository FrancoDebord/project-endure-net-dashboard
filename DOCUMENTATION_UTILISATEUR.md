# Documentation utilisateur — ENDURE-Net Dashboard

**Version :** 1.3 · **Date :** Juin 2026  
**Projet :** AIRID / ENDURE-Net · Suivi MILDA, données REDCap & KoboToolbox

---

## Table des matières

1. [Vue d'ensemble](#1-vue-densemble)
2. [Connexion et authentification](#2-connexion-et-authentification)
3. [Interface principale — Navigation](#3-interface-principale--navigation)
4. [Tableau de bord — Onglet Baseline & Distribution](#4-tableau-de-bord--onglet-baseline--distribution)
5. [Tableau de bord — Onglet Acceptabilité](#5-tableau-de-bord--onglet-acceptabilité)
6. [Tableau de bord — Onglet Événements indésirables](#6-tableau-de-bord--onglet-événements-indésirables)
7. [Tableau de bord — Onglet Suivi](#7-tableau-de-bord--onglet-suivi)
8. [Section Distribution des moustiquaires par binôme](#8-section-distribution-des-moustiquaires-par-binôme)
9. [Module Queries — Contrôle qualité des données](#9-module-queries--contrôle-qualité-des-données)
10. [Module Export GPS](#10-module-export-gps)
11. [Exports PDF](#11-exports-pdf)
12. [Actualisation du cache](#12-actualisation-du-cache)
13. [Module Inspections physiques](#13-module-inspections-physiques)
14. [Rapport d'exécution par bras](#14-rapport-dexécution-par-bras)
15. [Gestion des erreurs et connectivité](#15-gestion-des-erreurs-et-connectivité)

---

## 1. Vue d'ensemble

Le tableau de bord ENDURE-Net est une application web de suivi des données de terrain pour l'étude AIRID. Il agrège en temps réel les données collectées via REDCap (projet 46) et fournit :

- **Statistiques de terrain** : visites, consentements, cohortes A/B, moustiquaires distribuées
- **Contrôle qualité** : détection automatique d'incohérences et de données manquantes
- **Export GPS** : génération de fichiers GPX pour la navigation terrain (OsmAnd)
- **Rapports PDF** : impression de tableaux de distribution, rapports de performance
- **Inspections physiques** : suivi des inspections de moustiquaires via KoboToolbox
- **Rapport bras** : taux d'exécution par bras de traitement et par cohorte

**Zones géographiques couvertes :**

| Village | Code | Grappes |
|---|---|---|
| Djigbé | DJ | 1, 2 |
| Gbônou | GB | 3, 4, 5 |
| Miniffi | MI | 6, 7, 8, 9 |
| Dogbo | DO | 10 |
| Agao | AG | 11 |
| Akoba | AK | 12 |

**Bras de l'étude :**
- **Bras 1 (PERMANET DUAL)** — Grappes 1, 5, 7, 8
- **Bras 2 (INTERCEPTOR G2)** — Grappes 2, 3, 4, 6

---

## 2. Connexion et authentification

### 2.1 Connexion par email et mot de passe

1. Accéder à l'URL de l'application.
2. Renseigner votre adresse e-mail institutionnelle et votre mot de passe.
3. Cocher **"Se souvenir de moi"** pour rester connecté entre les sessions.
4. Cliquer sur **"Se connecter"**.

> En cas d'erreur, un message s'affiche sous le champ e-mail. Vérifier que votre compte est actif auprès de l'administrateur.

### 2.2 Connexion via Microsoft 365

Cliquer sur le bouton **"Se connecter avec Microsoft 365"** sous le formulaire.  
Vous serez redirigé vers la page de connexion Microsoft de votre organisation.

- Seuls les comptes dont l'adresse e-mail correspond à un compte **actif** dans la base AIRID sont autorisés.
- Si votre compte n'est pas reconnu, contactez l'administrateur pour l'activation.

### 2.3 Mot de passe oublié

1. Cliquer sur **"Mot de passe oublié ?"** sous le formulaire.
2. Saisir votre adresse e-mail institutionnelle.
3. Un lien de réinitialisation vous sera envoyé par e-mail.
4. Cliquer sur le lien reçu et définir un nouveau mot de passe (minimum 8 caractères).

### 2.4 Déconnexion

Cliquer sur le bouton **"Déconnexion"** dans le coin supérieur droit de la bannière.

---

## 3. Interface principale — Navigation

### 3.1 Bannière supérieure

La bannière affiche :
- Le logo et le nom de l'application **ENDURE-Net**
- Un lien **"Queries"** vers le module de contrôle qualité
- Un lien **"GPS"** vers le module d'export GPS
- Un lien **"Rapport bras"** vers le rapport d'exécution par bras
- Un lien **"Inspections"** vers le module d'inspections physiques
- Un lien **"Docs"** vers cette documentation
- Un bouton **"Actualiser"** pour vider le cache et recharger les données REDCap
- Le **nom de l'utilisateur connecté** (initiales + prénom nom)
- Le bouton **"Déconnexion"**

### 3.2 Onglets du tableau de bord

Le tableau de bord comporte 5 onglets :

| Onglet | Contenu |
|---|---|
| **Baseline & Distribution** | Statistiques de visites, consentements, cohortes, moustiquaires |
| **Acceptabilité** | Données d'acceptabilité utilisateur des MILDA |
| **Événements indésirables** | Suivi des effets indésirables signalés |
| **Suivi** | Données des visites de suivi |
| **Distribution** *(section en bas)* | Tableau détaillé par binôme/tablette |

Cliquer sur le nom d'un onglet pour l'afficher. L'onglet actif est souligné en rouge.

### 3.3 Filtres globaux

En haut du tableau de bord, des filtres s'appliquent à l'ensemble des onglets :

- **Village** : Djigbé / Gbônou / Miniffi / Dogbo / Agao / Akoba / Tous
- **Grappe** : 1 à 12 (filtrage par cluster)
- **Bras** : Bras 1 (PERMANET DUAL) / Bras 2 (INTERCEPTOR G2) / Tous

> Les filtres Village et Bras sont liés : sélectionner un village filtre automatiquement les grappes correspondantes.

---

## 4. Tableau de bord — Onglet Baseline & Distribution

### 4.1 Cartes KPI (indicateurs clés)

Cinq cartes s'affichent en haut :

| Carte | Description |
|---|---|
| **Ménages visités** | Nombre total de ménages ayant reçu au moins une visite |
| **Consentements** | Nombre et pourcentage de ménages ayant donné leur consentement |
| **Cohorte A** | Ménages enrôlés en cohorte A |
| **Cohorte B** | Ménages enrôlés en cohorte B |
| **Moustiquaires** | Total de MILDA distribuées |

### 4.2 Tableau Cohortes par bras

Ce tableau récapitule la répartition des ménages par bras et par cohorte.

- La colonne de gauche liste les bras (Bras 1 et Bras 2).
- Pour chaque bras : nombre de ménages **consentis** (en gras) sur le total visité.
- Les lignes suivantes détaillent la répartition par cohorte (A et B uniquement).

### 4.3 Tableau de détail des ménages

Le tableau liste tous les ménages avec :

| Colonne | Description |
|---|---|
| Code Ménage | Identifiant unique du ménage |
| Village / Grappe | Localisation |
| Bras | Bras de l'étude |
| Tablette | Identifiant de la tablette de collecte |
| Cohorte | A ou B |
| Consentement | Statut (Accepté / Non accepté) |
| Taille | Nombre de membres du ménage |
| Moustiquaires | Nombre de MILDA distribuées |

#### Filtres du tableau de détail

Une barre de filtre au-dessus du tableau permet de restreindre l'affichage :

- **Recherche** : recherche libre dans le code ménage
- **Village** : filtre par village
- **Grappe** : filtre par numéro de grappe
- **Bras** : filtre par bras
- **Cohorte** : filtre par cohorte (A / B)
- **Consentement** : filtre les ménages ayant consenti / non consentis
- **Tablette** : filtre par identifiant de tablette

### 4.4 Carte interactive (Leaflet)

Une carte géographique affiche les ménages géolocalisés sous forme de points colorés :

- **Points rouges** : ménages Bras 1 (PERMANET DUAL)
- **Points bleus** : ménages Bras 2 (INTERCEPTOR G2)

Cliquer sur un point pour afficher le code ménage et ses informations de base.

Les couches de fond disponibles (en haut à gauche de la carte) :
- OpenStreetMap (standard)
- Satellite (imagerie aérienne)

---

## 5. Tableau de bord — Onglet Acceptabilité

Cet onglet présente les données du questionnaire d'acceptabilité utilisateur (UA) des MILDA :

- **Taux d'utilisation** : proportion de ménages déclarant avoir utilisé les moustiquaires
- **Raisons de non-utilisation** : graphique ou tableau des motifs signalés
- **Satisfaction** : indicateurs de satisfaction par type de moustiquaire

> Les données sont calculées uniquement pour les ménages ayant retourné le formulaire UA.

---

## 6. Tableau de bord — Onglet Événements indésirables

Cet onglet regroupe les données du formulaire Adverse Events (AE) :

- Liste des symptômes signalés après utilisation des MILDA
- Répartition par type de symptôme et par bras
- Taux de signalement par rapport aux ménages exposés

---

## 7. Tableau de bord — Onglet Suivi

Données issues des visites de suivi post-distribution :

- **Présence des moustiquaires** : vérification que les MILDA sont toujours au domicile
- **Utilisation** : déclaration d'utilisation lors de la dernière nuit
- **Nombre de nuits d'utilisation** : statistiques d'usage
- **Comparaison Bras 1 / Bras 2**

---

## 8. Section Distribution des moustiquaires par binôme

Cette section, accessible en bas du tableau de bord (après les onglets), présente le détail des moustiquaires distribuées par binôme de terrain (tablette).

### 8.1 Filtres de la section Distribution

- **Date de début / Date de fin** : période de distribution
- **Tablettes** : sélection des binômes à afficher (cases à cocher)

Cliquer sur **"Appliquer"** pour filtrer.

### 8.2 Tableau par tablette

Chaque tablette est présentée dans un bloc repliable (▼) :

- Cliquer sur l'en-tête pour **replier / déplier** le tableau de la tablette.
- L'en-tête affiche : Tablette · ménages visités · ménages enrôlés · total moustiquaires.

Le tableau de chaque tablette contient :

| Colonne | Description |
|---|---|
| Code Ménage | Identifiant du ménage |
| Taille | Nombre de membres du ménage |
| Date visite | Date de la visite de distribution |
| Cohorte | A ou B |
| Code Moustiquaire | Code-barres de chaque MILDA |
| Type | Type de moustiquaire (PERMANET DUAL / INTERCEPTOR G2) |
| Total | Nombre de MILDA distribuées au ménage |

La ligne de total en rouge en bas de chaque bloc indique le total de MILDA pour la tablette.

### 8.3 Export PDF — Distribution par tablette

Le bouton **"PDF"** en haut de la section génère un fichier PDF de la distribution filtrée. Le PDF inclut la colonne **Taille** pour chaque ménage.

---

## 9. Module Queries — Contrôle qualité des données

Accessible via le lien **"Queries"** dans la bannière, ou l'URL `/queries`.

### 9.1 Vue d'ensemble

Ce module détecte automatiquement les incohérences et données manquantes dans les formulaires REDCap. Il génère une liste de **queries** (signalements) classées par sévérité.

### 9.2 Indicateurs KPI

Quatre cartes affichent les comptages par sévérité :

| Sévérité | Couleur | Signification |
|---|---|---|
| **Critique** | Rouge | Donnée manquante ou erreur bloquante (GPS absent, consentement non renseigné) |
| **Avertissement** | Orange | Incohérence probable nécessitant vérification |
| **Info** | Bleu | Anomalie mineure ou point d'attention |
| **Total** | Gris | Somme de toutes les queries |

### 9.3 Filtres

- **Sévérité** : Critique / Avertissement / Info
- **Formulaire** : Baseline / Membres / Moustiquaires / Adverse Events / Acceptabilité / Suivi
- **Tablette** : filtre par identifiant de tablette
- **Initiales** : filtre par initiales de l'agent de terrain
- **Codes query** : cases à cocher par code pour afficher/masquer des types spécifiques

### 9.4 Tableau des queries

Chaque ligne représente une query avec :

| Champ | Description |
|---|---|
| Code | Identifiant de la règle (ex. Q-GPS-01) |
| Sévérité | Critique / Avertissement / Info |
| Ménage | Code du ménage concerné |
| Formulaire | Formulaire REDCap concerné |
| Tablette | Tablette de collecte |
| Titre | Description courte du problème |
| Détail | Explication et valeur observée |
| Suggestion | Action corrective recommandée |

### 9.5 Liste des codes query

| Code | Sévérité | Description |
|---|---|---|
| Q-VIS-01 | Avertissement | Séquence de visites inattendue |
| Q-ID-01 | Critique | Format de l'identifiant ménage invalide (format attendu : `CODE_VILLAGE-GRAPPE-BRAS-TABLETTE-MENAGE`, ex. `MI-03-PD-01-024` ; codes villages : DJ/GB/MI/DO/AG/AK) |
| Q-ID-02 | Avertissement | Incohérence entre ID ménage et village |
| Q-ID-03 | Avertissement | Incohérence entre ID ménage et grappe |
| Q-ID-04 | Avertissement | Incohérence entre ID ménage et bras |
| Q-ID-05 | Avertissement | Incohérence entre ID ménage et tablette |
| Q-ID-06 | Avertissement | Doublon d'identifiant ménage |
| Q-BASE-01 | Critique | Champs obligatoires manquants au baseline |
| Q-CNS-01 | Avertissement | Statut de consentement non renseigné |
| Q-GPS-01 | Critique | Coordonnées GPS absentes ou partielles |
| Q-GPS-02 | Avertissement | GPS hors zone attendue |
| Q-SLP-01 | Avertissement | Incohérence espaces de couchage / pièces |
| Q-SLP-02 | Info | Nombre de pièces suspect |
| Q-EDU-01 | Info | Logique de saut éducation non respectée |
| Q-MBR-01 | Avertissement | Nombre de membres déclaré incohérent |
| Q-MBR-02 | Info | Écart entre membres formulaire et membres individuels |
| Q-NET-01 | Critique | Code moustiquaire manquant |
| Q-NET-02 | Avertissement | Nombre de MILDA incohérent avec la taille du ménage |
| Q-NET-03 | Avertissement | Code moustiquaire en double |
| Q-NET-04 | Info | Type de MILDA ne correspond pas au bras |
| Q-AE-01 | Avertissement | Symptôme coché mais intensité non renseignée |
| Q-AE-02 | Avertissement | Contradiction dans les données d'événement indésirable |
| Q-AE-03 | Info | Symptôme signalé sans lien avec les MILDA |
| Q-UA-01 | Avertissement | Contradiction dans les données d'acceptabilité |
| Q-UA-02 | Info | Réponse d'utilisation incohérente |
| Q-FW-01 | Avertissement | Moustiquaire absente lors du suivi |
| Q-FW-02 | Avertissement | Contradiction présence / utilisation au suivi |
| Q-FW-03 | Info | Nombre de nuits d'utilisation suspect |

### 9.6 Export PDF des queries

Le bouton **"Exporter PDF"** génère un fichier PDF des queries actuellement filtrées, avec tous les détails et suggestions.

### 9.7 Actualisation des queries

Le bouton **"Actualiser"** recharge les données depuis REDCap et recalcule toutes les queries.

---

## 10. Module Export GPS

Accessible via le lien **"GPS"** dans la bannière, ou l'URL `/gps-export`.

### 10.1 Présentation

Ce module génère des fichiers **GPX** (format standard de navigation GPS) contenant les coordonnées des ménages sélectionnés. Ces fichiers sont destinés à être importés dans **OsmAnd** pour faciliter la navigation terrain lors des visites de suivi.

### 10.2 Informations incluses dans chaque point GPS

Pour chaque ménage exporté, le fichier GPX contient :
- Code ménage (identifiant)
- Nom du chef de ménage
- Cohorte (A ou B)
- Village
- Adresse / repère
- Numéro de tablette

### 10.3 Utilisation

#### Étape 1 — Choisir le mode de regroupement

Quatre modes de regroupement sont disponibles. Le mode choisi détermine comment les fichiers GPX seront organisés :

| Mode | Description | Fichiers générés |
|---|---|---|
| **Par Grappe** *(défaut)* | Un fichier GPX par grappe sélectionnée | `ENDURE-Net_Djigbe_Grappe-1.gpx`, … |
| **Par Village** | Un fichier GPX par village | `ENDURE-Net_Djigbe.gpx`, … |
| **Par Tablette** | Un fichier GPX par binôme (tablette) | `ENDURE-Net_Tablette-01.gpx`, … |
| **Par DAG** | Un fichier GPX par Data Access Group REDCap | `ENDURE-Net_<dag>.gpx` |

> Si une seule unité est sélectionnée, un fichier `.gpx` unique est téléchargé directement. Pour plusieurs unités, une archive `.zip` est générée.

#### Étape 2 — Sélectionner les éléments à exporter

La liste de sélection s'adapte automatiquement au mode de regroupement choisi :

- **Par Grappe** : les grappes sont organisées par village :
  - **Djigbé** : Grappes 1, 2
  - **Gbônou** : Grappes 3, 4, 5
  - **Miniffi** : Grappes 6, 7, 8, 9
  - **Dogbo** : Grappe 10
  - **Agao** : Grappe 11
  - **Akoba** : Grappe 12
- **Par Village** : liste des villages présents dans les données REDCap
- **Par Tablette** : liste des tablettes détectées dans les identifiants ménage
- **Par DAG** : liste des Data Access Groups REDCap

Les boutons **"Tout cocher"** / **"Tout décocher"** permettent une sélection rapide.

#### Étape 3 — Filtrer par cohorte (optionnel)

- **Toutes** : exporte les cohortes A et B
- **Cohorte A** : uniquement les ménages en cohorte A
- **Cohorte B** : uniquement les ménages en cohorte B

#### Étape 4 — Filtrer par consentement

- **Consentis uniquement** *(recommandé)* : n'exporte que les ménages ayant donné leur consentement
- **Tous avec GPS valide** : exporte tous les ménages avec des coordonnées GPS, qu'ils aient consenti ou non

#### Étape 5 — Télécharger

Cliquer sur **"Télécharger GPX"**.

- **Une seule unité** sélectionnée → fichier `.gpx` unique.
- **Plusieurs unités** → archive `.zip` contenant un fichier `.gpx` par unité.

### 10.4 Importer dans OsmAnd

1. Transférer le(s) fichier(s) `.gpx` sur le téléphone (via câble, email, ou cloud).
2. Dans OsmAnd : **Menu → Mes lieux → Traces → Importer**.
3. Sélectionner le fichier `.gpx`.
4. Les ménages apparaissent comme waypoints sur la carte avec leurs informations (nom du chef, cohorte, village, adresse, tablette).

---

## 11. Exports PDF

Plusieurs exports PDF sont disponibles depuis le tableau de bord.

### 11.1 Rapport général

Bouton **"Rapport PDF"** dans l'onglet Baseline :  
Génère un rapport de synthèse avec les statistiques globales par bras, village et cohorte.

### 11.2 Distribution par tablette

Bouton **"PDF"** dans la section Distribution :  
Génère le tableau de distribution de la sélection courante (période + tablettes filtrées), avec :
- Code ménage, Taille, Date visite, Cohorte
- Codes moustiquaires
- Totaux par tablette

### 11.3 Distribution — Résumé

Bouton **"Résumé PDF"** dans la section Distribution :  
Version condensée sans le détail des codes moustiquaires, une ligne par ménage.

### 11.4 Performance

Bouton **"Performance PDF"** dans l'onglet correspondant :  
Graphiques et tableaux de performance par agent de terrain / tablette.

---

## 12. Actualisation du cache

Les données affichées proviennent d'un cache local (durée : 30 minutes) pour optimiser les performances.

Pour forcer le rechargement depuis REDCap :
1. Cliquer sur le bouton **"Actualiser"** dans la bannière.
2. Le cache est vidé et les données sont rechargées immédiatement.
3. Un indicateur visuel confirme la réactualisation.

> **Note :** l'actualisation peut prendre quelques secondes selon le volume de données.

---

---

## 13. Module Inspections physiques

Accessible via le lien **"Inspections"** dans la bannière, ou l'URL `/inspections`.

### 13.1 Présentation

Ce module affiche les données des inspections physiques de moustiquaires collectées via **KoboToolbox** (formulaire dédié). Il permet de suivre l'état des moustiquaires stockées par marque, lot, lieu de stockage et enquêteur.

### 13.2 Cartes KPI

Quatre indicateurs s'affichent en haut de page :

| Carte | Description |
|---|---|
| **Total inspections** | Nombre total d'inspections enregistrées |
| **7 derniers jours** | Inspections effectuées dans la semaine |
| **30 derniers jours** | Inspections effectuées dans le mois |
| **Enquêteurs** | Nombre d'enquêteurs distincts ayant effectué des inspections |

### 13.3 Cartes par marque

Deux cartes colorées résument les inspections par marque de moustiquaire :

- **PMD** (PermaNet Dual) — violet
- **IG2** (Interceptor G2) — bleu

Chaque carte affiche le nombre d'inspections et le pourcentage sur le total. Cliquer sur une carte **filtre automatiquement** le tableau sur cette marque.

### 13.4 Filtres

| Filtre | Description |
|---|---|
| **Recherche** | Recherche libre dans toutes les colonnes |
| **Enquêteur** | Filtre par initiales de l'enquêteur (`initiales`) |
| **Marque** | Filtre par type de moustiquaire (`moustiquaires/itn_brand`) : PMD ou IG2 |
| **Lieu d'entrepôt** | Filtre par lieu de stockage (`lieu_entrepot`) |
| **N° lot** | Filtre par numéro de lot (`moustiquaires/lot_number`) — mis à jour dynamiquement selon la marque sélectionnée |
| **Date de début / fin** | Filtre par date d'inspection |

> Lorsque vous sélectionnez une marque, la liste des lots se met automatiquement à jour pour n'afficher que les lots de cette marque.

### 13.5 Tableau des inspections

Le tableau affiche toutes les inspections avec les colonnes suivantes (entêtes = noms de variables bruts) :

| Variable | Contenu |
|---|---|
| `_id` | Identifiant unique de la soumission KoboToolbox |
| `initiales` | Initiales de l'enquêteur |
| `itn_brand` | Marque de la moustiquaire (PMD / IG2) |
| `date_inspection` | Date de l'inspection |
| `code_moustiquaire` | Code unique de la moustiquaire |
| `type_moustiquaire` | Type de moustiquaire |
| `fabricant` | Fabricant |
| `modele` | Modèle |
| `lot_number` | Numéro de lot |
| `numero_balle` | Numéro de balle |
| `sampling_position` | Position de prélèvement |
| `lieu_entrepot` | Lieu d'entrepôt |
| `remarque` | Remarques de l'enquêteur |
| `presence` | Présence de la moustiquaire |

#### Pagination du tableau

Le tableau est paginé pour optimiser la lisibilité. Un bandeau en bas du tableau permet de :

- **Choisir le nombre de lignes par page** : 25 / 50 / 100 / 200 / Tout afficher
- **Naviguer** entre les pages avec les boutons **←** et **→**
- **Connaître la position exacte** : indicateur *"X–Y sur Z soumissions"* et *"Page N / N"*

> La pagination se réinitialise automatiquement à la page 1 à chaque changement de filtre.

### 13.6 Export CSV

> Cette fonctionnalité est **réservée à l'administrateur** (`francis.houeha@airid-africa.com`).

Le bouton **"Export CSV"** génère un fichier CSV (séparateur `;`, encodage UTF-8 avec BOM) de toutes les inspections, avec :
- Les colonnes identiques au tableau
- Les coordonnées GPS (`latitude`, `longitude`) en colonnes supplémentaires
- Les en-têtes sont les noms de variables bruts

### 13.7 Actualisation du cache

Le bouton **"Actualiser"** vide le cache KoboToolbox (durée : 30 minutes) et recharge les données depuis l'API. L'opération peut prendre quelques secondes.

### 13.8 Fiche d'inspection visuelle — contrôle qualité

Le bouton **"Fiche inspection"** (vert, en haut à droite) ouvre une fenêtre modale affichant le tableau standardisé de contrôle qualité visuel (Section 16.1.3 du protocole ENDURE-Net).

#### Structure du tableau

Le tableau est organisé en deux sections :

| Section | Marque | Rôle dans l'étude |
|---|---|---|
| **Test product** | PermaNet Dual (PMD) | Produit testé |
| **Reference product** | Interceptor G2 (IG2) | Produit de référence |

Chaque ligne correspond à une **combinaison unique** de : lot × balle × position de prélèvement.

#### Colonnes du tableau

| Colonne | Variable source | Description |
|---|---|---|
| **Lot number** | `lot_number` | Numéro de lot de la moustiquaire |
| **Bale number** | `numero_balle` | Numéro de balle |
| **Sampling position** | `sampling_position` | Position de prélèvement dans la balle |
| **Fabric integrity** | `presence_trous`, `nb_taille1–4` | Nombre de moustiquaires intactes / avec trous, ventilé par taille (T1–T4) |
| **Colour** | `decoloration` | Nombre avec bonne intensité (0) / décolorées (1) |
| **Symmetry** | `symmetric` | Comptage de chaque modalité distincte |
| **Tache** | `presence_tache` | Nombre avec (1) / sans (0) tache |
| **Overall damage present?** | `etat_moustiquaire` | Distribution des 5 grades : 1-Excellent · 2-Bon · 3-Moyen · 4-Médiocre · 5-Inacceptable (toujours affiché, les grades à 0 sont grisés) |

Le **pied de tableau** indique le nombre total de moustiquaires inspectées par bras (n PMD / n IG2).

#### Code couleur des cellules

- **Vert** : valeur satisfaisante (intacte, bonne couleur, sans tache)
- **Rouge** : défaut détecté (présence de trous)
- **Orange** : anomalie modérée (décoloration, tache)
- **Grisé (opacité réduite)** : modalité absente dans le groupe (compte = 0)

#### Impression / Export PDF de la fiche

Le bouton **"Imprimer / Exporter PDF"** dans l'en-tête de la fenêtre ouvre une nouvelle fenêtre avec le tableau rempli formaté pour l'impression en **A4 paysage**, puis déclenche automatiquement le dialogue d'impression du navigateur. Depuis ce dialogue, sélectionner **"Enregistrer en PDF"** pour obtenir le fichier.

---

### 13.9 Graphiques comparatifs PMD vs IG2

La section **"Analyse comparative — PMD vs IG2"**, placée entre les cartes de marque et les filtres, présente 6 graphiques côte à côte résumant les résultats des inspections par bras.

#### Graphiques disponibles

| # | Titre | Type | Ce qu'il montre |
|---|---|---|---|
| 1 | **Intégrité du tissu** | Barres groupées | Nombre de moustiquaires intactes vs avec trous |
| 2 | **Trous par taille** | Barres groupées | Nombre cumulé de trous par catégorie (T1 / T2 / T3 / T4) |
| 3 | **Couleur** | Barres groupées | Nombre avec bonne intensité vs décolorées |
| 4 | **Symétrie** | Barres groupées | Comptage de chaque modalité de symétrie |
| 5 | **Présence de tache** | Barres groupées | Nombre sans tache vs avec tache |
| 6 | **État général (1–5)** | Barres empilées horizontales 100 % | Distribution des grades 1 à 5 par bras, normalisée en pourcentage |

- **PMD (PermaNet Dual)** = violet · **IG2 (Interceptor G2)** = bleu ciel
- Le **survol d'une barre** affiche le compte brut et le pourcentage correspondant
- Le graphique "État général" est **normalisé à 100 %** : les deux barres occupent toute la largeur, permettant de comparer la distribution de sévérité quel que soit le nombre total de moustiquaires

#### Interprétation automatique

Sous chaque graphique, une courte interprétation en français est générée automatiquement à partir des données réelles. Elle indique :
- Les pourcentages clés pour chaque bras
- Le bras le plus affecté (si différence significative)
- Un constat neutre en l'absence d'anomalies

#### Réduire / Développer

Le bouton **"Réduire / Développer"** (en haut à droite de la section) permet de masquer les graphiques pour libérer de l'espace à l'écran. La section est masquée à l'impression de la page.

---

### 13.10 Impression des graphiques comparatifs

Le bouton **"Imprimer les graphiques"** (vert, en haut à droite de la section graphiques) génère une page d'impression dédiée contenant :

- **En-tête** : titre de l'analyse + date de génération automatique
- **Grille 3 × 2** : les 6 graphiques au format image (PNG haute résolution)
- **Interprétation** sous chaque graphique, dans un encadré gris avec bordure teal

Le format de sortie est **A4 paysage**. La fenêtre d'impression s'ouvre automatiquement — sélectionner **"Enregistrer en PDF"** pour exporter.

---

## 14. Rapport d'exécution par bras

Accessible via le lien **"Rapport bras"** dans la bannière, ou l'URL `/rapport/bras`.

### 14.1 Présentation

Ce rapport présente le taux d'exécution du protocole d'enrôlement et de distribution de moustiquaires, organisé par **bras de traitement** et **cohorte**. Il est conçu pour être partagé en réunion ou exporté en PDF.

**Cibles protocole :**
- **921 ménages par bras** (611 en Cohorte A + 310 en Cohorte B)
- **3 moustiquaires par ménage** enrôlé en moyenne
- Total : 1 842 ménages · 5 526 moustiquaires

### 14.2 Indicateurs globaux

Quatre cartes affichent les chiffres agrégés tous bras confondus :

| Carte | Description |
|---|---|
| **Ménages enrôlés** | Total enrôlé vs cible 1 842, avec taux et barre de progression |
| **Moustiquaires distribuées** | Total distribué vs cible 5 526, avec taux et barre de progression |
| **Ménages restants** | Nombre de ménages encore à enrôler pour atteindre la cible |
| **Filets attendus** | Nombre de moustiquaires supplémentaires attendues |

### 14.3 Cartes par bras

Deux cartes colorées, une par bras :

- **Bras 1 — PERMANET DUAL** (violet)
- **Bras 2 — INTERCEPTOR G2** (bleu)

Chaque carte affiche :
- Le nombre total de ménages enrôlés et les barres de progression (ménages + moustiquaires)
- Le détail par **Cohorte A** et **Cohorte B** avec :
  - Nombre de ménages enrôlés / cible
  - Nombre de moustiquaires distribuées / cible
  - Nombre de ménages et filets restants
  - Taux d'exécution et moyenne filets/ménage

### 14.4 Tableau récapitulatif

Un tableau complet présente toutes les valeurs en une vue :

| Colonne | Description |
|---|---|
| Bras | PERMANET DUAL ou INTERCEPTOR G2 |
| Cohorte | A ou B |
| Cible ménages | Nombre de ménages attendus |
| Enrôlés | Nombre de ménages enrôlés |
| Taux | Taux d'exécution (code couleur) |
| Restants | Ménages à compléter |
| Cible filets | Nombre de moustiquaires attendues |
| Distribués | Nombre de moustiquaires distribuées |
| Taux | Taux d'exécution moustiquaires |
| Manquants | Moustiquaires supplémentaires à distribuer |

Des **lignes de sous-total** par bras et une **ligne TOTAL** (fond sombre) récapitulent les chiffres.

### 14.5 Code couleur des taux

| Couleur | Seuil | Signification |
|---|---|---|
| Vert | ≥ 90% | Objectif quasi atteint |
| Orange | ≥ 70% | En cours — vigilance |
| Rouge | < 70% | Retard significatif |

### 14.6 Téléchargement PDF

Le bouton **"Télécharger PDF"** dans la bannière génère directement un fichier PDF téléchargeable (`rapport-bras-AAAA-MM-JJ.pdf`) mis en page au format A4 portrait, sans passer par le dialogue d'impression du navigateur.

---

---

## 15. Gestion des erreurs et connectivité

Le dashboard dispose de pages d'erreur personnalisées qui s'affichent automatiquement en cas de problème serveur ou réseau.

### 15.1 Pages d'erreur

| Code | Page affichée | Cause typique |
|---|---|---|
| **500** | Erreur serveur / Base de données inaccessible | Serveur MySQL injoignable, panne serveur |
| **503** | Service momentanément indisponible | Maintenance programmée, surcharge temporaire |
| **404** | Page introuvable | URL incorrecte, page supprimée |
| **419** | Session expirée | Inactivité prolongée, jeton CSRF expiré |

Chaque page inclut :
- Un **icône animé** représentant le type d'erreur
- Un **message clair en français** avec la cause probable
- Des **boutons d'action** adaptés : Réessayer, Retour à l'accueil, Se reconnecter
- Un **lien de contact** vers le support (`support@airid-africa.com`)

### 15.2 Page erreur 500 — spécificités

Lorsque la base de données MySQL est inaccessible (erreur `SQLSTATE`), la page 500 :
- Détecte automatiquement qu'il s'agit d'une **erreur de base de données** et adapte le message
- Affiche un **compte à rebours de 30 secondes** avant rechargement automatique
- Montre deux chips de statut : **MySQL** (état de la connexion DB) et **Réseau** (état de la connexion Internet)
- En mode debug (`APP_DEBUG=true`) : un accordéon **"Détails techniques"** affiche le type d'exception et le message brut

### 15.3 Détection de la connectivité Internet

Sur toutes les pages d'erreur, une **bannière orange** apparaît automatiquement en haut de l'écran si la connexion Internet est perdue (`navigator.onLine = false`). Elle disparaît dès que la connexion est rétablie.

> Cette détection est en temps réel : si votre connexion tombe pendant la navigation, la bannière s'affiche sans rechargement de page.

---

*Documentation générée pour AIRID — ENDURE-Net · Juin 2026 · v1.3*
