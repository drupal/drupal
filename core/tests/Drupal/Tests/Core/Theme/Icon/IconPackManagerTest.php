<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Icon;

use Drupal\Core\Theme\Icon\IconExtractorPluginManager;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Theme\Icon\Plugin\IconPackManager;
use Drupal\Core\Theme\Icon\IconCollector;
use Drupal\Core\Theme\Icon\Exception\IconPackConfigErrorException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\Core\Theme\Icon\Plugin\IconPackManager.
 *
 * @group icon
 */
#[CoversClass(IconPackManager::class)]
#[Group('icon')]
class IconPackManagerTest extends UnitTestCase {

  /**
   * Data provider for ::testLibraryName().
   *
   * @return array
   *   Provide test data as:
   *   - (string) Library name to test
   *   - (bool) Flag for validity
   */
  public static function providerIconPackLibraryName(): array {
    return [
      'valid name with underscore' => ['my_theme/my_library', TRUE],
      'valid name with dot' => ['my_theme/my.library', TRUE],
      'valid name with hyphen' => ['my_theme/my-library', TRUE],
      'valid name with hyphen and numbers' => ['my_theme/my-long-library_with_mixed-hyphens123', TRUE],
      'invalid theme name case 1' => ['my-theme/my-library', FALSE],
      'invalid theme name case 2' => ['my-theme/my_library', FALSE],
      'Invalid library name case 1' => ['my_theme/my library', FALSE],
      'Invalid library name special characters' => ['my-theme/$#random!', FALSE],
    ];
  }

  /**
   * Test the library names.
   *
   * @param string $name
   *   The icon data.
   * @param bool $is_valid
   *   The result expected is valid or not.
   */
  #[DataProvider('providerIconPackLibraryName')]
  public function testIconPackLibraryName(string $name, bool $is_valid): void {
    // Minimal required values.
    $definition = [
      'id' => 'foo',
      'provider' => 'icon_test',
      'extractor' => 'bar',
      'template' => '',
      'library' => $name,
    ];

    // Mock all dependencies for IconPackManager.
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $theme_handler = $this->createMock(ThemeHandlerInterface::class);
    $cache_backend = $this->createMock(CacheBackendInterface::class);

    // Add the IconExtractorPluginManager mock here.
    $icon_extractor_manager = $this->createMock(IconExtractorPluginManager::class);

    $icon_collector = $this->createMock(IconCollector::class);

    // Create the IconPackManager instance and pass the manager with definition.
    $iconPackManager = new IconPackManager(
      $module_handler,
      $theme_handler,
      $cache_backend,
      $icon_extractor_manager,
      $icon_collector,
      $this->root,
    );

    $iconPackManager->setValidator();

    $reflection = new \ReflectionClass($iconPackManager);
    $method = $reflection->getMethod('validateDefinition');
    $method->setAccessible(TRUE);

    try {
      $result = $method->invoke($iconPackManager, $definition);
      if ($is_valid) {
        $this->assertTrue($result);
      }
    }
    catch (\Exception $e) {
      if (!$is_valid) {
        $this->assertSame('icon_test:foo Error in definition `foo`:[library] Does not match the regex pattern ^\w+/[A-Za-z]+[\w\.-]*$', $e->getMessage());
        $this->assertInstanceOf(IconPackConfigErrorException::class, $e);
      }
    }

  }

}
