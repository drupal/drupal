<?php

declare(strict_types=1);

namespace Drupal\Tests\update\Functional;

/**
 * Provides a trait to set system info and XML mappings.
 *
 * @see update_test_system_info_alter
 * @see \Drupal\update_test\Controller\UpdateTestController::updateTest
 * @see \Drupal\Core\Extension\ExtensionList::doList()
 * @see \Drupal\Core\Extension\InfoParserInterface
 * @see update_test_system_info_alter
 */
trait UpdateTestTrait {

  /**
   * Sets information about installed extensions.
   *
   * @param array<string, array<string, string|bool>> $installed_extensions
   *   An array containing mocked installed extensions info. Keys are
   *   extension names, values are arrays containing key-value pairs that would
   *   be present in extensions' *.info.yml files.
   *   For a list of accepted keys, see InfoParserInterface. Key-value pairs not
   *   present here will be inherited from $default_info.
   *   For example:
   *
   * @code
   *   'drupal' => [
   *     'project' => 'drupal',
   *     'version' => '8.0.0',
   *     'hidden' => FALSE,
   *   ]
   * @endcode
   *
   * @throws \Exception
   */
  protected function mockInstalledExtensionsInfo(array $installed_extensions): void {
    if (in_array('#all', array_keys($installed_extensions), TRUE)) {
      throw new \Exception("#all (default value) shouldn't be set here instead use ::mockDefaultExtensionsInfo().");
    }
    $system_info = $this->config('update_test.settings')->get('system_info');
    $system_info = $installed_extensions + $system_info;
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
  }

  /**
   * Sets default information about installed extensions.
   *
   * @param string[] $default_info
   *   The *.info.yml key-value pairs to be mocked across all
   *   extensions. Hence, these can be seen as default/fallback values.
   */
  protected function mockDefaultExtensionsInfo(array $default_info): void {
    $system_info = $this->config('update_test.settings')->get('system_info');
    $system_info = ['#all' => $default_info] + $system_info;
    $this->config('update_test.settings')->set('system_info', $system_info)->save();
  }

  /**
   * Sets available release history.
   *
   * @param string[] $release_history
   *   The release history XML files to use for particular extension(s). The
   *   keys are the extension names (use 'drupal' for Drupal core itself), and
   *   the values are the suffix of the release history XML file to use. For
   *   example, "['drupal' => 'sec.8.0.2']" will map to a file called
   *   "drupal.sec.8.0.2.xml". Look at
   *   core/modules/update/tests/fixtures/release-history for more release
   *   history XML examples.
   */
  protected function mockReleaseHistory(array $release_history): void {
    $this->config('update_test.settings')->set('xml_map', $release_history)->save();
  }

}
