<?php

namespace Drupal\Tests\tour\Unit\Plugin\tour\tip;

use Drupal\Tests\UnitTestCase;
use Drupal\tour\Plugin\tour\tip\TipPluginText;

/**
 * @coversDefaultClass \Drupal\tour\Plugin\tour\tip\TipPluginText
 * @group tour
 */
class TipPluginTextTest extends UnitTestCase {

  /**
   * Tests that getAriaId returns unique id per plugin instance.
   *
   * @see \Drupal\tour\Plugin\tour\tip\TipPluginText::getAriaId()
   * @runTestsInSeparateProcesses
   * This test calls \Drupal\Component\Utility\Html::getUniqueId() which uses a
   * static list. Run this test in a separate process to prevent side effects.
   */
  public function testGetAriaId() {
    $id_instance_one = 'one';
    $id_instance_two = 'two';
    $config_instance_one = [
      'id' => $id_instance_one,
    ];
    $config_instance_two = [
      'id' => $id_instance_two,
    ];
    $definition = [];
    $plugin_id = 'text';
    $token = $this->createMock('\Drupal\Core\Utility\Token');
    $instance_one = new TipPluginText($config_instance_one, $plugin_id, $definition, $token);
    $instance_two = new TipPluginText($config_instance_two, $plugin_id, $definition, $token);
    $instance_three = new TipPluginText($config_instance_one, $plugin_id, $definition, $token);

    $this->assertEquals($id_instance_one, $instance_one->getAriaId());
    $this->assertEquals($id_instance_two, $instance_two->getAriaId());
    $this->assertNotEquals($instance_one->getAriaId(), $instance_two->getAriaId());
    $this->assertNotEquals($instance_one->getAriaId(), $instance_three->getAriaId());
  }

}
