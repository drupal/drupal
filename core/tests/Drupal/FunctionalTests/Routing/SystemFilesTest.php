<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Routing;

use Drupal\Component\Serialization\Yaml;
use Drupal\Tests\BrowserTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests system.files route.
 */
#[Group('routing')]
#[RunTestsInSeparateProcesses]
class SystemFilesTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function prepareSettings(): void {
    parent::prepareSettings();

    // Allow info files to be accessed by the `module` and `theme` stream
    // wrappers.
    $services_file = $this->siteDirectory . '/services.yml';
    $this->assertFileExists($services_file);
    $services = file_get_contents($services_file);
    $services = Yaml::decode($services);
    $services['parameters']['stream_wrapper.allowed_file_extensions'] = [
      'module' => ['yml'],
      'theme' => ['yml'],
    ];
    file_put_contents($services_file, Yaml::encode($services));
  }

  /**
   * Test theme and module stream wrappers are not available via system.files route.
   */
  public function testExtensionStreamWrappers(): void {
    $path = 'system/system.info.yml';
    $this->assertFileExists('module://' . $path);
    $this->drupalGet('/system/files/module', ['query' => ['file' => $path]]);
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('/system/files/module/' . $path);
    $this->assertSession()->statusCodeEquals(404);

    $path = 'stark/stark.info.yml';
    $this->assertFileExists('theme://' . $path);
    $this->drupalGet('/system/files/theme', ['query' => ['file' => $path]]);
    $this->assertSession()->statusCodeEquals(404);
    $this->drupalGet('/system/files/theme/' . $path);
    $this->assertSession()->statusCodeEquals(404);
  }

}
