<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Kernel\Theme;

use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests theme deprecations.
 */
#[Group('Theme')]
#[RunTestsInSeparateProcesses]
#[IgnoreDeprecations]
class ThemeDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['module_test_procedural_preprocess'];

  /**
   * Tests deprecations around template_preprocess functions and include files.
   */
  public function testTemplatePreprocessIncludes(): void {
    $this->expectDeprecation('Providing a file for theme hook module_test_procedural_preprocess_theme is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use initial preprocess for template_preprocess instead. See https://www.drupal.org/node/3549500');
    $this->expectDeprecation('Providing template_preprocess_module_test_procedural_preprocess_theme() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use initial preprocess for template_preprocess instead. See https://www.drupal.org/node/3504125');
    $this->expectDeprecation('Providing includes for theme hook module_test_procedural_preprocess_includes is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use initial preprocess for template_preprocess instead. See https://www.drupal.org/node/3549500');
    $this->expectDeprecation('Providing template_preprocess_module_test_procedural_preprocess_includes() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. Use initial preprocess for template_preprocess instead. See https://www.drupal.org/node/3504125');

    $registry = $this->container->get('theme.registry');

    $theme = $registry->getRuntime()->get('module_test_procedural_preprocess_theme');
    $this->assertEquals([
      'file' => 'module_test_procedural_preprocess.theme.inc',
      'type' => 'module',
      'theme path' => 'core/modules/system/tests/modules/module_test_procedural_preprocess',
      'includes' => [
        'core/modules/system/tests/modules/module_test_procedural_preprocess/module_test_procedural_preprocess.theme.inc',
      ],
      'template' => 'module-test-procedural-preprocess-theme',
      'preprocess functions' => [
        'template_preprocess_module_test_procedural_preprocess_theme',
      ],
      'path' => 'core/modules/system/tests/modules/module_test_procedural_preprocess/templates',
    ], $theme);

    $theme = $registry->getRuntime()->get('module_test_procedural_preprocess_includes');
    $this->assertEquals([
      'type' => 'module',
      'theme path' => 'core/modules/system/tests/modules/module_test_procedural_preprocess',
      'includes' => [
        'core/modules/system/tests/modules/module_test_procedural_preprocess/module_test_procedural_preprocess.additional.inc',
      ],
      'template' => 'module-test-procedural-preprocess-includes',
      'preprocess functions' => [
        'template_preprocess_module_test_procedural_preprocess_includes',
      ],
      'path' => 'core/modules/system/tests/modules/module_test_procedural_preprocess/templates',
    ], $theme);
  }

}
