<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Plugin\DefaultSinglePluginBagTest.
 */

namespace Drupal\Tests\Core\Plugin;

use Drupal\Core\Plugin\DefaultSinglePluginBag;

/**
 * @coversDefaultClass \Drupal\Core\Plugin\DefaultSinglePluginBag
 * @group Plugin
 */
class DefaultSinglePluginBagTest extends PluginBagTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setupPluginBag(\PHPUnit_Framework_MockObject_Matcher_InvokedRecorder $create_count = NULL) {
    $definitions = $this->getPluginDefinitions();
    $this->pluginInstances['apple'] = $this->getPluginMock('apple', $definitions['apple']);
    $create_count = $create_count ?: $this->never();
    $this->pluginManager->expects($create_count)
      ->method('createInstance')
      ->will($this->returnValue($this->pluginInstances['apple']));

    $this->defaultPluginBag = new DefaultSinglePluginBag($this->pluginManager, 'apple', array('id' => 'apple', 'key' => 'value'));
  }

  /**
   * Tests the get() method.
   */
  public function testGet() {
    $this->setupPluginBag($this->once());
    $apple = $this->pluginInstances['apple'];

    $this->assertSame($apple, $this->defaultPluginBag->get('apple'));
  }

}
