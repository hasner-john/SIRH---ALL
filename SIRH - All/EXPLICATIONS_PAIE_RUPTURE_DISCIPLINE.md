# Paie, rupture et discipline : à quoi servent les variables ?

Ce document explique ce que font les colonnes et les paramètres que l'on voit dans les pages
**Variables / indemnités**, **Paramètres RH → Rupture** et **Paramètres RH → Discipline**.
Il décrit aussi les deux corrections de code qui vont avec (voir la dernière section).

---

## 1. La colonne `nombre` de la table `variable_paie`

Une variable de paie = *un employé + une période + une rubrique + un montant*. La colonne `nombre`
est une **quantité indicative** : combien de jours, d'unités ou d'occurrences se cache derrière le montant.

| Colonne | Rôle |
|---|---|
| `montant` | **Total en Ariary** de la ligne. C'est la seule valeur utilisée par le calcul de paie. |
| `nombre` | Information pour la RH (ex. `20` jours d'indemnité de transport, `1` prime). Valeur par défaut : `1`. |

- Le calcul (`PaieService::variables()`) **additionne les `montant`** par catégorie de rubrique. Il **ne multiplie jamais** par `nombre`.
  Si vous saisissez `nombre = 20` et `montant = 5 000`, le bulletin prendra **5 000 Ar**, pas 100 000 Ar.
  Il faut donc saisir le montant total (20 × 5 000 = 100 000).
- La contrainte `UNIQUE(employé, période, rubrique)` fait qu'un employé n'a qu'**une ligne par rubrique et par mois**.
  Si on en ressaisit une, la ligne existante est modifiée (upsert), elle n'est pas dupliquée.
- Les anciennes lignes `HS_30`, `HS_50`, `DIMANCHE_NUIT` du seed avaient `nombre = 4, 3, 2` et `montant = 0` : elles sont ignorées,
  car les heures supplémentaires sont calculées depuis la page **Heures supplémentaires**, jamais depuis les variables.

---

## 2. Les rubriques RAPPEL, AUTRE et AVANCE : où vont-elles et à quoi servent-elles ?

Chaque rubrique (`rubrique_paie`) a une **catégorie**. C'est elle, et non le code texte, qui décide du traitement.
Seules les catégories `INDEMNITE`, `PRIME`, `RAPPEL` et `AVANCE` sont proposées dans le formulaire de saisie.

| Catégorie | Nature | Où va le montant | Ligne du bulletin | Effet |
|---|---|---|---|---|
| `INDEMNITE` / `PRIME` **imposable** | Gain | `bulletin.indemnites` → **brut** | `2000 Indemnités et primes` | Soumis à CNaPS, OSTIE et IRSA |
| `INDEMNITE` / `PRIME` **non imposable** | Gain | `indemnites_non_imposables` → **hors brut** | `2900 Indemnités non imposables` | Ajouté au **net**, aucune cotisation ni impôt |
| **`RAPPEL`** | Gain | `bulletin.rappels` → **brut** | `2100 Rappels / autres` | Soumis à CNaPS, OSTIE et IRSA |
| **`AVANCE`** | Retenue | `autres_retenues` → **net** | `6000 Acomptes / avances` | Déduite **après** impôt, n'est pas soumise aux cotisations |
| `AUTRE` (ex. `MAJORATION`) | — | **Ignorée** | — | N'est pas saisissable |
| `HS` (`HS_30`, `HS_50`, `DIMANCHE_NUIT`) | — | **Ignorée** | — | Calculée depuis la page Heures supplémentaires |

### RAPPEL (libellé « Rappel de salaire » ou « Rappels et autres »)
Un rappel est un **gain de régularisation** sur un mois déjà clôturé ou mal payé :
salaire ou prime oubliés, erreur de calcul d'un mois précédent, **annulation d'une mise à pied déjà retenue**
(le système crée alors tout seul un RAPPEL sur le mois suivant). C'est la seule façon de corriger une paie
**validée**, car un bulletin `VALIDE` est immuable (RG-TRANS-02). On ne modifie pas le passé : on ajoute un
rappel dans la période ouverte.

### AVANCE (« Avance ou acompte »)
Une avance est de l'argent versé à l'employé **avant** la paie. Elle est **retenue sur le net** du mois où on la saisit
(après IRSA, car elle ne change ni le brut ni l'impôt). Si l'employé quitte l'entreprise avant de l'avoir remboursée
(avance sans bulletin correspondant), elle est déduite du **solde de tout compte**.

### « Autre » : pourquoi n'est-ce pas sur le bulletin ?
La catégorie `AUTRE` ne produit **aucune ligne**. Une rubrique qui n'est ni indemnité, ni prime, ni rappel, ni avance
n'est pas prise en compte par le calcul. Pour qu'une rubrique compte, il faut lui donner une des quatre catégories saisissables.

### Pourquoi un RAPPEL ou une AVANCE n'apparaît pas sur le bulletin ?
Les lignes du bulletin sont **fabriquées au moment du calcul**. Une variable saisie *après* le dernier calcul n'y figure pas.
Vérifiez, dans l'ordre :

1. La variable est bien sur la **bonne période** et la bonne rubrique (RAPPEL ou AVANCE, pas une rubrique `AUTRE`).
2. La période est `OUVERT` ou `CALCUL` : sinon la saisie est refusée (période verrouillée).
3. **Recalculez la période** (page *Périodes de paie* → bouton *Recalculer*). Après la saisie, le message *« Recalcul nécessaire »* l'indique.
4. Ouvrez ensuite le PDF du bulletin : les lignes `2100` (rappel) et `6000` (avance) apparaissent seulement si leur montant est **supérieur à 0**.

Test fait sur la base de démonstration : un RAPPEL de 80 000 Ar et une AVANCE de 30 000 Ar, après recalcul, donnent
`2100 Rappels / autres : 80 000` et `6000 Acomptes / avances : 30 000` sur le bulletin.

---

## 3. Paramètres RH, groupe **RUPTURE**

Ces valeurs sont lues dans `parametre_rh` (RG-TRANS-05 : rien n'est écrit en dur dans le code).
La case **actif** décide si le paramètre est utilisé. **Décochée**, le code retombe sur sa valeur par défaut
(indemnités : 0, préavis : 0 jour).

### `INDEM_LICENCIEMENT_ECO_JOURS_AN` : indemnité de licenciement économique (jours/an)
Nombre de **jours de salaire par année d'ancienneté** accordés en cas de motif `LICENCIEMENT_ECONOMIQUE`.
```
indemnité = ancienneté (en années) × INDEM_LICENCIEMENT_ECO_JOURS_AN × (salaire de base / 30)
```
Exemple : salaire 1 900 000 Ar, 1,72 an d'ancienneté, 10 jours/an → 1,72 × 10 × 63 333 = **1 087 200 Ar**.
Cette indemnité entre dans la ligne « Indemnité de rupture » du STC. **La valeur 10 est une valeur de test** : à faire valider
(code du travail, convention collective) avant tout usage réel.

### `INDEM_RETRAITE_JOURS_AN` : indemnité de départ à la retraite (jours/an)
Même formule, appliquée au motif `RETRAITE`. Même réserve : valeur de test à valider.

Pour les motifs `DEMISSION`, `FIN_CDD` et `LICENCIEMENT_FAUTE`, l'indemnité de rupture est **0**.
Pour `RUPTURE_CONVENTIONNELLE` et `AUTRE`, le montant est **saisi à la main** dans la page du STC.

### `PREAVIS_DEMISSION_JOURS` : préavis par défaut en cas de démission (jours)
Si, à la création de la rupture, la **date de sortie est laissée vide** et que le motif est `DEMISSION`, elle est calculée :
```
date de sortie = date de notification + PREAVIS_DEMISSION_JOURS
```
Exemple : démission notifiée le 01/12 avec 30 jours → sortie le 31/12. On peut toujours saisir une autre date.
Le champ « Préavis (j) » du formulaire de rupture remplace cette valeur pour une rupture donnée.

### `PREAVIS_LICENCIEMENT_JOURS` : préavis par défaut en cas de licenciement économique (jours)
Même principe pour le motif `LICENCIEMENT_ECONOMIQUE`.

Autres motifs : `FIN_CDD` → la sortie est la **date de fin du contrat** ; `LICENCIEMENT_FAUTE` (venant de la discipline) → préavis 0 ;
`RETRAITE`, `RUPTURE_CONVENTIONNELLE`, `AUTRE` → préavis 0 sauf si vous le saisissez.

---

## 4. Paramètres RH, groupe **DISCIPLINE**

### `DISC_DELAI_REPONSE_JOURS` : délai de réponse proposé par défaut (jours)
Quand la RH clique sur **Demander des explications**, la date limite de réponse est **pré-remplie** avec
`aujourd'hui + DISC_DELAI_REPONSE_JOURS` (5 jours par défaut). La RH peut la modifier avant d'envoyer.
Cette date limite sert ensuite à :
- marquer la réponse de l'employé **« en retard »** si elle arrive après ;
- autoriser la décision **sans réponse** : une sanction est possible dès qu'il existe une demande notifiée
  et (une réponse **ou** un délai dépassé). C'est la phase contradictoire.

### `DISC_RECIDIVE_MOIS` : fenêtre de récidive (mois)
Sur la page de **décision**, le système cherche les sanctions de l'employé (hors `ANNULEE`) prises dans les
`DISC_RECIDIVE_MOIS` derniers mois (12 par défaut). S'il y en a, une **alerte de récidive** liste ces sanctions.
C'est **informatif** : l'alerte n'empêche rien, la décision reste à la RH.

---

## 5. Corrections apportées au code

### 5.1 Mise à pied et retards non visibles dans la colonne « Ret. abs. »
**Constat.** Sur `/rh/modules-avances/paie/consulter`, la colonne « Ret. abs. » affichait 0 alors que le salaire
du mois était bien réduit.

**Cause.** La colonne lisait uniquement `retenue_absence`. La mise à pied (`retenue_mise_a_pied`), les retards
(`retenue_retard`) et le prorata (`retenue_prorata`) sont stockés dans **d'autres colonnes**. Le calcul était donc
juste (la « base du mois » était bien diminuée), mais l'affichage ne les montrait pas.

**Correction.** La colonne regroupe maintenant **absences + retards + mise à pied + prorata**, comme la colonne
« Retenues » de l'état Excel. Le détail par nature reste sur le PDF du bulletin (lignes `1010`, `1011`, `1012`, `1013`).

Cas de test (novembre 2026, salaire 1 750 000 Ar, sortie le 20/11) :

| Élément | Montant |
|---|---|
| Retard de 60 min (taux horaire × 60/60) | 10 096,35 |
| Mise à pied de 3 jours (3 × 1 750 000 / 30) | 175 000,00 |
| Prorata (10 jours non travaillés sur 30) | 583 333,33 |
| **Colonne « Ret. abs. »** | **768 430** (avant : 0) |
| Base du mois | 981 570 |

### 5.2 Solde de tout compte faux alors que l'état de paie est juste
**Constat.** Le net du STC ne correspondait pas au dernier bulletin affiché dans l'état de paie.

**Cause.** Le STC reprend le **net du dernier bulletin**. Deux défauts :
1. Si le bulletin du mois de sortie avait été calculé **avant** la rupture, c'était un bulletin de **mois complet**, et le STC le reprenait tel quel.
2. Quand la paie était recalculée **après** la rupture (prorata, mise à pied, retards), le STC en brouillon n'était **pas mis à jour** et gardait l'ancien montant.

**Correction.**
- Le STC ne reprend que le bulletin **calculé avec la rupture** (`inclus_stc = 1`). À défaut, il utilise la formule de secours `base × jours travaillés / 30`.
- À chaque (re)calcul d'une période, les STC encore en **brouillon** des employés sortis sont **recalculés automatiquement**.
- La page du STC indique la source du « salaire proratisé » (net du bulletin n°… ou formule de secours).
- Un STC `VALIDE` ou `PAIE` reste **immuable** (RG-TRANS-02).

Vérification : STC avant recalcul de la paie **1 166 667** (formule de secours) ; après recalcul de la paie **999 251**,
identique au net du bulletin de l'employé. Avec la mise à pied de 3 jours en plus : **862 051**, là aussi identique au bulletin.

**Formule du STC (rappel).**
```
STC net = salaire proratisé (net du dernier bulletin)
        + indemnité de congés (jours restants × base / 30)
        + indemnité de rupture (selon le motif, voir section 3)
        − avances non remboursées
        − retenues saisies à la main
```
