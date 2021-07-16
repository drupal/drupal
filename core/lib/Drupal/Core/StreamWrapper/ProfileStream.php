<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ThemeHandlerInterface;
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
class ProfileStream extends ThemeStream {

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
   * @param \Symfony\Component\HttpFoundation\RequestStack|null $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface|null $themeHandler
   *   The module handler service.
   * @param string|null $install_profile
   *   The install profile.
   */
  public function __construct(RequestStack $requestStack = NULL, ThemeHandlerInterface $themeHandler = NULL, string $install_profile = NULL) {
    parent::__construct($requestStack, $themeHandler);
    $this->installProfile = $install_profile ?? \Drupal::getContainer()->getParameter('install_profile');
  }

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName(): string {
    return $this->installProfile ?? \Drupal::getContainer()->getParameter('install_profile');
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
