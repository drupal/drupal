<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\Module;

use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests that the deprecated template is correctly marked.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
class DeprecatedTemplateTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['deprecated_twig_template'];

  /**
   * Tests that the deprecated template is marked as deprecated.
   */
  #[IgnoreDeprecations]
  public function testDeprecatedTemplate(): void {
    $this->expectDeprecation('The "deprecated-template.html.twig" template is deprecated in drupal:X.0.0 and is removed from drupal:Y.0.0. Use another template instead. See https://www.example.com');
    $this->drupalGet('/deprecated-twig-template');
  }

}
