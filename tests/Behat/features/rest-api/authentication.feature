Feature:
  As a developer I want to be able to verify that the rest api is protected by authentication.

  @logout
  Scenario: View documentation without authenticating
    Given I add "Accept" header equal to "text/html"
    And I send a "GET" request to "/api/v2"
    Then the response status code should be 200
    And the header "Content-Type" should be equal to "text/html; charset=UTF-8"

  @logout
  Scenario: Trying to access the api without auth should not be possible
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value         |
      | identifiers | 8788711829100 |
      | type        | isbn          |
    Then the response status code should be 401

  @createFixtures @login
  Scenario: Trying to access the api with proper credentials should be allowed
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value         |
      | identifiers | 8788711829100 |
      | type        | isbn          |
    Then the response status code should be 200
