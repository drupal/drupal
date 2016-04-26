<?php

namespace Drupal\Tests\rest\Kernel;

use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that REST type and relation link managers work as expected
 * @group rest
 */
class RestLinkManagerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['rest', 'rest_test', 'system'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Tests that type hooks work as expected.
   */
  public function testRestLinkManagers() {
    \Drupal::moduleHandler()->invoke('rest', 'install');
    /* @var \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('rest.link_manager.type');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $type_manager->getTypeUri('node', 'page');
    $this->assertEqual($link, $base . 'rest/type/node/page');
    // Now with optional context.
    $link = $type_manager->getTypeUri('node', 'page', ['rest_test' => TRUE]);
    $this->assertEqual($link, 'rest_test_type');

    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('rest.link_manager.relation');
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertEqual($link, $base . 'rest/relation/node/page/field_ref');
    // Now with optional context.
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['rest_test' => TRUE]);
    $this->assertEqual($link, 'rest_test_relation');
  }

  /**
   * Tests that type hooks work as expected even without install hook.
   */
  public function testRestLinkManagersNoInstallHook() {
    /* @var \Drupal\rest\LinkManager\TypeLinkManagerInterface $type_manager */
    $type_manager = \Drupal::service('rest.link_manager.type');
    $base = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
    $link = $type_manager->getTypeUri('node', 'page');
    $this->assertEqual($link, $base . 'rest/type/node/page');
    // Now with optional context.
    $link = $type_manager->getTypeUri('node', 'page', ['rest_test' => TRUE]);
    $this->assertEqual($link, 'rest_test_type');

    /* @var \Drupal\rest\LinkManager\RelationLinkManagerInterface $relation_manager */
    $relation_manager = \Drupal::service('rest.link_manager.relation');
    $link = $relation_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertEqual($link, $base . 'rest/relation/node/page/field_ref');
    // Now with optional context.
    $link = $relation_manager->getRelationUri('node', 'page', 'foobar', ['rest_test' => TRUE]);
    $this->assertEqual($link, 'rest_test_relation');
  }

  /**
   * Tests \Drupal\rest\LinkManager\LinkManager::setLinkDomain().
   */
  public function testRestLinkManagersSetLinkDomain() {
    /* @var \Drupal\rest\LinkManager\LinkManager $link_manager */
    $link_manager = \Drupal::service('rest.link_manager');
    $link_manager->setLinkDomain('http://example.com/');
    $link = $link_manager->getTypeUri('node', 'page');
    $this->assertEqual($link, 'http://example.com/rest/type/node/page');
    $link = $link_manager->getRelationUri('node', 'page', 'field_ref');
    $this->assertEqual($link, 'http://example.com/rest/relation/node/page/field_ref');
  }

}
