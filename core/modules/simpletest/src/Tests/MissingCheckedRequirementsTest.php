<?php

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests a test case with missing requirements.
 *
 * @group simpletest
 * @group WebTestBase
 * @group legacy
 */
class MissingCheckedRequirementsTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simpletest'];

  protected function setUp() {
    parent::setUp();
    $admin_user = $this->drupalCreateUser(['administer unit tests']);
    $this->drupalLogin($admin_user);
  }

  /**
   * Overrides checkRequirements().
   */
  protected function checkRequirements() {
    if ($this->isInChildSite()) {
      return [
        'Test is not allowed to run.',
      ];
    }
    return parent::checkRequirements();
  }

  /**
   * Ensures test will not run when requirements are missing.
   */
  public function testCheckRequirements() {
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
