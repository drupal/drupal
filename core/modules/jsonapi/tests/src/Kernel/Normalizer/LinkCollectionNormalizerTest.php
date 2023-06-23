<?php

namespace Drupal\Tests\jsonapi\Kernel\Normalizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\Normalizer\LinkCollectionNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * @coversDefaultClass \Drupal\jsonapi\Normalizer\LinkCollectionNormalizer
 * @group jsonapi
 *
 * @internal
 */
class LinkCollectionNormalizerTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * The serializer.
   *
   * @var \Symfony\Component\Serializer\SerializerInterface
   */
  protected $serializer;

  /**
   * The subject under test.
   *
   * @var \Drupal\jsonapi\Normalizer\LinkCollectionNormalizer
   */
  protected $normalizer;

  /**
   * Test users.
   *
   * @var \Drupal\user\UserInterface[]
   */
  protected $testUsers;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'jsonapi',
    'serialization',
    'system',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Add the entity schemas.
    $this->installEntitySchema('user');
    // Add the additional table schemas.
    $this->installSchema('user', ['users_data']);
    // Set the user IDs to something higher than 1 so these users cannot be
    // mistaken for the site admin.
    $this->testUsers[] = $this->createUser([], NULL, FALSE, ['uid' => 2]);
    $this->testUsers[] = $this->createUser([], NULL, FALSE, ['uid' => 3]);
    $this->serializer = $this->container->get('jsonapi.serializer');
  }

  /**
   * Tests the link collection normalizer.
   */
  public function testNormalize() {
    $link_context = new ResourceObject(new CacheableMetadata(), new ResourceType('n/a', 'n/a', 'n/a'), 'n/a', NULL, [], new LinkCollection([]));
    $link_collection = (new LinkCollection([]))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Most viewed']))
      ->withLink('related', new Link(new CacheableMetadata(), Url::fromUri('http://example.com/post/42'), 'related', ['title' => 'Top rated']))
      ->withContext($link_context);
    // Create the SUT.
    $normalized = $this->getNormalizer()->normalize($link_collection)->getNormalization();
    $this->assertIsArray($normalized);
    foreach (array_keys($normalized) as $key) {
      $this->assertStringStartsWith('related', $key);
    }
    $this->assertSame([
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Most viewed',
        ],
      ],
      [
        'href' => 'http://example.com/post/42',
        'meta' => [
          'title' => 'Top rated',
        ],
      ],
    ], array_values($normalized));
  }

  /**
   * Tests the link collection normalizer.
   *
   * @dataProvider linkAccessTestData
   */
  public function testLinkAccess($current_user_id, $edit_form_uid, $expected_link_keys, $expected_cache_contexts) {
    // Get the current user and an edit-form URL.
    foreach ($this->testUsers as $user) {
      $uid = (int) $user->id();
      if ($uid === $current_user_id) {
        $current_user = $user;
      }
      if ($uid === $edit_form_uid) {
        $edit_form_url = $user->toUrl('edit-form');
      }
    }
    assert(isset($current_user));
    assert(isset($edit_form_url));

    // Create a link collection to normalize.
    $mock_resource_object = $this->createMock(ResourceObject::class);
    $link_collection = new LinkCollection([
      'edit-form' => new Link(new CacheableMetadata(), $edit_form_url, 'edit-form', ['title' => 'Edit']),
    ]);
    $link_collection = $link_collection->withContext($mock_resource_object);

    // Normalize the collection.
    $actual_normalization = $this->getNormalizer($current_user)->normalize($link_collection);

    // Check that it returned the expected value object.
    $this->assertInstanceOf(CacheableNormalization::class, $actual_normalization);

    // Get the raw normalized data.
    $actual_data = $actual_normalization->getNormalization();
    $this->assertIsArray($actual_data);

    // Check that the expected links are present and unexpected links are
    // absent.
    $actual_link_keys = array_keys($actual_data);
    sort($expected_link_keys);
    sort($actual_link_keys);
    $this->assertSame($expected_link_keys, $actual_link_keys);

    // Check that the expected cache contexts were added.
    $actual_cache_contexts = $actual_normalization->getCacheContexts();
    sort($expected_cache_contexts);
    sort($actual_cache_contexts);
    $this->assertSame($expected_cache_contexts, $actual_cache_contexts);

    // If the edit-form link was present, check that it has the correct href.
    if (isset($actual_data['edit-form'])) {
      $this->assertSame($actual_data['edit-form'], [
        'href' => $edit_form_url->setAbsolute()->toString(),
        'meta' => [
          'title' => 'Edit',
        ],
      ]);
    }
  }

  /**
   * Provides test cases for testing link access checking.
   *
   * @return array[]
   */
  public function linkAccessTestData() {
    return [
      'the edit-form link is present because uid 2 has access to the targeted resource (its own edit form)' => [
        'uid' => 2,
        'edit-form uid' => 2,
        'expected link keys' => ['edit-form'],
        'expected cache contexts' => ['url.site', 'user'],
      ],
      "the edit-form link is omitted because uid 3 doesn't have access to the targeted resource (another account's edit form)" => [
        'uid' => 3,
        'edit-form uid' => 2,
        'expected link keys' => [],
        'expected cache contexts' => ['url.site', 'user'],
      ],
    ];
  }

  /**
   * Get an instance of the normalizer to test.
   */
  protected function getNormalizer(AccountInterface $current_user = NULL) {
    if (is_null($current_user)) {
      $current_user = $this->setUpCurrentUser();
    }
    else {
      $this->setCurrentUser($current_user);
    }
    $normalizer = new LinkCollectionNormalizer($current_user);
    $normalizer->setSerializer($this->serializer);
    return $normalizer;
  }

}
