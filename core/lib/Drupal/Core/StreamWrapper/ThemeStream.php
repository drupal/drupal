<?php

declare(strict_types=1);

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Extension;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the read-only theme:// stream wrapper for theme files.
 *
 * Only enabled themes are supported.
 *
 * Example Usage:
 * @code
 * theme://my_theme/css/component.css
 * @endcode
 * Points to the component.css file in the theme my_theme's css directory.
 */
final class ThemeStream extends ExtensionStreamBase {

  /**
   * {@inheritdoc}
   */
  public function getName(): TranslatableMarkup {
    return new TranslatableMarkup('Theme files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return new TranslatableMarkup("Local files stored under a theme's directory.");
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtension(string $extension_name): Extension {
    return \Drupal::service('theme_handler')->getTheme($extension_name);
  }

}
