<?php

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\ThemeHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines the read-only theme:// stream wrapper for theme files.
 *
 * Usage:
 *
 * @code
 * theme://{name}
 * @endcode
 * Points to the theme {name} root directory. Only installed themes can be
 * referred.
 */
class ThemeStream extends ExtensionStreamBase {

  /**
   * The theme handler service.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   The theme handler service.
   */
  public function __construct(RequestStack $requestStack, ThemeHandlerInterface $themeHandler) {
    parent::__construct($requestStack);
    $this->themeHandler = $themeHandler;
  }

  /**
   * {@inheritdoc}
   */
  protected function getOwnerName(): string {
    $name = parent::getOwnerName();
    if (!$this->themeHandler->themeExists($name)) {
      // The theme does not exist or is not installed.
      throw new \RuntimeException("Theme $name does not exist or is not installed");
    }
    return $name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->themeHandler->getTheme($this->getOwnerName())->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->t('Theme files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Local files stored under theme directory.');
  }

}
