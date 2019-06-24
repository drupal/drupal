<?php

namespace Drupal\FunctionalTests\Installer;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Archiver\ArchiveTar;
use Drupal\Core\Installer\Form\SelectProfileForm;

/**
 * Provides a base class for testing installing from existing configuration.
 */
abstract class InstallerExistingConfigTestBase extends InstallerTestBase {

  /**
   * This is set by the profile in the core.extension extracted.
   */
  protected $profile = NULL;

  /**
   * @todo
   */
  protected $existingSyncDirectory = FALSE;

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

    // Create a profile for testing.
    $info = [
      'type' => 'profile',
      'core' => \Drupal::CORE_COMPATIBILITY,
      'name' => 'Configuration installation test profile (' . $this->profile . ')',
    ];

    // File API functions are not available yet.
    $path = $this->siteDirectory . '/profiles/' . $this->profile;
    if ($this->existingSyncDirectory) {
      $config_sync_directory = $this->siteDirectory . '/config/sync';
      $this->settings['settings']['config_sync_directory'] = (object) [
        'value' => $config_sync_directory,
        'required' => TRUE,
      ];
    }
    else {
      // Put the sync directory inside the profile.
      $config_sync_directory = $path . '/config/sync';
    }

    mkdir($path, 0777, TRUE);
    file_put_contents("$path/{$this->profile}.info.yml", Yaml::encode($info));

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
    unset($parameters['forms']['install_configure_form']['update_status_module']);

    return $parameters;
  }

  /**
   * Confirms that the installation installed the configuration correctly.
   */
  public function testConfigSync() {
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
    $this->assertEqual($expected, $change_list);
  }

  /**
   * Installer step: Select installation profile.
   */
  protected function setUpProfile() {
    if ($this->existingSyncDirectory) {
      $edit = [
        'profile' => SelectProfileForm::CONFIG_INSTALL_PROFILE_KEY,
      ];
      $this->drupalPostForm(NULL, $edit, $this->translations['Save and continue']);
    }
    else {
      parent::setUpProfile();
    }
  }

}
