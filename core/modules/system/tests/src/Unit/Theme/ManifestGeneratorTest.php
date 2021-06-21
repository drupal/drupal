<?php

namespace Drupal\Tests\system\Unit\Theme;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Theme\Manifest;
use Drupal\Core\Theme\ManifestGenerator;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the manifest generator.
 *
 * @group Theme
 * @coversDefaultClass \Drupal\Core\Theme\ManifestGenerator
 */
class ManifestGeneratorTest extends UnitTestCase {

  /**
   * Tests that the data for the manifest.json file gets generated correctly.
   *
   * @covers ::generateManifest
   */
  public function testGenerateManifest() {
    $language_manager = $this->createMock(LanguageManagerInterface::class);

    $language_manager
      ->expects($this->any())
      ->method('getCurrentLanguage')
      ->will($this->returnValue(
        new Language(['id' => 'en'])
      ));

    $config_factory = $this->getConfigFactoryStub([
      'bartik.settings' => [
        // An empty manifest here forces the use of the global theme manifest.
        'manifest' => [],
      ],
      'system.site' => [
        'manifest.display' => 'browser',
        'manifest.name' => 'Micrathene whitneyi',
        'manifest.short_name' => 'owl',
        'manifest.start_url' => '/',
      ],
      'system.theme.global' => [
        'manifest.orientation' => 'portrait',
        'manifest.background_color' => '#00f',
        'manifest.theme_color' => '#f00',
      ],
    ]);

    $module_handler = $this->createMock(ModuleHandlerInterface::class);

    $manifest_generator = new ManifestGenerator($language_manager, $config_factory, $module_handler);

    $manifest_object = $manifest_generator->generateManifest('bartik');
    $this->assertInstanceOf(Manifest::class, $manifest_object);
    $data = $manifest_object->toArray();

    // Language data is extracted as expected.
    $this->assertArrayHasKey('lang', $data);
    $this->assertArrayHasKey('dir', $data);
    $this->assertSame('en', $data['lang']);
    $this->assertSame('ltr', $data['dir']);

    // Site configuration.
    $this->assertArrayHasKey('short_name', $data);
    $this->assertArrayHasKey('name', $data);
    $this->assertArrayHasKey('display', $data);
    $this->assertArrayHasKey('start_url', $data);
    $this->assertSame('owl', $data['short_name']);
    $this->assertSame('Micrathene whitneyi', $data['name']);
    $this->assertSame('browser', $data['display']);
    $this->assertSame('/', $data['start_url']);

    // Theme configuration.
    $this->assertArrayHasKey('orientation', $data);
    $this->assertArrayHasKey('theme_color', $data);
    $this->assertArrayHasKey('background_color', $data);
    $this->assertSame('portrait', $data['orientation']);
    $this->assertSame('#f00', $data['theme_color']);
    $this->assertSame('#00f', $data['background_color']);
  }

}
