<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Extension;

use Composer\Autoload\ClassLoader;
use Drupal\Core\Extension\Theme;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Extension\ThemeHandler;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Stub;

/**
 * Tests Drupal\Core\Extension\ThemeHandler.
 */
#[CoversClass(ThemeHandler::class)]
#[Group('Extension')]
class ThemeHandlerTest extends UnitTestCase {

  /**
   * The mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configFactory;

  /**
   * The theme listing service.
   */
  protected ThemeExtensionList&Stub $themeList;

  /**
   * The tested theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandler|\Drupal\Tests\Core\Extension\StubThemeHandler
   */
  protected $themeHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->configFactory = $this->getConfigFactoryStub([
      'core.extension' => [
        'module' => [],
        'theme' => ['stark' => 'stark'],
        'disabled' => [
          'theme' => [],
        ],
      ],
    ]);
    $this->themeList = $this->createStub(ThemeExtensionList::class);
    $this->themeHandler = new StubThemeHandler($this->root, $this->configFactory, $this->themeList);

    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container
      ->method('get')
      ->with('class_loader')
      ->willReturn($this->createStub(ClassLoader::class));
    \Drupal::setContainer($container);
  }

  /**
   * Tests empty libraries in theme.info.yml file.
   */
  public function testThemeLibrariesEmpty(): void {
    $theme = new Theme($this->root, 'core/modules/system/tests/themes/test_theme_libraries_empty', [], 'test_theme_libraries_empty.info.yml');
    try {
      $this->themeHandler->addTheme($theme);
      $this->assertTrue(TRUE, 'Empty libraries key in theme.info.yml does not cause PHP warning');
    }
    catch (\Exception) {
      $this->fail('Empty libraries key in theme.info.yml causes PHP warning.');
    }
  }

  /**
   * Test that a missing theme doesn't break ThemeHandler::listInfo().
   *
   * @see ::listInfo
   */
  public function testMissingTheme(): void {
    $themes = $this->themeHandler->listInfo();
    $this->assertSame([], $themes);
  }

}

/**
 * Extends the default theme handler to mock some drupal_ methods.
 */
class StubThemeHandler extends ThemeHandler {

  /**
   * Whether the CSS cache was cleared.
   *
   * @var bool
   */
  protected $clearedCssCache;

  /**
   * Whether the registry should be rebuilt.
   *
   * @var bool
   */
  protected $registryRebuild;

  /**
   * {@inheritdoc}
   */
  protected function clearCssCache(): void {
    $this->clearedCssCache = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function themeRegistryRebuild(): void {
    $this->registryRebuild = TRUE;
  }

}
