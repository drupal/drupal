<?php

declare(strict_types=1);

namespace Drupal\Tests\system\Functional\UpdateSystem;

use Drupal\Component\Serialization\Yaml;
use Drupal\system\Install\Requirements\SystemRequirements;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the update script access and functionality.
 */
#[Group('Update')]
#[Group('#slow')]
#[RunTestsInSeparateProcesses]
class UpdateScriptMissingExtensionTest extends UpdateScriptTestBase {

  /**
   * Tests that a missing extension prevents updates.
   *
   * @param array $core
   *   An array keyed by 'module' and 'theme' where each sub array contains
   *   a list of extension machine names.
   * @param array $contrib
   *   An array keyed by 'module' and 'theme' where each sub array contains
   *   a list of extension machine names.
   */
  #[DataProvider('providerMissingExtension')]
  public function testMissingExtension(array $core, array $contrib): void {
    $this->drupalLogin(
      $this->drupalCreateUser(
        [
          'administer software updates',
          'administer site configuration',
          'administer modules',
          'administer themes',
        ]
      )
    );

    $all_extensions_info = [];
    $file_paths = [];
    $test_error_texts = [];
    $test_error_urls = [];
    $extension_base_info = [
      'version' => 'VERSION',
      'core_version_requirement' => \Drupal::VERSION,
    ];

    // For each core extension create an error from info.yml information and
    // the expected error message.
    foreach ($core as $type => $extensions) {
      $removed_list = [];
      $error_url = 'https://www.drupal.org/node/3223395#s-recommendations-for-deprecated-modules';
      $extension_base_info += ['package' => 'Core'];
      if ($type === 'module') {
        $removed_core_list = SystemRequirements::DRUPAL_CORE_REMOVED_MODULE_LIST;
      }
      else {
        $removed_core_list = SystemRequirements::DRUPAL_CORE_REMOVED_THEME_LIST;
      }

      foreach ($extensions as $extension) {
        $extension_info = $extension_base_info +
          [
            'name' => "The magically disappearing core $type $extension",
            'type' => $type,
          ];
        if ($type === 'theme') {
          $extension_info['base theme'] = FALSE;
        }
        $all_extensions_info[$extension] = $extension_info;
        $removed_list[] = $removed_core_list[$extension];
      }

      // Create the requirements test message.
      if (!empty($extensions)) {
        $handbook_message = "For more information read the documentation on deprecated {$type}s.";
        if (count($removed_list) === 1) {
          $test_error_texts[$type][] = "Removed core {$type} "
            . "You must add the following contributed $type and reload this page."
            . implode($removed_list)
            . "This $type is installed on your site but is no longer provided by Core."
            . $handbook_message;
        }
        else {
          $test_error_texts[$type][] = "Removed core {$type}s "
            . "You must add the following contributed {$type}s and reload this page."
            . implode($removed_list)
            . "These {$type}s are installed on your site but are no longer provided by Core."
            . $handbook_message;
        }
        $test_error_urls[$type][] = $error_url;
      }
    }

    // For each contrib extension create an error from info.yml information and
    // the expected error message.
    foreach ($contrib as $type => $extensions) {
      unset($extension_base_info['package']);
      $handbook_message = 'Review the suggestions for resolving this incompatibility to repair your installation, and then re-run update.php.';
      $error_url = 'https://www.drupal.org/docs/updating-drupal/troubleshooting-database-updates';
      foreach ($extensions as $extension) {
        $extension_info = $extension_base_info +
          [
            'name' => "The magically disappearing contrib $type $extension",
            'type' => $type,
          ];
        if ($type === 'theme') {
          $extension_info['base theme'] = FALSE;
        }
        $all_extensions_info[$extension] = $extension_info;
      }

      // Create the requirements test message.
      if (!empty($extensions)) {
        if (count($extensions) === 1) {
          $test_error_texts[$type][] = "Missing or invalid {$type} "
            . "The following {$type} is marked as installed in the core.extension configuration, but it is missing:"
            . implode($extensions)
            . $handbook_message;
        }
        else {
          $test_error_texts[$type][] = "Missing or invalid {$type}s "
            . "The following {$type}s are marked as installed in the core.extension configuration, but they are missing:"
            . implode($extensions)
            . $handbook_message;
        }
        $test_error_urls[$type][] = $error_url;
      }
    }

    // Create the info.yml files for each extension.
    foreach ($all_extensions_info as $machine_name => $extension_info) {
      $type = $extension_info['type'];
      $folder_path = \Drupal::getContainer()->getParameter('site.path') . "/{$type}s/contrib/$machine_name";
      $file_path = "$folder_path/$machine_name.info.yml";
      mkdir($folder_path, 0777, TRUE);
      file_put_contents($file_path, Yaml::encode($extension_info));
      $file_paths[$machine_name] = $file_path;
    }

    // Enable all the extensions.
    foreach ($all_extensions_info as $machine_name => $extension_info) {
      $extension_machine_names = [$machine_name];
      $extension_names = [$extension_info['name']];
      $this->enableExtensions($extension_info['type'], $extension_machine_names, $extension_names);
    }

    // If there are no requirements warnings or errors, we expect to be able to
    // go through the update process uninterrupted.
    $this->drupalGet($this->statusReportUrl);
    $types = ['module', 'theme'];
    foreach ($types as $type) {
      $all = array_merge($core[$type], $contrib[$type]);
      $this->assertUpdateWithNoErrors($test_error_texts[$type], $type, $all);
    }

    // Delete the info.yml(s) and confirm updates are prevented.
    foreach ($file_paths as $file_path) {
      unlink($file_path);
    }
    $this->drupalGet($this->statusReportUrl);
    foreach ($types as $type) {
      $all = array_merge($core[$type], $contrib[$type]);
      $this->assertErrorOnUpdates($test_error_texts[$type], $type, $all, $test_error_urls[$type]);
    }

    // Add the info.yml file(s) back and confirm we are able to go through the
    // update process uninterrupted.
    foreach ($all_extensions_info as $machine_name => $extension_info) {
      file_put_contents($file_paths[$machine_name], Yaml::encode($extension_info));
    }
    $this->drupalGet($this->statusReportUrl);
    foreach ($types as $type) {
      $all = array_merge($core[$type], $contrib[$type]);
      $this->assertUpdateWithNoErrors($test_error_texts[$type], $type, $all);
    }
  }

  /**
   * Data provider for ::testMissingExtension().
   *
   * @return array[]
   *   Set of test cases to pass to the test method.
   */
  public static function providerMissingExtension(): array {
    return [
      'core only' => [
        'core' => [
          'module' => ['aggregator'],
          'theme' => ['seven'],
        ],
        'contrib' => [
          'module' => [],
          'theme' => [],
        ],
      ],
      'contrib only' => [
        'core' => [
          'module' => [],
          'theme' => [],
        ],
        'contrib' => [
          'module' => ['module'],
          'theme' => ['theme'],
        ],
      ],
      'core and contrib' => [
        'core' => [
          'module' => ['aggregator', 'rdf'],
          'theme' => ['seven'],
        ],
        'contrib' => [
          'module' => ['module_a', 'module_b'],
          'theme' => ['theme_a', 'theme_b'],
        ],
      ],
    ];
  }

}
