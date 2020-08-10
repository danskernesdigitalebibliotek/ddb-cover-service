Feature:
  As a developer I want to get multiple covers by type and ID in specific image format(s),
  specific image size(s) and with or without generic covers.

  # We only create schema and add test data once pr. feature because we have to do "wait(1)"
  # after adding search entries to give elasticsearch time to build the index

  @createFixtures @login
  Scenario: Build and index test data
    Given the following search entries exists:
      | identifiers             | type  | url                                                                                            | image_format | width | height |
      | 9788711829100           | isbn  | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/9788711829100.jpg | jpeg         | 1000  | 2000   |
      | 9788711829101           | isbn  | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/9788711829101.jpg | jpeg         | 1000  | 2000   |
      | 9788711829102           | isbn  | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/9788711829102.jpg | jpeg         | 1000  | 2000   |
      | 55126216                | faust | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/55126216.jpg      | jpeg         | 1000  | 2000   |
      | 65126216                | faust | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/55126216.jpg      | jpeg         | 1000  | 2000   |
      | 75126216                | faust | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/75126216.jpg      | jpeg         | 1000  | 2000   |
      | 870970-basis:52182794   | pid   | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/55126216.jpg      | jpeg         | 1000  | 2000   |
      | 870970-katalog:52182794 | pid   | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/55126216.jpg      | jpeg         | 1000  | 2000   |
      | 870970-basis:52182796   | pid   | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/55126216.jpg      | jpeg         | 1000  | 2000   |
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value         |
      | identifiers | 9788711829100 |
      | type        | isbn          |
    Then the response status code should be 200

  @login
  Scenario Outline: Get multiple covers by type and identifier
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value         |
      | identifiers | <identifiers> |
      | type        | <type>        |
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "[0].id" should be equal to "<identifier1>"
    And the JSON node "[1].id" should be equal to "<identifier2>"
    And the JSON node "[2].id" should be equal to "<identifier3>"
    And the JSON node "[0].type" should be equal to "<type>"
    And the JSON node "[1].type" should be equal to "<type>"
    And the JSON node "[2].type" should be equal to "<type>"
    And the JSON node "root" should have 3 elements

    Examples:
      | identifiers                                                         | type  | identifier1           | identifier2           | identifier3             |
      | 8788711829100,9788711829100,9788711829101,9788711829102             | isbn  | 9788711829100         | 9788711829101         | 9788711829102           |
      | 45126216,55126216,65126216,75126216                                 | faust | 55126216              | 65126216              | 75126216                |
      | 870970-basis:52182794,870970-katalog:52182794,870970-basis:52182796 | pid   | 870970-basis:52182794 | 870970-basis:52182796 | 870970-katalog:52182794 |

  @login
  Scenario Outline: Search for unknown covers should return an ampty list
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value                |
      | identifiers | <unknownIdentifiers> |
      | type        | <type>               |
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "root" should have 0 elements

    Examples:
      | unknownIdentifiers                                      | type  |
      | 9788711829200,9788711829201,9788711829202               | isbn  |
      | 8788711829100,9788711829100,9788711829101,9788711829102 | pid   |
      | 45126200,55126200,65126200,75126200                     | faust |
      | 970970-basis:52182794,970970-katalog:52182794           | faust |

  # APP_API_MAX_IDENTIFIERS is 5 for .env.test
  @login
  Scenario: I should get a 400 bad request if i send to many identifiers
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value                                                                               |
      | identifiers | 9780119135640,9799913633580,9792806497771,9781351129428,9798058560423,9789318143272 |
      | type        | isbn                                                                                |
    Then the response status code should be 400

  @login
  Scenario: I should get a 400 bad request if i send an empty sizes parameter
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value                       |
      | identifiers | 9780119135640,9799913633580 |
      | type        | isbn                        |
      | sizes       |                             |
    Then the response status code should be 400

  @login
  Scenario: I should get a 400 bad request if i request unknown sizes
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value                       |
      | identifiers | 9780119135640,9799913633580 |
      | type        | isbn                        |
      | sizes       | unknown                     |
    Then the response status code should be 400