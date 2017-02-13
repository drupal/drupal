<?php

namespace Drupal\system\Tests\Entity;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\simpletest\WebTestBase;

/**
 * Tests that hooks are invoked or not depending on render cache.
 *
 * @group Entity
 */
class EntityRenderCacheHookTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('entity_test_hooks', 'entity_test');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    \Drupal::service('module_installer')->uninstall(['page_cache', 'dynamic_page_cache']);
  }

  /**
   * Tests that hooks are not called when entities are render cached.
   */
  public function testHookInvocationAndRenderCache() {
    $entity = EntityTest::create(['name' => 'test entity']);
    $entity->save();

    $this->drupalGet('test-view/' . $entity->id());
    $this->assertText('test entity');
    $this->assertText('custom hook invocation called');

    $this->drupalGet('test-view/' . $entity->id());
    $this->assertText('test entity');
    $this->assertNoText('custom hook invocation called');

    $this->drupalGet('test-view-multiple/' . $entity->id());
    $this->assertText('test entity');
    $this->assertNoText('custom hook invocation called');

  }
}
