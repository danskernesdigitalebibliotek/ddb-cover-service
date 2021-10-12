# DDB Cover Service

This is the codebase for the [DDB Cover
Service](https://cover.dandigbib.org/api). The service provides an API to search
for cover images for library materials. Search input must be a known identifier
type such as 'isbn', 'pid', 'faust', etc. and one or more actual ids. Response
is a list of cover image URLs by id, format and size.

## Tech Stack

This is a Symfony 4 (flex) project based on the [Api-platform
framework](https://github.com/api-platform/api-platform).  Please see the
[Api-platform documentation](https://api-platform.com/docs/) for a basic
understanding of concepts and structure.

Server/hosting reference requirements: _PHP 7.2, Nginx 1.14, MariaDB 10.2,
ElasticSearch 6.5, Redis Server 3.2, Kibana 6.x._

The application is currently developed and hosted on this stack. However, the
individual components can be swapped for relevant alternatives. Apache can be
used instead of Nginx. Any database supported by Doctrine DBAL such as MySQL or
PostgreSQL can replace MariaDB. Redis is used as both caching layer for Symfony
and persistence layer for Enqueue. Both support multiple other persistence
layers such as memcache and RabbitMQ, respectively, and can be changed as
needed.

Application components:

* [Symfony 4 (flex)](https://symfony.com/) - underlying Web Application
  framework
* [Doctrine 2](https://www.doctrine-project.org/) - database DBAL/ORM layer
* [Api-platform](https://github.com/api-platform/api-platform) - REST and
  GraphQL API framework
* [Enqueue](https://github.com/php-enqueue/enqueue-dev) - Message Queue, Job
  Queue packages for PHP, Symfony

## Architecture Overview

The application consists of two logical parts:

* A web facing REST API powered by the ElasticSearch index

### Messaging

For performance reasons both parts are designed around a messaging-based
architecture to allow for asynchronous handling of tasks.  For the API this
means that any task not strictly needed for the response such as various logging
tasks are deferred and handled after the request/response. For the import engine
only the initial read from source is done synchronously. For each imported cover
image individual index and upload jobs are created and run later.

### Services and Dependency Injection

All internal functionality is defined as individual services. These are
autowired through dependency injection by [Symfony's Service
Container](https://symfony.com/doc/current/service_container.html)

### Persistence

The import engine defines a number of entities for storing relevant data on
imports and images. These are mapped to and persisted in the database through
doctrine. Further a 'search' entity is defined with the fields exposed by the
REST API. This entity is mapped one-to-one to an index in ElasticSearch.

### Logging

We use [Kibana](https://www.elastic.co/products/kibana) for logging. All
relevant events and errors are logged to enable usage monitoring and debugging.

## Implementation Overview

### REST API

The API functionality is built on
[api-platform](https://github.com/api-platform/api-platform) and adapted to our
specific API design and performance requirements. To define and expose the
defined API, relevant data transfer objects (DTO) are defined for each of the id
types we support. We use a different 'list' format than api-platform for
submitting multiple values for the same parameter. To enable this and to support
searching directly in ElasticSearch and bypass the database custom [data
providers](https://api-platform.com/docs/core/data-providers/)
(`/src/Api/DataProvider/*`) and
[filters](https://api-platform.com/docs/core/filters/) (`/src/Api/Filter/*`)
are defined. All other custom functionality related to the REST API is also
defined under `/src/Api`.

A test suite for the REST API is defined as Behat features under `/features`.

### Import/Index/Upload Engine

See [Cover Service Importers](https://github.com/danskernesdigitalebibliotek/ddb-cover-service-importers)

#### Services

The application defines a number of internal services for the various
tasks. These are autowired through dependency injection by [Symfony's Service
Container](https://symfony.com/doc/current/service_container.html)

##### CoverStore

Abstracts [Cloudinarys Upload
API](https://cloudinary.com/documentation/image_upload_api_reference)
functionality into a set of helper methods for upload, delete and
generate. "Generate" will create a generic cover based on a default image.

##### OpenPlatform

Implements authentication and search against [Open
Search](https://www.dbc.dk/produkter-services/webservices/open-search)

##### Vendor Services

Common functionality for all Vendor importers is shared in
`AbstractBaseVendorService`. Individual importers are defined for each vendor to
contain the import logic for the vendors specific access setup
(FTP/Spreadsheet/API etc).

## Development Setup

### Docker compose

The project comes with a docker-compose setup base on development only images, that comes with all required extensions
to PHP (including xdebug) and all services required to run the application.

For easy usage it's recommended to use træfik (proxy) and the wrapper script for docker-compose used at ITKDev
(<https://github.com/aakb/itkdev-docker/tree/develop/scripts>). It's not an requirement and the setup examples below is
without the script. The script just makes working with docker simpler and faster.

### Install

We assume you have a working local/vagrant/docker web server setup with PHP,
Nginx, MariaDB, ElasticSearch and Redis.

1. Checkout the project code from GitHub and run `composer install` from the
   project root dir
2. Create a `/.env.local` file and define the relevant environment variables to
   match your setup
3. Run migrations `bin/console doctrine:migrations:migrate`
4. Create ES search index `bin/console fos:elastica:create`
5. Run `vendor/bin/phpunit` and `vendor/bin/behat` to ensure your test suite is
   working.

API is now exposed at `http://<servername>/api`

### Fixtures

To add test data to the database and elastic index you can run the database
fixtures command.  Run `bin/console doctrine:fixtures:load` to populate the
database with random data.

### Doctrine Migrations

The project uses [Doctrine
Migrations](https://symfony.com/doc/master/bundles/DoctrineMigrationsBundle/index.html)
to handle updates to the database schema. Any changes to the schema should have
a matching migration. If you make changes to the entity model you should run
`bin/console doctrine:migrations:diff` to generate a migration with the
necessary `sql` statements. Review the migration before executing it with
`bin/console doctrine:migrations:migrate`

After changes to the entity model and migrations always run `bin/console
doctrine:schema:validate` to ensure that mapping is correct and database schema
is in sync with the current mapping file(s).

### Testing

The application has a test suite consisting of unit tests and Behat features.

To run the unit tests located in `/tests` you can run:

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/phpunit
```

To run the Behat features in `/feature` you can run:

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/behat
```

Both bugfixes and added features should be supported by matching tests.

### Psalm static analysis

We are using [Psalm](https://psalm.dev/) for static analysis. To run
psalm do

```shell
docker compose exec phpfpm composer install
docker compose exec phpfpm ./vendor/bin/psalm
```

### Check Coding Standard

The following command let you test that the code follows
the coding standard for the project.

* PHP files (PHP-CS-Fixer)

    ```shell
    docker compose exec phpfpm composer check-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:14 install
    docker run -v ${PWD}:/app itkdev/yarn:14 check-coding-standards
    ```

### Apply Coding Standards

To attempt to automatically fix coding style

* PHP files (PHP-CS-Fixer)

    ```sh
    docker compose exec phpfpm composer apply-coding-standards
    ```

* Markdown files (markdownlint standard rules)

    ```shell
    docker run -v ${PWD}:/app itkdev/yarn:14 install
    docker run -v ${PWD}:/app itkdev/yarn:14 apply-coding-standards
    ```

## CI

Github Actions are used to run the test suite and code style checks on all PR's.

If you wish to test against the jobs locally you can install [act](https://github.com/nektos/act).
Then do:

```sh
act -P ubuntu-latest=shivammathur/node:latest pull_request
```

## Versioning

We use [SemVer](http://semver.org/) for versioning.
For the versions available, see the
[tags on this repository](https://github.com/itk-dev/openid-connect/tags).

## License

This project is licensed under the AGPL-3.0 License - see the
[LICENSE.md](LICENSE.md) file for details
