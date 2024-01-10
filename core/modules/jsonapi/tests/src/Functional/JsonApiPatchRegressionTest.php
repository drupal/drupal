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
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use GuzzleHttp\RequestOptions;

// cspell:ignore llamalovers catcuddlers Cuddlers

/**
 * JSON:API regression tests.
 *
 * @group jsonapi
 * @group #slow
 *
 * @internal
 */
class JsonApiPatchRegressionTest extends JsonApiFunctionalTestBase {

  use CommentTestTrait;

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
   * Ensure filtering on relationships works with bundle-specific target types.
   *
   * @see https://www.drupal.org/project/drupal/issues/2953207
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
   * Ensure POST and PATCH works for bundle-less relationship routes.
   *
   * @see https://www.drupal.org/project/drupal/issues/2976371
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
   * Cannot PATCH an entity with dangling references in an ER field.
   *
   * @see https://www.drupal.org/project/drupal/issues/2968972
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
   * Ensures PATCHing datetime (both date-only & date+time) fields is possible.
   *
   * @see https://www.drupal.org/project/drupal/issues/3021194
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
   * Ensure includes are respected even when PATCHing.
   *
   * @see https://www.drupal.org/project/drupal/issues/3026030
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
   * Ensure PATCHing a non-existing field property results in a helpful error.
   *
   * @see https://www.drupal.org/project/drupal/issues/3127883
   */
  public function testPatchInvalidFieldPropertyFromIssue3127883() {
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // Set up data model.
    $this->drupalCreateContentType(['type' => 'page']);
    $this->rebuildAll();

    // Create data.
    $node = Node::create([
      'title' => 'foo',
      'type' => 'page',
      'body' => [
        'format' => 'plain_text',
        'value' => 'Hello World',
      ],
    ]);
    $node->save();

    // Test.
    $user = $this->drupalCreateUser(['bypass node access']);
    $url = Url::fromUri('internal:/jsonapi/node/page/' . $node->uuid());
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--page',
          'id' => $node->uuid(),
          'attributes' => [
            'title' => 'Updated title',
            'body' => [
              'value' => 'Hello World … still.',
              // Intentional typo in the property name!
              'form' => 'plain_text',
              // Another intentional typo.
              // cSpell:disable-next-line
              'sumary' => 'Boring old "Hello World".',
              // And finally, one that is completely absurd.
              'foobarbaz' => '<script>alert("HI!");</script>',
            ],
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $url, $request_options);

    // Assert a helpful error response is present.
    $data = Json::decode((string) $response->getBody());
    $this->assertSame(422, $response->getStatusCode());
    $this->assertNotNull($data);
    // cSpell:disable-next-line
    $this->assertSame("The properties 'form', 'sumary', 'foobarbaz' do not exist on the 'body' field of type 'text_with_summary'. Writable properties are: 'value', 'format', 'summary'.", $data['errors'][0]['detail']);

    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
      RequestOptions::JSON => [
        'data' => [
          'type' => 'node--page',
          'id' => $node->uuid(),
          'attributes' => [
            'title' => 'Updated title',
            'body' => [
              'value' => 'Hello World … still.',
              // Intentional typo in the property name!
              'form' => 'plain_text',
              // Another intentional typo.
              // cSpell:disable-next-line
              'sumary' => 'Boring old "Hello World".',
            ],
          ],
        ],
      ],
    ];
    $response = $this->request('PATCH', $url, $request_options);

    // Assert a helpful error response is present.
    $data = Json::decode((string) $response->getBody());
    $this->assertSame(422, $response->getStatusCode());
    $this->assertNotNull($data);
    // cSpell:disable-next-line
    $this->assertSame("The properties 'form', 'sumary' do not exist on the 'body' field of type 'text_with_summary'. Did you mean 'format', 'summary'?", $data['errors'][0]['detail']);
  }

}
