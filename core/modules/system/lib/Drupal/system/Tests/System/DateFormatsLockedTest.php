<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\DateFormatsLockedTest.
 */

namespace Drupal\system\Tests\System;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the locked functionality of date formats.
 */
class DateFormatsLockedTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Locked date formats',
      'description' => 'Tests the locked functionality of date formats.',
      'group' => 'System',
    );
  }

  /**
   * Tests attempts at listing, editing, and deleting locked date formats.
   */
  public function testDateLocking() {
    $this->drupalLogin($this->root_user);

    // Locked date formats do not show on the listing page.
    $this->drupalGet('admin/config/regional/date-time');
    $this->assertLinkByHref('admin/config/regional/date-time/formats/manage/short');
    $this->assertNoLinkByHref('admin/config/regional/date-time/formats/manage/html_date');

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
