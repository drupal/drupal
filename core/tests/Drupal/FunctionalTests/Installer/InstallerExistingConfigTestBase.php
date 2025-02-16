<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Database\Database;
use Drupal\Core\Installer\Form\SelectProfileForm;

/**
 * Provides a base class for testing installing from existing configuration.
 *
 * @deprecated in drupal:10.4.0 and is removed from drupal:12.0.0. Use
 *   \Drupal\FunctionalTests\Installer\InstallerConfigDirectoryTestBase
 *   instead.
 *
 * @see https://www.drupal.org/node/3460001
 */
abstract class InstallerExistingConfigTestBase extends InstallerTestBase {

  /**
   * {@inheritdoc}
   */
  protected $profile = NULL;

  /**
   * @var bool
   */
  protected $existingSyncDirectory = FALSE;

  /**
   * {@inheritdoc}
   */
  public function __construct(string $name) {
    @trigger_error(__CLASS__ . ' is deprecated in drupal:10.4.0 and is removed from drupal:12.0.0. Use \Drupal\FunctionalTests\Installer\InstallerConfigDirectoryTestBase instead. See https://www.drupal.org/node/3460001');
    parent::__construct($name);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareEnvironment() {
    parent::prepareEnvironment();
    $archiver = new ArchiveTar($this->getConfigTarball(), 'gz');

    if ($this->profile === NULL) {
      $core_extension = Yaml::decode($archiver->extractInString('core.extension.yml'));
      $this->profile = $core_extension['profile'];
    }

    if ($this->profile !== FALSE) {
      // Create a profile for testing. We set core_version_requirement to '*'
      // for the test so that it does not need to be updated between major
      // versions.
      $info = [
        'type' => 'profile',
        'core_version_requirement' => '*',
        'name' => 'Configuration installation test profile (' . $this->profile . ')',
      ];

      // File API functions are not available yet.
      $path = $this->siteDirectory . '/profiles/' . $this->profile;

      // Put the sync directory inside the profile.
      $config_sync_directory = $path . '/config/sync';

      mkdir($path, 0777, TRUE);
      file_put_contents("$path/{$this->profile}.info.yml", Yaml::encode($info));
    }
    else {
      // If we have no profile we must use an existing sync directory.
      $this->existingSyncDirectory = TRUE;
      $config_sync_directory = $this->siteDirectory . '/config/sync';
    }

    if ($this->existingSyncDirectory) {
      $config_sync_directory = $this->siteDirectory . '/config/sync';
      $this->settings['settings']['config_sync_directory'] = (object) [
        'value' => $config_sync_directory,
        'required' => TRUE,
      ];
    }

    // Create config/sync directory and extract tarball contents to it.
    mkdir($config_sync_directory, 0777, TRUE);
    $files = [];
    $list = $archiver->listContent();
    if (is_array($list)) {
      /** @var array $list */
      foreach ($list as $file) {
        $files[] = $file['filename'];
      }
      $archiver->extractList($files, $config_sync_directory);
    }

    // Add the module that is providing the database driver to the list of
    // modules that can not be uninstalled in the core.extension configuration.
    if (file_exists($config_sync_directory . '/core.extension.yml')) {
      $core_extension = Yaml::decode(file_get_contents($config_sync_directory . '/core.extension.yml'));
      $module = Database::getConnection()->getProvider();
      if ($module !== 'core') {
        $core_extension['module'][$module] = 0;
        $core_extension['module'] = module_config_sort($core_extension['module']);
      }
      if ($this->profile === FALSE && array_key_exists('profile', $core_extension)) {
        // Remove the profile.
        unset($core_extension['module'][$core_extension['profile']]);
        unset($core_extension['profile']);

        // Set a default theme to the first theme that will be installed as this
        // can not be retrieved from the profile.
        $this->defaultTheme = array_key_first($core_extension['theme']);
      }
      file_put_contents($config_sync_directory . '/core.extension.yml', Yaml::encode($core_extension));
    }
  }

  /**
   * Gets the filepath to the configuration tarball.
   *
   * The tarball will be extracted to the install profile's config/sync
   * directory for testing.
   *
   * @return string
   *   The filepath to the configuration tarball.
   */
  abstract protected function getConfigTarball();

  /**
   * {@inheritdoc}
   */
  protected function installParameters() {
    $parameters = parent::installParameters();

    // The options that change configuration are disabled when installing from
    // existing configuration.
    unset($parameters['forms']['install_configure_form']['site_name']);
    unset($parameters['forms']['install_configure_form']['site_mail']);
    unset($parameters['forms']['install_configure_form']['enable_update_status_module']);
    unset($parameters['forms']['install_configure_form']['enable_update_status_emails']);

    return $parameters;
  }

  /**
   * Confirms that the installation installed the configuration correctly.
   */
  public function testConfigSync(): void {
    // After installation there is no snapshot and nothing to import.
    $change_list = $this->configImporter()->getStorageComparer()->getChangelist();
    $expected = [
      'create' => [],
      // The system.mail is changed configuration because the test system
      // changes it to ensure that mails are not sent.
      'update' => ['system.mail'],
      'delete' => [],
      'rename' => [],
    ];
    $this->assertEquals($expected, $change_list);
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    if ($this->existingSyncDirectory) {
      $edit = [
        'profile' => SelectProfileForm::CONFIG_INSTALL_PROFILE_KEY,
      ];
      $this->submitForm($edit, $this->translations['Save and continue']);
    }
    else {
      parent::setUpProfile();
    }
  }

}
