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
- Réductions (Facturation → Réductions) : types définis par année scolaire (« Fratrie », « Bourse »…),
  en pourcentage ou en montant fixe, sur les frais cochés ; attribuées élève par élève sur l'inscription
- Une réduction ne porte que sur les frais que l'élève doit (pas sur un frais optionnel non souscrit) ;
  un montant fixe se partage entre ses frais au prorata de leur montant
- Chaque tranche est réduite au prorata (l'échéancier garde son rythme), en francs entiers, le reste d'arrondi
  sur la dernière tranche ; les réductions s'additionnent sans dépasser le frais
- Comme les tarifs, les réductions sont calculées en direct (rien n'est figé) ; l'échéancier montre la réduction
  par tranche et le reçu rappelle le total des réductions
- Moyens de paiement : espèces, Mobile Money (Orange, MTN, Moov, Wave), virement, chèque ; pas de paiement en ligne
- Référence obligatoire pour tout paiement autre qu'en espèces (ID de transaction, n° de virement ou de chèque)
- Un paiement ne peut pas dépasser le reste à payer de l'année
- Les paiements règlent les tranches dans l'ordre des échéances (la plus ancienne d'abord)
- Reçu PDF (A5) numéroté REC-AAAA-NNNNN (AAAA = année civile, numérotation sans doublon), montant en lettres
  (extension PHP intl requise en production) et reste à payer après ce paiement
- Un paiement ne se modifie ni ne se supprime : un administrateur l'annule avec un motif, il reste visible
  (« annulé ») et ne compte plus ; on saisit alors le bon paiement
- Compte de l'élève : onglet « Compte » de l'inscription (paiements, échéancier, solde, retard)
- Impayés (Facturation → Impayés) : élèves de l'année en cours en retard à une date donnée (par défaut aujourd'hui),
  plus gros retard d'abord, avec le téléphone des parents ; export CSV (séparateur « ; », lisible par Excel)
- Retard = montant des tranches échues à la date − total payé
- Portail : pour chaque enfant, reste à payer (et retard), échéancier, paiements et téléchargement des reçus

## Présences
- Appel par heure de cours (primaire et secondaire) : classe + matière, date, heure de début, durée en heures,
  rattaché à un trimestre ouvert (Présences → Appels) ; pas d'emploi du temps pour l'instant
- Une classe ne peut pas avoir deux appels qui se chevauchent le même jour
- Chaque élève est Présent(e), Absent(e) ou En retard ; seuls les absences et retards sont enregistrés
  (un élève sans ligne pour un appel était présent)
- Le personnel fait tous les appels ; un enseignant ceux de ses matières, et ne peut plus les modifier
  une fois le trimestre clôturé
- Une justification déjà acceptée est conservée si l'on passe d'absent à en retard (ou l'inverse)
- Heures d'absence = durée des cours manqués ; un retard compte comme un retard, pas en heures
- Seul le personnel justifie (Présences → Absences et retards), avec un motif, une ou plusieurs absences à la fois ;
  une justification peut être retirée
- Onglet « Absences » de l'inscription : liste et totaux par trimestre et pour l'année
- Bulletin : « Assiduité du trimestre » = heures d'absence (dont justifiées) et nombre de retards
- Portail : pour chaque enfant, heures d'absence de l'année (dont non justifiées) et retards, avec le détail ;
  le parent ne justifie pas en ligne, il apporte le justificatif au secrétariat

## Messagerie
- Messages sur le portail uniquement (pas de SMS ni d'e-mail pour l'instant)
- Annonces (Messagerie → Annonces) : de l'école vers les parents, sans réponse ; destinataires = toute l'école,
  un niveau ou une classe, pour l'année scolaire en cours ; publiées immédiatement
- Les destinataires suivent les inscriptions : un parent dont l'accès portail est créé plus tard voit
  aussi les annonces déjà publiées
- Le personnel écrit à tous ; un enseignant seulement aux parents des classes où il enseigne, et ne voit que ses annonces
- Texte simple (pas de mise en forme) ; signé « Administration » pour le personnel, du nom de l'enseignant sinon
- Une annonce est marquée lue quand le parent ouvre la page Annonces ; l'école voit « lue par X / Y parents »
- Conversations (Messagerie → Messages) : entre une famille (tous ses parents) et l'école, éventuellement
  au sujet d'un enfant ; côté école, l'interlocuteur est l'administration ou un enseignant
- Le personnel écrit au nom de l'administration à toute famille d'un élève inscrit ; un enseignant écrit en son nom
  aux familles des classes où il enseigne
- Le personnel voit et peut répondre à toutes les conversations (y compris celles des enseignants) ;
  un enseignant ne voit que les siennes
- Lu / non lu propre à chaque utilisateur ; ouvrir une conversation la marque lue ; nombre de non lues dans le menu
- Une conversation et ses messages ne se suppriment pas
- Portail → Messages : le parent lit et répond aux conversations de sa famille (tous les parents de la famille
  voient les mêmes) ; il écrit à l'administration ou à un enseignant d'une classe de ses enfants cette année,
  éventuellement au sujet d'un enfant

## Emploi du temps
- Une semaine type par classe, valable toute l'année (pas de semaines A/B) ; du lundi au samedi
- Un cours = matière de la classe (donc son enseignant), jour, heure de début, durée en heures entières (1 à 4),
  salle facultative (Emploi du temps → Salles)
- Refusé si, sur un même créneau de la même année scolaire, la classe, l'enseignant ou la salle a déjà un cours
- Saisie par le personnel (Emploi du temps → Par classe) ; une salle utilisée ne peut pas être supprimée
- Un enseignant voit sa semaine (Mon emploi du temps) ; le personnel celle de n'importe quel enseignant (Par enseignant)
- PDF de l'emploi du temps d'une classe (A4 paysage) : personnel, enseignants, et parents d'un élève de la classe
- Portail : emploi du temps de la classe de chaque enfant, avec le PDF
- Présences → Appels : « Cours du jour » d'après l'emploi du temps (ceux de l'enseignant, tous pour le personnel),
  appel fait / à faire ; « Faire l'appel » crée l'appel prérempli (classe, matière, heure, durée, premier trimestre
  ouvert) ou rouvre celui déjà fait ; refusé si la classe a déjà un appel sur ce créneau ou si tout est clôturé
- L'appel manuel (« Faire l'appel » en haut de page) reste possible pour un cours hors emploi du temps

## Rapports
- Tableau de bord de la gestion (personnel seulement) pour l'année en cours : élèves inscrits, encaissé ce mois
  et sur l'année, paiements en retard, heures d'absence des 30 derniers jours (dont non justifiées) ;
  élèves par niveau ; encaissements par mois ; assiduité par classe
- Taux d'absence d'une classe = heures d'absence / (heures d'appel × nombre d'élèves)
- Enseignants : l'accueil montre leurs cours du jour (le personnel voit tous les cours du jour)
- Bilan des résultats (Rapports, personnel) : pour un trimestre, chaque classe avec effectif, élèves classés,
  moyenne de la classe, plus forte, plus faible, part des élèves ayant la moyenne (5/10 au primaire, 10/20 au
  secondaire) et premier(e) ; export CSV ; lien vers le détail de la classe (Notes → Résultats)

## Hébergement
- VPS en Europe, avec déclaration et demande d'autorisation de transfert à l'ARTCI
- Fuseau horaire : Africa/Abidjan
- Local : PostgreSQL installé dans C:\PostgreSQL\data, locale "English, United States"