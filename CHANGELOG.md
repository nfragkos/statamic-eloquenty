# Changelog

All notable changes will be documented in this file.

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
