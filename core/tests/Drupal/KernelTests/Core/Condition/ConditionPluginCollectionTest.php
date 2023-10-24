<?php

namespace Drupal\KernelTests\Core\Condition;

use Drupal\Core\Condition\ConditionPluginCollection;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Condition\ConditionPluginCollection
 *
 * @group Condition
 */
class ConditionPluginCollectionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'path_alias',
  ];

  /**
   * @covers ::getConfiguration
   */
  public function testGetConfiguration(): void {
    // Include a condition that has custom configuration and a type mismatch on
    // 'negate' by using 0 instead of FALSE.
    $configuration['request_path'] = [
      'id' => 'request_path',
      'negate' => 0,
      'context_mapping' => [],
      'pages' => '/user/*',
    ];
    // Include a condition that matches default values but with a type mismatch
    // on 'negate' by using 0 instead of FALSE. This condition will be removed,
    // because condition configurations that match default values with "=="
    // comparison are not saved or exported.
    $configuration['user_role'] = [
      'id' => 'user_role',
      'negate' => '0',
      'context_mapping' => [],
      'roles' => [],
    ];
    $collection = new ConditionPluginCollection(\Drupal::service('plugin.manager.condition'), $configuration);

    $expected['request_path'] = [
      'id' => 'request_path',
      'negate' => 0,
      'context_mapping' => [],
      'pages' => '/user/*',
    ];
    // NB: The 'user_role' property should not exist in expected set.
    $this->assertSame($expected, $collection->getConfiguration());
  }

}
