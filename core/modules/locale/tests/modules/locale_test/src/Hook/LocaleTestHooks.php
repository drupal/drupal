<?php

declare(strict_types=1);

namespace Drupal\locale_test\Hook;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\Url;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for locale_test.
 */
class LocaleTestHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * Make the test scripts to be believe this is not a hidden test module, but
   * a regular custom module.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(&$info, Extension $file, $type): void {
    // Only modify the system info if required.
    // By default the locale_test modules are hidden and have a project specified.
    // To test the module detection process by locale_project_list() the
    // test modules should mimic a custom module. I.e. be non-hidden.
    if (\Drupal::state()->get('locale.test_system_info_alter')) {
      if ($file->getName() == 'locale_test' || $file->getName() == 'locale_test_translate') {
        // Don't hide the module.
        $info['hidden'] = FALSE;
      }
    }
    // Alter the name and the core version of the project. This should not affect
    // the locale project information.
    if (\Drupal::state()->get('locale.test_system_info_alter_name_core')) {
      if ($file->getName() == 'locale_test') {
        $info['core'] = '8.6.7';
        $info['name'] = 'locale_test_alter';
      }
    }
  }

  /**
   * Implements hook_locale_translation_projects_alter().
   *
   * The translation status process by default checks the status of the installed
   * projects. This function replaces the data of the installed modules by a
   * predefined set of modules with fixed file names and release versions. Project
   * names, versions, timestamps etc must be fixed because they must match the
   * files created by the test script.
   *
   * The "locale.test_projects_alter" state variable must be set by the
   * test script in order for this hook to take effect.
   */
  #[Hook('locale_translation_projects_alter')]
  public function localeTranslationProjectsAlter(&$projects): void {
    // Drupal core should not be translated. By overriding the server pattern we
    // make sure that no translation for drupal core will be found and that the
    // translation update system will not go out to l.d.o to check.
    $projects['drupal']['server_pattern'] = 'translations://';
    if (\Drupal::state()->get('locale.remove_core_project')) {
      unset($projects['drupal']);
    }
    if (\Drupal::state()->get('locale.test_projects_alter')) {
      // Instead of the default ftp.drupal.org we use the file system of the test
      // instance to simulate a remote file location.
      $url = Url::fromRoute('<front>', [], ['absolute' => TRUE])->toString();
      $remote_url = $url . PublicStream::basePath() . '/remote/';
      // Completely replace the project data with a set of test projects.
      $projects = [
        'contrib_module_one' => [
          'name' => 'contrib_module_one',
          'info' => [
            'name' => 'Contributed module one',
            'interface translation server pattern' => $remote_url . '%core/%project/%project-%version.%language._po',
            'package' => 'Other',
            'version' => '8.x-1.1',
            'project' => 'contrib_module_one',
            'datestamp' => '1344471537',
            '_info_file_ctime' => 1348767306,
          ],
          'datestamp' => '1344471537',
          'project_type' => 'module',
          'project_status' => TRUE,
        ],
        'contrib_module_two' => [
          'name' => 'contrib_module_two',
          'info' => [
            'name' => 'Contributed module two',
            'interface translation server pattern' => $remote_url . '%core/%project/%project-%version.%language._po',
            'package' => 'Other',
            'version' => '8.x-2.0-beta4',
            'project' => 'contrib_module_two',
            'datestamp' => '1344471537',
            '_info_file_ctime' => 1348767306,
          ],
          'datestamp' => '1344471537',
          'project_type' => 'module',
          'project_status' => TRUE,
        ],
        'contrib_module_three' => [
          'name' => 'contrib_module_three',
          'info' => [
            'name' => 'Contributed module three',
            'interface translation server pattern' => $remote_url . '%core/%project/%project-%version.%language._po',
            'package' => 'Other',
            'version' => '8.x-1.0',
            'project' => 'contrib_module_three',
            'datestamp' => '1344471537',
            '_info_file_ctime' => 1348767306,
          ],
          'datestamp' => '1344471537',
          'project_type' => 'module',
          'project_status' => TRUE,
        ],
        'locale_test' => [
          'name' => 'locale_test',
          'info' => [
            'name' => 'Locale test',
            'interface translation project' => 'locale_test',
            'interface translation server pattern' => 'core/modules/locale/tests/test.%language.po',
            'package' => 'Other',
            'version' => NULL,
            'project' => 'locale_test',
            '_info_file_ctime' => 1348767306,
            'datestamp' => 0,
          ],
          'datestamp' => 0,
          'project_type' => 'module',
          'project_status' => TRUE,
        ],
        'custom_module_one' => [
          'name' => 'custom_module_one',
          'info' => [
            'name' => 'Custom module one',
            'interface translation project' => 'custom_module_one',
            'interface translation server pattern' => 'translations://custom_module_one.%language.po',
            'package' => 'Other',
            'version' => NULL,
            'project' => 'custom_module_one',
            '_info_file_ctime' => 1348767306,
            'datestamp' => 0,
          ],
          'datestamp' => 0,
          'project_type' => 'module',
          'project_status' => TRUE,
        ],
      ];
    }
  }

  /**
   * Implements hook_language_fallback_candidates_OPERATION_alter().
   */
  #[Hook('language_fallback_candidates_locale_lookup_alter')]
  public function languageFallbackCandidatesLocaleLookupAlter(array &$candidates, array $context): void {
    \Drupal::state()->set('locale.test_language_fallback_candidates_locale_lookup_alter_candidates', $candidates);
    \Drupal::state()->set('locale.test_language_fallback_candidates_locale_lookup_alter_context', $context);
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme() : array {
    $return = [];
    $return['locale_test_tokenized'] = ['variable' => ['content' => '']];
    return $return;
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $info = [];
    $info['types']['locale_test'] = ['name' => t('Locale test'), 'description' => t('Locale test')];
    $info['tokens']['locale_test']['security_test1'] = ['type' => 'text', 'name' => t('Security test 1')];
    $info['tokens']['locale_test']['security_test2'] = ['type' => 'text', 'name' => t('Security test 2')];
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data = [], array $options = []) {
    $return = [];
    if ($type == 'locale_test') {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'security_test1':
            $return[$original] = "javascript:alert('Hello!');";
            break;

          case 'security_test2':
            $return[$original] = "<script>alert('Hello!');</script>";
            break;
        }
      }
    }
    return $return;
  }

  /**
   * Implements hook_countries_alter().
   */
  #[Hook('countries_alter')]
  public function countriesAlter(&$countries): void {
    $countries['EB'] = 'Elbonia';
  }

}
