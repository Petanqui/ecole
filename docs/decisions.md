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
- Moyenne annuelle = (T1 + 2×T2 + 2×T3) / 5, sur les trimestres où l'élève a une moyenne
  (formule donnée pour le secondaire, appliquée aussi au primaire : À CONFIRMER)
- Distinctions (sur 20, ramenées sur 10 au primaire) : Félicitations ≥ 16, Encouragements ≥ 14,
  Tableau d'honneur ≥ 12, Avertissement < 8,5 ; sur le bulletin du 3e trimestre, elles portent sur la moyenne annuelle
- Appréciations par matière : Excellent ≥ 18, Très bien ≥ 16, Bien ≥ 14, Assez bien ≥ 12, Passable ≥ 10,
  Insuffisant ≥ 8, Faible < 8 (config/grades.php)
- Clôturer un trimestre : les enseignants ne peuvent plus modifier les notes ; les parents voient alors le bulletin
- Bulletin PDF (barryvdh/laravel-dompdf) ; en-tête de l'école via SCHOOL_* dans .env

## Hébergement
- VPS en Europe, avec déclaration et demande d'autorisation de transfert à l'ARTCI
- Fuseau horaire : Africa/Abidjan
- Local : PostgreSQL installé dans C:\PostgreSQL\data, locale "English, United States"