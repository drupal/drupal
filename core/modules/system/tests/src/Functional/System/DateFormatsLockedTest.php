<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\System;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the locked functionality of date formats.
 *
 * @group system
 */
class DateFormatsLockedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests attempts at listing, editing, and deleting locked date formats.
   */
  public function testDateLocking(): void {
    $this->drupalLogin($this->drupalCreateUser(['administer site configuration']));

    // Locked date formats are not linked on the listing page, locked date
    // formats are clearly marked as such; unlocked formats are not marked as
    // "locked".
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertSession()->linkByHrefExists('admin/config/regional/date-time/formats/manage/short');
    $this->assertSession()->linkByHrefNotExists('admin/config/regional/date-time/formats/manage/html_date');
    $this->assertSession()->pageTextContains('Fallback date format');
    $this->assertSession()->pageTextNotContains('short (locked)');

    // Locked date formats are not editable.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/short');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_date');
    $this->assertSession()->statusCodeEquals(403);

    // Locked date formats are not deletable.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/short/delete');
    $this->assertSession()->statusCodeEquals(200);
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_date/delete');
    $this->assertSession()->statusCodeEquals(403);
  }

}
