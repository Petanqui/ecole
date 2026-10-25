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
  à la création de la classe ; au lycée la série est la 1re lettre de la section (2nde C1 → C)
- Coefficients types inspirés de la pratique ivoirienne : À VÉRIFIER par l'école et ajuster dans config/grades.php
- Barème libre par évaluation (sur 10, 20, 50…)
- Secondaire : chaque note ramenée sur 20, moyenne de matière = moyenne simple des notes, sur 20
- Primaire : moyenne de matière = total des points / total des barèmes, ramené sur 10
  (une composition sur 50 pèse 5 fois une note sur 10) ; tous les coefficients à 1
- Rôle Enseignant : accès à /gestion limité à la saisie des notes de ses matières dans ses classes

## Hébergement
- VPS en Europe, avec déclaration et demande d'autorisation de transfert à l'ARTCI
- Fuseau horaire : Africa/Abidjan
- Local : PostgreSQL installé dans C:\PostgreSQL\data, locale "English, United States"