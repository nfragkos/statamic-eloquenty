# Eloquenty Statamic Addon

[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)  

:warning: **Currently in BETA. please do not use in production environments.** :warning:

This package allows you to store entries for specific collections to the database via Laravel's Eloquent ORM.

**Structures are disabled for performance reasons.**

## Requirements

- PHP 7.4+
- Statamic v3

## How it works

Eloquenty uses Statamic Eloquent Driver (https://github.com/statamic/eloquent-driver) but with modifications that allows to use the 
driver for specific collections entries instead for every entry. 

![Screenshot 2021-01-27 162333](https://user-images.githubusercontent.com/11143495/106163609-09a72300-6192-11eb-9d04-8b67a405eb33.png)
Both standard and Eloquenty collections will be visible under `/cp/collections` but the Entries column will display the message 
"Managed by Eloquenty" for Eloquenty collections.

![Screenshot 2021-01-27 162410](https://user-images.githubusercontent.com/11143495/106163445-d82e5780-6191-11eb-84ad-7c9207e8baf8.png)
Eloquenty collections are managed on a separate route `/cp/eloquenty/collections` which is a clone of `/cp/collections` route group 
with modifications to use the Entry and EntryRepository classes under Eloquenty\Entries that uses Eloquent ORM. The Entry related 
classes and Controllers are extending the original Statamic classes.

There's a middleware that redirects from `/cp/collections/{collection}` to `/cp/eloquenty/collections/{collection}` when clicking to 
view Eloquenty collections under `/cp/collections`.

Collection modifications should be performed under the original `/cp/collections` route.



## Installation

You can install this package via composer using:

```bash
composer require nfragkos/eloquenty
```

The package will automatically register itself.

## Post-Install

1. Publish eloquenty config and migration files:

    `php artisan vendor:publish --provider="Eloquenty\ServiceProvider"`


2. Run `php artisan migrate` to create Eloquenty table.  
   (Make sure laravel is configured properly and schema is created. See https://laravel.com/docs/8.x/database#configuration)  


3. Create a new collection or enter existing collection handle in config/eloquenty.php:
    
    ```bash
    'collections' => [
        'blog',
    ],
    ```

    This means that the entries for blog collection will be handled by Eloquenty.


4. If you entered an existing collection to Eloquenty config, there's an import command to import entries to the database:

    ```bash
    php artisan eloquenty:import-entries blog
    ```

   Please delete your collection entries from the filesystem after you verify that entries are imported successfully.

## Usage

Eloquenty now should be visible now under Content section of the navigation menu in Control Panel. CRUD for Eloquenty collections works
the same way as standard collections. 

### Antlers

In Antlers templates use the `eloquenty` tag instead of the collection tag for Eloquenty collections:

```
{{ eloquenty from="blog" limit="10" taxonomy:tags="demo|example" sort="date:desc" }}
    <a href="{{ url }}">{{ title }}</a>
{{ /eloquenty }}
```

### Data retrieval and manipulation

Calling the queryEloquentyEntries() method of Collection repository (**not the existing queryEntries() method**), will return the 
correct QueryBuilder for Eloquenty collections:
```
\Statamic\Facades\Collection::find('blog')->queryEloquentyEntries()
```
Alternatively you can use the Eloquenty's Facade repository() method to retrieve the instance of EntryRepository 
(implements `Statamic\Contracts\Entries\EntryRepository`):
```
Eloquenty\Facades\Eloquenty::repository()
```
**Keep in mind that calling the whereCollection() method will fetch all entries in the table. You should use the query() method 
instead that returns the query builder instance to build your query.**

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Pull requests are welcome and will be fully credited.

1. Fork the repo and create your branch from `main` branch.
3. Update the README file if needed.
5. Make sure your code follows the PSR-2 coding standard.
6. Issue that pull request!

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits
Many thanks to Statamic for the Eloquent driver and the awesome CMS :heart:
