<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\TranslatableInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\entity_test\Entity\EntityTestMapField;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\shortcut\Entity\Shortcut;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API regression tests.
 *
 * @group jsonapi
 *
 * @internal
 */
class JsonApiRegressionTest extends JsonApiFunctionalTestBase {

  use CommentTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'basic_auth',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensure filtering on relationships works with bundle-specific target types.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2953207
   */
  public function testBundleSpecificTargetEntityTypeFromIssue2953207() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['comment'], TRUE), 'Installed modules.');
    $this->addDefaultCommentField('taxonomy_term', 'tags', 'comment', CommentItemInterface::OPEN, 'tcomment');
    $this->rebuildAll();

    // Create data.
    Term::create([
      'name' => 'foobar',
      'vid' => 'tags',
    ])->save();
    Comment::create([
      'subject' => 'Llama',
      'entity_id' => 1,
      'entity_type' => 'taxonomy_term',
      'field_name' => 'comment',
    ])->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access comments',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/comment/tcomment?include=entity_id&filter[entity_id.name]=foobar'), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Ensure deep nested include works on multi target entity type field.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2973681
   */
  public function testDeepNestedIncludeMultiTargetEntityTypeFieldFromIssue2973681() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['comment'], TRUE), 'Installed modules.');
    $this->addDefaultCommentField('node', 'article');
    $this->addDefaultCommentField('taxonomy_term', 'tags', 'comment', CommentItemInterface::OPEN, 'tcomment');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->createEntityReferenceField(
      'node',
      'page',
      'field_comment',
      NULL,
      'comment',
      'default',
      [
        'target_bundles' => [
          'comment' => 'comment',
          'tcomment' => 'tcomment',
        ],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->rebuildAll();

    // Create data.
    $node = Node::create([
      'title' => 'test article',
      'type' => 'article',
    ]);
    $node->save();
    $comment = Comment::create([
      'subject' => 'Llama',
      'entity_id' => 1,
      'entity_type' => 'node',
      'field_name' => 'comment',
    ]);
    $comment->save();
    $page = Node::create([
      'title' => 'test node',
      'type' => 'page',
      'field_comment' => [
        'entity' => $comment,
      ],
    ]);
    $page->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access content',
      'access comments',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/page?include=field_comment,field_comment.entity_id,field_comment.entity_id.uid'), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Ensure POST and PATCH works for bundle-less relationship routes.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2976371
   */
  public function testBundlelessRelationshipMutationFromIssue2973681() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->createEntityReferenceField(
      'node',
      'page',
      'field_test',
      NULL,
      'user',
      'default',
      [
        'target_bundles' => NULL,
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->rebuildAll();

    // Create data.
    $node = Node::create([
      'title' => 'test article',
      'type' => 'page',
    ]);
    $node->save();
    $target = $this->createUser();

    // Test.
    $user = $this->drupalCreateUser(['bypass node access']);
    $url = Url::fromRoute('jsonapi.node--page.field_test.relationship.post', ['entity' => $node->uuid()]);
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          ['type' => 'user--user', 'id' => $target->uuid()],
        ],
      ],
    ];
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(204, $response->getStatusCode(), (string) $response->getBody());
  }

  /**
   * Ensures GETting terms works when multiple vocabularies exist.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2977879
   */
  public function testGetTermWhenMultipleVocabulariesExistFromIssue2977879() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['taxonomy'], TRUE), 'Installed modules.');
    Vocabulary::create([
      'name' => 'one',
      'vid' => 'one',
    ])->save();
    Vocabulary::create([
      'name' => 'two',
      'vid' => 'two',
    ])->save();
    $this->rebuildAll();

    // Create data.
    Term::create(['vid' => 'one'])
      ->setName('Test')
      ->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/taxonomy_term/one'), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Cannot PATCH an entity with dangling references in an ER field.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2968972
   */
  public function testDanglingReferencesInAnEntityReferenceFieldFromIssue2968972() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->drupalCreateContentType(['type' => 'journal_issue']);
    $this->drupalCreateContentType(['type' => 'journal_article']);
    $this->createEntityReferenceField(
      'node',
      'journal_article',
      'field_issue',
      NULL,
      'node',
      'default',
      [
        'target_bundles' => [
          'journal_issue' => 'journal_issue',
        ],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->rebuildAll();

    // Create data.
    $issue_node = Node::create([
      'title' => 'Test Journal Issue',
      'type' => 'journal_issue',
    ]);
    $issue_node->save();

    $user = $this->drupalCreateUser([
      'access content',
      'edit own journal_article content',
    ]);
    $article_node = Node::create([
      'title' => 'Test Journal Article',
      'type' => 'journal_article',
      'field_issue' => [
        'target_id' => $issue_node->id(),
      ],
    ]);
    $article_node->setOwner($user);
    $article_node->save();

    // Test.
    $url = Url::fromUri(sprintf('internal:/jsonapi/node/journal_article/%s', $article_node->uuid()));
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--journal_article',
          'id' => $article_node->uuid(),
          'attributes' => [
            'title' => 'My New Article Title',
          ],
        ],
      ],
    ];
    $issue_node->delete();
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
  }

  /**
   * Ensures GETting node collection + hook_node_grants() implementations works.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2984964
   */
  public function testGetNodeCollectionWithHookNodeGrantsImplementationsFromIssue2984964() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['node_access_test'], TRUE), 'Installed modules.');
    node_access_rebuild();
    $this->rebuildAll();

    // Create data.
    Node::create([
      'title' => 'test article',
      'type' => 'article',
    ])->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/article'), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertContains('user.node_grants:view', explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
  }

  /**
   * Cannot GET an entity with dangling references in an ER field.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2984647
   */
  public function testDanglingReferencesInAnEntityReferenceFieldFromIssue2984647() {
    // Set up data model.
    $this->drupalCreateContentType(['type' => 'journal_issue']);
    $this->drupalCreateContentType(['type' => 'journal_conference']);
    $this->drupalCreateContentType(['type' => 'journal_article']);
    $this->createEntityReferenceField(
      'node',
      'journal_article',
      'field_issue',
      NULL,
      'node',
      'default',
      [
        'target_bundles' => [
          'journal_issue' => 'journal_issue',
        ],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createEntityReferenceField(
      'node',
      'journal_article',
      'field_mentioned_in',
      NULL,
      'node',
      'default',
      [
        'target_bundles' => [
          'journal_issue' => 'journal_issue',
          'journal_conference' => 'journal_conference',
        ],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->rebuildAll();

    // Create data.
    $issue_node = Node::create([
      'title' => 'Test Journal Issue',
      'type' => 'journal_issue',
    ]);
    $issue_node->save();
    $conference_node = Node::create([
      'title' => 'First Journal Conference!',
      'type' => 'journal_conference',
    ]);
    $conference_node->save();

    $user = $this->drupalCreateUser([
      'access content',
      'edit own journal_article content',
    ]);
    $article_node = Node::create([
      'title' => 'Test Journal Article',
      'type' => 'journal_article',
      'field_issue' => [
        ['target_id' => $issue_node->id()],
      ],
      'field_mentioned_in' => [
        ['target_id' => $issue_node->id()],
        ['target_id' => $conference_node->id()],
      ],
    ]);
    $article_node->setOwner($user);
    $article_node->save();

    // Test.
    $url = Url::fromUri(sprintf('internal:/jsonapi/node/journal_article/%s', $article_node->uuid()));
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
    ];
    $issue_node->delete();
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());

    // Entity reference field allowing a single bundle: dangling reference's
    // resource type is deduced.
    $this->assertSame([
      [
        'type' => 'node--journal_issue',
        'id' => 'missing',
        'meta' => [
          'links' => [
            'help' => [
              'href' => 'https://www.drupal.org/docs/8/modules/json-api/core-concepts#missing',
              'meta' => [
                'about' => "Usage and meaning of the 'missing' resource identifier.",
              ],
            ],
          ],
        ],
      ],
    ], Json::decode((string) $response->getBody())['data']['relationships']['field_issue']['data']);

    // Entity reference field allowing multiple bundles: dangling reference's
    // resource type is NOT deduced.
    $this->assertSame([
      [
        'type' => 'unknown',
        'id' => 'missing',
        'meta' => [
          'links' => [
            'help' => [
              'href' => 'https://www.drupal.org/docs/8/modules/json-api/core-concepts#missing',
              'meta' => [
                'about' => "Usage and meaning of the 'missing' resource identifier.",
              ],
            ],
          ],
        ],
      ],
      [
        'type' => 'node--journal_conference',
        'id' => $conference_node->uuid(),
      ],
    ], Json::decode((string) $response->getBody())['data']['relationships']['field_mentioned_in']['data']);
  }

  /**
   * Ensures that JSON:API routes are caches are dynamically rebuilt.
   *
   * Adding a new relationship field should cause new routes to be immediately
   * regenerated. The site builder should not need to manually rebuild caches.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2984886
   */
  public function testThatRoutesAreRebuiltAfterDataModelChangesFromIssue2984886() {
    $user = $this->drupalCreateUser(['access content']);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ];

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/dog'), $request_options);
    $this->assertSame(404, $response->getStatusCode());

    $node_type_dog = NodeType::create(['type' => 'dog']);
    $node_type_dog->save();
    NodeType::create(['type' => 'cat'])->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/dog'), $request_options);
    $this->assertSame(200, $response->getStatusCode());

    $this->createEntityReferenceField('node', 'dog', 'field_test', NULL, 'node');
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $dog = Node::create(['type' => 'dog', 'title' => 'Rosie P. Mosie']);
    $dog->save();

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/dog/' . $dog->uuid() . '/field_test'), $request_options);
    $this->assertSame(200, $response->getStatusCode());

    $this->createEntityReferenceField('node', 'cat', 'field_test', NULL, 'node');
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $cat = Node::create(['type' => 'cat', 'title' => 'E. Napoleon']);
    $cat->save();

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/cat/' . $cat->uuid() . '/field_test'), $request_options);
    $this->assertSame(200, $response->getStatusCode());

    FieldConfig::loadByName('node', 'cat', 'field_test')->delete();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/cat/' . $cat->uuid() . '/field_test'), $request_options);
    $this->assertSame(404, $response->getStatusCode());

    $node_type_dog->delete();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/dog'), $request_options);
    $this->assertSame(404, $response->getStatusCode());
  }

  /**
   * Ensures denormalizing relationships with aliased field names works.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3007113
   * @see https://www.drupal.org/project/jsonapi_extras/issues/3004582#comment-12817261
   */
  public function testDenormalizeAliasedRelationshipFromIssue2953207() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Since the JSON:API module does not have an explicit mechanism to set up
    // field aliases, create a strange data model so that automatic aliasing
    // allows us to test aliased relationships.
    // @see \Drupal\jsonapi\ResourceType\ResourceTypeRepository::getFieldMapping()
    $internal_relationship_field_name = 'type';
    $public_relationship_field_name = 'taxonomy_term_' . $internal_relationship_field_name;

    // Set up data model.
    $this->createEntityReferenceField(
      'taxonomy_term',
      'tags',
      $internal_relationship_field_name,
      NULL,
      'user'
    );
    $this->rebuildAll();

    // Create data.
    Term::create([
      'name' => 'foobar',
      'vid' => 'tags',
      'type' => ['target_id' => 1],
    ])->save();

    // Test.
    $user = $this->drupalCreateUser([
      'edit terms in tags',
    ]);
    $body = [
      'data' => [
        'type' => 'user--user',
        'id' => User::load(0)->uuid(),
      ],
    ];

    // Test.
    $response = $this->request('PATCH', Url::fromUri(sprintf('internal:/jsonapi/taxonomy_term/tags/%s/relationships/%s', Term::load(1)->uuid(), $public_relationship_field_name)), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
      ],
      RequestOptions::BODY => Json::encode($body),
    ]);
    $this->assertSame(204, $response->getStatusCode());
  }

  /**
   * Ensures that Drupal's page cache is effective.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3009596
   */
  public function testPageCacheFromIssue3009596() {
    $anonymous_role = Role::load(RoleInterface::ANONYMOUS_ID);
    $anonymous_role->grantPermission('access content');
    $anonymous_role->trustData()->save();

    NodeType::create(['type' => 'emu_fact'])->save();
    \Drupal::service('router.builder')->rebuildIfNeeded();

    $node = Node::create([
      'type' => 'emu_fact',
      'title' => "Emus don't say moo!",
    ]);
    $node->save();

    $request_options = [
      RequestOptions::HEADERS => ['Accept' => 'application/vnd.api+json'],
    ];
    $node_url = Url::fromUri('internal:/jsonapi/node/emu_fact/' . $node->uuid());

    // The first request should be a cache MISS.
    $response = $this->request('GET', $node_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('MISS', $response->getHeader('X-Drupal-Cache')[0]);

    // The second request should be a cache HIT.
    $response = $this->request('GET', $node_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame('HIT', $response->getHeader('X-Drupal-Cache')[0]);
  }

  /**
   * Ensures that filtering by a sequential internal ID named 'id' is possible.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3015759
   */
  public function testFilterByIdFromIssue3015759() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['shortcut'], TRUE), 'Installed modules.');
    $this->rebuildAll();

    // Create data.
    $shortcut = Shortcut::create([
      'shortcut_set' => 'default',
      'title' => $this->randomMachineName(),
      'weight' => -20,
      'link' => [
        'uri' => 'internal:/user/logout',
      ],
    ]);
    $shortcut->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access shortcuts',
      'customize shortcut links',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/shortcut/default?filter[drupal_internal__id]=' . $shortcut->id()), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertNotEmpty($doc['data']);
    $this->assertSame($doc['data'][0]['id'], $shortcut->uuid());
    $this->assertSame($doc['data'][0]['attributes']['drupal_internal__id'], (int) $shortcut->id());
    $this->assertSame($doc['data'][0]['attributes']['title'], $shortcut->label());
  }

  /**
   * Ensures datetime fields are normalized using the correct timezone.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/2999438
   */
  public function testPatchingDateTimeNormalizedWrongTimeZoneIssue3021194() {
    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['datetime'], TRUE), 'Installed modules.');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->rebuildAll();
    FieldStorageConfig::create([
      'field_name' => 'when',
      'type' => 'datetime',
      'entity_type' => 'node',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ])
      ->save();
    FieldConfig::create([
      'field_name' => 'when',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])
      ->save();

    // Create data.
    $page = Node::create([
      'title' => 'Stegosaurus',
      'type' => 'page',
      'when' => [
        'value' => '2018-09-16T12:00:00',
      ],
    ]);
    $page->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/page/' . $page->uuid()), [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ]);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertSame('2018-09-16T22:00:00+10:00', $doc['data']['attributes']['when']);
  }

  /**
   * Ensures PATCHing datetime (both date-only & date+time) fields is possible.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3021194
   */
  public function testPatchingDateTimeFieldsFromIssue3021194() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['datetime'], TRUE), 'Installed modules.');
    $this->drupalCreateContentType(['type' => 'page']);
    $this->rebuildAll();
    FieldStorageConfig::create([
      'field_name' => 'when',
      'type' => 'datetime',
      'entity_type' => 'node',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATE],
    ])
      ->save();
    FieldConfig::create([
      'field_name' => 'when',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])
      ->save();
    FieldStorageConfig::create([
      'field_name' => 'when_exactly',
      'type' => 'datetime',
      'entity_type' => 'node',
      'settings' => ['datetime_type' => DateTimeItem::DATETIME_TYPE_DATETIME],
    ])
      ->save();
    FieldConfig::create([
      'field_name' => 'when_exactly',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])
      ->save();

    // Create data.
    $page = Node::create([
      'title' => 'Stegosaurus',
      'type' => 'page',
      'when' => [
        'value' => '2018-12-19',
      ],
      'when_exactly' => [
        'value' => '2018-12-19T17:00:00',
      ],
    ]);
    $page->save();

    // Test.
    $user = $this->drupalCreateUser([
      'access content',
      'edit any page content',
    ]);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
    ];
    $node_url = Url::fromUri('internal:/jsonapi/node/page/' . $page->uuid());
    $response = $this->request('GET', $node_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertSame('2018-12-19', $doc['data']['attributes']['when']);
    $this->assertSame('2018-12-20T04:00:00+11:00', $doc['data']['attributes']['when_exactly']);
    $doc['data']['attributes']['when'] = '2018-12-20';
    $doc['data']['attributes']['when_exactly'] = '2018-12-19T19:00:00+01:00';
    $request_options = $request_options + [RequestOptions::JSON => $doc];
    $response = $this->request('PATCH', $node_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertSame('2018-12-20', $doc['data']['attributes']['when']);
    $this->assertSame('2018-12-20T05:00:00+11:00', $doc['data']['attributes']['when_exactly']);
  }

  /**
   * Ensure includes are respected even when POSTing.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3026030
   */
  public function testPostToIncludeUrlDoesNotReturnIncludeFromIssue3026030() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->rebuildAll();

    // Test.
    $user = $this->drupalCreateUser(['bypass node access']);
    $url = Url::fromUri('internal:/jsonapi/node/page?include=uid');
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--page',
          'attributes' => [
            'title' => 'test',
          ],
        ],
      ],
    ];
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(201, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertArrayHasKey('included', $doc);
    $this->assertSame($user->label(), $doc['included'][0]['attributes']['name']);
  }

  /**
   * Ensure includes are respected even when PATCHing.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3026030
   */
  public function testPatchToIncludeUrlDoesNotReturnIncludeFromIssue3026030() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->rebuildAll();

    // Create data.
    $user = $this->drupalCreateUser(['bypass node access']);
    $page = Node::create([
      'title' => 'original',
      'type' => 'page',
      'uid' => $user->id(),
    ]);
    $page->save();

    // Test.
    $url = Url::fromUri(sprintf('internal:/jsonapi/node/page/%s/?include=uid', $page->uuid()));
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--page',
          'id' => $page->uuid(),
          'attributes' => [
            'title' => 'modified',
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    $this->assertArrayHasKey('included', $doc);
    $this->assertSame($user->label(), $doc['included'][0]['attributes']['name']);
  }

  /**
   * Ensure `@FieldType=map` fields are normalized correctly.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3040590
   */
  public function testMapFieldTypeNormalizationFromIssue3040590() {
    $this->assertTrue($this->container->get('module_installer')->install(['entity_test'], TRUE), 'Installed modules.');

    // Create data.
    $entity_a = EntityTestMapField::create([
      'name' => 'A',
      'data' => [
        'foo' => 'bar',
        'baz' => 'qux',
      ],
    ]);
    $entity_a->save();
    $entity_b = EntityTestMapField::create([
      'name' => 'B',
    ]);
    $entity_b->save();
    $user = $this->drupalCreateUser([
      'administer entity_test content',
    ]);

    // Test.
    $url = Url::fromUri('internal:/jsonapi/entity_test_map_field/entity_test_map_field?sort=drupal_internal__id');
    $request_options = [
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
    ];
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $data = Json::decode((string) $response->getBody());
    $this->assertSame([
      'foo' => 'bar',
      'baz' => 'qux',
    ], $data['data'][0]['attributes']['data']);
    $this->assertNull($data['data'][1]['attributes']['data']);
    $entity_a->set('data', [
      'foo' => 'bar',
    ])->save();
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $data = Json::decode((string) $response->getBody());
    $this->assertSame(['foo' => 'bar'], $data['data'][0]['attributes']['data']);
  }

  /**
   * Tests that the response still has meaningful error messages.
   */
  public function testRecursionDetectedWhenResponseContainsViolationsFrom3042124() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up default request.
    $url = Url::fromUri('internal:/jsonapi/node/article');
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--article',
          'attributes' => [],
        ],
      ],
    ];

    // Set up test users.
    $user = $this->drupalCreateUser(['bypass node access'], 'Sam');
    $admin = $this->drupalCreateUser([], 'Gandalf', TRUE);

    // Make request as regular user.
    $request_options[RequestOptions::AUTH] = [$user->getAccountName(), $user->pass_raw];
    $this->request('POST', $url, $request_options);
    $response = $this->request('POST', $url, $request_options);

    // Assert that the response has a body.
    $data = Json::decode((string) $response->getBody());
    $this->assertSame(422, $response->getStatusCode());
    $this->assertNotNull($data);
    $this->assertSame(sprintf('title: This value should not be null.'), $data['errors'][0]['detail']);

    // Make request as regular user.
    $request_options[RequestOptions::AUTH] = [$admin->getAccountName(), $admin->pass_raw];
    $this->request('POST', $url, $request_options);
    $response = $this->request('POST', $url, $request_options);

    // Assert that the response has a body.
    $data = Json::decode((string) $response->getBody());
    $this->assertSame(422, $response->getStatusCode());
    $this->assertNotNull($data);
    $this->assertSame(sprintf('title: This value should not be null.'), $data['errors'][0]['detail']);
  }

  /**
   * Ensure that child comments can be retrieved via JSON:API.
   */
  public function testLeakedCacheMetadataViaRdfFromIssue3053827() {
    $this->assertTrue($this->container->get('module_installer')->install(['comment', 'rdf'], TRUE), 'Installed modules.');
    $this->addDefaultCommentField('node', 'article', 'comment', CommentItemInterface::OPEN, 'comment');
    $this->rebuildAll();

    // Create data.
    Node::create([
      'title' => 'Commented Node',
      'type' => 'article',
    ])->save();
    $default_values = [
      'entity_id' => 1,
      'entity_type' => 'node',
      'field_name' => 'comment',
      'status' => 1,
    ];
    $parent = Comment::create(['subject' => 'Marlin'] + $default_values);
    $parent->save();
    $child = Comment::create(['subject' => 'Nemo', 'pid' => $parent->id()] + $default_values);
    $child->save();

    // Test.
    $user = $this->drupalCreateUser(['access comments']);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ];
    // Requesting the comment collection should succeed.
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/comment/comment'), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

  /**
   * Ensure non-translatable entities can be PATCHed with an alternate language.
   *
   * @see https://www.drupal.org/project/drupal/issues/3043168
   */
  public function testNonTranslatableEntityUpdatesFromIssue3043168() {
    // Enable write-mode.
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);
    // Set the site language to Russian.
    $this->config('system.site')->set('langcode', 'ru')->set('default_langcode', 'ru')->save(TRUE);
    // Install a "custom" entity type that is not translatable.
    $this->assertTrue($this->container->get('module_installer')->install(['entity_test'], TRUE), 'Installed modules.');
    // Clear and rebuild caches and routes.
    $this->rebuildAll();
    // Create a test entity.
    // @see \Drupal\language\DefaultLanguageItem
    $entity = EntityTest::create([
      'name' => 'Alexander',
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $entity->save();
    // Ensure it is an instance of TranslatableInterface and that it is *not*
    // translatable.
    $this->assertInstanceOf(TranslatableInterface::class, $entity);
    $this->assertFalse($entity->isTranslatable());
    // Set up a test user with permission to view and update the test entity.
    $user = $this->drupalCreateUser([
      'view test entity',
      'administer entity_test content',
    ]);
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::AUTH] = [
      $user->getAccountName(),
      $user->pass_raw,
    ];
    // GET the test entity via JSON:API.
    $entity_url = Url::fromUri('internal:/jsonapi/entity_test/entity_test/' . $entity->uuid());
    $response = $this->request('GET', $entity_url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $response_document = Json::decode($response->getBody());
    // Ensure that the entity's langcode attribute is 'und'.
    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $response_document['data']['attributes']['langcode']);
    // Prepare to PATCH the entity via JSON:API.
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options[RequestOptions::JSON] = [
      'data' => [
        'type' => 'entity_test--entity_test',
        'id' => $entity->uuid(),
        'attributes' => [
          'name' => 'Constantine',
        ],
      ],
    ];
    // Issue the PATCH request and verify that the test entity was successfully
    // updated.
    $response = $this->request('PATCH', $entity_url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $response_document = Json::decode($response->getBody());
    // Ensure that the entity's langcode attribute is still 'und' and the name
    // was successfully updated.
    $this->assertSame(LanguageInterface::LANGCODE_NOT_SPECIFIED, $response_document['data']['attributes']['langcode']);
    $this->assertSame('Constantine', $response_document['data']['attributes']['name']);
  }

  /**
   * Ensure POSTing invalid data results in a 422 response, not a PHP error.
   *
   * @see https://www.drupal.org/project/drupal/issues/3052954
   */
  public function testInvalidDataTriggersUnprocessableEntityErrorFromIssue3052954() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $user = $this->drupalCreateUser(['bypass node access']);

    // Test.
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'article',
          'attributes' => [
            'title' => 'foobar',
            'created' => 'not_a_date',
          ],
        ],
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
    ];
    $response = $this->request('POST', Url::fromUri('internal:/jsonapi/node/article'), $request_options);
    $this->assertSame(422, $response->getStatusCode());
  }

  /**
   * Ensure optional `@FieldType=map` fields are denormalized correctly.
   */
  public function testEmptyMapFieldTypeDenormalization() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->assertTrue($this->container->get('module_installer')->install(['entity_test'], TRUE), 'Installed modules.');

    // Create data.
    $entity = EntityTestMapField::create([
      'name' => 'foo',
    ]);
    $entity->save();
    $user = $this->drupalCreateUser([
      'administer entity_test content',
    ]);

    // Test.
    $url = Url::fromUri(sprintf('internal:/jsonapi/entity_test_map_field/entity_test_map_field/%s', $entity->uuid()));
    $request_options = [
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
    ];
    // Retrieve the current representation of the entity.
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $doc = Json::decode((string) $response->getBody());
    // Modify the title. The @FieldType=map normalization is not changed. (The
    // name of this field is confusingly also 'data'.)
    $doc['data']['attributes']['name'] = 'bar';
    $request_options[RequestOptions::HEADERS] = [
      'Content-Type' => 'application/vnd.api+json',
      'Accept' => 'application/vnd.api+json',
    ];
    $request_options[RequestOptions::BODY] = Json::encode($doc);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertSame($doc['data']['attributes']['data'], Json::decode((string) $response->getBody())['data']['attributes']['data']);
  }

  /**
   * Ensure EntityAccessDeniedHttpException cacheability is taken into account.
   */
  public function testLeakCacheMetadataInOmitted() {
    $term = Term::create([
      'name' => 'Llama term',
      'vid' => 'tags',
    ]);
    $term->setUnpublished();
    $term->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Llama node',
      'field_tags' => ['target_id' => $term->id()],
    ]);
    $node->save();

    $user = $this->drupalCreateUser([
      'access content',
    ]);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ];

    // Request with unpublished term. At this point it would include the term
    // into "omitted" part of the response. The point here is that we
    // purposefully warm up the cache where it is excluded from response and
    // on the next run we will assure merely publishing term is enough to make
    // it visible, i.e. that the 1st response was invalidated in Drupal cache.
    $url = Url::fromUri('internal:/jsonapi/' . $node->getEntityTypeId() . '/' . $node->bundle(), [
      'query' => ['include' => 'field_tags'],
    ]);
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());

    $response = Json::decode((string) $response->getBody());
    $this->assertArrayNotHasKey('included', $response, 'JSON API response does not contain "included" taxonomy term as the latter is not published, i.e not accessible.');

    $omitted = $response['meta']['omitted']['links'];
    unset($omitted['help']);
    $omitted = reset($omitted);
    $expected_url = Url::fromUri('internal:/jsonapi/' . $term->getEntityTypeId() . '/' . $term->bundle() . '/' . $term->uuid());
    $expected_url->setAbsolute();
    $this->assertSame($expected_url->toString(), $omitted['href'], 'Entity that is excluded due to access constraints is correctly reported in the "Omitted" section of the JSON API response.');

    $term->setPublished();
    $term->save();
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode());
    $this->assertEquals($term->uuid(), Json::decode((string) $response->getBody())['included'][0]['id'], 'JSON API response contains "included" taxonomy term as it became published, i.e accessible.');
  }

  /**
   * Tests that "virtual/missing" resources can exist for renamed fields.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3034786
   * @see https://www.drupal.org/project/jsonapi_extras/issues/3035544
   */
  public function testAliasedFieldsWithVirtualRelationships() {
    // Set up the data model.
    $this->assertTrue($this->container->get('module_installer')->install([
      'taxonomy',
      'jsonapi_test_resource_type_building',
    ], TRUE), 'Installed modules.');
    \Drupal::state()->set('jsonapi_test_resource_type_builder.resource_type_field_aliases', [
      'node--article' => [
        'field_tags' => 'field_aliased',
      ],
    ]);
    $this->rebuildAll();

    $tag_term = Term::create([
      'vid' => 'tags',
      'name' => 'test_tag',
    ]);
    $tag_term->save();

    $article_node = Node::create([
      'type' => 'article',
      'title' => 'test_article',
      'field_tags' => ['target_id' => $tag_term->id()],
    ]);
    $article_node->save();

    // Make a broken reference.
    $tag_term->delete();

    // Make sure that accessing a node that references a deleted term does not
    // cause an error.
    $user = $this->drupalCreateUser(['bypass node access']);
    $request_options = [
      RequestOptions::AUTH => [
        $user->getAccountName(),
        $user->pass_raw,
      ],
    ];
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/article/' . $article_node->uuid()), $request_options);
    $this->assertSame(200, $response->getStatusCode());
  }

}
