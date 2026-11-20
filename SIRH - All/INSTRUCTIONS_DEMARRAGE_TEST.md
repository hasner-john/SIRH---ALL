# Démarrage et test du SIRH

Ce guide décrit le lancement du projet dans **WSL Ubuntu** avec PHP,
CodeIgniter 4, SQLite et Vue/Vite.

## 1. Prérequis

Depuis WSL, vérifier :

```bash
php -v
composer --version
node -v
npm -v
```

PHP doit disposer des extensions suivantes :

```bash
php -m | grep -Ei 'intl|mbstring|pdo_sqlite|sqlite3|fileinfo'
```

Les extensions `intl`, `mbstring`, `pdo_sqlite`, `sqlite3` et `fileinfo`
doivent être activées.

## 2. Installer les dépendances

Depuis la racine du projet :

```bash
composer install
npm install
```

Bootstrap est déjà fourni localement dans
`public/assets/vendor/bootstrap`. Aucun accès Internet n’est nécessaire pour
charger le CSS ou le JavaScript de l’espace RH. Après une modification du
projet, videz le cache du navigateur avec `Ctrl+F5`.

### Export Excel des états de paie

PhpSpreadsheet est installé avec Composer :

```bash
composer require phpoffice/phpspreadsheet
composer install
composer dump-autoload
```

Vérification :

```bash
php -r 'require "vendor/autoload.php"; echo class_exists("PhpOffice\\\\PhpSpreadsheet\\\\Spreadsheet") ? "OK\n" : "MANQUANT\n";'
```

Cette erreur n’est pas liée à une extension de moteur de base de données.
Elle nécessite les extensions PHP `zip`, `xml`, `mbstring` et `intl`.

## 3. Initialiser SQLite

La base utilisée par l’application est :

```text
writable/database.db
```

Créer la base et charger le jeu de données complet (recrutement, dossier
salarié, pointage et congés) :

```bash
php spark db:init
```

Le seeder PHP est [DemoDatabaseSeeder.php](app/Services/DemoDatabaseSeeder.php).
Il crée des données cohérentes dans toutes les tables et utilise `password`
comme mot de passe pour les comptes de démonstration.

Le script `base_recrutement.sql` contient maintenant les tables des modules :

- recrutement et candidatures ;
- Bloc 5 : salariés, affectations, salaires, documents et journal d’accès ;
- Bloc 6 : régimes, horaires, plannings, pointages, retards, heures
  supplémentaires, temps travaillé et anomalies ;
- Bloc 7 : types de congés, droits, soldes, demandes, justificatifs et
  absences.

### Réinitialiser complètement la base

Cette opération supprime les données locales actuelles :

```bash
php spark db:reset
```

Pour une exécution non interactive (tests ou CI) :

```bash
php spark db:reset --force
```

## 4. Construire le frontend

```bash
npm run build
```

Pendant le développement :

```bash
npm run watch
```

## 5. Démarrer l’application

```bash
php spark serve
```

Ouvrir ensuite :

```text
http://localhost:8080/
```

Le serveur CodeIgniter sert automatiquement le dossier `public`.

## 6. Comptes de démonstration

Le mot de passe des comptes fournis par le seeder est `password`.

| Rôle | Identifiant |
|---|---|
| Administrateur | `admin@test.local` |
| Direction | `direction@test.local` |
| RH | `rh@test.local` |
| Département | `departement@test.local` |
| Finance | `finance@test.local` |
| Candidat | `candidat@test.local` |
| Employé 1 | `employe1@test.local` |
| Employé 2 | `employe2@test.local` |
| Employé 3 | `employe3@test.local` |

Le jeu de données contient quatre salariés (`EMP-0001` à `EMP-0004`).
Chaque salarié possède un contrat signé et un PDF de démonstration
téléchargeable depuis son dossier RH. Les contrats candidats sont également
disponibles dans l’espace candidat lorsque le compte correspondant est utilisé.

Les rôles disposent tous d’un bouton ou lien de déconnexion visible dans
leur espace. L’administrateur peut ouvrir l’espace RH et valider ou refuser
les demandes de recrutement depuis `/rh/demandes`. Ces décisions sont
journalisées avec le type de validation `ADMINISTRATEUR`.

En développement, les connexions rapides sont disponibles :

## 7. Blocs 8 à 12

Après `php spark db:reset --force`, l’espace RH contient les données des
modules avancés :

- **Paie** : périodes, rubriques, variables, paramètres CNaPS/OSTIE/IRSA et bulletins.
- **Évaluation et formation** : référentiel de compétences, objectifs, évaluations et formations.
- **Carrière** : promotions, mutations, révisions salariales et affectations avec dates d’effet.
- **Discipline** : incident, demande d’explications, réponse et traçabilité d’audit.
- **Rupture / offboarding** : rupture, STC, attestations et checklist de départ.

Accès RH :

```text
http://localhost:8080/rh/modules-avances
```

Depuis cette page, les RH peuvent calculer les bulletins de paie pour une
période, créer une formation ou un mouvement de carrière, signaler un incident
et enregistrer une rupture avec sa checklist.

Le guide détaillé des opérations, des calculs et des dépendances est disponible
dans [MODE_EMPLOI_BLOCS_8_A_12.md](MODE_EMPLOI_BLOCS_8_A_12.md).

## 8. Test PHP complet

Une campagne HTTP couvre les blocs 1 à 12, l’authentification, les espaces des
six rôles, les liens de déconnexion et les actions métier principales :

```bash
php vendor/bin/phpunit tests/feature/CompleteSirhTest.php
```

```text
http://localhost:8080/dev/login-as/DIRECTION
http://localhost:8080/dev/login-as/RH
http://localhost:8080/dev/login-as/DEPARTEMENT
http://localhost:8080/dev/login-as/CANDIDAT
http://localhost:8080/dev/login-as/CANDIDAT2
http://localhost:8080/dev/login-as/CANDIDAT3
http://localhost:8080/dev/login-as/CANDIDAT4
http://localhost:8080/dev/logout
```

Ces routes sont réservées au développement et ne doivent pas être
conservées en production.

## 7. Parcours de test recommandés

### Test A — Recrutement et Direction

1. Se connecter comme `direction@test.local`.
2. Ouvrir `/direction`.
3. Vérifier `/direction/offres`.
4. Valider une offre en attente.
5. Ouvrir `/direction/candidatures`.
6. Consulter une candidature et ses documents.
7. Retenir une candidature présélectionnée.
8. Vérifier la notification reçue par le candidat.

### Test B — RH et recrutement

1. Se connecter comme `rh@test.local`.
2. Ouvrir `/rh`.
3. Consulter les demandes, offres et candidatures.
4. Présélectionner une candidature.
5. Programmer un entretien dans `/rh/entretiens`.
6. Le marquer `REALISE`.
7. Ajouter une évaluation dans `/rh/evaluations`.
8. Consulter les requêtes candidat dans `/rh/requetes`.

### Test C — Bloc 5 : dossier salarié

1. Avec un compte RH, ouvrir `/rh/employes`.
2. Ouvrir le dossier d’un salarié.
3. Vérifier l’identité, le contrat et les affectations.
4. Ajouter une évolution salariale.
5. Ajouter un document PDF, JPEG ou PNG de moins de 5 Mo.
6. Vérifier sa date d’expiration.
7. Télécharger le document.
8. Vérifier que la consultation et le téléchargement sont inscrits
   dans `journal_acces`.

Routes principales :

```text
/rh/employes
/rh/employes/{id}
/rh/employes/{id}/documents/{id_document}
```

### Test D — Bloc 6 : temps de travail et pointage

1. Ouvrir `/rh/temps-travail`.
2. Saisir un salarié, une date et une heure d’entrée.
3. Ajouter une heure de sortie.
4. Vérifier le temps travaillé et le statut du pointage.
5. Tester une entrée après l’horaire prévu pour vérifier le retard.
6. Tester une durée supérieure à l’horaire prévu pour vérifier les heures
   supplémentaires.
7. Vérifier les anomalies pour une entrée ou une sortie manquante.

Route de saisie :

```text
/rh/temps-travail
```

### Test E — Bloc 7 : congés et absences

1. Ouvrir `/rh/conges`.
2. Créer une demande de congé avec un salarié, un type et une période.
3. Vérifier le contrôle du solde disponible.
4. Tester une période qui chevauche une autre demande.
5. Ajouter un justificatif si nécessaire.
6. Valider ou refuser la demande.
7. Déclarer une absence.
8. Vérifier la mise à jour du solde après validation.

Routes principales :

```text
/rh/conges
/rh/absences
```

## 8. Vérifications rapides en base

Afficher les volumes principaux :

```bash
php -r '$db = new PDO("sqlite:writable/database.db"); foreach (["departement", "utilisateur", "employe", "offre_emploi", "candidature", "pointage", "demande_conge", "absence"] as $table) { echo $table . "=" . $db->query("SELECT COUNT(*) FROM " . $table)->fetchColumn() . PHP_EOL; }'
```

Vérifier la présence de toutes les tables des Blocs 5 à 7 :

```bash
php -r '$db = new PDO("sqlite:writable/database.db"); foreach (["employe", "historique_salaire", "document_employe", "journal_acces", "regime_travail", "horaire", "planning", "pointage", "retard", "heure_supplementaire", "temps_travaille", "anomalie_pointage", "type_conge", "droit_conge", "solde_conge", "demande_conge", "justificatif_conge", "absence"] as $table) { $ok = (int) $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type = \"table\" AND name = " . $db->quote($table))->fetchColumn(); echo $table . "=" . ($ok ? "OK" : "MANQUANTE") . PHP_EOL; }'
```

## 9. Tests automatisés et vérifications PHP

Le dépôt contient actuellement peu de tests métier. Lancer PHPUnit directement
avec PHP :

```bash
php vendor/bin/phpunit --testsuite unit --no-coverage
```

Le script Composer peut échouer si le binaire `vendor/bin/phpunit` n’a pas
le droit d’exécution dans WSL :

```bash
composer test
```

Dans ce cas, utiliser la commande PHPUnit directe ci-dessus.

Vérifier la syntaxe des fichiers modifiés :

```bash
find app/Controllers app/Models app/Services -type f -name '*.php' -print0 \
  | xargs -0 -n1 php -l
```

Valider le schéma sans modifier la base locale :

```bash
php -r '$db = new PDO("sqlite::memory:"); $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); $db->exec(file_get_contents("base_recrutement.sql")); echo "Schéma valide.\n";'
```

## 10. Dépannage

### Avertissements `already loaded` ou erreur `pdo_dbh_ce`

Si PHP affiche plusieurs avertissements comme :

```text
Module "sqlite3" is already loaded
Unable to load dynamic library "pdo_sqlite"
undefined symbol: pdo_dbh_ce
```

Les extensions sont déclarées deux fois : dans `php.ini` et dans les fichiers
`/etc/php/8.5/cli/conf.d/`. Elles doivent rester déclarées dans `conf.d`,
qui garantit également le chargement de `pdo` avant `pdo_sqlite`.

Sauvegarder puis commenter uniquement les cinq lignes concernées dans
`php.ini` :

```bash
sudo cp /etc/php/8.5/cli/php.ini /etc/php/8.5/cli/php.ini.bak
sudo sed -i \
  -e 's/^[[:space:]]*extension=fileinfo\(\.so\)\?[[:space:]]*$/;extension=fileinfo/' \
  -e 's/^[[:space:]]*extension=intl\(\.so\)\?[[:space:]]*$/;extension=intl/' \
  -e 's/^[[:space:]]*extension=mbstring\(\.so\)\?[[:space:]]*$/;extension=mbstring/' \
  -e 's/^[[:space:]]*extension=pdo_sqlite\(\.so\)\?[[:space:]]*$/;extension=pdo_sqlite/' \
  -e 's/^[[:space:]]*extension=sqlite3\(\.so\)\?[[:space:]]*$/;extension=sqlite3/' \
  /etc/php/8.5/cli/php.ini
```

Vérifier ensuite :

```bash
php -m | grep -Ei 'PDO|pdo_sqlite|sqlite3|fileinfo|intl|mbstring'
php -r 'print_r(PDO::getAvailableDrivers());'
```

La sortie PDO doit contenir `sqlite`, et aucun avertissement ne doit apparaître.

### `could not find driver`

Vérifier les extensions utilisées par le PHP de WSL :

```bash
php --ini
php -m | grep -Ei 'pdo_sqlite|sqlite3'
```

### `Class "Locale" not found`

Installer ou activer `intl`, puis redémarrer le terminal WSL.

### La base semble vide

Vérifier que `writable/database.db` existe et relancer l’initialisation
depuis la racine du projet.

### Le frontend n’est pas à jour

```bash
npm run build
```

Puis actualiser le navigateur avec `Ctrl+F5`.

### Consulter les logs

Les logs CodeIgniter sont disponibles dans :

```text
writable/logs/
```
