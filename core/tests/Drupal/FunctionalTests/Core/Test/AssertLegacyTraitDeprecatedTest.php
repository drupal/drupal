<?php

namespace Drupal\FunctionalTests\Core\Test;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests deprecated AssertLegacyTrait functionality.
 *
 * @group browsertestbase
 * @group legacy
 */
class AssertLegacyTraitDeprecatedTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['form_test'];

  /**
   * Tests getAllOptions().
   *
   * @expectedDeprecation AssertLegacyTrait::getAllOptions() is scheduled for removal in Drupal 9.0.0. Use $element->findAll('xpath', 'option') instead.
   */
  public function testGetAllOptions() {
    $this->drupalGet('/form-test/select');
    $this->assertCount(6, $this->getAllOptions($this->cssSelect('select[name="opt_groups"]')[0]));
  }

}
