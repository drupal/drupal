<?php

namespace Drupal\Tests\taxonomy\Functional\Rest;

use Drupal\Core\Cache\Cache;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\rest\Functional\EntityResource\EntityResourceTestBase;
use GuzzleHttp\RequestOptions;

abstract class TermResourceTestBase extends EntityResourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'path'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'taxonomy_term';

  /**
   * {@inheritdoc}
   */
  protected static $patchProtectedFieldNames = [
    'changed' => NULL,
  ];

  /**
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
  protected function getExpectedNormalizedEntity() {
    // We test with multiple parent terms, and combinations thereof.
    // @see ::createEntity()
    // @see ::testGet()
    // @see ::testGetTermWithParent()
    // @see ::providerTestGetTermWithParent()
    $parent_term_ids = [];
    for ($i = 0; $i < $this->entity->get('parent')->count(); $i++) {
      $parent_term_ids[$i] = (int) $this->entity->get('parent')[$i]->target_id;
    }

    $expected_parent_normalization = FALSE;
    switch ($parent_term_ids) {
      case [0]:
        $expected_parent_normalization = [
          [
            'target_id' => NULL,
          ],
        ];
        break;

      case [2]:
        $expected_parent_normalization = [
          [
            'target_id' => 2,
            'target_type' => 'taxonomy_term',
            'target_uuid' => Term::load(2)->uuid(),
            'url' => base_path() . 'taxonomy/term/2',
          ],
        ];
        break;

      case [0, 2]:
        $expected_parent_normalization = [
          [
            'target_id' => NULL,
          ],
          [
            'target_id' => 2,
            'target_type' => 'taxonomy_term',
            'target_uuid' => Term::load(2)->uuid(),
            'url' => base_path() . 'taxonomy/term/2',
          ],
        ];
        break;

      case [3, 2]:
        $expected_parent_normalization = [
          [
            'target_id' => 3,
            'target_type' => 'taxonomy_term',
            'target_uuid' => Term::load(3)->uuid(),
            'url' => base_path() . 'taxonomy/term/3',
          ],
          [
            'target_id' => 2,
            'target_type' => 'taxonomy_term',
            'target_uuid' => Term::load(2)->uuid(),
            'url' => base_path() . 'taxonomy/term/2',
          ],
        ];
        break;
    }

    return [
      'tid' => [
        ['value' => 1],
      ],
      'revision_id' => [
        ['value' => 1],
      ],
      'uuid' => [
        ['value' => $this->entity->uuid()],
      ],
      'vid' => [
        [
          'target_id' => 'camelids',
          'target_type' => 'taxonomy_vocabulary',
          'target_uuid' => Vocabulary::load('camelids')->uuid(),
        ],
      ],
      'name' => [
        ['value' => 'Llama'],
      ],
      'description' => [
        [
          'value' => 'It is a little known fact that llamas cannot count higher than seven.',
          'format' => NULL,
          'processed' => "<p>It is a little known fact that llamas cannot count higher than seven.</p>\n",
        ],
      ],
      'parent' => $expected_parent_normalization,
      'weight' => [
        ['value' => 0],
      ],
      'langcode' => [
        [
          'value' => 'en',
        ],
      ],
      'changed' => [
        [
          'value' => (new \DateTime())->setTimestamp($this->entity->getChangedTime())->setTimezone(new \DateTimeZone('UTC'))->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'default_langcode' => [
        [
          'value' => TRUE,
        ],
      ],
      'path' => [
        [
          'alias' => '/llama',
          'pid' => 1,
          'langcode' => 'en',
        ],
      ],
      'status' => [
        [
          'value' => TRUE,
        ],
      ],
      'revision_created' => [
        [
          'value' => (new \DateTime())->setTimestamp((int) $this->entity->getRevisionCreationTime())
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format(\DateTime::RFC3339),
          'format' => \DateTime::RFC3339,
        ],
      ],
      'revision_user' => [],
      'revision_log_message' => [],
      'revision_translation_affected' => [
        [
          'value' => TRUE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizedPostEntity() {
    return [
      'vid' => [
        [
          'target_id' => 'camelids',
        ],
      ],
      'name' => [
        [
          'value' => 'Dramallama',
        ],
      ],
      'description' => [
        [
          'value' => 'Dramallamas are the coolest camelids.',
          'format' => NULL,
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
   * Tests PATCHing a term's path.
   *
   * For a negative test, see the similar test coverage for Node.
   *
   * @see \Drupal\Tests\rest\Functional\EntityResource\Node\NodeResourceTestBase::testPatchPath()
   */
  public function testPatchPath() {
    $this->initAuthentication();
    $this->provisionEntityResource();
    $this->setUpAuthorization('GET');
    $this->setUpAuthorization('PATCH');

    $url = $this->getEntityResourceUrl()->setOption('query', ['_format' => static::$format]);

    // GET term's current normalization.
    $response = $this->request('GET', $url, $this->getAuthenticationRequestOptions('GET'));
    $normalization = $this->serializer->decode((string) $response->getBody(), static::$format);

    // Change term's path alias.
    $normalization['path'][0]['alias'] .= 's-rule-the-world';

    // Create term PATCH request.
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;
    $request_options = array_merge_recursive($request_options, $this->getAuthenticationRequestOptions('PATCH'));
    $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

    // PATCH request: 200.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $updated_normalization = $this->serializer->decode((string) $response->getBody(), static::$format);
    $this->assertSame($normalization['path'], $updated_normalization['path']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheTags() {
    return Cache::mergeTags(parent::getExpectedCacheTags(), ['config:filter.format.plain_text', 'config:filter.settings']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    return Cache::mergeContexts(['url.site'], $this->container->getParameter('renderer.config')['required_cache_contexts']);
  }

  /**
   * Tests GETting a term with a parent term other than the default <root> (0).
   *
   * @see ::getExpectedNormalizedEntity()
   *
   * @dataProvider providerTestGetTermWithParent
   */
  public function testGetTermWithParent(array $parent_term_ids) {
    // Create all possible parent terms.
    Term::create(['vid' => Vocabulary::load('camelids')->id()])
      ->setName('Lamoids')
      ->save();
    Term::create(['vid' => Vocabulary::load('camelids')->id()])
      ->setName('Wimoids')
      ->save();

    // Modify the entity under test to use the provided parent terms.
    $this->entity->set('parent', $parent_term_ids)->save();

    $this->initAuthentication();
    $url = $this->getEntityResourceUrl();
    $url->setOption('query', ['_format' => static::$format]);
    $request_options = $this->getAuthenticationRequestOptions('GET');
    $this->provisionEntityResource();
    $this->setUpAuthorization('GET');
    $response = $this->request('GET', $url, $request_options);
    $expected = $this->getExpectedNormalizedEntity();
    static::recursiveKSort($expected);
    $actual = $this->serializer->decode((string) $response->getBody(), static::$format);
    static::recursiveKSort($actual);
    $this->assertSame($expected, $actual);
  }

  public function providerTestGetTermWithParent() {
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
  protected function getExpectedUnauthorizedEntityAccessCacheability($is_authenticated) {
    // @see \Drupal\taxonomy\TermAccessControlHandler::checkAccess()
    return parent::getExpectedUnauthorizedEntityAccessCacheability($is_authenticated)
      ->addCacheTags(['taxonomy_term:1']);
  }

}
