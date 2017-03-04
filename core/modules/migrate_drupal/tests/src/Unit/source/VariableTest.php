<?php

namespace Drupal\Tests\migrate_drupal\Unit\source;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests the variable source plugin.
 *
 * @group migrate_drupal
 */
class VariableTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\Variable';

  protected $migrationConfiguration = [
    'id' => 'test',
    'highWaterProperty' => ['field' => 'test'],
    'source' => [
      'plugin' => 'd6_variable',
      'variables' => [
        'foo',
        'bar',
      ],
    ],
  ];

  protected $expectedResults = [
    [
      'id' => 'foo',
      'foo' => 1,
      'bar' => FALSE,
    ],
  ];

  protected $databaseContents = [
    'variable' => [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ],
  ];

}
