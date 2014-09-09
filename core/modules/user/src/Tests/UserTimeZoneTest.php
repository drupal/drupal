<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTimeZoneTest.
 */

namespace Drupal\user\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Set a user time zone and verify that dates are displayed in local time.
 *
 * @group user
 */
class UserTimeZoneTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('node');

  /**
   * Tests the display of dates and time when user-configurable time zones are set.
   */
  function testUserTimeZone() {
    // Setup date/time settings for Los Angeles time.
    \Drupal::config('system.date')
      ->set('timezone.user.configurable', 1)
      ->set('timezone.default', 'America/Los_Angeles')
      ->save();
    entity_load('date_format', 'medium')
      ->setPattern('Y-m-d H:i T')
      ->save();

    // Create a user account and login.
    $web_user = $this->drupalCreateUser();
    $this->drupalLogin($web_user);

    // Create some nodes with different authored-on dates.
    // Two dates in PST (winter time):
    $date1 = '2007-03-09 21:00:00 -0800';
    $date2 = '2007-03-11 01:00:00 -0800';
    // One date in PDT (summer time):
    $date3 = '2007-03-20 21:00:00 -0700';
    $this->drupalCreateContentType(array('type' => 'article'));
    $node1 = $this->drupalCreateNode(array('created' => strtotime($date1), 'type' => 'article'));
    $node2 = $this->drupalCreateNode(array('created' => strtotime($date2), 'type' => 'article'));
    $node3 = $this->drupalCreateNode(array('created' => strtotime($date3), 'type' => 'article'));

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-03-09 21:00 PST', 'Date should be PST.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-03-11 01:00 PST', 'Date should be PST.');
    $this->drupalGet('node/' . $node3->id());
    $this->assertText('2007-03-20 21:00 PDT', 'Date should be PDT.');

    // Change user time zone to Santiago time.
    $edit = array();
    $edit['mail'] = $web_user->getEmail();
    $edit['timezone'] = 'America/Santiago';
    $this->drupalPostForm("user/" . $web_user->id() . "/edit", $edit, t('Save'));
    $this->assertText(t('The changes have been saved.'), 'Time zone changed to Santiago time.');

    // Confirm date format and time zone.
    $this->drupalGet('node/' . $node1->id());
    $this->assertText('2007-03-10 02:00 CLST', 'Date should be Chile summer time; five hours ahead of PST.');
    $this->drupalGet('node/' . $node2->id());
    $this->assertText('2007-03-11 05:00 CLT', 'Date should be Chile time; four hours ahead of PST');
    $this->drupalGet('node/' . $node3->id());
    $this->assertText('2007-03-21 00:00 CLT', 'Date should be Chile time; three hours ahead of PDT.');
  }
}
