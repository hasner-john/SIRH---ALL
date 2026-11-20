# Mission : finaliser et corriger les blocs RH 8 à 12 de l'application

## Contexte
Application de recrutement et gestion RH en **CodeIgniter 4 + SQLite** (espaces Département, RH, Direction, Candidat, Admin, Employé). Les blocs 1 à 7 (besoin, recrutement, sélection/embauche, contrat/onboarding, congés et absences) existent déjà. Les **blocs 8 à 12** ont une première implémentation **partielle, bugguée, avec possiblement des erreurs de logique métier** :

- Bloc 8 : Paie (Madagascar : CNaPS, OSTIE, IRSA, SME)
- Bloc 9 : Évaluation et Formation
- Bloc 10 : Carrière
- Bloc 11 : Discipline
- Bloc 12 : Rupture / Solde de tout compte (STC)

## Fichiers joints (à lire INTÉGRALEMENT avant toute modification)
1. **`blocs_8_12_reste_a_faire.md`** : la spécification de référence. Elle contient le SQL commun (tables à remplacer, `migration_blocs_8_12.sql`, corrections du seed), les règles de gestion, le détail des 5 blocs, les routes, les fichiers à créer et la **liste des 10 bugs connus (§10)**.
2. **`repartition_blocs_8_12_ABCD.md`** : le découpage du travail, les dépendances, les jalons J0 à J3, le chemin critique et les règles transverses **RG-TRANS-01 à 05**. Sert surtout à respecter l'ordre des dépendances (ex. `Transitions`, `ParametreRh`, `NotificationService` d'abord).

En cas de contradiction entre le code existant et ces documents, **les documents priment**.

## Contraintes techniques (non négociables)
- **Aucun Model CodeIgniter** (`app/Models/` reste inchangé) : `\Config\Database::connect()` + Query Builder dans les contrôleurs et dans `app/Services/`.
- Toute opération sur plusieurs tables : `transBegin/transCommit` avec rollback complet en cas d'échec.
- Tout changement de statut passe par la library `Transitions` ; si la transition est refusée, afficher un message.
- Toute notification passe par `NotificationService::envoyer()`.
- Aucun taux, plafond ou durée en dur : tout vient de `parametre_rh` ou `ParametrePaie` (via `ParametreRh::get()`).
- SQLite : on ne modifie pas une contrainte `CHECK`. On remplace les 4 tables (`utilisateur`, `contrat`, `heure_supplementaire`, `mouvement_carriere`) dans `base_recrutement.sql`, on ajoute `migration_blocs_8_12.sql`, on corrige le seed, puis on **recrée la base**.
- Pas de modification directe dispersée de `Routes.php` : le squelette complet du §8 est posé une seule fois.
- Ajouter chaque nouvelle page dans la grille admin et dans le menu `rh/layout`.

## Règles de gestion transverses
- **RG-TRANS-01** : toute écriture sensible laisse une ligne `audit_rh` (user, date UTC, action, `objet_id`, ancienne/nouvelle valeur, motif).
- **RG-TRANS-02** : on n'écrase jamais l'historique. Une correction crée un nouvel enregistrement lié (`RAPPEL`, nouveau contrat, nouvelle évaluation). Bulletin `VALIDE`, évaluation `VALIDE`, STC `VALIDE`, contrat `SIGNE` sont immuables.
- **RG-TRANS-03** : toute donnée contractuelle ou salariale a une `date_effet`, et la situation à une date passée doit être reconstituable.
- **RG-TRANS-04** : accès par rôle (l'ADMINISTRATEUR passe partout) et `journal_acces` pour les bulletins et les dossiers disciplinaires.
- **RG-TRANS-05** : paramètres en base, jamais en dur.

## Méthode de travail (étapes obligatoires, dans cet ordre)

### Étape 1 : Audit (sans rien modifier)
Parcourir le projet et produire un **rapport court** :
- ce qui existe déjà pour chaque bloc 8 à 12 (contrôleurs, services, vues, routes, tables) ;
- les écarts avec la spécification ;
- les **bugs de syntaxe/exécution** (routes dupliquées, `<th>` mal placé, `incidents[0]`, `dev/login-as`, etc.) ;
- les **erreurs de logique métier** : formules de paie (prorata, ordre brut → cotisations → IRSA → net, retards déduits du brut et non après impôt, `PRIME` et `INDEMNITE_NON_IMPOSABLE` pris en compte, exclusion des `PARTI`), formule du STC, règle de non-chevauchement des affectations, délais de la phase contradictoire en discipline ;
- vérifier chacun des 10 bugs du §10, plus tout autre bug trouvé.

**Arrête-toi après le rapport et attends ma validation.**

### Étape 2 : Socle (jalon J0)
Dans cet ordre : SQL commun (1.A, 1.B, 1.C) et recréation de la base → `RoleFilter` + squelette `Routes.php` + `Transitions` + `dev/login-as` (rôle `EMPLOYE`) → `ParametreRh` + page `rh/parametres` → `NotificationService` → menu `rh/layout` et page admin.

### Étape 3 : Les blocs, un par un
Respecter les dépendances et le chemin critique **`assurer()` → embauche** et **`RuptureService::creer` → discipline**. Ordre conseillé :
1. Bloc 8 Paie (périodes, variables, HS, retards, refonte de `PaieService`, états/PDF)
2. Embauche directe + `ContratPdfService`
3. Bloc 9 Évaluation/Formation
4. Bloc 10 Carrière
5. Bloc 11 Discipline + espace employé
6. Bloc 12 Rupture, STC, attestations, lien licenciement → rupture

**À la fin de chaque bloc :** liste des fichiers créés/modifiés, bugs corrigés, et comment le tester (rôle `dev/login-as` à utiliser, résultat attendu, test du refus pour un rôle non autorisé). Puis **attends mon feu vert** avant le bloc suivant.

## Format de tes réponses
- Concis. Pas de réécriture complète d'un fichier existant quand un correctif ciblé suffit : donne le **snippet + l'endroit exact où le placer**. Fichiers entiers uniquement pour les nouveaux fichiers.
- Explique en 1 à 3 phrases le **principe métier** derrière chaque correction importante (pas seulement le code).
- Si une règle est ambiguë ou si le code existant contredit la spec, **pose la question** au lieu de deviner.
- Réponds en français. Ne produis pas de longs récapitulatifs inutiles.

## Définition de « terminé »
L'application se lance sur une base recréée, chaque page des blocs 8 à 12 fonctionne avec le bon rôle, refuse les autres rôles, laisse une trace `audit_rh`, et les cas de calcul (paie avec HS, retards, prorata, mise à pied, STC avec sortie en cours de mois) donnent les résultats attendus.

**Commence par l'Étape 1 (audit).**