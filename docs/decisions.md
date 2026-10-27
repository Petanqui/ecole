# Journal des décisions

## Stack
- Laravel 13, PHP 8.5, PostgreSQL 18, Node 26 (la production devra avoir les mêmes versions)
- Laravel + PostgreSQL + Livewire + Filament + Tailwind
- Laravel Boost installé (outil de développement IA, pas utilisé en production)
- Monolithe modulaire : dossiers Laravel standards, sous-dossiers par module
  (Core, Grades, Billing, Attendance, Messaging, Timetable, Reports)
- Deux espaces Filament : /gestion (personnel) et /portail (parents, élèves)

## Données
- Niveaux : primaire (CP1 → CM2) et secondaire (6e → Terminale), pas de maternelle
- Classe = niveau + section ; le nom est composé automatiquement (6e + 1 = « 6e1 », 2nde + C1 = « 2nde C1 »)
- Séries du secondaire (A, C, D…) portées par la section pour le MVP
- Matricule national MENA : facultatif à l'inscription, mais unique
- Code interne élève généré automatiquement (EL-AAAA-NNNN) : AAAA = année civile de création de la fiche,
  NNNN repart à 0001 chaque année ; un code n'est jamais réattribué, même après suppression
- Un parent appartient à UNE seule famille (MVP)
- Une inscription par élève et par année scolaire
- Barème primaire par défaut : notes sur 10 (compétences possibles)

## Connexion
- Identifiant : numéro de téléphone + mot de passe (e-mail facultatif)
- Rôles : Administrateur et Personnel → /gestion ; Parent → /portail (enseignants et élèves : plus tard)
- Seuls les administrateurs gèrent les comptes du personnel (Administration → Comptes du personnel)
- Comptes parents créés depuis la fiche famille ; mot de passe généré (8 caractères lisibles) à transmettre au parent
- Supprimer un parent supprime son accès ; changer son nom/téléphone met à jour son compte

## Notes
- Année découpée en 3 trimestres, créés automatiquement avec l'année scolaire
- Chaque classe a son programme (matière, coefficient, enseignant), pré-rempli depuis config/grades.php
  à la création de la classe ; au lycée la série est le début de la section (2nde C1 → C, Tle A2 → A2)
- Coefficients du secondaire : circulaire n° 02505/MENA/DPFC du 21/07/2022 (en vigueur depuis 2022-2023)
  - 1er cycle : Français = 3 disciplines (expression orale + lecture, orthographe + grammaire, expression écrite)
  - 1ère/Tle A : séries A1 et A2 (seules les maths changent) → la section doit commencer par A1 ou A2
  - LV2 facultative en 1ère/Tle C et D : pas dans le programme par défaut (règle de calcul à définir)
  - Instruction religieuse et travaux manuels : jamais comptés dans les moyennes
- Primaire : coefficients à 1 (pas de coefficients officiels fournis) ; la dictée est une matière (circulaire 02504)
- Barème libre par évaluation (sur 10, 20, 50…)
- Secondaire : chaque note ramenée sur 20, moyenne de matière = moyenne simple des notes, sur 20
- Primaire : moyenne de matière = total des points / total des barèmes, ramené sur 10
  (une composition sur 50 pèse 5 fois une note sur 10) ; tous les coefficients à 1
- Rôle Enseignant : accès à /gestion limité à la saisie des notes de ses matières dans ses classes
- Moyenne générale = Σ (moyenne matière × coef) / Σ coef, sur les matières où l'élève a au moins une note
- Rang : ex æquo à moyenne égale (arrondie à 2 décimales), rang suivant sauté (1er, 2e, 2e ex æquo, 4e)
- Moyenne annuelle, sur les trimestres où l'élève a une moyenne :
  - secondaire : (T1 + 2×T2 + 2×T3) / 5
  - primaire (guide MENA) : (T1 + T2 + T3) / 3 ; de CP1 à CM1, passage en classe supérieure si ≥ 5/10
    (affiché comme décision sur le bulletin du 3e trimestre)
  - CM2 : moyenne d'admission en 6e (MAP) = (Compo régionale 1 + 2 + Examen blanc 1 + 2) / 4, admis si ≥ 10/20 ;
    une note globale sur 20 par épreuve, saisie par le personnel (Notes → Admission en 6e), hors moyennes
    trimestrielles ; MAP et décision sur le bulletin du 3e trimestre, une fois les 4 notes saisies
- Distinctions (sur 20, ramenées sur 10 au primaire) : Félicitations ≥ 16, Encouragements ≥ 14,
  Tableau d'honneur ≥ 12, Avertissement < 8,5 ; sur le bulletin du 3e trimestre, elles portent sur la moyenne annuelle
- Appréciations par matière : Excellent ≥ 18, Très bien ≥ 16, Bien ≥ 14, Assez bien ≥ 12, Passable ≥ 10,
  Insuffisant ≥ 8, Faible < 8 (config/grades.php)
- Clôturer un trimestre : les enseignants ne peuvent plus modifier les notes ; les parents voient alors le bulletin
- Bulletin PDF (barryvdh/laravel-dompdf) ; en-tête de l'école via SCHOOL_* dans .env

## Facturation
- Montants en FCFA, entiers
- Tarifs par année scolaire et par niveau (ou « tous les niveaux », ex. inscription), saisis dans Facturation → Tarifs
- Chaque frais a un échéancier : une ou plusieurs tranches à date fixe ; montant du frais = somme des tranches
- Frais obligatoire : dû par tous les élèves du niveau ; frais optionnel (cantine, transport…) : dû seulement par
  les élèves inscrits à ce frais (cases à cocher sur l'inscription)
- Ce qu'un élève doit est calculé à partir des tarifs actuels (rien n'est figé) : modifier un tarif modifie le dû
  de tous les élèves concernés ; un élève inscrit en cours d'année doit toutes les tranches
- Pas de réductions pour l'instant (fratrie, bourses, enfants du personnel : plus tard)
- Moyens de paiement : espèces, Mobile Money (Orange, MTN, Moov, Wave), virement, chèque ; pas de paiement en ligne
- Référence obligatoire pour tout paiement autre qu'en espèces (ID de transaction, n° de virement ou de chèque)
- Un paiement ne peut pas dépasser le reste à payer de l'année
- Les paiements règlent les tranches dans l'ordre des échéances (la plus ancienne d'abord)
- Reçu PDF (A5) numéroté REC-AAAA-NNNNN (AAAA = année civile, numérotation sans doublon), montant en lettres
  (extension PHP intl requise en production) et reste à payer après ce paiement
- Un paiement ne se modifie ni ne se supprime : un administrateur l'annule avec un motif, il reste visible
  (« annulé ») et ne compte plus ; on saisit alors le bon paiement
- Compte de l'élève : onglet « Compte » de l'inscription (paiements, échéancier, solde, retard)

## Hébergement
- VPS en Europe, avec déclaration et demande d'autorisation de transfert à l'ARTCI
- Fuseau horaire : Africa/Abidjan
- Local : PostgreSQL installé dans C:\PostgreSQL\data, locale "English, United States"