<?php

namespace Drupal\system\Tests\System;

use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests validity of date format machine names.
 *
 * @group system
 */
class DateFormatsMachineNameTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create a new administrator user for the test.
    $admin = $this->drupalCreateUser(array('administer site configuration'));
    $this->drupalLogin($admin);
  }

  /**
   * Tests that date formats cannot be created with invalid machine names.
   */
  public function testDateFormatsMachineNameAllowedValues() {
    // Try to create a date format with a not allowed character to test the date
    // format specific machine name replace pattern.
    $edit = array(
      'label' => 'Something Not Allowed',
      'id' => 'something.bad',
      'date_format_pattern' => 'Y-m-d',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'), 'It is not possible to create a date format with the machine name that has any character other than lowercase letters, digits or underscore.');

    // Try to create a date format with the reserved machine name "custom".
    $edit = array(
      'label' => 'Custom',
      'id' => 'custom',
      'date_format_pattern' => 'Y-m-d',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".'), 'It is not possible to create a date format with the machine name "custom".');

    // Try to create a date format with a machine name, "fallback", that
    // already exists.
    $edit = array(
      'label' => 'Fallback',
      'id' => 'fallback',
      'date_format_pattern' => 'j/m/Y',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'), 'It is not possible to create a date format with the machine name "fallback". It is a built-in format that already exists.');

    // Create a date format with a machine name distinct from the previous two.
    $id = Unicode::strtolower($this->randomMachineName(16));
    $edit = array(
      'label' => $this->randomMachineName(16),
      'id' => $id,
      'date_format_pattern' => 'd/m/Y',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('Custom date format added.'), 'It is possible to create a date format with a new machine name.');

    // Try to create a date format with same machine name as the previous one.
    $edit = array(
      'label' => $this->randomMachineName(16),
      'id' => $id,
      'date_format_pattern' => 'd-m-Y',
    );
    $this->drupalPostForm('admin/config/regional/date-time/formats/add', $edit, t('Add format'));
    $this->assertText(t('The machine-readable name is already in use. It must be unique.'), 'It is not possible to create a new date format with an existing machine name.');
  }

}
