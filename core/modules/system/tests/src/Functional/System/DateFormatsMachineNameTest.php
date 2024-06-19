<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests validity of date format machine names.
 *
 * @group system
 */
class DateFormatsMachineNameTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create a new administrator user for the test.
    $admin = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($admin);
  }

  /**
   * Tests that date formats cannot be created with invalid machine names.
   */
  public function testDateFormatsMachineNameAllowedValues(): void {
    // Try to create a date format with a not allowed character to test the date
    // format specific machine name replace pattern.
    $edit = [
      'label' => 'Something Not Allowed',
      'id' => 'something.bad',
      'date_format_pattern' => 'Y-m-d',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');
    $this->assertSession()->pageTextContains('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".');

    // Try to create a date format with the reserved machine name "custom".
    $edit = [
      'label' => 'Custom',
      'id' => 'custom',
      'date_format_pattern' => 'Y-m-d',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');
    $this->assertSession()->pageTextContains('The machine-readable name must be unique, and can only contain lowercase letters, numbers, and underscores. Additionally, it can not be the reserved word "custom".');

    // Try to create a date format with a machine name, "fallback", that
    // already exists.
    $edit = [
      'label' => 'Fallback',
      'id' => 'fallback',
      'date_format_pattern' => 'j/m/Y',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');

    // Create a date format with a machine name distinct from the previous two.
    $id = $this->randomMachineName(16);
    $edit = [
      'label' => $this->randomMachineName(16),
      'id' => $id,
      'date_format_pattern' => 'd/m/Y',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');
    $this->assertSession()->pageTextContains('Custom date format added.');

    // Try to create a date format with same machine name as the previous one.
    $edit = [
      'label' => $this->randomMachineName(16),
      'id' => $id,
      'date_format_pattern' => 'd-m-Y',
    ];
    $this->drupalGet('admin/config/regional/date-time/formats/add');
    $this->submitForm($edit, 'Add format');
    $this->assertSession()->pageTextContains('The machine-readable name is already in use. It must be unique.');
  }

}
