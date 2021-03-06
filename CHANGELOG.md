# Changelog

All notable changes will be documented in this file.

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
