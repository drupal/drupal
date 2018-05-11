<?php

namespace Drupal\Tests\user\Kernel\Plugin\migrate\source;

use Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase;

/**
 * Tests the profile_field source plugin.
 *
 * @covers \Drupal\user\Plugin\migrate\source\ProfileField
 * @group user
 */
class ProfileFieldTest extends MigrateSqlSourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['user', 'migrate_drupal'];

  /**
   * {@inheritdoc}
   */
  public function providerSource() {
    $tests = [
      [
        'source_data' => [],
        'expected_data' => [],
       ],
    ];

    $profile_fields = [
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
        'options' => '',
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
        'options' => '',
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
        'options' => '',
      ],
      [
        'fid' => 4,
        'title' => 'Color',
        'name' => 'profile_color',
        'explanation' => 'A selection that allows user to select a color',
        'category' => 'profile',
        'page' => '',
        'type' => 'selection',
        'weight' => 0,
        'required' => 0,
        'register' => 0,
        'visibility' => 2,
        'autocomplete' => 0,
        'options' => "red\nblue\ngreen",
      ],
    ];

    $tests[0]['source_data']['profile_fields'] = $profile_fields;

    // Profile values are merged with pre-set options of a "selection" field.
    $tests[0]['source_data']['profile_values'] = [
      [
        'fid' => 4,
        'uid' => 1,
        'value' => 'yellow',
      ],
    ];

    // Expected options are:
    //  for "checkbox" fields - array with NULL options
    //  for "selection" fields - options in both keys and values
    $expected_field_options = [
      '',
      '',
      [NULL, NULL],
      [
        'red' => 'red',
        'blue' => 'blue',
        'green' => 'green',
        'yellow' => 'yellow',
      ],
    ];

    $tests[0]['expected_data'] = $profile_fields;

    foreach ($tests[0]['expected_data'] as $delta => $row) {
      $tests[0]['expected_data'][$delta]['options'] = $expected_field_options[$delta];
    }

    return $tests;
  }

}
