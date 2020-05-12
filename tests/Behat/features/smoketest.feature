Feature:
  As a developer I want to be able to test that my behat test setup works. Including
  being able to reset and pupulate both database and elasticsearch schema.

  Scenario: View documentation
    And I send a "GET" request to "/api/v2"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "text/html; charset=UTF-8"

  @createFixtures
  Scenario: View single cover
    Given the following search entries exists:
      | identifiers | type | url                                                                                   | image_format | width | height |
      | 1234        | isbn | https://res.cloudinary.com/dandigbib/image/upload/v1589166346/bogportalen.dk/1234.jpg | jpeg         | 1000  | 2000   |
    And I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value |
      | identifiers | 1234  |
      | type        | isbn  |
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "application/json; charset=utf-8"
