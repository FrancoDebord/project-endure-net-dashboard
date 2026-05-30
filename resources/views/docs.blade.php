<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation — ENDURE-Net</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root { --red: #C41230; --red-dark: #8B0D21; }
        body { background: #F1F5F9; }

        /* Prose styles */
        .prose h2 { font-size: 1.2rem; font-weight: 800; color: #111; margin-top: 2.5rem; margin-bottom: .75rem; padding-left: .75rem; border-left: 4px solid #C41230; }
        .prose h3 { font-size: 1rem; font-weight: 700; color: #1A1A1A; margin-top: 1.75rem; margin-bottom: .5rem; }
        .prose h4 { font-size: .9rem; font-weight: 600; color: #374151; margin-top: 1.25rem; margin-bottom: .4rem; }
        .prose p  { color: #374151; line-height: 1.75; margin-bottom: .75rem; font-size: .925rem; }
        .prose ul { list-style: disc; padding-left: 1.5rem; margin-bottom: .75rem; }
        .prose li { color: #374151; font-size: .925rem; line-height: 1.7; margin-bottom: .2rem; }
        .prose strong { color: #111; }
        .prose code { background: #F1F5F9; border: 1px solid #E2E8F0; border-radius: 4px; padding: 1px 5px; font-size: .82rem; font-family: monospace; color: #C41230; }
        .prose blockquote { border-left: 3px solid #E2E8F0; padding-left: 1rem; color: #6B7280; font-style: italic; margin: 1rem 0; background:#F8FAFC; border-radius:0 6px 6px 0; padding: .75rem 1rem; }

        /* Tables */
        .prose table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; font-size: .875rem; }
        .prose thead th { background: #1A1A1A; color: #fff; font-weight: 600; padding: 8px 12px; text-align: left; font-size: .8rem; letter-spacing: .03em; }
        .prose tbody td { padding: 7px 12px; border-bottom: 1px solid #E2E8F0; color: #374151; vertical-align: top; }
        .prose tbody tr:nth-child(even) td { background: #F8FAFC; }

        /* Sidebar nav */
        .nav-link { display: block; padding: 5px 10px; border-radius: 6px; font-size: .8rem; color: #4B5563; transition: background .15s, color .15s; cursor: pointer; }
        .nav-link:hover { background: #FEE2E2; color: #C41230; }
        .nav-link.active { background: #FEE2E2; color: #C41230; font-weight: 600; }
        .nav-section { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #9CA3AF; padding: 12px 10px 4px; }
    </style>
</head>
<body>

{{-- Top bar --}}
<header class="sticky top-0 z-50 bg-white border-b border-gray-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-6 h-14 flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <div style="background:linear-gradient(135deg,#8B0D21,#C41230)" class="rounded-xl p-2">
                <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
            </div>
            <div>
                <span class="font-extrabold text-gray-900 tracking-wide text-sm">ENDURE-Net</span>
                <span class="ml-2 text-xs text-gray-400">Documentation utilisateur</span>
            </div>
        </div>
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Tableau de bord
        </a>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 py-8 flex gap-8">

    {{-- Sidebar --}}
    <aside class="hidden lg:block w-56 flex-shrink-0">
        <div class="sticky top-20 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100">
                <p class="text-xs font-bold text-gray-700 uppercase tracking-wider">Sommaire</p>
            </div>
            <nav class="p-2 space-y-0.5">
                <div class="nav-section">Général</div>
                <a class="nav-link" href="#overview">Vue d'ensemble</a>
                <a class="nav-link" href="#auth">Connexion</a>
                <a class="nav-link" href="#nav">Navigation</a>
                <div class="nav-section">Tableau de bord</div>
                <a class="nav-link" href="#baseline">Baseline & Distribution</a>
                <a class="nav-link" href="#ua">Acceptabilité</a>
                <a class="nav-link" href="#ae">Événements indésirables</a>
                <a class="nav-link" href="#followup">Suivi</a>
                <a class="nav-link" href="#distribution">Distribution par binôme</a>
                <div class="nav-section">Modules</div>
                <a class="nav-link" href="#queries">Queries (qualité)</a>
                <a class="nav-link" href="#gps">Export GPS</a>
                <a class="nav-link" href="#pdf">Exports PDF</a>
                <a class="nav-link" href="#cache">Cache</a>
                <a class="nav-link" href="#inspections">Inspections physiques</a>
                <a class="nav-link" href="#rapport-bras">Rapport par bras</a>
            </nav>
        </div>
    </aside>

    {{-- Main content --}}
    <main class="flex-1 min-w-0">
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-8 prose">

            {{-- Hero --}}
            <div class="mb-8 p-5 rounded-xl" style="background:linear-gradient(135deg,#8B0D21,#C41230)">
                <h1 class="text-white font-extrabold text-xl mb-1 mt-0" style="border:none;padding:0;margin-top:0">Documentation utilisateur — ENDURE-Net Dashboard</h1>
                <p class="text-red-100 text-sm mb-0">Version 1.1 · Mai 2026 · Projet AIRID / ENDURE-Net · Données REDCap &amp; KoboToolbox</p>
            </div>

            {{-- 1. Vue d'ensemble --}}
            <h2 id="overview">1. Vue d'ensemble</h2>
            <p>Le tableau de bord ENDURE-Net est une application web de suivi des données de terrain pour l'étude AIRID. Il agrège en temps réel les données collectées via REDCap (projet 46) et fournit :</p>
            <ul>
                <li><strong>Statistiques de terrain</strong> : visites, consentements, cohortes A/B, moustiquaires distribuées</li>
                <li><strong>Contrôle qualité</strong> : détection automatique d'incohérences et de données manquantes</li>
                <li><strong>Export GPS</strong> : génération de fichiers GPX pour la navigation terrain (OsmAnd)</li>
                <li><strong>Rapports PDF</strong> : impression de tableaux de distribution, rapports de performance</li>
                <li><strong>Inspections physiques</strong> : suivi des inspections de moustiquaires via KoboToolbox</li>
                <li><strong>Rapport bras</strong> : taux d'exécution par bras de traitement (PERMANET DUAL / INTERCEPTOR G2) et par cohorte</li>
            </ul>

            <h3>Zones géographiques et bras de l'étude</h3>
            <table>
                <thead><tr><th>Village</th><th>Grappes</th><th>Bras</th><th>Type de MILDA</th></tr></thead>
                <tbody>
                    <tr><td>Djigbé</td><td>1, 2</td><td>1 (1) / 2 (2)</td><td>PERMANET DUAL / INTERCEPTOR G2</td></tr>
                    <tr><td>Gbônou</td><td>3, 4, 5</td><td>2 (3,4) / 1 (5)</td><td>INTERCEPTOR G2 / PERMANET DUAL</td></tr>
                    <tr><td>Miniffi</td><td>6, 7, 8</td><td>2 (6) / 1 (7,8)</td><td>INTERCEPTOR G2 / PERMANET DUAL</td></tr>
                </tbody>
            </table>
            <ul>
                <li><strong>Bras 1 — PERMANET DUAL</strong> : Grappes 1, 5, 7, 8</li>
                <li><strong>Bras 2 — INTERCEPTOR G2</strong> : Grappes 2, 3, 4, 6</li>
            </ul>

            {{-- 2. Authentification --}}
            <h2 id="auth">2. Connexion et authentification</h2>

            <h3>Connexion par email et mot de passe</h3>
            <ol style="padding-left:1.5rem;color:#374151;font-size:.925rem;line-height:1.7">
                <li style="margin-bottom:.3rem">Accéder à l'URL de l'application.</li>
                <li style="margin-bottom:.3rem">Renseigner votre adresse e-mail institutionnelle et votre mot de passe.</li>
                <li style="margin-bottom:.3rem">Cocher <strong>"Se souvenir de moi"</strong> pour rester connecté entre les sessions.</li>
                <li>Cliquer sur <strong>"Se connecter"</strong>.</li>
            </ol>
            <blockquote>En cas d'erreur, un message s'affiche sous le champ e-mail. Vérifiez que votre compte est actif auprès de l'administrateur.</blockquote>

            <h3>Connexion via Microsoft 365</h3>
            <p>Cliquer sur le bouton <strong>"Se connecter avec Microsoft 365"</strong> sous le formulaire. Vous serez redirigé vers la page de connexion Microsoft de votre organisation.</p>
            <ul>
                <li>Seuls les comptes dont l'adresse e-mail correspond à un compte <strong>actif</strong> dans la base AIRID sont autorisés.</li>
                <li>Si votre compte n'est pas reconnu, contactez l'administrateur pour l'activation.</li>
            </ul>

            <h3>Mot de passe oublié</h3>
            <ol style="padding-left:1.5rem;color:#374151;font-size:.925rem;line-height:1.7">
                <li style="margin-bottom:.3rem">Cliquer sur <strong>"Mot de passe oublié ?"</strong> sous le formulaire.</li>
                <li style="margin-bottom:.3rem">Saisir votre adresse e-mail institutionnelle.</li>
                <li style="margin-bottom:.3rem">Un lien de réinitialisation vous sera envoyé par e-mail.</li>
                <li>Cliquer sur le lien reçu et définir un nouveau mot de passe (minimum 8 caractères).</li>
            </ol>

            <h3>Déconnexion</h3>
            <p>Cliquer sur le bouton <strong>"Déconnexion"</strong> dans le coin supérieur droit de la bannière.</p>

            {{-- 3. Navigation --}}
            <h2 id="nav">3. Interface principale — Navigation</h2>

            <h3>Bannière supérieure</h3>
            <p>La bannière affiche :</p>
            <ul>
                <li>Le logo et le nom de l'application <strong>ENDURE-Net</strong></li>
                <li>Un lien <strong>"Queries"</strong> vers le module de contrôle qualité</li>
                <li>Un lien <strong>"GPS"</strong> vers le module d'export GPS</li>
                <li>Un lien <strong>"Rapport bras"</strong> vers le rapport d'exécution par bras</li>
                <li>Un lien <strong>"Inspections"</strong> vers le module d'inspections physiques</li>
                <li>Un lien <strong>"Docs"</strong> vers cette documentation</li>
                <li>Un bouton <strong>"Actualiser"</strong> pour vider le cache et recharger les données REDCap</li>
                <li>Le <strong>nom de l'utilisateur connecté</strong> (initiales + prénom nom)</li>
                <li>Le bouton <strong>"Déconnexion"</strong></li>
            </ul>

            <h3>Onglets du tableau de bord</h3>
            <table>
                <thead><tr><th>Onglet</th><th>Contenu</th></tr></thead>
                <tbody>
                    <tr><td><strong>Baseline & Distribution</strong></td><td>Statistiques de visites, consentements, cohortes, moustiquaires</td></tr>
                    <tr><td><strong>Acceptabilité</strong></td><td>Données d'acceptabilité utilisateur des MILDA</td></tr>
                    <tr><td><strong>Événements indésirables</strong></td><td>Suivi des effets indésirables signalés</td></tr>
                    <tr><td><strong>Suivi</strong></td><td>Données des visites de suivi</td></tr>
                    <tr><td><strong>Distribution</strong> (bas de page)</td><td>Tableau détaillé par binôme/tablette</td></tr>
                </tbody>
            </table>

            <h3>Filtres globaux</h3>
            <p>Une barre de filtres en haut s'applique à l'ensemble des onglets :</p>
            <ul>
                <li><strong>Village</strong> : Djigbé / Gbônou / Miniffi / Tous</li>
                <li><strong>Grappe</strong> : 1 à 8</li>
                <li><strong>Bras</strong> : Bras 1 (PERMANET DUAL) / Bras 2 (INTERCEPTOR G2) / Tous</li>
            </ul>

            {{-- 4. Baseline --}}
            <h2 id="baseline">4. Onglet Baseline & Distribution</h2>

            <h3>Cartes KPI</h3>
            <table>
                <thead><tr><th>Carte</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Ménages visités</strong></td><td>Nombre total de ménages ayant reçu au moins une visite</td></tr>
                    <tr><td><strong>Consentements</strong></td><td>Nombre et pourcentage de ménages ayant donné leur consentement</td></tr>
                    <tr><td><strong>Cohorte A</strong></td><td>Ménages enrôlés en cohorte A</td></tr>
                    <tr><td><strong>Cohorte B</strong></td><td>Ménages enrôlés en cohorte B</td></tr>
                    <tr><td><strong>Moustiquaires</strong></td><td>Total de MILDA distribuées</td></tr>
                </tbody>
            </table>

            <h3>Tableau Cohortes par bras</h3>
            <p>Récapitule la répartition des ménages par bras et par cohorte. Pour chaque bras, affiche le nombre de ménages <strong>consentis</strong> (en gras) sur le total visité. Les lignes détaillent la répartition par cohorte A et B.</p>

            <h3>Tableau de détail des ménages</h3>
            <table>
                <thead><tr><th>Colonne</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td>Code Ménage</td><td>Identifiant unique du ménage</td></tr>
                    <tr><td>Village / Grappe</td><td>Localisation géographique</td></tr>
                    <tr><td>Bras</td><td>Bras de l'étude</td></tr>
                    <tr><td>Tablette</td><td>Identifiant de la tablette de collecte</td></tr>
                    <tr><td>Cohorte</td><td>A ou B</td></tr>
                    <tr><td>Consentement</td><td>Statut : Accepté / Non accepté</td></tr>
                    <tr><td>Taille</td><td>Nombre de membres du ménage</td></tr>
                    <tr><td>Moustiquaires</td><td>Nombre de MILDA distribuées</td></tr>
                </tbody>
            </table>

            <h4>Filtres du tableau de détail</h4>
            <p>Recherche libre, Village, Grappe, Bras, Cohorte, Consentement, Tablette — ces filtres s'appliquent indépendamment et se cumulent.</p>

            <h3>Carte interactive</h3>
            <p>Affiche les ménages géolocalisés sous forme de points colorés (<strong>rouge</strong> = Bras 1, <strong>bleu</strong> = Bras 2). Cliquer sur un point pour afficher les informations du ménage. Deux fonds de carte disponibles : OpenStreetMap et Satellite.</p>

            {{-- 5. UA --}}
            <h2 id="ua">5. Onglet Acceptabilité</h2>
            <p>Présente les données du questionnaire d'acceptabilité utilisateur (UA) des MILDA : taux d'utilisation, raisons de non-utilisation, satisfaction par type de moustiquaire. Les données sont calculées uniquement pour les ménages ayant retourné le formulaire UA.</p>

            {{-- 6. AE --}}
            <h2 id="ae">6. Onglet Événements indésirables</h2>
            <p>Regroupe les données du formulaire Adverse Events (AE) : liste des symptômes signalés après utilisation des MILDA, répartition par type de symptôme et par bras, taux de signalement par rapport aux ménages exposés.</p>

            {{-- 7. Suivi --}}
            <h2 id="followup">7. Onglet Suivi</h2>
            <p>Données issues des visites de suivi post-distribution : présence des moustiquaires au domicile, déclaration d'utilisation lors de la dernière nuit, nombre de nuits d'utilisation, comparaison Bras 1 / Bras 2.</p>

            {{-- 8. Distribution --}}
            <h2 id="distribution">8. Section Distribution des moustiquaires par binôme</h2>

            <h3>Filtres</h3>
            <ul>
                <li><strong>Date de début / Date de fin</strong> : période de distribution</li>
                <li><strong>Tablettes</strong> : sélection des binômes à afficher (cases à cocher)</li>
            </ul>
            <p>Cliquer sur <strong>"Appliquer"</strong> pour filtrer.</p>

            <h3>Tableaux repliables par tablette</h3>
            <p>Chaque tablette est présentée dans un bloc repliable (▼). Cliquer sur l'en-tête pour <strong>replier / déplier</strong>. L'en-tête affiche le nombre de ménages visités, enrôlés, et le total moustiquaires.</p>
            <table>
                <thead><tr><th>Colonne</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td>Code Ménage</td><td>Identifiant du ménage</td></tr>
                    <tr><td>Taille</td><td>Nombre de membres du ménage</td></tr>
                    <tr><td>Date visite</td><td>Date de la visite de distribution</td></tr>
                    <tr><td>Cohorte</td><td>A ou B</td></tr>
                    <tr><td>Code Moustiquaire</td><td>Code-barres de chaque MILDA</td></tr>
                    <tr><td>Type</td><td>PERMANET DUAL / INTERCEPTOR G2</td></tr>
                    <tr><td>Total</td><td>Nombre de MILDA distribuées au ménage</td></tr>
                </tbody>
            </table>
            <p>La ligne de total en rouge indique le total de MILDA pour la tablette.</p>

            {{-- 9. Queries --}}
            <h2 id="queries">9. Module Queries — Contrôle qualité</h2>
            <p>Accessible via le lien <strong>"Queries"</strong> dans la bannière ou l'URL <code>/queries</code>. Ce module détecte automatiquement les incohérences et données manquantes dans les formulaires REDCap.</p>

            <h3>Sévérités</h3>
            <table>
                <thead><tr><th>Sévérité</th><th>Couleur</th><th>Signification</th></tr></thead>
                <tbody>
                    <tr><td><strong>Critique</strong></td><td>Rouge</td><td>Donnée manquante ou erreur bloquante (GPS absent, consentement non renseigné)</td></tr>
                    <tr><td><strong>Avertissement</strong></td><td>Orange</td><td>Incohérence probable nécessitant vérification</td></tr>
                    <tr><td><strong>Info</strong></td><td>Bleu</td><td>Anomalie mineure ou point d'attention</td></tr>
                </tbody>
            </table>

            <h3>Filtres disponibles</h3>
            <p>Sévérité, Formulaire, Tablette, Initiales agent, Codes query (cases à cocher).</p>

            <h3>Codes query</h3>
            <table>
                <thead><tr><th>Code</th><th>Sévérité</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><code>Q-VIS-01</code></td><td>Avertissement</td><td>Séquence de visites inattendue</td></tr>
                    <tr><td><code>Q-ID-01</code></td><td>Critique</td><td>Format de l'identifiant ménage invalide</td></tr>
                    <tr><td><code>Q-ID-02</code></td><td>Avertissement</td><td>Incohérence entre ID ménage et village</td></tr>
                    <tr><td><code>Q-ID-03</code></td><td>Avertissement</td><td>Incohérence entre ID ménage et grappe</td></tr>
                    <tr><td><code>Q-ID-04</code></td><td>Avertissement</td><td>Incohérence entre ID ménage et bras</td></tr>
                    <tr><td><code>Q-ID-05</code></td><td>Avertissement</td><td>Incohérence entre ID ménage et tablette</td></tr>
                    <tr><td><code>Q-ID-06</code></td><td>Avertissement</td><td>Doublon d'identifiant ménage</td></tr>
                    <tr><td><code>Q-BASE-01</code></td><td>Critique</td><td>Champs obligatoires manquants au baseline</td></tr>
                    <tr><td><code>Q-CNS-01</code></td><td>Avertissement</td><td>Statut de consentement non renseigné</td></tr>
                    <tr><td><code>Q-GPS-01</code></td><td>Critique</td><td>Coordonnées GPS absentes ou partielles</td></tr>
                    <tr><td><code>Q-GPS-02</code></td><td>Avertissement</td><td>GPS hors zone attendue</td></tr>
                    <tr><td><code>Q-SLP-01</code></td><td>Avertissement</td><td>Incohérence espaces de couchage / pièces</td></tr>
                    <tr><td><code>Q-SLP-02</code></td><td>Info</td><td>Nombre de pièces suspect</td></tr>
                    <tr><td><code>Q-EDU-01</code></td><td>Info</td><td>Logique de saut éducation non respectée</td></tr>
                    <tr><td><code>Q-MBR-01</code></td><td>Avertissement</td><td>Nombre de membres déclaré incohérent</td></tr>
                    <tr><td><code>Q-MBR-02</code></td><td>Info</td><td>Écart entre membres formulaire et membres individuels</td></tr>
                    <tr><td><code>Q-NET-01</code></td><td>Critique</td><td>Code moustiquaire manquant</td></tr>
                    <tr><td><code>Q-NET-02</code></td><td>Avertissement</td><td>Nombre de MILDA incohérent avec la taille du ménage</td></tr>
                    <tr><td><code>Q-NET-03</code></td><td>Avertissement</td><td>Code moustiquaire en double</td></tr>
                    <tr><td><code>Q-NET-04</code></td><td>Info</td><td>Type de MILDA ne correspond pas au bras</td></tr>
                    <tr><td><code>Q-AE-01</code></td><td>Avertissement</td><td>Symptôme coché mais intensité non renseignée</td></tr>
                    <tr><td><code>Q-AE-02</code></td><td>Avertissement</td><td>Contradiction dans les données d'événement indésirable</td></tr>
                    <tr><td><code>Q-AE-03</code></td><td>Info</td><td>Symptôme signalé sans lien avec les MILDA</td></tr>
                    <tr><td><code>Q-UA-01</code></td><td>Avertissement</td><td>Contradiction dans les données d'acceptabilité</td></tr>
                    <tr><td><code>Q-UA-02</code></td><td>Info</td><td>Réponse d'utilisation incohérente</td></tr>
                    <tr><td><code>Q-FW-01</code></td><td>Avertissement</td><td>Moustiquaire absente lors du suivi</td></tr>
                    <tr><td><code>Q-FW-02</code></td><td>Avertissement</td><td>Contradiction présence / utilisation au suivi</td></tr>
                    <tr><td><code>Q-FW-03</code></td><td>Info</td><td>Nombre de nuits d'utilisation suspect</td></tr>
                </tbody>
            </table>

            <h3>Export PDF des queries</h3>
            <p>Le bouton <strong>"Exporter PDF"</strong> génère un fichier PDF des queries actuellement filtrées, avec tous les détails et suggestions correctives.</p>

            {{-- 10. GPS --}}
            <h2 id="gps">10. Module Export GPS</h2>
            <p>Accessible via le lien <strong>"GPS"</strong> dans la bannière ou <code>/gps-export</code>. Génère des fichiers <strong>GPX</strong> pour importation dans <strong>OsmAnd</strong> pour la navigation terrain lors des visites de suivi.</p>

            <h3>Informations exportées par point GPS</h3>
            <ul>
                <li>Code ménage (identifiant)</li>
                <li>Nom du chef de ménage</li>
                <li>Cohorte (A ou B)</li>
                <li>Village</li>
                <li>Adresse / repère</li>
            </ul>

            <h3>Procédure d'export</h3>
            <ol style="padding-left:1.5rem;color:#374151;font-size:.925rem;line-height:1.7">
                <li style="margin-bottom:.5rem"><strong>Sélectionner les grappes</strong> : cocher les grappes souhaitées (Djigbé : 1–2 / Gbônou : 3–5 / Miniffi : 6–8). Utiliser "Tout cocher / Tout décocher" pour une sélection rapide.</li>
                <li style="margin-bottom:.5rem"><strong>Filtrer par cohorte</strong> (optionnel) : Toutes / Cohorte A / Cohorte B.</li>
                <li style="margin-bottom:.5rem"><strong>Filtrer par consentement</strong> : "Consentis uniquement" (recommandé) ou "Tous avec GPS valide".</li>
                <li><strong>Cliquer sur "Télécharger GPX"</strong> : une grappe → fichier <code>.gpx</code> unique ; plusieurs grappes → archive <code>.zip</code> avec un fichier par grappe.</li>
            </ol>

            <h3>Importer dans OsmAnd</h3>
            <ol style="padding-left:1.5rem;color:#374151;font-size:.925rem;line-height:1.7">
                <li style="margin-bottom:.3rem">Transférer le(s) fichier(s) <code>.gpx</code> sur le téléphone (câble, e-mail ou cloud).</li>
                <li style="margin-bottom:.3rem">Dans OsmAnd : <strong>Menu → Mes lieux → Traces → Importer</strong>.</li>
                <li style="margin-bottom:.3rem">Sélectionner le fichier <code>.gpx</code>.</li>
                <li>Les ménages apparaissent comme waypoints sur la carte avec leurs informations.</li>
            </ol>

            {{-- 11. PDF --}}
            <h2 id="pdf">11. Exports PDF</h2>
            <table>
                <thead><tr><th>Export</th><th>Déclencheur</th><th>Contenu</th></tr></thead>
                <tbody>
                    <tr><td><strong>Rapport général</strong></td><td>Bouton "Rapport PDF" — onglet Baseline</td><td>Statistiques globales par bras, village et cohorte</td></tr>
                    <tr><td><strong>Distribution par tablette</strong></td><td>Bouton "PDF" — section Distribution</td><td>Code ménage, Taille, Date visite, Cohorte, Codes moustiquaires, Totaux</td></tr>
                    <tr><td><strong>Distribution résumé</strong></td><td>Bouton "Résumé PDF" — section Distribution</td><td>Version condensée sans le détail des codes moustiquaires</td></tr>
                    <tr><td><strong>Performance</strong></td><td>Bouton "Performance PDF" — onglet Performance</td><td>Graphiques et tableaux de performance par agent / tablette</td></tr>
                    <tr><td><strong>Queries</strong></td><td>Bouton "Exporter PDF" — page Queries</td><td>Queries filtrées avec détails et suggestions</td></tr>
                </tbody>
            </table>

            {{-- 12. Cache --}}
            <h2 id="cache">12. Actualisation du cache</h2>
            <p>Les données affichées proviennent d'un cache local (durée : 30 minutes) pour optimiser les performances. Pour forcer le rechargement depuis REDCap :</p>
            <ol style="padding-left:1.5rem;color:#374151;font-size:.925rem;line-height:1.7">
                <li style="margin-bottom:.3rem">Cliquer sur le bouton <strong>"Actualiser"</strong> dans la bannière.</li>
                <li style="margin-bottom:.3rem">Le cache est vidé et les données sont rechargées immédiatement.</li>
                <li>Un indicateur visuel confirme la réactualisation.</li>
            </ol>
            <blockquote>L'actualisation peut prendre quelques secondes selon le volume de données REDCap.</blockquote>

            {{-- 13. Inspections physiques --}}
            <h2 id="inspections">13. Module Inspections physiques</h2>
            <p>Accessible via le lien <strong>"Inspections"</strong> dans la bannière ou <code>/inspections</code>. Ce module affiche les données des inspections physiques de moustiquaires collectées via <strong>KoboToolbox</strong>. Il permet de suivre l'état des moustiquaires stockées par marque, lot, lieu et enquêteur.</p>

            <h3>Cartes KPI</h3>
            <table>
                <thead><tr><th>Carte</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Total inspections</strong></td><td>Nombre total d'inspections enregistrées</td></tr>
                    <tr><td><strong>7 derniers jours</strong></td><td>Inspections effectuées dans la semaine</td></tr>
                    <tr><td><strong>30 derniers jours</strong></td><td>Inspections effectuées dans le mois</td></tr>
                    <tr><td><strong>Enquêteurs</strong></td><td>Nombre d'enquêteurs distincts ayant effectué des inspections</td></tr>
                </tbody>
            </table>

            <h3>Cartes par marque</h3>
            <p>Deux cartes résument les inspections par marque. Cliquer sur une carte <strong>filtre automatiquement</strong> le tableau sur cette marque :</p>
            <ul>
                <li><strong>PMD</strong> — PermaNet Dual (violet)</li>
                <li><strong>IG2</strong> — Interceptor G2 (bleu)</li>
            </ul>

            <h3>Filtres disponibles</h3>
            <table>
                <thead><tr><th>Filtre</th><th>Variable</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td>Recherche</td><td>—</td><td>Recherche libre dans toutes les colonnes</td></tr>
                    <tr><td>Enquêteur</td><td><code>initiales</code></td><td>Filtre par initiales de l'enquêteur</td></tr>
                    <tr><td>Marque</td><td><code>moustiquaires/itn_brand</code></td><td>PMD ou IG2</td></tr>
                    <tr><td>Lieu d'entrepôt</td><td><code>lieu_entrepot</code></td><td>Filtre par lieu de stockage</td></tr>
                    <tr><td>N° lot</td><td><code>moustiquaires/lot_number</code></td><td>Mis à jour dynamiquement selon la marque</td></tr>
                    <tr><td>Date début / fin</td><td><code>date_inspection</code></td><td>Filtre par période d'inspection</td></tr>
                </tbody>
            </table>
            <blockquote>Lorsque vous sélectionnez une marque, la liste des lots se met automatiquement à jour pour n'afficher que les lots de cette marque.</blockquote>

            <h3>Colonnes du tableau</h3>
            <p>Les en-têtes affichent les <strong>noms de variables bruts</strong> du formulaire KoboToolbox :</p>
            <table>
                <thead><tr><th>Variable</th><th>Contenu</th></tr></thead>
                <tbody>
                    <tr><td><code>_id</code></td><td>Identifiant unique de la soumission KoboToolbox</td></tr>
                    <tr><td><code>initiales</code></td><td>Initiales de l'enquêteur</td></tr>
                    <tr><td><code>itn_brand</code></td><td>Marque de la moustiquaire (PMD / IG2)</td></tr>
                    <tr><td><code>date_inspection</code></td><td>Date de l'inspection</td></tr>
                    <tr><td><code>code_moustiquaire</code></td><td>Code unique de la moustiquaire</td></tr>
                    <tr><td><code>type_moustiquaire</code></td><td>Type de moustiquaire</td></tr>
                    <tr><td><code>fabricant</code></td><td>Fabricant</td></tr>
                    <tr><td><code>modele</code></td><td>Modèle</td></tr>
                    <tr><td><code>lot_number</code></td><td>Numéro de lot</td></tr>
                    <tr><td><code>numero_balle</code></td><td>Numéro de balle</td></tr>
                    <tr><td><code>sampling_position</code></td><td>Position de prélèvement</td></tr>
                    <tr><td><code>lieu_entrepot</code></td><td>Lieu d'entrepôt</td></tr>
                    <tr><td><code>remarque</code></td><td>Remarques de l'enquêteur</td></tr>
                    <tr><td><code>presence</code></td><td>Présence de la moustiquaire</td></tr>
                </tbody>
            </table>

            <h3>Export CSV</h3>
            <p>Le bouton <strong>"Export CSV"</strong> est réservé à l'administrateur. Il génère un fichier CSV (séparateur <code>;</code>, encodage UTF-8) de toutes les inspections, incluant les colonnes du tableau et les coordonnées GPS (<code>latitude</code>, <code>longitude</code>).</p>

            <h3>Actualisation du cache</h3>
            <p>Le bouton <strong>"Actualiser"</strong> vide le cache KoboToolbox (durée : 30 minutes) et recharge les données depuis l'API.</p>

            {{-- 14. Rapport bras --}}
            <h2 id="rapport-bras">14. Rapport d'exécution par bras</h2>
            <p>Accessible via le lien <strong>"Rapport bras"</strong> dans la bannière ou <code>/rapport/bras</code>. Ce rapport présente le taux d'exécution du protocole d'enrôlement et de distribution de moustiquaires, par bras de traitement et par cohorte.</p>

            <h3>Cibles protocole</h3>
            <ul>
                <li><strong>921 ménages par bras</strong> — dont 611 en Cohorte A et 310 en Cohorte B</li>
                <li><strong>3 moustiquaires par ménage</strong> en moyenne → 2 763 moustiquaires par bras</li>
                <li>Total global : 1 842 ménages · 5 526 moustiquaires</li>
            </ul>

            <h3>Indicateurs globaux</h3>
            <table>
                <thead><tr><th>Carte</th><th>Description</th></tr></thead>
                <tbody>
                    <tr><td><strong>Ménages enrôlés</strong></td><td>Total enrôlé vs cible 1 842, avec barre de progression</td></tr>
                    <tr><td><strong>Moustiquaires distribuées</strong></td><td>Total distribué vs cible 5 526, avec barre de progression</td></tr>
                    <tr><td><strong>Ménages restants</strong></td><td>Ménages encore à enrôler pour atteindre la cible</td></tr>
                    <tr><td><strong>Filets attendus</strong></td><td>Moustiquaires supplémentaires à distribuer</td></tr>
                </tbody>
            </table>

            <h3>Cartes par bras</h3>
            <p>Deux cartes colorées (violet pour PERMANET DUAL, bleu pour INTERCEPTOR G2) détaillent chaque bras :</p>
            <ul>
                <li>Nombre total de ménages enrôlés et barres de progression (ménages + moustiquaires)</li>
                <li>Détail par <strong>Cohorte A</strong> et <strong>Cohorte B</strong> : enrôlés / cible, distribués / cible, restants à compléter, moyenne filets/ménage</li>
            </ul>

            <h3>Tableau récapitulatif</h3>
            <p>Un tableau complet (Bras × Cohorte) présente toutes les valeurs : cible ménages, enrôlés, taux, restants, cible filets, distribués, taux, manquants. Des sous-totaux par bras et une ligne TOTAL global sont inclus.</p>

            <h3>Code couleur des taux</h3>
            <table>
                <thead><tr><th>Couleur</th><th>Seuil</th><th>Signification</th></tr></thead>
                <tbody>
                    <tr><td style="color:#15803D;font-weight:700">Vert</td><td>≥ 90%</td><td>Objectif quasi atteint</td></tr>
                    <tr><td style="color:#92400E;font-weight:700">Orange</td><td>≥ 70%</td><td>En cours — vigilance recommandée</td></tr>
                    <tr><td style="color:#991B1B;font-weight:700">Rouge</td><td>&lt; 70%</td><td>Retard significatif</td></tr>
                </tbody>
            </table>

            <h3>Téléchargement PDF</h3>
            <p>Le bouton <strong>"Télécharger PDF"</strong> dans la bannière génère directement un fichier PDF téléchargeable (<code>rapport-bras-AAAA-MM-JJ.pdf</code>) au format A4 portrait, sans passer par le dialogue d'impression du navigateur.</p>

            <div class="mt-10 pt-6 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">© {{ date('Y') }} AIRID — ENDURE-Net · Documentation interne · v1.1</p>
            </div>

        </div>
    </main>
</div>

<script>
    // Highlight active nav link on scroll
    const sections = document.querySelectorAll('[id]');
    const navLinks = document.querySelectorAll('.nav-link');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(e => {
            if (e.isIntersecting) {
                navLinks.forEach(l => l.classList.remove('active'));
                const active = document.querySelector('.nav-link[href="#' + e.target.id + '"]');
                if (active) active.classList.add('active');
            }
        });
    }, { threshold: 0.3 });
    sections.forEach(s => observer.observe(s));
</script>

</body>
</html>
