<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\jsonapi\Traits\CommonCollectionFilterAccessTestPatternsTrait;
use GuzzleHttp\RequestOptions;

/**
 * JSON:API integration test for the "Term" content entity type.
 *
 * @group jsonapi
 */
class TermTest extends ResourceTestBase {

  use CommonCollectionFilterAccessTestPatternsTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['taxonomy', 'path'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'taxonomy_term--camelids';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    switch ($method) {
      case 'GET':
        $this->grantPermissionsToTestedRole(['access content']);
        break;

      case 'POST':
        $this->grantPermissionsToTestedRole(['create terms in camelids']);
        break;

      case 'PATCH':
        // Grant the 'create url aliases' permission to test the case when
        // the path field is accessible, see
        // \Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase
        // for a negative test.
        $this->grantPermissionsToTestedRole(['edit terms in camelids', 'create url aliases']);
        break;

      case 'DELETE':
        $this->grantPermissionsToTestedRole(['delete terms in camelids']);
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $vocabulary = Vocabulary::load('camelids');
    if (!$vocabulary) {
      // Create a "Camelids" vocabulary.
      $vocabulary = Vocabulary::create([
        'name' => 'Camelids',
        'vid' => 'camelids',
      ]);
      $vocabulary->save();
    }

    // Create a "Llama" taxonomy term.
    $term = Term::create(['vid' => $vocabulary->id()])
      ->setName('Llama')
      ->setDescription("It is a little known fact that llamas cannot count higher than seven.")
      ->setChangedTime(123456789)
      ->set('path', '/llama');
    $term->save();

    return $term;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/taxonomy_term/camelids/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();

    // We test with multiple parent terms, and combinations thereof.
    // @see ::createEntity()
    // @see ::testGetIndividual()
    // @see ::testGetIndividualTermWithParent()
    // @see ::providerTestGetIndividualTermWithParent()
    $parent_term_ids = [];
    for ($i = 0; $i < $this->entity->get('parent')->count(); $i++) {
      $parent_term_ids[$i] = (int) $this->entity->get('parent')[$i]->target_id;
    }

    $expected_parent_normalization = FALSE;
    switch ($parent_term_ids) {
      case [0]:
        $expected_parent_normalization = [
          'data' => [
            [
              'id' => 'virtual',
              'type' => 'taxonomy_term--camelids',
              'meta' => [
                'links' => [
                  'help' => [
                    'href' => 'https://www.drupal.org/docs/8/modules/json-api/core-concepts#virtual',
                    'meta' => [
                      'about' => "Usage and meaning of the 'virtual' resource identifier.",
                    ],
                  ],
                ],
              ],
            ],
          ],
          'links' => [
            'related' => ['href' => $self_url . '/parent'],
            'self' => ['href' => $self_url . '/relationships/parent'],
          ],
        ];
        break;

      case [2]:
        $expected_parent_normalization = [
          'data' => [
            [
              'id' => Term::load(2)->uuid(),
              'type' => 'taxonomy_term--camelids',
            ],
          ],
          'links' => [
            'related' => ['href' => $self_url . '/parent'],
            'self' => ['href' => $self_url . '/relationships/parent'],
          ],
        ];
        break;

      case [0, 2]:
        $expected_parent_normalization = [
          'data' => [
            [
              'id' => 'virtual',
              'type' => 'taxonomy_term--camelids',
              'meta' => [
                'links' => [
                  'help' => [
                    'href' => 'https://www.drupal.org/docs/8/modules/json-api/core-concepts#virtual',
                    'meta' => [
                      'about' => "Usage and meaning of the 'virtual' resource identifier.",
                    ],
                  ],
                ],
              ],
            ],
            [
              'id' => Term::load(2)->uuid(),
              'type' => 'taxonomy_term--camelids',
            ],
          ],
          'links' => [
            'related' => ['href' => $self_url . '/parent'],
            'self' => ['href' => $self_url . '/relationships/parent'],
          ],
        ];
        break;

      case [3, 2]:
        $expected_parent_normalization = [
          'data' => [
            [
              'id' => Term::load(3)->uuid(),
              'type' => 'taxonomy_term--camelids',
            ],
            [
              'id' => Term::load(2)->uuid(),
              'type' => 'taxonomy_term--camelids',
            ],
          ],
          'links' => [
            'related' => ['href' => $self_url . '/parent'],
            'self' => ['href' => $self_url . '/relationships/parent'],
          ],
        ];
        break;
    }

    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => ['href' => $self_url],
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'taxonomy_term--camelids',
        'links' => [
          'self' => ['href' => $self_url],
        ],
        'attributes' => [
          'changed' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'default_langcode' => TRUE,
          'description' => [
            'value' => 'It is a little known fact that llamas cannot count higher than seven.',
            'format' => NULL,
            'processed' => "<p>It is a little known fact that llamas cannot count higher than seven.</p>\n",
          ],
          'langcode' => 'en',
          'name' => 'Llama',
          'path' => [
            'alias' => '/llama',
            'pid' => 1,
            'langcode' => 'en',
          ],
          'weight' => 0,
          'drupal_internal__tid' => 1,
          'status' => TRUE,
          'drupal_internal__revision_id' => 1,
          'revision_created' => (new \DateTime())->setTimestamp($this->entity->getRevisionCreationTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'revision_log_message' => NULL,
          // @todo Attempt to remove this in https://www.drupal.org/project/drupal/issues/2933518.
          'revision_translation_affected' => TRUE,
        ],
        'relationships' => [
          'parent' => $expected_parent_normalization,
          'vid' => [
            'data' => [
              'id' => Vocabulary::load('camelids')->uuid(),
              'type' => 'taxonomy_vocabulary--taxonomy_vocabulary',
            ],
            'links' => [
              'related' => ['href' => $self_url . '/vid'],
              'self' => ['href' => $self_url . '/relationships/vid'],
            ],
          ],
          'revision_user' => [
            'data' => NULL,
            'links' => [
              'related' => [
                'href' => $self_url . '/revision_user',
              ],
              'self' => [
                'href' => $self_url . '/relationships/revision_user',
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedGetRelationshipDocumentData($relationship_field_name, EntityInterface $entity = NULL) {
    $data = parent::getExpectedGetRelationshipDocumentData($relationship_field_name, $entity);
    if ($relationship_field_name === 'parent') {
      $data = [
        0 => [
          'id' => 'virtual',
          'type' => 'taxonomy_term--camelids',
          'meta' => [
            'links' => [
              'help' => [
                'href' => 'https://www.drupal.org/docs/8/modules/json-api/core-concepts#virtual',
                'meta' => [
                  'about' => "Usage and meaning of the 'virtual' resource identifier.",
                ],
              ],
            ],
          ],
        ],
      ];
    }
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    return [
      'data' => [
        'type' => 'taxonomy_term--camelids',
        'attributes' => [
          'name' => 'Dramallama',
          'description' => [
            'value' => 'Dramallamas are the coolest camelids.',
            'format' => NULL,
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    switch ($method) {
      case 'GET':
        return "The 'access content' permission is required and the taxonomy term must be published.";

      case 'POST':
        return "The following permissions are required: 'create terms in camelids' OR 'administer taxonomy'.";

      case 'PATCH':
        return "The following permissions are required: 'edit terms in camelids' OR 'administer taxonomy'.";

      case 'DELETE':
        return "The following permissions are required: 'delete terms in camelids' OR 'administer taxonomy'.";

      default:
        return parent::getExpectedUnauthorizedAccessMessage($method);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    $cacheability = parent::getExpectedUnauthorizedAccessCacheability();
    $cacheability->addCacheableDependency($this->entity);
    return $cacheability;
  }

  /**
   * Tests PATCHing a term's path.
   *
   * For a negative test, see the similar test coverage for Node.
   *
   * @see \Drupal\Tests\jsonapi\Functional\NodeTest::testPatchPath()
   * @see \Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase::testPatchPath()
   */
  public function testPatchPath() {
    $this->setUpAuthorization('GET');
    $this->setUpAuthorization('PATCH');
    $this->config('jsonapi.settings')->set('read_only', FALSE)->save(TRUE);

    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // GET term's current normalization.
    $response = $this->request('GET', $url, $request_options);
    $normalization = Json::decode((string) $response->getBody());

    // Change term's path alias.
    $normalization['data']['attributes']['path']['alias'] .= 's-rule-the-world';

    // Create term PATCH request.
    $request_options[RequestOptions::BODY] = Json::encode($normalization);

    // PATCH request: 200.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $updated_normalization = Json::decode((string) $response->getBody());
    $this->assertSame($normalization['data']['attributes']['path']['alias'], $updated_normalization['data']['attributes']['path']['alias']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags(array $sparse_fieldset = NULL) {
    $tags = parent::getExpectedCacheTags($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('description', $sparse_fieldset)) {
      $tags = Cache::mergeTags($tags, ['config:filter.format.plain_text', 'config:filter.settings']);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts(array $sparse_fieldset = NULL) {
    $contexts = parent::getExpectedCacheContexts($sparse_fieldset);
    if ($sparse_fieldset === NULL || in_array('description', $sparse_fieldset)) {
      $contexts = Cache::mergeContexts($contexts, ['languages:language_interface', 'theme']);
    }
    return $contexts;
  }

  /**
   * Tests GETting a term with a parent term other than the default <root> (0).
   *
   * @see ::getExpectedNormalizedEntity()
   *
   * @dataProvider providerTestGetIndividualTermWithParent
   */
  public function testGetIndividualTermWithParent(array $parent_term_ids) {
    // Create all possible parent terms.
    Term::create(['vid' => Vocabulary::load('camelids')->id()])
      ->setName('Lamoids')
      ->save();
    Term::create(['vid' => Vocabulary::load('camelids')->id()])
      ->setName('Wimoids')
      ->save();

    // Modify the entity under test to use the provided parent terms.
    $this->entity->set('parent', $parent_term_ids)->save();

    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());
    $this->setUpAuthorization('GET');
    $response = $this->request('GET', $url, $request_options);
    $this->assertSameDocument($this->getExpectedDocument(), Json::decode($response->getBody()));
  }

  /**
   * Data provider for ::testGetIndividualTermWithParent().
   */
  public function providerTestGetIndividualTermWithParent() {
    return [
      'root parent: [0] (= no parent)' => [
        [0],
      ],
      'non-root parent: [2]' => [
        [2],
      ],
      'multiple parents: [0,2] (root + non-root parent)' => [
        [0, 2],
      ],
      'multiple parents: [3,2] (both non-root parents)' => [
        [3, 2],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function testRelated() {
    $this->markTestSkipped('Remove this in https://www.drupal.org/project/jsonapi/issues/2940339');
  }

  /**
   * {@inheritdoc}
   */
  public function testCollectionFilterAccess() {
    $this->doTestCollectionFilterAccessBasedOnPermissions('name', 'access content');
  }

}
