<?php

declare(strict_types=1);

namespace Drupal\locale_test_development_release\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for locale_test_development_release.
 */
class LocaleTestDevelopmentReleaseHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * Change the core version number to a development one for testing.
   * 8.0.0-alpha102-dev is the simulated version.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    if (isset($info['package']) && $info['package'] == 'Core') {
      $info['version'] = '8.0.0-alpha102-dev';
    }
  }

  /**
   * Implements hook_locale_translation_projects_alter().
   *
   * Add a contrib module with a dev release to list of translatable modules.
   */
  #[Hook('locale_translation_projects_alter')]
  public function localeTranslationProjectsAlter(&$projects): void {
    $projects['contrib'] = [
      'name' => 'contrib',
      'info' => [
        'name' => 'Contributed module',
        'package' => 'Other',
        'version' => '12.x-10.4-unstable11+14-dev',
        'project' => 'contrib_module',
        'datestamp' => '0',
        '_info_file_ctime' => 1442933959,
      ],
      'datestamp' => '0',
      'project_type' => 'module',
      'project_status' => TRUE,
    ];
  }

}
