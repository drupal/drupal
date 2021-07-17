<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ExtensionList;

/**
 * Defines the read-only profile:// stream wrapper for installed profile files.
 *
 * Usage:
 * @code
 * profile://
 * @endcode
 * Points to the installed profile root directory.
 */
class ProfileStream extends ExtensionStreamBase {

  use LocalStreamTrait;

  /**
   * The profile extension list service.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $profileExtensionList;

  /**
   * {@inheritdoc}
   */
  protected function getExtensionName(): string {
    return \Drupal::getContainer()->getParameter('install_profile');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->getProfileExtensionList()->getPath($this->getExtensionName());
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Installed profile files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t("Local files stored under the installed profile's directory.");
  }

  /**
   * Returns the module handler service.
   *
   * @return \Drupal\Core\Extension\ExtensionList
   *   The profile extension list service.
   */
  protected function getProfileExtensionList(): ExtensionList {
    if (!isset($this->profileExtensionList)) {
      $this->profileExtensionList = \Drupal::service('extension.list.profile');
    }
    return $this->profileExtensionList;
  }

}
