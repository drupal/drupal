<?php

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests that the deprecated template is correctly marked.
 *
 * @group Theme
 */
class DeprecatedTemplateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['deprecated_twig_template'];

  /**
   * Tests that the deprecated template is marked as deprecated.
   *
   * @group legacy
   */
  public function testDeprecatedTemplate() {
    $this->expectDeprecation('The "deprecated-template.html.twig" template is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another template instead. See https://www.example.com');
    $this->drupalGet('/deprecated-twig-template');
  }

}
