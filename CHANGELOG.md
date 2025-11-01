# Changelog

All notable changes will be documented in this file.

## 3.0.3 - 2025-11-01

- Eloqunty Collections UI fixes (added missing Collection actions).
  
## 3.0.2 - 2025-10-30

- Updated composer.json for Laravel 12 support.
- Collections listing url fix.

## 3.0.1 - 2024-08-28

- Fix CollectionsController@index not compatible with parent: Add $request parameter to index method (thanks @dryven-daniel)
- Don't validate unique uri if slugs are not required: For collections without slugs, the slug validation fails. This commit allows these type of entries to pass the validation (thanks @dryven-daniel)

## 3.0.0 - 2024-05-21

- Statamic v5 support

## 3.0.0 - 2024-05-21

- Statamic v5 support
  
## 2.0.4 - 2024-02-27

- fix validateUniqueUri function

## 2.0.3 - 2023-12-18

- Statamic v4.41 compatibility fixes

## 2.0.2 - 2023-08-16

- Statamic v4.16 compatibility fixes

## 2.0.1 - 2023-07-31

- Fix Create not saving the entries with Eloquenty

## 2.0.0 - 2023-07-27

- Changes for Statamic v4.13.1+
- Stable release

## 2.0.0-beta.5 - 2023-07-04

- Laravel 10 support

## 2.0.0-beta.4 - 2023-06-19

- Minor bug fixes (thanks @Kakoushias )

## 2.0.0-beta.3 - 2023-06-16

- Collection queryEntries() method will now return the correct query builder for eloquenty collections.
- Added EloquentyEntry Facade

## 2.0.0-beta.2 - 2023-05-17

- Eloquenty entries field fixes

## 2.0.0-beta.1 - 2023-05-17

- Statamic v4 support
- Added asset references update for db entries (can be turned off via config option: 'update_references' => false)

## 1.3.2 - 2023-04-28

- Fixes for entries create and edit (default values, permissions, autosave)

## 1.3.1 - 2022-10-30

- Fixed EntryModel to keep the id if it is defined on creating event (fixes import command)

## 1.3.0 - 2022-08-01

- Migrated changes from Statamic v3.3.24 (Thanks @v-subz3r0)
- Requires Statamic >=v3.3.24

## 1.2.0 - 2022-07-05

- Migrated fixes from Statamic v3.3.17
- Requires Statamic >=v3.3.17

## 1.1.9 - 2022-05-20

Fix EntryQueryBuilder's paginate() not compatible with EloquentQueryBuilder (thanks @dryven)

## 1.1.8 - 2022-05-10

- Collections Entry Listing: Respect user-defined sort_by and sort_dir (thanks @dryven)

## 1.1.7 - 2022-04-08

- Let Statamic handle the lastModified() result (thanks @ddm-studio) 

## 1.1.6 - 2022-03-18

- Updated composer.json for Laravel 9 support

## 1.1.5 - 2022-02-05

- Fixed PHP 7.4 support (thanks tomhelmer)

## 1.1.4 - 2022-01-27

- Fixes compatibility with Statamic v3.2.32

## 1.1.3 - 2022-01-26

- Fixed PHP 7.4 support (thanks tomhelmer)

## 1.1.2 - 2022-01-02

- Fixes editing eloquenty entries when using a custom cp route

## 1.1.1 - 2021-12-21

- Migrated fixes and improvements from Statamic v3.2.27

## 1.1.0 - 2021-10-25

- Added Eloquenty Entries field type (similar to Entries field type) for creating relationships with eloquenty entries.

## 1.0.1 - 2021-10-15

- Migrated fixes from Statamic v3.2.14:
  - Collection entry counts are site specific. #4424 by jasonvarga

## 1.0.0 - 2021-09-24

- Stable release

## 1.0.0.rc.1 - 2021-07-09

  - Improvements and fixes from statamic/eloquent-driver:v0.1.1
  - Enabled EntrySaving event

## 0.9.0.beta.10 - 2021-07-08

  - Fixed delete and bulk actions.

## 0.9.0.beta.9 - 2021-06-30

  - Fix Entry saving events

## 0.9.0.beta.8 - 2021-06-09

  - Enabled live preview for Eloquenty entries

## 0.9.0.beta.6 - 2021-04-13

  - More permission fixes

## 0.9.0.beta.5 - 2021-04-13

  - Fixed create permission

## 0.9.0.beta.4 - 2021-04-12

  - Including origin on taxonomy filtering for Eloquenty collections

## 0.9.0.beta.3 - 2021-03-24

  - Improved collection-view component init (redirecting route "statamic.cp.collections.entries.index" to 
    "eloquenty.collections.entries.index" for Eloquenty Collections)

## 0.9.0.beta.2 - 2021-03-24

  - Enabled EntrySaving and EntrySaved events
  - Fix error when attempting to get entry origin if the entry is new and not saved (EntrySaving event listener).

## 0.9.0.beta.1 - 2021-01-27

  - Initial beta release
