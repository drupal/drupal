<?php

declare(strict_types=1);

namespace Drupal\help\Attribute;

use Drupal\Component\Plugin\Attribute\Plugin;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines a HelpSection attribute object for plugin discovery.
 *
 * Plugin Namespace: Plugin\HelpSection
 *
 * For a working example, see \Drupal\help\Plugin\HelpSection\HookHelpSection.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 * @see \Drupal\help\Plugin\HelpSection\HelpSectionPluginBase
 * @see \Drupal\help\HelpSectionManager
 * @see hook_help_section_info_alter()
 * @see plugin_api
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class HelpSection extends Plugin {

  /**
   * Constructs a HelpSection attribute.
   *
   * @param string $id
   *   The plugin ID.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $title
   *   The text to use as the title of the help page section.
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup|null $description
   *   (optional) The description of the help page section.
   * @param string|null $permission
   *   (optional) The permission required to access the help page section.
   *
   *   Only set if this section needs its own permission, beyond the generic
   *   'access help pages' permission needed to see the /admin/help
   *   page itself.
   * @param int|null $weight
   *   (optional) The weight of the help page section.
   * @param class-string|null $deriver
   *   (optional) The deriver class.
   *
   *   The sections will be ordered by this weight on the help page.
   */
  public function __construct(
    public readonly string $id,
    public readonly TranslatableMarkup $title,
    public readonly ?TranslatableMarkup $description = NULL,
    public readonly ?string $permission = NULL,
    public readonly ?int $weight = NULL,
    public readonly ?string $deriver = NULL,
  ) {}

}
