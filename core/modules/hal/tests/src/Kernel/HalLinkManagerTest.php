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
  public static $modules = [ 'hal', 'hal_test', 'serialization', 'system', 'node', 'user', 'field'];

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
   */
  public function testGetTypeUri() {
    /* @var \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('hal.link_manager.type');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $type_manager->getTypeUri('node', 'page');
    $this->assertSame($link, $base . 'rest/type/node/page');
    // Now with optional context.
    $link = $type_manager->getTypeUri('node', 'page', ['hal_test' => TRUE]);
    $this->assertSame($link, 'hal_test_type');
    // Test BC: hook_rest_type_uri_alter().
    $link = $type_manager->getTypeUri('node', 'page', ['rest_test' => TRUE]);
    $this->assertSame($link, 'rest_test_type');
  }

  /**
   * @covers ::getRelationUri
   */
  public function testGetRelationUri() {
    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('hal.link_manager.relation');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertSame($link, $base . 'rest/relation/node/page/field_ref');
    // Now with optional context.
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['hal_test' => TRUE]);
    $this->assertSame($link, 'hal_test_relation');
    // Test BC: hook_rest_relation_uri_alter().
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['rest_test' => TRUE]);
    $this->assertSame($link, 'rest_test_relation');
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
