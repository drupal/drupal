<?php

namespace Drupal\Tests\Core\Config;

use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the ConfigDependencyManager class.
 *
 * @group Config
 *
 * @coversDefaultClass \Drupal\Core\Config\Entity\ConfigDependencyManager
 */
class ConfigDependencyManagerTest extends UnitTestCase {

  /**
   * @dataProvider providerTestSortAll
   */
  public function testSortAll(array $data, array $expected_order) {
    $dependency_manager = new ConfigDependencyManager();
    $dependency_manager->setData($data);
    $this->assertEquals($expected_order, $dependency_manager->sortAll());
  }

  public function providerTestSortAll() {
    $datasets[] = [
      [
        'provider.entity_b' => [],
        'provider.entity_a' => [],
      ],
      ['provider.entity_a', 'provider.entity_b'],
    ];

    $datasets[] = [
      [
        'provider.entity_a' => [],
        'provider.entity_b' => [],
      ],
      ['provider.entity_a', 'provider.entity_b'],
    ];

    $datasets[] = [
      [
        'provider.entity_b' => ['dependencies' => ['config' => ['provider.entity_a']]],
        'provider.entity_a' => [],
      ],
      ['provider.entity_a', 'provider.entity_b'],
    ];

    $datasets[] = [
      [
        'provider.entity_a' => [],
        'provider.entity_b' => ['dependencies' => ['config' => ['provider.entity_a']]],
      ],
      ['provider.entity_a', 'provider.entity_b'],
    ];

    $datasets[] = [
      [
        'provider.entity_b' => [],
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_b']]],
      ],
      ['provider.entity_b', 'provider.entity_a'],
    ];

    $datasets[] = [
      [
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_b']]],
        'provider.entity_b' => [],
      ],
      ['provider.entity_b', 'provider.entity_a'],
    ];

    $datasets[] = [
      [
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_b']]],
        'provider.entity_b' => [],
        'block.block.a' => [],
        'block.block.b' => [],
      ],
      ['block.block.a', 'provider.entity_b', 'block.block.b', 'provider.entity_a'],
    ];

    $datasets[] = [
      [
        'provider.entity_b' => [],
        'block.block.b' => [],
        'block.block.a' => [],
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_b']]],
      ],
      ['block.block.a', 'provider.entity_b', 'block.block.b', 'provider.entity_a'],
    ];

    $datasets[] = [
      [
        'provider.entity_b' => [],
        'block.block.b' => [],
        'block.block.a' => [],
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_b']]],
        'provider.entity_c' => ['dependencies' => ['config' => ['block.block.a']]],
      ],
      ['block.block.a', 'block.block.b', 'provider.entity_b', 'provider.entity_a', 'provider.entity_c'],
    ];

    $datasets[] = [
      [
        'provider.entity_b' => ['dependencies' => ['module' => ['system']]],
        'block.block.b' => [],
        'block.block.a' => ['dependencies' => ['module' => ['system']]],
        'provider.entity_a' => ['dependencies' => ['config' => ['provider.entity_c']]],
        'provider.entity_c' => ['dependencies' => ['config' => ['block.block.a']]],
      ],
      ['block.block.b', 'block.block.a', 'provider.entity_c', 'provider.entity_a', 'provider.entity_b'],
    ];

    return $datasets;
  }

}
