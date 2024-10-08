# Info Rights

## Change log v2.0.4
### Bug Fix
- 2024-09-18 [BUGFIX] Solve tri issues for user backed info
- 2024-09-18 [BUGFIX] Solve route web_info issues
-

## Change log v2.0.3
### Bug Fix
- 2024-08-30 [!!!][BUGFIX] Fix configuration file.

## Change log v2.0.2
### Bug Fix
- 2024-08-26 [!!!][BUGFIX] Fix the "Show members" functionality in the Groups BE submodule.

## Change log v2.0.1
### Bug Fix
- 2024-05-27 [!!!][TASK] Solve warning message with module acces info and replace deprecated function

## Change log v2.0.0
### Breaking

- 2024-05-01 [FEATURE] New Typo3 admin module backend and not under the Info module of Typo3
- 2024-05-01 [FEATURE] Add support Typo3 version 12 LTS
- 2024-05-01 [!!!][TASK] Replace TsConfig showTabAccess,showTabUsers,showTabGroups and showMenuAccess,showMenuUsers, and showMenuGroups with the backend user group or the backend user module access


## Change log v1.2.7
### Feature
- 2023-04-07 [FEATURE]  Add and export "Crdate" column from the users list of qc_info_rights backend module

## Change log v1.2.6
### Bug Fix
- 2022-12-09 [BUGFIX]  Solve session handling bug for typo3 v11

## Change log v1.2.5
### Bug Fix
- 2022-08-05 [BUGFIX]  Solve authentification session problem by extending infoModuleController

## Change log v1.2.3
### Bug Fix

- 2022-05-20 [BUGFIX] Solve minor issue of redeclare function checking Ts config since are declared into cache file of ext_table
## Change log v1.2.3
### Bug Fix

- 2022-05-18 [BUGFIX] Solve minor issue to verify active session backend
## Change log v1.2.2
### Bug Fix

- 2022-05-18 [BUGFIX] Solve minor issue
## Change log v1.2.1
### Bug Fix

- 2022-05-18 [BUGFIX] Add missing user TsConfig to verify access to the context menu item
### Breaking

- 2022-05-16 [!!!][TASK]  Replace TsConfig showTabAccess,showTabUsers,showTabGroups with the new showMenuAccess,showMenuUsers, and showMenuGroups. The old TSconfig keyes will be removed in next update v 1.3.0


## Change log  v1.2.0
- 2022-05-16 [Feature] We replace the 2 tabs (user list and group list) with menu items.
- 2022-05-16 [!!!][TASK]  Replace TsConfig showTabAccess,showTabUsers,showTabGroups with the new showMenuAccess,showMenuUsers, and showMenuGroups. The old TSconfig keyes will be removed in next update
## Change log  v1.1.0
- 2022-01-13 [Feature] It is now possible to get the members of groups when checking the pages rights
