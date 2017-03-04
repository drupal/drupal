<?php

namespace Drupal\Tests\migrate_drupal\Unit\source;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Base class for variable multirow source unit tests.
 */
abstract class VariableMultiRowTestBase extends MigrateSqlSourceTestCase {

  // The plugin system is not working during unit testing so the source plugin
  // class needs to be manually specified.
  const PLUGIN_CLASS = 'Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow';

  // The fake Migration configuration entity.
  protected $migrationConfiguration = [
    'id' => 'test',
    'source' => [
      'plugin' => 'd6_variable_multirow',
      'variables' => [
        'foo',
        'bar',
      ],
    ],
  ];

  protected $expectedResults = [
    ['name' => 'foo', 'value' => 1],
    ['name' => 'bar', 'value' => FALSE],
  ];

  protected $databaseContents = [
    'variable' => [
      ['name' => 'foo', 'value' => 'i:1;'],
      ['name' => 'bar', 'value' => 'b:0;'],
    ],
  ];

}
