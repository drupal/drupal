<?php

namespace Drupal\Tests\serialization\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\serialization\LinkManager\LinkManager
 * @group serialization
 */
class SerializationLinkManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['serialization', 'serialization_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * @covers ::getTypeUri
   */
  public function testGetTypeUri() {
    /* @var \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('serialization.link_manager.type');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $type_manager->getTypeUri('node', 'page');
    $this->assertSame($link, $base . 'rest/type/node/page');
    // Now with optional context.
    $link = $type_manager->getTypeUri('node', 'page', ['serialization_test' => TRUE]);
    $this->assertSame($link, 'serialization_test_type');
    // Test BC: hook_rest_type_uri_alter().
    $link = $type_manager->getTypeUri('node', 'page', ['rest_test' => TRUE]);
    $this->assertSame($link, 'rest_test_type');
  }

  /**
   * @covers ::getRelationUri
   */
  public function testGetRelationUri() {
    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('serialization.link_manager.relation');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertSame($link, $base . 'rest/relation/node/page/field_ref');
    // Now with optional context.
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['serialization_test' => TRUE]);
    $this->assertSame($link, 'serialization_test_relation');
    // Test BC: hook_rest_relation_uri_alter().
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['rest_test' => TRUE]);
    $this->assertSame($link, 'rest_test_relation');
  }

  /**
   * @covers ::setLinkDomain
   */
  public function testSerializationLinkManagersSetLinkDomain() {
    /* @var \Drupal\rest\LinkManager\LinkManager $link_manager */
    $link_manager = \Drupal::service('serialization.link_manager');
    $link_manager->setLinkDomain('http://example.com/');
    $link = $link_manager->getTypeUri('node', 'page');
    $this->assertEqual($link, 'http://example.com/rest/type/node/page');
    $link = $link_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertEqual($link, 'http://example.com/rest/relation/node/page/field_ref');
  }

}
