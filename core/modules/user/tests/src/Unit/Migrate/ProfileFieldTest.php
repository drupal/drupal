<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\ProfileFieldTest.
 */

namespace Drupal\Tests\user\Unit\Migrate;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;

/**
 * Tests profile_field source plugin.
 *
 * @group user
 */
class ProfileFieldTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = 'Drupal\user\Plugin\migrate\source\ProfileField';

  protected $migrationConfiguration = [
    'id' => 'test_profile_fields',
    'source' => [
      'plugin' => 'd6_profile_field',
    ],
  ];

  // We need to set up the database contents; it's easier to do that below.
  // These are sample result queries.
  // @todo Add multiple cases.
  protected $expectedResults = [
    [
      'fid' => 1,
      'title' => 'First name',
      'name' => 'profile_first_name',
      'explanation' => 'First name user',
      'category' => 'profile',
      'page' => '',
      'type' => 'textfield',
      'weight' => 0,
      'required' => 1,
      'register' => 0,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => [],
    ],
    [
      'fid' => 2,
      'title' => 'Last name',
      'name' => 'profile_last_name',
      'explanation' => 'Last name user',
      'category' => 'profile',
      'page' => '',
      'type' => 'textfield',
      'weight' => 0,
      'required' => 0,
      'register' => 0,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => [],
    ],
    [
      'fid' => 3,
      'title' => 'Policy',
      'name' => 'profile_policy',
      'explanation' => 'A checkbox that say if you accept policy of website',
      'category' => 'profile',
      'page' => '',
      'type' => 'checkbox',
      'weight' => 0,
      'required' => 1,
      'register' => 1,
      'visibility' => 2,
      'autocomplete' => 0,
      'options' => [],
    ],
  ];

  /**
   * Prepopulate contents with results.
   */
  protected function setUp() {
    $this->databaseContents['profile_fields'] = $this->expectedResults;
    foreach ($this->databaseContents['profile_fields'] as &$row) {
      $row['options'] = serialize([]);
    }
    parent::setUp();
  }

}
