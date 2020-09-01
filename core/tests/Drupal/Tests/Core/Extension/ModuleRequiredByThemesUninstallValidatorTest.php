<?php

namespace Drupal\Tests\Core\Extension;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleRequiredByThemesUninstallValidator;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Extension\ModuleRequiredByThemesUninstallValidator
 * @group Extension
 */
class ModuleRequiredByThemesUninstallValidatorTest extends UnitTestCase {

  /**
   * Instance of ModuleRequiredByThemesUninstallValidator.
   *
   * @var \Drupal\Core\Extension\ModuleRequiredByThemesUninstallValidator
   */
  protected $moduleRequiredByThemeUninstallValidator;

  /**
   * Mock of ModuleExtensionList.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * Mock of ThemeExtensionList.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleExtensionList = $this->prophesize(ModuleExtensionList::class);
    $this->themeExtensionList = $this->prophesize(ThemeExtensionList::class);
    $this->moduleRequiredByThemeUninstallValidator = new ModuleRequiredByThemesUninstallValidator($this->getStringTranslationStub(), $this->moduleExtensionList->reveal(), $this->themeExtensionList->reveal());
  }

  /**
   * @covers ::validate
   */
  public function testValidateNoThemeDependency() {
    $this->themeExtensionList->getAllInstalledInfo()->willReturn([
      'stable' => [
        'name' => 'Stable',
        'dependencies' => [],
      ],
      'claro' => [
        'name' => 'Claro',
        'dependencies' => [],
      ],
    ]);

    $module = $this->randomMachineName();
    $expected = [];
    $reasons = $this->moduleRequiredByThemeUninstallValidator->validate($module);
    $this->assertSame($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateOneThemeDependency() {
    $module = 'single_module';
    $module_name = 'Single Module';
    $theme = 'one_theme';
    $theme_name = 'One Theme';
    $this->themeExtensionList->getAllInstalledInfo()->willReturn([
      'stable' => [
        'name' => 'Stable',
        'dependencies' => [],
      ],
      'claro' => [
        'name' => 'Claro',
        'dependencies' => [],
      ],
      $theme => [
        'name' => $theme_name,
        'dependencies' => [
          $module,
        ],
      ],
    ]);

    $this->moduleExtensionList->get($module)->willReturn((object) [
      'info' => [
        'name' => $module_name,
      ],
    ]);

    $expected = [
      "Required by the theme: $theme_name",
    ];

    $reasons = $this->moduleRequiredByThemeUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

  /**
   * @covers ::validate
   */
  public function testValidateTwoThemeDependencies() {
    $module = 'popular_module';
    $module_name = 'Popular Module';
    $theme1 = 'first_theme';
    $theme2 = 'second_theme';
    $theme_name_1 = 'First Theme';
    $theme_name_2 = 'Second Theme';
    $this->themeExtensionList->getAllInstalledInfo()->willReturn([
      'stable' => [
        'name' => 'Stable',
        'dependencies' => [],
      ],
      'claro' => [
        'name' => 'Claro',
        'dependencies' => [],
      ],
      $theme1 => [
        'name' => $theme_name_1,
        'dependencies' => [
          $module,
        ],
      ],
      $theme2 => [
        'name' => $theme_name_2,
        'dependencies' => [
          $module,
        ],
      ],
    ]);

    $this->moduleExtensionList->get($module)->willReturn((object) [
      'info' => [
        'name' => $module_name,
      ],
    ]);

    $expected = [
      "Required by the themes: $theme_name_1, $theme_name_2",
    ];

    $reasons = $this->moduleRequiredByThemeUninstallValidator->validate($module);
    $this->assertEquals($expected, $reasons);
  }

}
