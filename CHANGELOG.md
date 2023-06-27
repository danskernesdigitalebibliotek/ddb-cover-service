<!-- markdownlint-configure-file { "blanks-around-headers": { "lines_below": 0 } } -->
<!-- markdownlint-configure-file { "blanks-around-lists": false } -->

# Changelog

![keep a changelog](https://img.shields.io/badge/Keep%20a%20Changelog-v1.0.0-brightgreen.svg?logo=data%3Aimage%2Fsvg%2Bxml%3Bbase64%2CPHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIGZpbGw9IiNmMTVkMzAiIHZpZXdCb3g9IjAgMCAxODcgMTg1Ij48cGF0aCBkPSJNNjIgN2MtMTUgMy0yOCAxMC0zNyAyMmExMjIgMTIyIDAgMDAtMTggOTEgNzQgNzQgMCAwMDE2IDM4YzYgOSAxNCAxNSAyNCAxOGE4OSA4OSAwIDAwMjQgNCA0NSA0NSAwIDAwNiAwbDMtMSAxMy0xYTE1OCAxNTggMCAwMDU1LTE3IDYzIDYzIDAgMDAzNS01MiAzNCAzNCAwIDAwLTEtNWMtMy0xOC05LTMzLTE5LTQ3LTEyLTE3LTI0LTI4LTM4LTM3QTg1IDg1IDAgMDA2MiA3em0zMCA4YzIwIDQgMzggMTQgNTMgMzEgMTcgMTggMjYgMzcgMjkgNTh2MTJjLTMgMTctMTMgMzAtMjggMzhhMTU1IDE1NSAwIDAxLTUzIDE2bC0xMyAyaC0xYTUxIDUxIDAgMDEtMTItMWwtMTctMmMtMTMtNC0yMy0xMi0yOS0yNy01LTEyLTgtMjQtOC0zOWExMzMgMTMzIDAgMDE4LTUwYzUtMTMgMTEtMjYgMjYtMzMgMTQtNyAyOS05IDQ1LTV6TTQwIDQ1YTk0IDk0IDAgMDAtMTcgNTQgNzUgNzUgMCAwMDYgMzJjOCAxOSAyMiAzMSA0MiAzMiAyMSAyIDQxLTIgNjAtMTRhNjAgNjAgMCAwMDIxLTE5IDUzIDUzIDAgMDA5LTI5YzAtMTYtOC0zMy0yMy01MWE0NyA0NyAwIDAwLTUtNWMtMjMtMjAtNDUtMjYtNjctMTgtMTIgNC0yMCA5LTI2IDE4em0xMDggNzZhNTAgNTAgMCAwMS0yMSAyMmMtMTcgOS0zMiAxMy00OCAxMy0xMSAwLTIxLTMtMzAtOS01LTMtOS05LTEzLTE2YTgxIDgxIDAgMDEtNi0zMiA5NCA5NCAwIDAxOC0zNSA5MCA5MCAwIDAxNi0xMmwxLTJjNS05IDEzLTEzIDIzLTE2IDE2LTUgMzItMyA1MCA5IDEzIDggMjMgMjAgMzAgMzYgNyAxNSA3IDI5IDAgNDJ6bS00My03M2MtMTctOC0zMy02LTQ2IDUtMTAgOC0xNiAyMC0xOSAzN2E1NCA1NCAwIDAwNSAzNGM3IDE1IDIwIDIzIDM3IDIyIDIyLTEgMzgtOSA0OC0yNGE0MSA0MSAwIDAwOC0yNCA0MyA0MyAwIDAwLTEtMTJjLTYtMTgtMTYtMzEtMzItMzh6bS0yMyA5MWgtMWMtNyAwLTE0LTItMjEtN2EyNyAyNyAwIDAxLTEwLTEzIDU3IDU3IDAgMDEtNC0yMCA2MyA2MyAwIDAxNi0yNWM1LTEyIDEyLTE5IDI0LTIxIDktMyAxOC0yIDI3IDIgMTQgNiAyMyAxOCAyNyAzM3MtMiAzMS0xNiA0MGMtMTEgOC0yMSAxMS0zMiAxMXptMS0zNHYxNGgtOFY2OGg4djI4bDEwLTEwaDExbC0xNCAxNSAxNyAxOEg5NnoiLz48L3N2Zz4K)

All notable changes to this project will be documented in this file.

See [keep a changelog](https://keepachangelog.com/en/1.0.0/) for information about writing changes to this log.

## [Unreleased]

## [4.2.6] - 2023-06-27

### Changed
- Downgraded API platform core to 2.7
- Fixed composer version to 2.x in image

## [4.2.5] - 2023-06-27

### Changed
- Downgraded API platform core to 2.6

## [4.2.4] - 2023-06-27

### Changed
- Nginx container entry point script update

## [4.2.3] - 2023-06-27

### Changed
- Added `generic` field to fixtures
- Updated ElasticSerach index definition to match importers

## [4.2.0] - 2023-02-13

### Changed
- Bumped version to update service

## [4.2.0] - 2022-11-10

### Changed
- Added agency and profile to no-hit messages based on request token.

## [4.1.0] - 2022-08-28

### Added
- Added support for new cover size transformations xs (104px), xxs (136px), sm (230px), ml (430px).

## [4.0.3] - 2022-08-17

### Changed
- Renamed no-hit enable processing variable name.

## [4.0.2] - 2022-08-17

### Changed
- Added missing symfony/amqp-messenger package.

## [4.0.1] - 2022-08-16

### Changed
- Updated metrics bundle.

## [4.0.0] - 2022-08-15

### Added
- Allow plugins section to composer.json.

### Changed
- Update rabbit MQ to 3.9 in composer setup.
- Update symfony/fex.
- Switched from behat tests to php-unit tests only.
- Updated to Symfony 6.1.
- Update Elastic .env variables to indexing to match imports.
- Update PHP version in docker builds.
- Update helm charts.
- Update nginx version (1.23) in docker builds.

## [3.0.5] - 2022-03-18

### Changed
- Updated base docker image for new build.

## [3.0.4] - 2022-03-18

### Changed
- Updated base docker image for new build.

## [3.0.3] - 2022-03-09

### Added
- Ensured properties are initialized in queue messages.

## [3.0.2] - 2022-01-08

### Changed
- Updated cert-manager version in docs til 1.7.1.
- Removed statics logging to ElasticSearch.
