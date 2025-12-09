<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\DependencyInjection;

use Drupal\Core\Serialization\Yaml;
use Drupal\KernelTests\FileSystemModuleDiscoveryDataProviderTrait;
use Drupal\KernelTests\KernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests autoconfiguration of services.
 */
#[Group('DependencyInjection')]
#[RunTestsInSeparateProcesses]
class AutoconfigurationTest extends KernelTestBase {

  use FileSystemModuleDiscoveryDataProviderTrait;

  /**
   * Tests that core services do not use tags if autoconfiguration is enabled.
   */
  public function testCoreServiceTags(): void {
    $filenames = array_map(fn($module): string => "core/modules/{$module[0]}/{$module[0]}.services.yml", $this->coreModuleListDataProvider());
    $filenames[] = 'core/core.services.yml';
    foreach (array_filter($filenames, 'file_exists') as $filename) {
      $services = Yaml::decode(file_get_contents($filename))['services'] ?? [];
      if (!empty($services['_defaults']['autoconfigure'])) {
        foreach ($services as $id => $service) {
          if (is_array($service) && isset($service['tags'])) {
            foreach ($service['tags'] as $tag) {
              $tag_name = is_string($tag) ? $tag : $tag['name'];
              $this->assertNotEquals('event_subscriber', $tag_name, "Service '$id' in $filename should not be tagged with 'event_subscriber'.");
            }
          }
        }
      }
    }
  }

}
