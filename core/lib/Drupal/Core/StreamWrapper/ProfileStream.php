<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ModuleHandlerInterface;
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
class ProfileStream extends ModuleStream {

  use LocalStreamTrait;

  /**
   * The install profile name.
   *
   * @var string
   */
  protected $installProfile;

  /**
   * ProfileStream constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler service.
   * @param string $install_profile
   *   The install profile.
   */
  public function __construct(RequestStack $requestStack, ModuleHandlerInterface $moduleHandler, string $install_profile) {
    parent::__construct($requestStack, $moduleHandler);
    $this->installProfile = $install_profile;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName(): string {
    return $this->installProfile;
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
