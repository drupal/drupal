<?php

declare(strict_types=1);

namespace Drupal\Core\Mail\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a Mail attribute for plugin discovery.
 *
 * Plugin Namespace: Plugin\Mail
 *
 * For a working example, see \Drupal\Core\Mail\Plugin\Mail\PhpMail
 *
 * @see \Drupal\Core\Mail\MailInterface
 * @see \Drupal\Core\Mail\MailManager
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class Mail extends Plugin {

  /**
   * Constructs a Mail attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $label
   *   The label of the plugin.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) A description of the plugin.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $label,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
