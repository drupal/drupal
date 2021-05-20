<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\serialization\Normalizer\CacheableNormalizerInterface;

/**
 * @coversDefaultClass \Drupal\hal\LinkManager\LinkManager
 * @group hal
 */
class HalLinkManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'hal',
    'hal_test',
    'serialization',
    'system',
    'node',
    'user',
    'field',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');

    NodeType::create([
      'type' => 'page',
    ])->save();
    FieldStorageConfig::create([
      'entity_type' => 'node',
      'type' => 'entity_reference',
      'field_name' => 'field_ref',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_ref',
    ])->save();
  }

  /**
   * @covers ::getTypeUri
   * @dataProvider providerTestGetTypeUri
   */
  public function testGetTypeUri($link_domain, $entity_type, $bundle, array $context, $expected_return, array $expected_context) {
    $hal_settings = \Drupal::configFactory()->getEditable('hal.settings');

    if ($link_domain === NULL) {
      $hal_settings->clear('link_domain');
    }
    else {
      $hal_settings->set('link_domain', $link_domain)->save(TRUE);
    }

    /** @var \Drupal\hal\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('hal.link_manager.type');

    $link = $type_manager->getTypeUri($entity_type, $bundle, $context);
    $this->assertSame($link, str_replace('BASE_URL/', Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), $expected_return));
    $this->assertEquals($context, $expected_context);
  }

  public function providerTestGetTypeUri() {
    $serialization_context_collecting_cacheability = [
      CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => new CacheableMetadata(),
    ];
    $expected_serialization_context_cacheability_url_site = [
      CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => (new CacheableMetadata())->setCacheContexts(['url.site']),
    ];

    $base_test_case = [
      'link_domain' => NULL,
      'entity_type' => 'node',
      'bundle' => 'page',
    ];

    return [
      'site URL' => $base_test_case + [
        'context' => [],
        'link_domain' => NULL,
        'expected return' => 'BASE_URL/rest/type/node/page',
        'expected context' => [],
      ],
      'site URL, with optional context to collect cacheability metadata' => $base_test_case + [
        'context' => $serialization_context_collecting_cacheability,
        'expected return' => 'BASE_URL/rest/type/node/page',
        'expected context' => $expected_serialization_context_cacheability_url_site,
      ],
      // Test hook_hal_type_uri_alter().
      'site URL, with optional context, to test hook_hal_type_uri_alter()' => $base_test_case + [
        'context' => ['hal_test' => TRUE],
        'expected return' => 'hal_test_type',
        'expected context' => ['hal_test' => TRUE],
      ],
      'site URL, with optional context, to test hook_hal_type_uri_alter(), and collecting cacheability metadata' => $base_test_case + [
        'context' => ['hal_test' => TRUE] + $serialization_context_collecting_cacheability,
        'expected return' => 'hal_test_type',
        // No cacheability metadata bubbled.
        'expected context' => ['hal_test' => TRUE] + $serialization_context_collecting_cacheability,
      ],
      'configured URL' => [
        'link_domain' => 'http://llamas-rock.com/for-real/',
        'entity_type' => 'node',
        'bundle' => 'page',
        'context' => [],
        'expected return' => 'http://llamas-rock.com/for-real/rest/type/node/page',
        'expected context' => [],
      ],
      'configured URL, with optional context to collect cacheability metadata' => [
        'link_domain' => 'http://llamas-rock.com/for-real/',
        'entity_type' => 'node',
        'bundle' => 'page',
        'context' => $serialization_context_collecting_cacheability,
        'expected return' => 'http://llamas-rock.com/for-real/rest/type/node/page',
        'expected context' => [
          CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => (new CacheableMetadata())->setCacheTags(['config:hal.settings']),
        ],
      ],
    ];
  }

  /**
   * @covers ::getRelationUri
   * @dataProvider providerTestGetRelationUri
   */
  public function testGetRelationUri($link_domain, $entity_type, $bundle, $field_name, array $context, $expected_return, array $expected_context) {
    $hal_settings = \Drupal::configFactory()->getEditable('hal.settings');

    if ($link_domain === NULL) {
      $hal_settings->clear('link_domain');
    }
    else {
      $hal_settings->set('link_domain', $link_domain)->save(TRUE);
    }

    /** @var \Drupal\hal\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('hal.link_manager.relation');

    $link = $relation_manager->getRelationUri($entity_type, $bundle, $field_name, $context);
    $this->assertSame($link, str_replace('BASE_URL/', Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), $expected_return));
    $this->assertEquals($context, $expected_context);
  }

  public function providerTestGetRelationUri() {
    $serialization_context_collecting_cacheability = [
      CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => new CacheableMetadata(),
    ];
    $expected_serialization_context_cacheability_url_site = [
      CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => (new CacheableMetadata())->setCacheContexts(['url.site']),
    ];

    $field_name = $this->randomMachineName();
    $base_test_case = [
      'link_domain' => NULL,
      'entity_type' => 'node',
      'bundle' => 'page',
      'field_name' => $field_name,
    ];

    return [
      'site URL' => $base_test_case + [
        'context' => [],
        'link_domain' => NULL,
        'expected return' => 'BASE_URL/rest/relation/node/page/' . $field_name,
        'expected context' => [],
      ],
      'site URL, with optional context to collect cacheability metadata' => $base_test_case + [
        'context' => $serialization_context_collecting_cacheability,
        'expected return' => 'BASE_URL/rest/relation/node/page/' . $field_name,
        'expected context' => $expected_serialization_context_cacheability_url_site,
      ],
      // Test hook_hal_relation_uri_alter().
      'site URL, with optional context, to test hook_hal_relation_uri_alter()' => $base_test_case + [
        'context' => ['hal_test' => TRUE],
        'expected return' => 'hal_test_relation',
        'expected context' => ['hal_test' => TRUE],
      ],
      'site URL, with optional context, to test hook_hal_relation_uri_alter(), and collecting cacheability metadata' => $base_test_case + [
        'context' => ['hal_test' => TRUE] + $serialization_context_collecting_cacheability,
        'expected return' => 'hal_test_relation',
        // No cacheability metadata bubbled.
        'expected context' => ['hal_test' => TRUE] + $serialization_context_collecting_cacheability,
      ],
      'configured URL' => [
        'link_domain' => 'http://llamas-rock.com/for-real/',
        'entity_type' => 'node',
        'bundle' => 'page',
        'field_name' => $field_name,
        'context' => [],
        'expected return' => 'http://llamas-rock.com/for-real/rest/relation/node/page/' . $field_name,
        'expected context' => [],
      ],
      'configured URL, with optional context to collect cacheability metadata' => [
        'link_domain' => 'http://llamas-rock.com/for-real/',
        'entity_type' => 'node',
        'bundle' => 'page',
        'field_name' => $field_name,
        'context' => $serialization_context_collecting_cacheability,
        'expected return' => 'http://llamas-rock.com/for-real/rest/relation/node/page/' . $field_name,
        'expected context' => [
          CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => (new CacheableMetadata())->setCacheTags(['config:hal.settings']),
        ],
      ],
    ];
  }

  /**
   * @covers ::getRelationInternalIds
   */
  public function testGetRelationInternalIds() {
    /** @var \Drupal\hal\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('hal.link_manager.relation');
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $internal_ids = $relation_manager->getRelationInternalIds($link);

    $this->assertEquals([
      'entity_type_id' => 'node',
      'bundle' => 'page',
      'field_name' => 'field_ref',
    ], $internal_ids);
  }

  /**
   * @covers ::setLinkDomain
   */
  public function testHalLinkManagersSetLinkDomain() {
    $serialization_context = [
      CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY => new CacheableMetadata(),
    ];

    /** @var \Drupal\hal\LinkManager\LinkManager $link_manager */
    $link_manager = \Drupal::service('hal.link_manager');
    $link_manager->setLinkDomain('http://example.com/');
    $link = $link_manager->getTypeUri('node', 'page', $serialization_context);
    $this->assertEquals('http://example.com/rest/type/node/page', $link);
    $this->assertEquals($serialization_context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY], new CacheableMetadata());
    $link = $link_manager->getRelationUri('node', 'page', 'field_ref', $serialization_context);
    $this->assertEquals('http://example.com/rest/relation/node/page/field_ref', $link);
    $this->assertEquals($serialization_context[CacheableNormalizerInterface::SERIALIZATION_CONTEXT_CACHEABILITY], new CacheableMetadata());
  }

}
