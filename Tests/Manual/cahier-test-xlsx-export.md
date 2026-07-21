# Cahier de test — Export XLSX

**Extension** : `qc_info_rights`
**Instance** : pgu.pgu-local
**Prérequis** :
- phpoffice/phpspreadsheet installé (`composer require phpoffice/phpspreadsheet`)
- TSconfig `mod.qcinforights.showExportXlsxUsers = 1` et `mod.qcinforights.showExportXlsxGroups = 1` activés
- Au moins 3 utilisateurs BE et 2 groupes BE existants
- Un utilisateur BE avec accents dans le nom (ex: « André Côté »)

---

## A — Configuration et activation

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| A1 | Sans TSconfig (valeur par défaut) | Boutons « Export XLSX » absents des onglets Users BE et Groups BE | ☐ |
| A2 | Avec showExportXlsxUsers = 1 mais sans phpspreadsheet installé | Bouton « Export XLSX » absent de l'onglet Users BE | ☐ |
| A3 | Avec showExportXlsxGroups = 1 mais sans phpspreadsheet installé | Bouton « Export XLSX » absent de l'onglet Groups BE | ☐ |
| A4 | Avec showExportXlsxUsers = 1 ET phpspreadsheet installé | Bouton « Export XLSX » visible dans l'onglet Users BE | ☐ |
| A5 | Avec showExportXlsxGroups = 1 ET phpspreadsheet installé | Bouton « Export XLSX » visible dans l'onglet Groups BE | ☐ |
| A6 | User TSconfig override page TSconfig (showExportXlsxUsers = 0 en user) | Bouton masqué malgré page TSconfig = 1 | ☐ |

---

## B — Export XLSX Utilisateurs BE

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| B1 | Cliquer sur « Export XLSX » dans l'onglet Users BE | Téléchargement d'un fichier .xlsx | ☐ |
| B2 | Vérifier le nom du fichier | Format `Backend-user-list-Export-YYYY-MM-DD_HH-mm.xlsx` | ☐ |
| B3 | Ouvrir le fichier dans Excel/LibreOffice | 8 colonnes : UID, Username, Full Name, Email, Last Login, Is Hidden, Is Admin, Creation Date | ☐ |
| B4 | Vérifier les données | Mêmes utilisateurs que dans la liste affichée à l'écran | ☐ |
| B5 | Vérifier les en-têtes en français (backend FR) | En-têtes en français | ☐ |
| B6 | Vérifier les en-têtes en anglais (backend EN) | En-têtes en anglais | ☐ |
| B7 | Vérifier un nom avec accents (ex: André Côté) | Accents préservés correctement (UTF-8) | ☐ |
| B8 | Vérifier la colonne Last Login | « Never » ou date formatée correctement | ☐ |
| B9 | Vérifier la colonne Is Hidden | Oui/Non (ou Yes/No en anglais) | ☐ |

---

## C — Export XLSX Groupes BE

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| C1 | Cliquer sur « Export XLSX » dans l'onglet Groups BE | Téléchargement d'un fichier .xlsx | ☐ |
| C2 | Vérifier le nom du fichier | Format `Backend-user-group-list-Export-YYYY-MM-DD_HH-mm.xlsx` | ☐ |
| C3 | Ouvrir le fichier | 4 colonnes : UID, Title, Description, Is Hidden | ☐ |
| C4 | Vérifier les données | Mêmes groupes que dans la liste affichée | ☐ |
| C5 | Vérifier un titre de groupe avec accents | Accents préservés (UTF-8) | ☐ |

---

## D — Filtres et export

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| D1 | Filtrer par username puis exporter XLSX | Seuls les utilisateurs filtrés dans le XLSX | ☐ |
| D2 | Filtrer par email puis exporter XLSX | Seuls les utilisateurs filtrés dans le XLSX | ☐ |
| D3 | Cocher « Masquer les inactifs » puis exporter XLSX | Pas d'utilisateurs inactifs dans le XLSX | ☐ |
| D4 | TSconfig showAdministratorUser = 0, exporter XLSX | Pas d'administrateurs dans le XLSX | ☐ |

---

## E — Dégradation gracieuse

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| E1 | Désinstaller phpspreadsheet (`composer remove phpoffice/phpspreadsheet`) | Boutons XLSX disparaissent sans erreur PHP | ☐ |
| E2 | Réinstaller phpspreadsheet | Boutons XLSX réapparaissent | ☐ |

---

## F — Coexistence CSV et XLSX

| # | Action | Résultat attendu | Statut |
|---|--------|-------------------|--------|
| F1 | Avec CSV et XLSX activés | Les deux boutons (CSV et XLSX) sont visibles côte à côte | ☐ |
| F2 | Cliquer sur CSV | Export CSV fonctionne normalement | ☐ |
| F3 | Cliquer sur XLSX | Export XLSX fonctionne normalement | ☐ |
| F4 | Désactiver XLSX (TSconfig = 0) | Seul le bouton CSV reste visible | ☐ |

---

## Résultat global

- [ ] Section A — Configuration et activation
- [ ] Section B — Export XLSX Utilisateurs BE
- [ ] Section C — Export XLSX Groupes BE
- [ ] Section D — Filtres et export
- [ ] Section E — Dégradation gracieuse
- [ ] Section F — Coexistence CSV et XLSX

---

## Notes pour le testeur

- Le XLSX conserve les données en UTF-8 (pas de conversion iso-8859-15 comme le CSV)
- Les filtres appliqués dans l'interface s'appliquent aussi à l'export XLSX
- Le bouton n'apparaît que si DEUX conditions sont remplies : TSconfig = 1 ET phpspreadsheet installé
- La configuration TSconfig par défaut est 0 (désactivé) pour les deux options XLSX
- Les options existantes CSV (showExportUsers, showExportGroups) ne sont pas affectées
