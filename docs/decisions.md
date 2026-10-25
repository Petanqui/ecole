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
- Séries du secondaire (A, C, D…) portées par le nom de la classe (ex. « Tle D ») pour le MVP
- Matricule national MENA : facultatif à l'inscription, mais unique
- Code interne élève généré automatiquement (EL-AAAA-NNNN) : AAAA = année civile de création de la fiche,
  NNNN repart à 0001 chaque année ; un code n'est jamais réattribué, même après suppression
- Un parent appartient à UNE seule famille (MVP)
- Une inscription par élève et par année scolaire
- Barème primaire par défaut : notes sur 10 (compétences possibles)

## Connexion
- Identifiant : numéro de téléphone + mot de passe (e-mail facultatif)

## Hébergement
- VPS en Europe, avec déclaration et demande d'autorisation de transfert à l'ARTCI
- Fuseau horaire : Africa/Abidjan
- Local : PostgreSQL installé dans C:\PostgreSQL\data, locale "English, United States"