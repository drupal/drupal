<?php

declare(strict_types=1);

namespace Drupal\advisory_feed_test\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for advisory_feed_test.
 */
class AdvisoryFeedTestHooks {

  /**
   * Implements hook_extension_info_alter().
   */
  #[Hook('extension_info_alter')]
  public function extensionInfoAlter(&$info, Extension $file): void {
    // Alter the 'generic_module1_test' module to use the
    // 'generic_module1_project' project name.  This ensures that for an
    // extension where the 'name' and the 'project' properties do not match,
    // 'project' is used for matching 'project' in the JSON feed.
    $extension_info = [
      'generic_module1_test' => [
        'project' => 'generic_module1_project',
        'version' => '8.x-1.1',
        'hidden' => FALSE,
      ],
      'generic_module2_test' => [
        'project' => 'generic_module2_project',
        'version' => '8.x-1.1',
        'hidden' => FALSE,
      ],
    ];
    if (!empty($extension_info[$file->getName()])) {
      foreach ($extension_info[$file->getName()] as $key => $value) {
        $info[$key] = $value;
      }
    }
  }

}
