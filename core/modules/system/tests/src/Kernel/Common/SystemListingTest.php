<?php

namespace Drupal\Tests\system\Kernel\Common;

use Drupal\Core\Extension\ExtensionDiscovery;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests scanning system directories in drupal_system_listing().
 *
 * @group Common
 */
class SystemListingTest extends KernelTestBase {
  /**
   * Tests that files in different directories take precedence as expected.
   */
  function testDirectoryPrecedence() {
    // Define the module files we will search for, and the directory precedence
    // we expect.
    $expected_directories = array(
      // When both copies of the module are compatible with Drupal core, the
      // copy in the profile directory takes precedence.
      'drupal_system_listing_compatible_test' => array(
        'core/profiles/testing/modules',
        'core/modules/system/tests/modules',
      ),
    );

    // This test relies on two versions of the same module existing in
    // different places in the filesystem. Without that, the test has no
    // meaning, so assert their presence first.
    foreach ($expected_directories as $module => $directories) {
      foreach ($directories as $directory) {
        $filename = "$directory/$module/$module.info.yml";
        $this->assertTrue(file_exists(\Drupal::root() . '/' . $filename), format_string('@filename exists.', array('@filename' => $filename)));
      }
    }

    // Now scan the directories and check that the files take precedence as
    // expected.
    $listing = new ExtensionDiscovery(\Drupal::root());
    $listing->setProfileDirectories(array('core/profiles/testing'));
    $files = $listing->scan('module');
    foreach ($expected_directories as $module => $directories) {
      $expected_directory = array_shift($directories);
      $expected_uri = "$expected_directory/$module/$module.info.yml";
      $this->assertEqual($files[$module]->getPathname(), $expected_uri, format_string('Module @actual was found at @expected.', array(
        '@actual' => $files[$module]->getPathname(),
        '@expected' => $expected_uri,
      )));
    }
  }

  /**
   * Tests that directories matching file_scan_ignore_directories are ignored
   */
  public function testFileScanIgnoreDirectory() {
    $listing = new ExtensionDiscovery(\Drupal::root(), FALSE);
    $listing->setProfileDirectories(array('core/profiles/testing'));
    $files = $listing->scan('module');
    $this->assertArrayHasKey('drupal_system_listing_compatible_test', $files);

    // Reset the static to force a rescan of the directories.
    $reflected_class = new \ReflectionClass(ExtensionDiscovery::class);
    $reflected_property = $reflected_class->getProperty('files');
    $reflected_property->setAccessible(TRUE);
    $reflected_property->setValue($reflected_class, []);

    $this->setSetting('file_scan_ignore_directories', ['drupal_system_listing_compatible_test']);
    $listing = new ExtensionDiscovery(\Drupal::root(), FALSE);
    $listing->setProfileDirectories(array('core/profiles/testing'));
    $files = $listing->scan('module');
    $this->assertArrayNotHasKey('drupal_system_listing_compatible_test', $files);
  }

}
