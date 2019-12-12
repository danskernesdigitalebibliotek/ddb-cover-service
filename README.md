# DDB Cover Service

This is the codebase for the [DDB Cover
Service](https://cover.dandigbib.org/api). The service provides an API to search
for cover images for library materials. Search input must be a known identifier
type such as 'isbn', 'pid', 'faust', etc. and one or more actual ids. Response
is a list of cover image URLs by id, format and size.

# License

Copyright (C) 2018  Danskernes Digitale Bibliotek (DDB)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU Affero General Public License as
published by the Free Software Foundation, either version 3 of the
License.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.

You should have received a copy of the GNU Affero General Public License
along with this program. If not, see [https://www.gnu.org/licenses/](https://www.gnu.org/licenses/).

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

External Services:

* [Cloudinary](https://cloudinary.com/) is used as CDN and transformation engine
  for all cover images
* [Open Search](https://www.dbc.dk/produkter-services/webservices/open-search)
  is used for mapping between common ids (isbn etc.) and library specific id's
  such as 'pid' and 'faust'

## Architecture Overview

The application consists of two logical parts:

* A web facing REST API powered by the ElasticSearch index
* An CLI based image import/index/upload engine that handles import, indexing
  and uploading of cover images from external providers.

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

The overall flow of the consist of import -> upload -> index:

1. For each Vendor the full list of available materials is read. Each found
   material is saved as `Source` and a `ProcessMessage` is generated with
   `VendorImageTopic` and `id => image URL`
2. Each image URL is validated and it's verified that the remote image
   exists. If the image is found the `ProcessMessage` is forwarded with a
   `CoverStoreTopic` and `Source` is updated with relevant metadata.
3. Each image is added to Cloudinary through their API. This enables us to just
   instruct Cloudinary to fetch the image from the image URL and add to the
   Media Library. An `Image` is created containing Cloudinary metadata and an
   `ProcessMessage` with `SearchTopic`is sent.
4. A search is made in Open Search to determine what id 'aliases' the image
   should be indexed under. We know the ISxx from the Vendor but to build index
   entries for PID and FAUST we need to match these through Open Search. For
   each id a new `Search` entry is made which is automatically synced to
   ElasticSearch.

#### Vendors

The application needs to import covers from a number of different vendors
through their exposed access protocols. This means we need to support various
strategies such as crawling zip-archives via ftp, parsing excel files and
accessing APIs. Individual `VendorServices` are defined for each vendor to
support their respective data access. These all extend
`AbstractBaseVendorService` were common functionality needed by the importers is
defined.

All vendor implementations are located under `/src/Service/VendorService/*`

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
(https://github.com/aakb/itkdev-docker/tree/develop/scripts). It's not an requirement and the setup examples below is 
without the script. The script just makes working with docker simpler and faster. 

#### Running docker setup

Start the stack.
```
docker-compose up --detach
```

Access the site using the command blow to get the port number and append it to this URL `http://0.0.0.0:<PORT>` in your
browser.
```
docker-compose port nginx 80 | cut -d: -f2
```

All the symfony commands below to install the application can be executed using this pattern.
```
docker-compose exec phpfpm bin/console <CMD>
```

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

## Development

### Code style

The project follows the [PSR2](https://www.php-fig.org/psr/psr-2/) and
[Symfony](https://symfony.com/doc/current/contributing/code/standards.html) code
styles. The PHP CS Fixer tool is installed automatically. To check if your code
matches the expected code syntax you can run `composer php-cs-check`, to fix
code style errors you can run `composer php-cs-fix`

### Tests

The application has a test suite consisting of unit tests and Behat features.

* To run the unit tests located in `/tests` you can run `vendor/bin/phpunit`
* To run the Behat features in `/feature` you can run `vendor/bin/behat`

Both bugfixes and added features should be supported by matching tests.

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

### Commands

To simplify testing during development test console commands are defined for the
various services defined in the application.  These are described for each
service in the "Services" section of this document. Using these commands you can
manually test each service in isolation without having to run one or more
message queues.

#### OpenPlatform Authentication

Simple command to test that the authentication service is working and has the
correct configuration in the environment.

```sh
bin/console app:openplatform:auth
```

#### OpenPlatform Search

This command runs a search into the open platform datawell. The last parameter
if set will by-pass the cache. The command will output the search result.

```sh
bin/console app:openplatform:search 9788702173277 isbn
```

#### Cover Store

This command will upload an image into the cover store.

```sh
bin/console app:cover:upload <IMAGE URL> <FOLDER> <TAG(s)>
```

#### Vendors

This runs the importer for the configured vendors. The command will prompt for
which vendors to import.

```sh
bin/console app:vendor:load
```

Please note:
To ensure that the command run with a "flat" memory foot print in production
you must run it with `--no-debug` in the `prod` environment.

Production
```sh
bin/console app:vendor:load --env=prod --no-debug
```

Note: For some Vendors proper access credentials need to be set in the database
before running an import. To populate the `Vendor` table you can run

```sh
bin/console app:vendor:populate
```

This will create an entry for each defined vendor service that extends
`AbstractBaseVendorService`. However you must manually add the relevant
credentials to each row in the database.

#### Vendor event

This command will fire an insert event an place an job into the message queue
system that will import an image into Cover Store and update the search index.

```sh
bin/console app:vendor:event insert 9788702173277 ISBN 1
```

### Message queues

The application defines a number of job queues for the various background tasks
and is configured to use Redis as the persistence layer for queues/messages. To
have a fully functioning development setup you will need to run consumers for
all queues. Alternatively you can choose to only run select queues or to inspect
directly in Redis that messages are persisted there using the Redis CLI or any
available Redis client.

To run consumers for all queues do

```sh
bin/console enqueue:consume --env=prod --setup-broker --quiet --receive-timeout 5000 default
bin/console enqueue:consume --env=prod --quiet --receive-timeout 5000 CoverStoreQueue
bin/console enqueue:consume --env=prod --quiet --receive-timeout 5000 SearchQueue
bin/console enqueue:consume --env=prod --quiet --receive-timeout 5000 BackgroundQueue
```

Please note that:

* you must always run the broker even if your only need to run a consumer for
  one queue.
* without the `--receive-timeout 5000` option the CPU load with Redis gets very
  high as it polls Redis all the time (given a 40% load for each queue).

#### Possible other consumer options

```sh
--message-limit=MESSAGE-LIMIT      Consume n messages and exit
--time-limit=TIME-LIMIT            Consume messages during this time
--memory-limit=MEMORY-LIMIT        Consume messages until process reaches this memory limit in MB
--niceness=NICENESS
```
