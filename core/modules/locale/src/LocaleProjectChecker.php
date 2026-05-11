<?php

declare(strict_types=1);

namespace Drupal\locale;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\State\StateInterface;

/**
 * Provide Locale Project Checker helper methods.
 */
class LocaleProjectChecker {

  public function __construct(
    protected readonly LocaleSource $localeSource,
    protected readonly LocaleProjectRepository $localeProjectRepository,
    protected readonly StateInterface $state,
    protected readonly TimeInterface $time,
  ) {}

  /**
   * Check for the latest release of project translations.
   *
   * @param array $projects
   *   Array of project names to check.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   */
  public function checkProjects(array $projects, array $langcodes = []): void {
    if (locale_translation_use_remote_source()) {
      // Retrieve the status of both remote and local translation sources by
      // using a batch process.
      locale_translation_check_projects_batch($projects, $langcodes);
    }
    else {
      // Retrieve and save the status of local translations only.
      $this->checkLocalProjects($projects, $langcodes);
      $this->state->set('locale.translation_last_checked', $this->time->getRequestTime());
    }
  }

  /**
   * Check and store the status and timestamp of local po files.
   *
   * Only po files in the local file system are checked. Any remote translation
   * files will be ignored.
   *
   * Projects may contain a server_pattern option containing a pattern of the
   * path to the po source files. If no server_pattern is defined, the default
   * translation directory is checked for the po file. When a server_pattern is
   * defined, the specified location is checked. The server_pattern can be set
   * in the module's .info.yml file or by using
   * hook_locale_translation_projects_alter().
   *
   * @param array $projects
   *   Array of project names for which to check the state of translation files.
   *   Defaults to all translatable projects.
   * @param array $langcodes
   *   Array of language codes. Defaults to all translatable languages.
   */
  public function checkLocalProjects(array $projects, array $langcodes = []): void {
    $projects = $this->localeProjectRepository->getMultiple($projects);
    $langcodes = $langcodes ?: array_keys(locale_translatable_language_list());

    // For each project and each language we check if a local po file is
    // available. When found the source object is updated with the appropriate
    // type and timestamp of the po file.
    foreach ($projects as $name => $project) {
      foreach ($langcodes as $langcode) {
        $source = $this->localeSource->sourceBuild($project, $langcode);
        $file = $this->localeSource->sourceCheckFile($source);
        locale_translation_status_save($name, $langcode, LOCALE_TRANSLATION_LOCAL, $file);
      }
    }
  }

}
