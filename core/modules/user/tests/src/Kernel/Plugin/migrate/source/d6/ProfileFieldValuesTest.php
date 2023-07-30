<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source\d6;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the d6_profile_field_values source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\d6\ProfileFieldValues
 * @group user
 */
class ProfileFieldValuesTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [];

    // The source data.
    $tests[0]['source_data']['profile_values'] = [
      [
        'fid' => '8',
        'uid' => '2',
        'value' => 'red',
      ],
      [
        'fid' => '9',
        'uid' => '2',
        'value' => 'The quick brown fox ...',
      ],
    ];

    $tests[0]['source_data']['profile_fields'] = [
      [
        'fid' => '8',
        'title' => 'Favorite color',
        'name' => 'profile_color',
        'explanation' => 'List your favorite color',
        'category' => 'Personal information',
        'page' => 'People whose favorite color is %value',
        'type' => 'textfield',
        'weight' => '-10',
        'required' => '0',
        'register' => '1',
        'visibility' => '2',
        'autocomplete' => '1',
        'options' => '',
      ],
      [
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
      ],
    ];

    // The expected results.
    $tests[0]['expected_data'] = [
      [
        'profile_color' => ['red'],
        'profile_biography' => ['The quick brown fox ...'],
        'uid' => '2',
      ],
    ];

    return $tests;
  }

}
