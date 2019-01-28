<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * This test will check WebTestBase's default time zone handling.
 *
 * @group simpletest
 * @group WebTestBase
 */
class TimeZoneTest extends WebTestBase {

  /**
   * A user with administrative privileges.
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser(['administer site configuration']);
  }

  /**
   * Tests that user accounts have the default time zone set.
   */
  public function testAccountTimeZones() {
    $expected = 'Australia/Sydney';
    $this->assertEqual($this->rootUser->getTimeZone(), $expected, 'Root user has correct time zone.');
    $this->assertEqual($this->adminUser->getTimeZone(), $expected, 'Admin user has correct time zone.');
  }

}
