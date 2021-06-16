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
  protected static $modules = ['form_test'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests getAllOptions().
   */
  public function testGetAllOptions() {
    $this->expectDeprecation('AssertLegacyTrait::getAllOptions() is deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use $element->findAll(\'xpath\', \'option\') instead. See https://www.drupal.org/node/3129738');
    $this->drupalGet('/form-test/select');
    $this->assertCount(6, $this->getAllOptions($this->cssSelect('select[name="opt_groups"]')[0]));
  }

}
