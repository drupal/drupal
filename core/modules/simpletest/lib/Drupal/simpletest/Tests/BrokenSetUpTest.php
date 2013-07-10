<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\BrokenSetUpTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests a test case that does not run parent::setUp() in its setUp() method.
 *
 * If a test case does not call parent::setUp(), running
 * \Drupal\simpletest\WebTestBase::tearDown() would destroy the main site's
 * database tables. Therefore, we ensure that tests which are not set up
 * properly are skipped.
 *
 * @see \Drupal\simpletest\WebTestBase
 */
class BrokenSetUpTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  public static function getInfo() {
    return array(
      'name' => 'Broken SimpleTest method',
      'description' => 'Tests a test case that does not call parent::setUp().',
      'group' => 'SimpleTest'
    );
  }

  function setUp() {
    // If the test is being run from the main site, set up normally.
    if (!drupal_valid_test_ua()) {
      parent::setUp();
      // Create and log in user.
      $admin_user = $this->drupalCreateUser(array('administer unit tests'));
      $this->drupalLogin($admin_user);
    }
    // If the test is being run from within simpletest, set up the broken test.
    else {
      $this->pass(t('The test setUp() method has been run.'));
      // Don't call parent::setUp(). This should trigger an error message.
    }
  }

  function tearDown() {
    // If the test is being run from the main site, tear down normally.
    if (!drupal_valid_test_ua()) {
      parent::tearDown();
    }
    else {
      // If the test is being run from within simpletest, output a message.
      $this->pass(t('The tearDown() method has run.'));
    }
  }

  /**
   * Runs this test case from within the simpletest child site.
   */
  function testBreakSetUp() {
    // If the test is being run from the main site, run it again from the web
    // interface within the simpletest child site.
    if (!drupal_valid_test_ua()) {
      $edit['Drupal\simpletest\Tests\BrokenSetUpTest'] = TRUE;
      $this->drupalPost('admin/config/development/testing', $edit, t('Run tests'));

      // Verify that the broken test and its tearDown() method are skipped.
      $this->assertRaw(t('The test setUp() method has been run.'));
      $this->assertRaw(t('The test cannot be executed because it has not been set up properly.'));
      $this->assertNoRaw(t('The test method has run.'));
      $this->assertNoRaw(t('The tearDown() method has run.'));
    }
    // If the test is being run from within simpletest, output a message.
    else {
      $this->pass(t('The test method has run.'));
    }
  }
}
