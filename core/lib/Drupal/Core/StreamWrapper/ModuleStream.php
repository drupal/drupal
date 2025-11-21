<?php

declare(strict_types=1);

namespace Drupal\Core\StreamWrapper;

use Drupal\Core\Extension\Extension;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the read-only module:// stream wrapper for module files.
 *
 * Only enabled modules are supported.
 *
 * Example usage:
 * @code
 * module://my_module/css/component.css
 * @endcode
 * Points to the component.css file in the module my_module's css directory.
 */
final class ModuleStream extends ExtensionStreamBase {

  /**
   * {@inheritdoc}
   */
  public function getName(): TranslatableMarkup {
    return new TranslatableMarkup('Module files');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return new TranslatableMarkup("Local files stored under a module's directory.");
  }

  /**
   * {@inheritdoc}
   */
  protected function getExtension(string $extension_name): Extension {
    return \Drupal::moduleHandler()->getModule($extension_name);
  }

}
