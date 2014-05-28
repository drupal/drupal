<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\MissingCheckedRequirementsTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests missing requirements to run test.
 */
class MissingCheckedRequirementsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest');

  public static function getInfo() {
    return array(
      'name' => 'Broken requirements test',
      'description' => 'Tests a test case with missing requirements.',
      'group' => 'SimpleTest',
    );
  }

  function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(array('administer unit tests'));
    $this->drupalLogin($admin_user);
  }

  /**
   * Overrides checkRequirements().
   */
  protected function checkRequirements() {
    if ($this->isInChildSite()) {
      return array(
        'Test is not allowed to run.'
      );
    }
    return parent::checkRequirements();
  }

  /**
   * Ensures test will not run when requirements are missing.
   */
  protected function testCheckRequirements() {
    // If this is the main request, run the web test script and then assert
    // that the child tests did not run.
    if (!$this->isInChildSite()) {
      // Run this test from web interface.
      $edit['tests[Drupal\simpletest\Tests\MissingCheckedRequirementsTest]'] = TRUE;
      $this->drupalPostForm('admin/config/development/testing', $edit, t('Run tests'));
      $this->assertRaw('Test is not allowed to run.', 'Test check for requirements came up.');
      $this->assertNoText('Test ran when it failed requirements check.', 'Test requirements stopped test from running.');
    }
    else {
      $this->fail('Test ran when it failed requirements check.');
    }
  }
}
