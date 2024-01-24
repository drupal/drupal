<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\shortcut\Entity\Shortcut;
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
class JsonApiFilterRegressionTest extends JsonApiFunctionalTestBase {

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
   * Ensures that filtering by a sequential internal ID named 'id' is possible.
   *
   * @see https://www.drupal.org/project/drupal/issues/3015759
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
   * Ensure filtering for entities with empty entity reference fields works.
   *
   * @see https://www.drupal.org/project/jsonapi/issues/3025372
   */
  public function testEmptyRelationshipFilteringFromIssue3025372() {
    // Set up data model.
    $this->drupalCreateContentType(['type' => 'folder']);
    $this->createEntityReferenceField(
      'node',
      'folder',
      'field_parent_folder',
      NULL,
      'node',
      'default',
      [
        'target_bundles' => ['folder'],
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->rebuildAll();

    // Create data.
    $node = Node::create([
      'title' => 'root folder',
      'type' => 'folder',
    ]);
    $node->save();

    // Test.
    $user = $this->drupalCreateUser(['access content']);
    $url = Url::fromRoute('jsonapi.node--folder.collection');
    $request_options = [
      RequestOptions::HEADERS => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json',
      ],
      RequestOptions::AUTH => [$user->getAccountName(), $user->pass_raw],
    ];
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame($node->uuid(), Json::decode((string) $response->getBody())['data'][0]['id']);
    $response = $this->request('GET', $url->setOption('query', [
      'filter[test][condition][path]' => 'field_parent_folder',
      'filter[test][condition][operator]' => 'IS NULL',
    ]), $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertSame($node->uuid(), Json::decode((string) $response->getBody())['data'][0]['id']);
    $response = $this->request('GET', $url->setOption('query', [
      'filter[test][condition][path]' => 'field_parent_folder',
      'filter[test][condition][operator]' => 'IS NOT NULL',
    ]), $request_options);
    $this->assertSame(200, $response->getStatusCode(), (string) $response->getBody());
    $this->assertEmpty(Json::decode((string) $response->getBody())['data']);
  }

  /**
   * Tests that collections can be filtered by an entity reference target_id.
   *
   * @see https://www.drupal.org/project/drupal/issues/3036593
   */
  public function testFilteringEntitiesByEntityReferenceTargetId() {
    // Create two config entities to be the config targets of an entity
    // reference. In this case, the `roles` field.
    $role_llamalovers = $this->drupalCreateRole([], 'llamalovers', 'Llama Lovers');
    $role_catcuddlers = $this->drupalCreateRole([], 'catcuddlers', 'Cat Cuddlers');

    /** @var \Drupal\user\UserInterface[] $users */
    for ($i = 0; $i < 3; $i++) {
      // Create 3 users, one with the first role and two with the second role.
      $users[$i] = $this->drupalCreateUser();
      $users[$i]->addRole($i === 0 ? $role_llamalovers : $role_catcuddlers);
      $users[$i]->save();
      // For each user, create a node that is owned by that user. The node's
      // `uid` field will be used to test filtering by a content entity ID.
      Node::create([
        'type' => 'article',
        'uid' => $users[$i]->id(),
        'title' => 'Article created by ' . $users[$i]->uuid(),
      ])->save();
    }

    // Create a user that will be used to execute the test HTTP requests.
    $account = $this->drupalCreateUser([
      'administer users',
      'bypass node access',
    ]);
    $request_options = [
      RequestOptions::AUTH => [
        $account->getAccountName(),
        $account->pass_raw,
      ],
    ];

    // Ensure that an entity can be filtered by a target machine name.
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/user/user?filter[roles.meta.drupal_internal__target_id]=llamalovers'), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame(200, $response->getStatusCode(), var_export($document, TRUE));
    // Only one user should have the first role.
    $this->assertCount(1, $document['data']);
    $this->assertSame($users[0]->uuid(), $document['data'][0]['id']);
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/user/user?sort=drupal_internal__uid&filter[roles.meta.drupal_internal__target_id]=catcuddlers'), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame(200, $response->getStatusCode(), var_export($document, TRUE));
    // Two users should have the second role. A sort is used on this request to
    // ensure a consistent ordering with different databases.
    $this->assertCount(2, $document['data']);
    $this->assertSame($users[1]->uuid(), $document['data'][0]['id']);
    $this->assertSame($users[2]->uuid(), $document['data'][1]['id']);

    // Ensure that an entity can be filtered by an target entity integer ID.
    $response = $this->request('GET', Url::fromUri('internal:/jsonapi/node/article?filter[uid.meta.drupal_internal__target_id]=' . $users[1]->id()), $request_options);
    $document = Json::decode((string) $response->getBody());
    $this->assertSame(200, $response->getStatusCode(), var_export($document, TRUE));
    // Only the node authored by the filtered user should be returned.
    $this->assertCount(1, $document['data']);
    $this->assertSame('Article created by ' . $users[1]->uuid(), $document['data'][0]['attributes']['title']);
  }

}
