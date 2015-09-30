<?php

/**
 * @file
 * Contains \Drupal\Tests\user\Unit\Migrate\d6\ProfileFieldValuesTest.
 */

namespace Drupal\Tests\user\Unit\Migrate\d6;

use Drupal\Tests\migrate\Unit\MigrateSqlSourceTestCase;
use Drupal\user\Plugin\migrate\source\d6\ProfileFieldValues;

/**
 * Tests the d6_profile_field_values source plugin.
 *
 * @group user
 */
class ProfileFieldValuesTest extends MigrateSqlSourceTestCase {

  const PLUGIN_CLASS = ProfileFieldValues::class;

  protected $migrationConfiguration = array(
    'id' => 'test',
    'source' => array(
      'plugin' => 'd6_profile_field_values',
    ),
  );

  protected $expectedResults = array(
    array(
      'fid' => '8',
      'profile_color' => array('red'),
      'uid' => '2',
    ),
    array(
      'fid' => '9',
      'profile_biography' => array('Lorem ipsum dolor sit amet...'),
      'uid' => '2',
    ),
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->databaseContents['profile_values'] = array(
      array(
        'fid' => '8',
        'uid' => '2',
        'value' => 'red',
      ),
      array(
        'fid' => '9',
        'uid' => '2',
        'value' => 'Lorem ipsum dolor sit amet...',
      ),
    );
    $this->databaseContents['profile_fields'] = array(
      array(
        'fid' => '8',
        'title' => 'Favorite color',
        'name' => 'profile_color',
        'explanation' => 'List your favorite color',
        'category' => 'Personal information',
        'page' => 'Peole whose favorite color is %value',
        'type' => 'textfield',
        'weight' => '-10',
        'required' => '0',
        'register' => '1',
        'visibility' => '2',
        'autocomplete' => '1',
        'options' => '',
      ),
      array(
        'fid' => '9',
        'title' => 'Biography',
        'name' => 'profile_biography',
        'explanation' => 'Tell people a little bit about yourself',
        'category' => 'Personal information',
        'page' => '',
        'type' => 'textarea',
        'weight' => '-8',
        'required' => '0',
        'register' => '0',
        'visibility' => '2',
        'autocomplete' => '0',
        'options' => '',
      ),
    );
    parent::setUp();
  }

}
