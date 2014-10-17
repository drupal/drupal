<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\DefaultSingleLazyPluginCollectionTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\DefaultSingleLazyPluginCollection;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultSingleLazyPluginCollection
 * @group Plugin
 */
class DefaultSingleLazyPluginCollectionTest extends LazyPluginCollectionTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setupPluginCollection(\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $create_count = NULL) {
    $definitions = $this->getPluginDefinitions();
    $this->pluginInstances['apple'] = $this->getPluginMock('apple', $definitions['apple']);
    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->will($this->returnValue($this->pluginInstances['apple']));

    $this->defaultPluginCollection = new DefaultSingleLazyPluginCollection($this->pluginManager, 'apple', array('id' => 'apple', 'key' => 'value'));
  }

  /**
   * Tests the get() method.
   */
  public function testGet() {
    $this->setupPluginCollection($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginCollection->get('apple'));
  }

}
