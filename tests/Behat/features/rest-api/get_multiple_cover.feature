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

  @login
  Scenario:
    Given I add "Accept" header equal to "application/json"
    And I send a "GET" request to "/api/v2/covers" with parameters:
      | key         | value                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       |
      | identifiers | 9780119135640,9799913633580,9792806497771,9781351129428,9798058560423,9789318143272,9781363766970,9780776119267,9785305863321,9797259832100,9784314182416,9787549592371,9796110511918,9794912216925,9796535193638,9798060049046,9791500575716,9797841395211,9799053014874,9780160280818,9795749775357,9782877382922,9791632157705,9795760029958,9796380655879,9781296108052,9793216217782,9785172200731,9793771763809,9780806932651,9789218173089,9782681752713,9792878978673,9780701153649,9793043797587,9799856157358,9791356148546,9791071997696,9784570700102,9795730895620,9790446545999,9792662315905,9780580342752,9784586248315,9796081530796,9783569112025,9799976434445,9789305298916,9790734490710,9793986051524,9799474392681,9791816018051,9794788433525,9796814907789,9781746789909,9784655730642,9791510682923,9792584515759,9798130993941,9788619208567,9787721331644,9787807326373,9782438167166,9787367323409,9785650361633,9787457808373,9792954429365,9784870263819,9797354232126,9788419062734,9785750810383,9790374405693,9786207246885,9795939298239,9798098213525,9792515253019,9796603764449,9785126588502,9789756912133,9788191316506,9787108861672,9787272682165,9780248592321,9787171767222,9781527865143,9788456644139,9795747727716,9797431817055,9795682026165,9796230010599,9798978909593,9795740212516,9794154071641,9795730489522,9788636118597,9781721573721,9787077314711,9798781880140,9783116074943,9794563897818,9782451161936,9798961106558,9780049744202,9798502548076,9787598276727,9796610289300,9780428845995,9789578424029,9783004691849,9784089239483,9793478206814,9798346612520,9780898150209,9796899670813,9786101319609,9790631646982,9782460998158,9785848188332,9787567997431,9790788259363,9794799235514,9780207527593,9786238602889,9787391117548,9783246023262,9787218770901,9796328729464,9799364813494,9790163108378,9789333950121,9798435888522,9796782947619,9791025216910,9793679954774,9793230032743,9791820896898,9794726180498,9794855890596,9784959058619,9791351271201,9788690868865,9788114879088,9788833256115,9780664026035,9789904465795,9797673053433,9794856736923,9785567060292,9781387940615,9788318575359,9795008727349,9782646381910,9799925186654,9791065977567,9799342017319,9787831030772,9790407707756,9783106288725,9781171674467,9780838797341,9791971485491,9792093696192,9797989470238,9781438249551,9783435751051,9782143027441,9783318384222,9785484538126,9784308709926,9786593070347,9781034899488,9781457622557,9794543050325,9799830597293,9788074142765,9788232161331,9798195788384,9797797573763,9792953488615,9785478715519,9794570037399,9783407056627,9787362456218,9794662420863,9786256967304,9793690287912,9789285402815,9788898145638,9793761235682,9786552968227,9784924634923,9787033924299,9794402257629,9797869397631,9782592150004,9794964641676,9798816167925,9782992434896,9783057917347,9790009928542,9785877109988,9791058728749 |
      | type        | isbn                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        |
    Then the response status code should be 400