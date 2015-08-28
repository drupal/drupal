<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\VariableMultiRowTestBase.
 */

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
  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_variable_multirow',
      'variables' => array(
        'foo',
        'bar',
      ),
    ),
  );

  protected $expectedResults = array(
    array('name' => 'foo', 'value' => 1),
    array('name' => 'bar', 'value' => FALSE),
  );

  protected $databaseContents = array(
    'variable' => array(
      array('name' => 'foo', 'value' => 'i:1;'),
      array('name' => 'bar', 'value' => 'b:0;'),
    ),
  );

}
