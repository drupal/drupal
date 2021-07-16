<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ProfileExtensionList;
use Symfony\Component\HttpFoundation\RequestStack;

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
   * @var \Drupal\Core\Extension\ProfileExtensionList
   */
  protected $profileExtensionList;

  /**
   * ProfileStream constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Extension\ProfileExtensionList $profileExtensionList
   *   The profile extension list service.
   */
  public function __construct(RequestStack $requestStack, ProfileExtensionList $profileExtensionList) {
    parent::__construct($requestStack);
    $this->profileExtensionList = $profileExtensionList;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName(): string {
    return \Drupal::getContainer()->getParameter('install_profile');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->profileExtensionList->getPath($this->getOwnerName());
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
    return $this->t('Local files stored under installed profile directory.');
  }

}
