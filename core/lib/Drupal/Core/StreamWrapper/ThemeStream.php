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
   * {@inheritdoc}
   */
  protected function getExtensionName(): string {
    $extension_name = parent::getExtensionName();
    $this->getThemeHandler()->getTheme($extension_name);
    return $extension_name;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDirectoryPath() {
    return $this->getThemeHandler()->getTheme($this->getExtensionName())->getPath();
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
    return $this->t('Local files stored under a theme\'s directory.');
  }

  /**
   * Returns the theme handler service.
   *
   * @return \Drupal\Core\Extension\ThemeHandlerInterface
   *   The theme handler service.
   */
  protected function getThemeHandler(): ThemeHandlerInterface {
    if (!isset($this->themeHandler)) {
      $this->themeHandler = \Drupal::service('theme_handler');
    }
    return $this->themeHandler;
  }

}
