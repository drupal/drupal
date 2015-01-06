<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\DateFormatsLockedTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the locked functionality of date formats.
 *
 * @group system
 */
class DateFormatsLockedTest extends WebTestBase {

  /**
   * Tests attempts at listing, editing, and deleting locked date formats.
   */
  public function testDateLocking() {
    $this->drupalLogin($this->rootUser);

    // Locked date formats are not linked on the listing page, locked date
    // formats are clearly marked as such; unlocked formats are not marked as
    // "locked".
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/short');
    $this->assertNoLinkByHref('admin/config/regional/date-time/formats/manage/html_date');
    $this->assertText('fallback (locked)');
    $this->assertNoText('short (locked)');

    // Locked date formats are not editable.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/short');
    $this->assertResponse(200);
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_date');
    $this->assertResponse(403);

    // Locked date formats are not deletable.
    $this->drupalGet('admin/config/regional/date-time/formats/manage/short/delete');
    $this->assertResponse(200);
    $this->drupalGet('admin/config/regional/date-time/formats/manage/html_date/delete');
    $this->assertResponse(403);
  }

}
