<?php

namespace Drupal\Core\Archiver\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines an archiver attribute object.
 *
 * Plugin Namespace: Plugin\Archiver
 *
 * For a working example, see \Drupal\system\Plugin\Archiver\Zip
 *
 * @see \Drupal\Core\Archiver\ArchiverManager
 * @see \Drupal\Core\Archiver\ArchiverInterface
 * @see plugin_api
 * @see hook_archiver_info_alter()
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Archiver extends Plugin {

  /**
   * Constructs an archiver plugin attribute object.
   *
   * @param string $id
   *   The archiver plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $title
   *   The human-readable name of the archiver plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   The description of the archiver plugin.
   * @param array $extensions
   *   An array of valid extensions for this archiver.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly ?TranslatableMarkup $title = NULL,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly array $extensions = [],
    public readonly ?string $deriver = NULL,
  ) {}

}
