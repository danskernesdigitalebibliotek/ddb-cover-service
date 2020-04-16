Feature:
  As a developer I want to get one cover by type and ID in specific image format(s),
  specific image size(s) and with or without generic covers.

  # We only create schema and add test data once because we have to do "wait(1)" after
  # adding search entries to give elasticsearch time to build the index

  @createFixtures
  Scenario: Build and index test data
    Given the following search entries exists:
      | identifier            | type  | url                               | autogenerated | image_format | width | height |
      | 9788711829100         | isbn  | http://test.com/9788711829100.jpg | true          | jpeg         | 1000  | 2000   |
      | 55126216              | faust | http://test.com/55126216.jpg      | false         | jpeg         | 1000  | 2000   |
      | 870970-basis:52182794 | pid   | http://test.com/55126216.jpg      | false         | jpeg         | 1000  | 2000   |
    And I send a "GET" request to "/api/cover/isbn/9788711829100"
    Then the response status code should be 200

  Scenario Outline: Get single cover by type and identifier
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/cover/<type>/<identifier>"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
    And the response should be in JSON
    And the JSON node "id" should be equal to "<identifier>"
    And the JSON node "type" should be equal to "<type>"

    Examples:
      | identifier            | type  |
      | 9788711829100         | isbn  |
      | 55126216              | faust |
      | 870970-basis:52182794 | pid   |

  Scenario Outline: Unknown single cover should return 404
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/cover/<type>/<identifier>"
    Then the response status code should be 404

    Examples:
      | identifier              | type  |
      | 8788711829199           | isbn  |
      | 8788711829199           | isbn  |
      | 11112222                | faust |
      | 33334444                | faust |
      | 870970-basis:12345678   | pid   |
      | 870970-katalog:12345678 | pid   |
