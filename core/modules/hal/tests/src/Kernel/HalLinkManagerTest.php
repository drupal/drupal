<?php

namespace Drupal\Tests\hal\Kernel;

use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * @coversDefaultClass \Drupal\hal\LinkManager\LinkManager
 * @group hal
 */
class HalLinkManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['hal', 'hal_test', 'serialization', 'system', 'node', 'user', 'field'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
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

    \Drupal::service('router.builder')->rebuild();
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

    /* @var \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('hal.link_manager.type');

    $link = $type_manager->getTypeUri($entity_type, $bundle, $context);
    $this->assertSame($link, str_replace('BASE_URL/', Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), $expected_return));
    $this->assertEquals($context, $expected_context);
  }

  public function providerTestGetTypeUri() {
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
      // Test hook_hal_type_uri_alter().
      'site URL, with optional context, to test hook_hal_type_uri_alter()' => $base_test_case + [
        'context' => ['hal_test' => TRUE],
        'expected return' => 'hal_test_type',
        'expected context' => ['hal_test' => TRUE],
      ],
      // Test hook_rest_type_uri_alter() — for backwards compatibility.
      'site URL, with optional context, to test hook_rest_type_uri_alter()' => $base_test_case + [
        'context' => ['rest_test' => TRUE],
        'expected return' => 'rest_test_type',
        'expected context' => ['rest_test' => TRUE],
      ],
      'configured URL' => [
        'link_domain' => 'http://llamas-rock.com/for-real/',
        'entity_type' => 'node',
        'bundle' => 'page',
        'context' => [],
        'expected return' => 'http://llamas-rock.com/for-real/rest/type/node/page',
        'expected context' => [],
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

    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('hal.link_manager.relation');

    $link = $relation_manager->getRelationUri($entity_type, $bundle, $field_name, $context);
    $this->assertSame($link, str_replace('BASE_URL/', Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString(), $expected_return));
    $this->assertEquals($context, $expected_context);
  }

  public function providerTestGetRelationUri() {
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
      // Test hook_hal_relation_uri_alter().
      'site URL, with optional context, to test hook_hal_relation_uri_alter()' => $base_test_case + [
        'context' => ['hal_test' => TRUE],
        'expected return' => 'hal_test_relation',
        'expected context' => ['hal_test' => TRUE],
      ],
      // Test hook_rest_relation_uri_alter() — for backwards compatibility.
      'site URL, with optional context, to test hook_rest_relation_uri_alter()' => $base_test_case + [
        'context' => ['rest_test' => TRUE],
        'expected return' => 'rest_test_relation',
        'expected context' => ['rest_test' => TRUE],
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
    ];
  }

  /**
   * @covers ::getRelationInternalIds
   */
  public function testGetRelationInternalIds() {
    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('hal.link_manager.relation');
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $internal_ids = $relation_manager->getRelationInternalIds($link);

    $this->assertEquals([
      'entity_type_id' => 'node',
      'entity_type' => \Drupal::entityTypeManager()->getDefinition('node'),
      'bundle' => 'page',
      'field_name' => 'field_ref'
    ], $internal_ids);
  }

  /**
   * @covers ::setLinkDomain
   */
  public function testHalLinkManagersSetLinkDomain() {
    /* @var \Drupal\rest\LinkManager\LinkManager $link_manager */
    $link_manager = \Drupal::service('hal.link_manager');
    $link_manager->setLinkDomain('http://example.com/');
    $link = $link_manager->getTypeUri('node', 'page');
    $this->assertEqual($link, 'http://example.com/rest/type/node/page');
    $link = $link_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertEqual($link, 'http://example.com/rest/relation/node/page/field_ref');
  }

}
