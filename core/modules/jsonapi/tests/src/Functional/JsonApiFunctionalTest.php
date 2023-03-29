<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\node\Entity\Node;

/**
 * General functional test class.
 *
 * @group jsonapi
 *
 * @internal
 */
class JsonApiFunctionalTest extends JsonApiFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the GET method.
   */
  public function testRead() {
    $this->createDefaultContent(61, 5, TRUE, TRUE, static::IS_NOT_MULTILINGUAL, FALSE);
    // Unpublish the last entity, so we can check access.
    $this->nodes[60]->setUnpublished()->save();

    // Different databases have different sort orders, so a sort is required so
    // test expectations do not need to vary per database.
    $default_sort = ['sort' => 'drupal_internal__nid'];

    // 0. HEAD request allows a client to verify that JSON:API is installed.
    $this->httpClient->request('HEAD', $this->buildUrl('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);
    // 1. Load all articles (1st page).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(OffsetPage::SIZE_MAX, $collection_output['data']);
    $this->assertSession()
      ->responseHeaderEquals('Content-Type', 'application/vnd.api+json');
    // 2. Load all articles (Offset 3).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['offset' => 3]] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(OffsetPage::SIZE_MAX, $collection_output['data']);
    $this->assertStringContainsString('page%5Boffset%5D=53', $collection_output['links']['next']['href']);
    // 3. Load all articles (1st page, 2 items)
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['limit' => 2]] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(2, $collection_output['data']);
    // 4. Load all articles (2nd page, 2 items).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page' => [
          'limit' => 2,
          'offset' => 2,
        ],
      ] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(2, $collection_output['data']);
    $this->assertStringContainsString('page%5Boffset%5D=4', $collection_output['links']['next']['href']);
    // 5. Single article.
    $uuid = $this->nodes[0]->uuid();
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertEquals($this->nodes[0]->getTitle(), $single_output['data']['attributes']['title']);

    // 5.1 Single article with access denied because unauthenticated.
    Json::decode($this->drupalGet('/jsonapi/node/article/' . $this->nodes[60]->uuid()));
    $this->assertSession()->statusCodeEquals(401);

    // 5.1 Single article with access denied while authenticated.
    $this->drupalLogin($this->userCanViewProfiles);
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $this->nodes[60]->uuid()));
    $this->assertSession()->statusCodeEquals(403);
    $this->assertEquals('/data', $single_output['errors'][0]['source']['pointer']);
    $this->drupalLogout();

    // 6. Single relationship item.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/node_type'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertArrayNotHasKey('attributes', $single_output['data']);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 7. Single relationship image.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/field_image'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data']);
    $this->assertArrayNotHasKey('attributes', $single_output['data']);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 8. Multiple relationship item.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/field_tags'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertArrayHasKey('type', $single_output['data'][0]);
    $this->assertArrayNotHasKey('attributes', $single_output['data'][0]);
    $this->assertArrayHasKey('related', $single_output['links']);
    // 8b. Single related item, empty.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/field_heroless'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertNull($single_output['data']);
    // 9. Related tags with includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/field_tags', [
      'query' => ['include' => 'vid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('taxonomy_term--tags', $single_output['data'][0]['type']);
    $this->assertArrayNotHasKey('tid', $single_output['data'][0]['attributes']);
    $this->assertStringContainsString(
      '/taxonomy_term/tags/',
      $single_output['data'][0]['links']['self']['href']
    );
    $this->assertEquals(
      'taxonomy_vocabulary--taxonomy_vocabulary',
      $single_output['included'][0]['type']
    );
    // 10. Single article with includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid, [
      'query' => ['include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('node--article', $single_output['data']['type']);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'user--user',
      $first_include['type']
    );
    $last_include = end($single_output['included']);
    $this->assertEquals(
      'taxonomy_term--tags',
      $last_include['type']
    );

    // 10b. Single article with nested includes.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid, [
      'query' => ['include' => 'field_tags,field_tags.vid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('node--article', $single_output['data']['type']);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'taxonomy_term--tags',
      $first_include['type']
    );
    $last_include = end($single_output['included']);
    $this->assertEquals(
      'taxonomy_vocabulary--taxonomy_vocabulary',
      $last_include['type']
    );

    // 11. Includes with relationships.
    $this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/uid');
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/' . $uuid . '/relationships/uid', [
      'query' => ['include' => 'uid'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals('user--user', $single_output['data']['type']);
    $this->assertArrayHasKey('related', $single_output['links']);
    $this->assertArrayHasKey('included', $single_output);
    $first_include = reset($single_output['included']);
    $this->assertEquals(
      'user--user',
      $first_include['type']
    );
    $this->assertNotEmpty($first_include['attributes']);
    $this->assertArrayNotHasKey('mail', $first_include['attributes']);
    $this->assertArrayNotHasKey('pass', $first_include['attributes']);
    // 12. Collection with one access denied.
    $this->nodes[1]->set('status', FALSE);
    $this->nodes[1]->save();
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['page' => ['limit' => 2]] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(1, $single_output['data']);
    $non_help_links = array_filter(array_keys($single_output['meta']['omitted']['links']), function ($key) {
      return $key !== 'help';
    });
    $this->assertCount(1, $non_help_links);
    $link_keys = array_keys($single_output['meta']['omitted']['links']);
    $this->assertSame('help', reset($link_keys));
    $this->assertMatchesRegularExpression('/^item--[a-zA-Z0-9]{7}$/', next($link_keys));
    $this->nodes[1]->set('status', TRUE);
    $this->nodes[1]->save();
    // 13. Test filtering when using short syntax.
    $filter = [
      'uid.id' => ['value' => $this->user->uuid()],
      'field_tags.id' => ['value' => $this->tags[0]->uuid()],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThan(0, count($single_output['data']));
    // 14. Test filtering when using long syntax.
    $filter = [
      'and_group' => ['group' => ['conjunction' => 'AND']],
      'filter_user' => [
        'condition' => [
          'path' => 'uid.id',
          'value' => $this->user->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
      'filter_tags' => [
        'condition' => [
          'path' => 'field_tags.id',
          'value' => $this->tags[0]->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'uid,field_tags'],
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThan(0, count($single_output['data']));
    // 15. Test filtering when using invalid syntax.
    $filter = [
      'and_group' => ['group' => ['conjunction' => 'AND']],
      'filter_user' => [
        'condition' => [
          'name-with-a-typo' => 'uid.id',
          'value' => $this->user->uuid(),
          'memberOf' => 'and_group',
        ],
      ],
    ];
    $this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]);
    $this->assertSession()->statusCodeEquals(400);
    // 16. Test filtering on the same field.
    $filter = [
      'or_group' => ['group' => ['conjunction' => 'OR']],
      'filter_tags_1' => [
        'condition' => [
          'path' => 'field_tags.id',
          'value' => $this->tags[0]->uuid(),
          'memberOf' => 'or_group',
        ],
      ],
      'filter_tags_2' => [
        'condition' => [
          'path' => 'field_tags.id',
          'value' => $this->tags[1]->uuid(),
          'memberOf' => 'or_group',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter, 'include' => 'field_tags'] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(2, count($single_output['included']));
    // 17. Single user (check fields lacking 'view' access).
    $user_url = Url::fromRoute('jsonapi.user--user.individual', [
      'entity' => $this->user->uuid(),
    ]);
    $response = $this->request('GET', $user_url, [
      'auth' => [
        $this->userCanViewProfiles->getAccountName(),
        $this->userCanViewProfiles->pass_raw,
      ],
    ]);
    $single_output = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('user--user', $single_output['data']['type']);
    $this->assertEquals($this->user->get('name')->value, $single_output['data']['attributes']['name']);
    $this->assertArrayNotHasKey('mail', $single_output['data']['attributes']);
    $this->assertArrayNotHasKey('pass', $single_output['data']['attributes']);
    // 18. Test filtering on the column of a link.
    $filter = [
      'linkUri' => [
        'condition' => [
          'path' => 'field_link.uri',
          'value' => 'https://',
          'operator' => 'STARTS_WITH',
        ],
      ],
    ];
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(1, count($single_output['data']));
    // 19. Test non-existing route without 'Accept' header.
    $this->drupalGet('/jsonapi/node/article/broccoli');
    $this->assertSession()->statusCodeEquals(404);
    // Even without the 'Accept' header the 404 error is formatted as JSON:API.
    $this->assertSession()->responseHeaderEquals('Content-Type', 'application/vnd.api+json');
    // 20. Test non-existing route with 'Accept' header.
    $single_output = Json::decode($this->drupalGet('/jsonapi/node/article/broccoli', [], [
      'Accept' => 'application/vnd.api+json',
    ]));
    $this->assertEquals(404, $single_output['errors'][0]['status']);
    $this->assertSession()->statusCodeEquals(404);
    // With the 'Accept' header we can know we want the 404 error formatted as
    // JSON:API.
    $this->assertSession()->responseHeaderContains('Content-Type', 'application/vnd.api+json');
    // 22. Test sort criteria on multiple fields: both ASC.
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page[limit]' => 6,
        'sort' => 'field_sort1,field_sort2',
      ],
    ]));
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(6, $output_uuids);
    $this->assertSame([
      Node::load(5)->uuid(),
      Node::load(4)->uuid(),
      Node::load(3)->uuid(),
      Node::load(2)->uuid(),
      Node::load(1)->uuid(),
      Node::load(10)->uuid(),
    ], $output_uuids);
    // 23. Test sort criteria on multiple fields: first ASC, second DESC.
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page[limit]' => 6,
        'sort' => 'field_sort1,-field_sort2',
      ],
    ]));
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(6, $output_uuids);
    $this->assertSame([
      Node::load(1)->uuid(),
      Node::load(2)->uuid(),
      Node::load(3)->uuid(),
      Node::load(4)->uuid(),
      Node::load(5)->uuid(),
      Node::load(6)->uuid(),
    ], $output_uuids);
    // 24. Test sort criteria on multiple fields: first DESC, second ASC.
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page[limit]' => 6,
        'sort' => '-field_sort1,field_sort2',
      ],
    ]));
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(5, $output_uuids);
    $this->assertCount(2, $output['meta']['omitted']['links']);
    $this->assertSame([
      Node::load(60)->uuid(),
      Node::load(59)->uuid(),
      Node::load(58)->uuid(),
      Node::load(57)->uuid(),
      Node::load(56)->uuid(),
    ], $output_uuids);
    // 25. Test sort criteria on multiple fields: both DESC.
    $output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => [
        'page[limit]' => 6,
        'sort' => '-field_sort1,-field_sort2',
      ],
    ]));
    $output_uuids = array_map(function ($result) {
      return $result['id'];
    }, $output['data']);
    $this->assertCount(5, $output_uuids);
    $this->assertCount(2, $output['meta']['omitted']['links']);
    $this->assertSame([
      Node::load(56)->uuid(),
      Node::load(57)->uuid(),
      Node::load(58)->uuid(),
      Node::load(59)->uuid(),
      Node::load(60)->uuid(),
    ], $output_uuids);
    // 25. Test collection count.
    $this->container->get('module_installer')->install(['jsonapi_test_collection_count']);
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertEquals(61, $collection_output['meta']['count']);
    $this->container->get('module_installer')->uninstall(['jsonapi_test_collection_count']);

    // Test documentation filtering examples.
    // 1. Only get published nodes.
    $filter = [
      'status-filter' => [
        'condition' => [
          'path' => 'status',
          'value' => 1,
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 2. Nested Filters: Get nodes created by user admin.
    $filter = [
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 3. Filtering with arrays: Get nodes created by users [admin, john].
    $filter = [
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'operator' => 'IN',
          'value' => [
            $this->user->getAccountName(),
            $this->getRandomGenerator()->name(),
          ],
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 4. Grouping filters: Get nodes that are published and create by admin.
    $filter = [
      'and-group' => [
        'group' => [
          'conjunction' => 'AND',
        ],
      ],
      'name-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
          'memberOf' => 'and-group',
        ],
      ],
      'status-filter' => [
        'condition' => [
          'path' => 'status',
          'value' => 1,
          'memberOf' => 'and-group',
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertGreaterThanOrEqual(OffsetPage::SIZE_MAX, count($collection_output['data']));
    // 5. Grouping grouped filters: Get nodes that are promoted or sticky and
    //    created by admin.
    $filter = [
      'and-group' => [
        'group' => [
          'conjunction' => 'AND',
        ],
      ],
      'or-group' => [
        'group' => [
          'conjunction' => 'OR',
          'memberOf' => 'and-group',
        ],
      ],
      'admin-filter' => [
        'condition' => [
          'path' => 'uid.name',
          'value' => $this->user->getAccountName(),
          'memberOf' => 'and-group',
        ],
      ],
      'sticky-filter' => [
        'condition' => [
          'path' => 'sticky',
          'value' => 1,
          'memberOf' => 'or-group',
        ],
      ],
      'promote-filter' => [
        'condition' => [
          'path' => 'promote',
          'value' => 0,
          'memberOf' => 'or-group',
        ],
      ],
    ];
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article', [
      'query' => ['filter' => $filter] + $default_sort,
    ]));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(0, $collection_output['data']);

    // Request in maintenance mode returns valid JSON.
    $this->container->get('state')->set('system.maintenance_mode', TRUE);
    $response = $this->drupalGet('/jsonapi/taxonomy_term/tags');
    $this->assertSession()->statusCodeEquals(503);
    $this->assertSession()->responseHeaderContains('Content-Type', 'application/vnd.api+json');
    $retry_after_time = $this->getSession()->getResponseHeader('Retry-After');
    $this->assertTrue($retry_after_time >= 5 && $retry_after_time <= 10);
    $expected_message = 'Drupal is currently under maintenance. We should be back shortly. Thank you for your patience.';
    $this->assertSame($expected_message, Json::decode($response)['errors'][0]['detail']);

    // Test that logged in user does not get logged out in maintenance mode
    // when hitting jsonapi route.
    $this->container->get('state')->set('system.maintenance_mode', FALSE);
    $this->drupalLogin($this->userCanViewProfiles);
    $this->container->get('state')->set('system.maintenance_mode', TRUE);
    $this->drupalGet('/jsonapi/taxonomy_term/tags');
    $this->assertSession()->statusCodeEquals(503);
    $this->assertTrue($this->drupalUserIsLoggedIn($this->userCanViewProfiles));
    // Test that user gets logged out when hitting non-jsonapi route.
    $this->drupalGet('/some/normal/route');
    $this->assertFalse($this->drupalUserIsLoggedIn($this->userCanViewProfiles));
    $this->container->get('state')->set('system.maintenance_mode', FALSE);

    // Test that admin user can bypass maintenance mode.
    $admin_user = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin_user);
    $this->container->get('state')->set('system.maintenance_mode', TRUE);
    $this->drupalGet('/jsonapi/taxonomy_term/tags');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertTrue($this->drupalUserIsLoggedIn($admin_user));
    $this->container->get('state')->set('system.maintenance_mode', FALSE);
    $this->drupalLogout();
  }

  /**
   * Tests the GET method on articles referencing the same tag twice.
   */
  public function testReferencingTwiceRead() {
    $this->createDefaultContent(1, 1, FALSE, FALSE, static::IS_NOT_MULTILINGUAL, TRUE);

    // 1. Load all articles (1st page).
    $collection_output = Json::decode($this->drupalGet('/jsonapi/node/article'));
    $this->assertSession()->statusCodeEquals(200);
    $this->assertCount(1, $collection_output['data']);
    $this->assertSession()
      ->responseHeaderEquals('Content-Type', 'application/vnd.api+json');
  }

  /**
   * Tests POST, PATCH and DELETE.
   */
  public function testWrite() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    $this->createDefaultContent(0, 3, FALSE, FALSE, static::IS_NOT_MULTILINGUAL, FALSE);
    // 1. Successful post.
    $collection_url = Url::fromRoute('jsonapi.node--article.collection.post');
    $body = [
      'data' => [
        'type' => 'node--article',
        'attributes' => [
          'langcode' => 'en',
          'title' => 'My custom title',
          'default_langcode' => '1',
          'body' => [
            'value' => 'Custom value',
            'format' => 'plain_text',
            'summary' => 'Custom summary',
          ],
        ],
        'relationships' => [
          'field_tags' => [
            'data' => [
              [
                'type' => 'taxonomy_term--tags',
                'id' => $this->tags[0]->uuid(),
              ],
              [
                'type' => 'taxonomy_term--tags',
                'id' => $this->tags[1]->uuid(),
              ],
            ],
          ],
        ],
      ],
    ];
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(201, $response->getStatusCode());
    $this->assertArrayNotHasKey('uuid', $created_response['data']['attributes']);
    $uuid = $created_response['data']['id'];
    $this->assertCount(2, $created_response['data']['relationships']['field_tags']['data']);
    $this->assertEquals($created_response['data']['links']['self']['href'], $response->getHeader('Location')[0]);

    // 2. Authorization error.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(401, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Unauthorized', $created_response['errors'][0]['title']);

    // 2.1 Authorization error with a user without create permissions.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->userCanViewProfiles->getAccountName(), $this->userCanViewProfiles->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Forbidden', $created_response['errors'][0]['title']);

    // 3. Missing Content-Type error.
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Accept' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(415, $response->getStatusCode());

    // 4. Article with a duplicate ID.
    $invalid_body = $body;
    $invalid_body['data']['id'] = Node::load(1)->uuid();
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($invalid_body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(409, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Conflict', $created_response['errors'][0]['title']);
    // 5. Article with wrong reference UUIDs for tags.
    $body_invalid_tags = $body;
    $body_invalid_tags['data']['relationships']['field_tags']['data'][0]['id'] = 'lorem';
    $body_invalid_tags['data']['relationships']['field_tags']['data'][1]['id'] = 'ipsum';
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($body_invalid_tags),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(404, $response->getStatusCode());
    // 6. Decoding error.
    $response = $this->request('POST', $collection_url, [
      'body' => '{"bad json",,,}',
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(400, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertEquals('Bad Request', $created_response['errors'][0]['title']);
    // 6.1 Denormalizing error.
    $response = $this->request('POST', $collection_url, [
      'body' => '{"data":{"type":"something"},"valid yet nonsensical json":[]}',
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertStringStartsWith('Unprocessable', $created_response['errors'][0]['title']);
    // 6.2 Relationships are not included in "data".
    $malformed_body = $body;
    unset($malformed_body['data']['relationships']);
    $malformed_body['relationships'] = $body['data']['relationships'];
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($malformed_body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode((string) $response->getBody());
    $this->assertSame(400, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertSame("Bad Request", $created_response['errors'][0]['title']);
    $this->assertSame("Found \"relationships\" within the document's top level. The \"relationships\" key must be within resource object.", $created_response['errors'][0]['detail']);
    // 6.2 "type" not included in "data".
    $missing_type = $body;
    unset($missing_type['data']['type']);
    $response = $this->request('POST', $collection_url, [
      'body' => Json::encode($missing_type),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
      ],
    ]);
    $created_response = Json::decode((string) $response->getBody());
    $this->assertSame(400, $response->getStatusCode());
    $this->assertNotEmpty($created_response['errors']);
    $this->assertSame("Bad Request", $created_response['errors'][0]['title']);
    $this->assertSame("Resource object must include a \"type\".", $created_response['errors'][0]['detail']);
    // 7. Successful PATCH.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => ['title' => 'My updated title'],
      ],
    ];
    $individual_url = Url::fromRoute('jsonapi.node--article.individual', [
      'entity' => $uuid,
    ]);
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertEquals('My updated title', $updated_response['data']['attributes']['title']);

    // 7.1 Unsuccessful PATCH due to access restrictions.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => ['title' => 'My updated title'],
      ],
    ];
    $individual_url = Url::fromRoute('jsonapi.node--article.individual', [
      'entity' => $uuid,
    ]);
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->userCanViewProfiles->getAccountName(), $this->userCanViewProfiles->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(403, $response->getStatusCode());

    // 8. Field access forbidden check.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'title' => 'My updated title',
          'status' => 0,
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(403, $response->getStatusCode());
    $this->assertEquals("The current user is not allowed to PATCH the selected field (status). The 'administer nodes' permission is required.",
      $updated_response['errors'][0]['detail']);

    $node = \Drupal::service('entity.repository')->loadEntityByUuid('node', $uuid);
    $this->assertEquals(1, $node->get('status')->value, 'Node status was not changed.');
    // 9. Successful POST to related endpoint.
    $body = [
      'data' => [
        [
          'id' => $this->tags[2]->uuid(),
          'type' => 'taxonomy_term--tags',
        ],
      ],
    ];
    $relationship_url = Url::fromRoute('jsonapi.node--article.field_tags.relationship.post', [
      'entity' => $uuid,
    ]);
    $response = $this->request('POST', $relationship_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(200, $response->getStatusCode());
    $this->assertCount(3, $updated_response['data']);
    $this->assertEquals('taxonomy_term--tags', $updated_response['data'][2]['type']);
    $this->assertEquals($this->tags[2]->uuid(), $updated_response['data'][2]['id']);
    // 10. Successful PATCH to related endpoint.
    $body = [
      'data' => [
        [
          'id' => $this->tags[1]->uuid(),
          'type' => 'taxonomy_term--tags',
        ],
      ],
    ];
    $response = $this->request('PATCH', $relationship_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $this->assertEmpty($response->getBody()->__toString());
    // 11. Successful DELETE to related endpoint.
    $response = $this->request('DELETE', $relationship_url, [
      // Send a request with no body.
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(
      'You need to provide a body for DELETE operations on a relationship (field_tags).',
      $updated_response['errors'][0]['detail']
    );
    $this->assertEquals(400, $response->getStatusCode());
    $response = $this->request('DELETE', $relationship_url, [
      // Send a request with no authentication.
      'body' => Json::encode($body),
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(401, $response->getStatusCode());
    $response = $this->request('DELETE', $relationship_url, [
      // Remove the existing relationship item.
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => ['Content-Type' => 'application/vnd.api+json'],
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $this->assertEmpty($response->getBody()->__toString());
    // 12. PATCH with invalid title and body format.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'title' => '',
          'body' => [
            'value' => 'Custom value',
            'format' => 'invalid_format',
            'summary' => 'Custom summary',
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertCount(2, $updated_response['errors']);
    for ($i = 0; $i < 2; $i++) {
      $this->assertStringStartsWith('Unprocessable', $updated_response['errors'][$i]['title']);
      $this->assertEquals(422, $updated_response['errors'][$i]['status']);
    }
    $this->assertEquals("title: This value should not be null.", $updated_response['errors'][0]['detail']);
    $this->assertEquals("body.0.format: The value you selected is not a valid choice.", $updated_response['errors'][1]['detail']);
    $this->assertEquals("/data/attributes/title", $updated_response['errors'][0]['source']['pointer']);
    $this->assertEquals("/data/attributes/body/format", $updated_response['errors'][1]['source']['pointer']);
    // 13. PATCH with field that doesn't exist on Entity.
    $body = [
      'data' => [
        'id' => $uuid,
        'type' => 'node--article',
        'attributes' => [
          'field_that_does_not_exist' => 'foobar',
        ],
      ],
    ];
    $response = $this->request('PATCH', $individual_url, [
      'body' => Json::encode($body),
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ]);
    $updated_response = Json::decode($response->getBody()->__toString());
    $this->assertEquals(422, $response->getStatusCode());
    $this->assertEquals("The attribute field_that_does_not_exist does not exist on the node--article resource type.",
      $updated_response['errors']['0']['detail']);
    // 14. Successful DELETE.
    $response = $this->request('DELETE', $individual_url, [
      'auth' => [$this->user->getAccountName(), $this->user->pass_raw],
    ]);
    $this->assertEquals(204, $response->getStatusCode());
    $response = $this->request('GET', $individual_url, []);
    $this->assertEquals(404, $response->getStatusCode());
  }

}
