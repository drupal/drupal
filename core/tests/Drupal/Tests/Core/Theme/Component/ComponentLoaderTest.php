<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Theme\Component;

use Drupal\Core\Plugin\Component;
use Drupal\Core\Template\Loader\ComponentLoader;
use Drupal\Core\Theme\ComponentPluginManager;
use Drupal\Tests\UnitTestCaseTest;
use org\bovigo\vfs\vfsStream;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for the component loader class.
 *
 * @coversDefaultClass \Drupal\Core\Template\Loader\ComponentLoader
 * @group sdc
 */
class ComponentLoaderTest extends UnitTestCaseTest {

  /**
   * Tests the is fresh function for component loader.
   */
  public function testIsFresh(): void {
    $vfs_root = vfsStream::setup();
    $component_directory = vfsStream::newDirectory('loader-test')->at($vfs_root);
    $current_time = time();
    $component_twig_file = vfsStream::newFile('loader-test.twig')
      ->at($component_directory)
      ->setContent('twig')
      // Mark files as changed before the current time.
      ->lastModified($current_time - 1000);
    $component_yml_file = vfsStream::newFile('loader-test.component.yml')
      ->at($component_directory)
      ->setContent('')
      // Mark file as changed before the current time.
      ->lastModified($current_time - 1000);

    $component = new Component(
      ['app_root' => '/fake/root'],
      'sdc_test:loader-test',
      [
        'machineName' => 'loader-test',
        'extension_type' => 'module',
        'id' => 'sdc_test:loader-test',
        'path' => 'vfs://' . $component_directory->path(),
        'provider' => 'sdc_test',
        'template' => 'loader-test.twig',
        'group' => 'my-group',
        'description' => 'My description',
        '_discovered_file_path' => 'vfs://' . $component_yml_file->path(),
      ]
    );

    $component_manager = $this->prophesize(ComponentPluginManager::class);
    $component_manager->find('sdc_test:loader-test')->willReturn($component);
    $component_loader = new ComponentLoader(
      $component_manager->reveal(),
      $this->createMock(LoggerInterface::class),
    );

    // Assert the component is fresh, as it changed before the current time.
    $this->assertTrue($component_loader->isFresh('sdc_test:loader-test', $current_time), 'Twig and YAML files were supposed to be fresh');
    // Pretend that we changed the twig file.
    // It shouldn't matter that the time is in "future".
    $component_twig_file->lastModified($current_time + 1000);
    // Clear stat cache, to make sure component loader gets updated time.
    clearstatcache();
    // Component shouldn't be "fresh" anymore.
    $this->assertFalse($component_loader->isFresh('sdc_test:loader-test', $current_time), 'Twig file was supposed to be outdated');

    // Pretend that we changed the YAML file.
    // It shouldn't matter that the time is in "future".
    $component_twig_file->lastModified($current_time);
    $component_yml_file->lastModified($current_time + 1000);
    // Clear stat cache, to make sure component loader gets updated time.
    clearstatcache();
    // Component shouldn't be "fresh" anymore.
    $this->assertFalse($component_loader->isFresh('sdc_test:loader-test', $current_time), 'YAML file was supposed to be outdated');

    // Pretend that we changed both files.
    // It shouldn't matter that the time is in "future".
    $component_twig_file->lastModified($current_time + 1000);
    $component_yml_file->lastModified($current_time + 1000);
    // Clear stat cache, to make sure component loader gets updated time.
    clearstatcache();
    // Component shouldn't be "fresh" anymore.
    $this->assertFalse($component_loader->isFresh('sdc_test:loader-test', $current_time), 'Twig and YAML files were supposed to be outdated');
  }

}
