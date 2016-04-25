<?php

namespace Drupal\help\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Plugin annotation object for help page section plugins.
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
 *
 * @Annotation
 */
class HelpSection extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The text to use as the title of the help page section.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $title;

  /**
   * The description of the help page section.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The (optional) permission needed to view the help section.
   *
   * Only set if this section needs its own permission, beyond the generic
   * 'access administration pages' permission needed to see the /admin/help
   * page itself.
   *
   * @var string
   */
  public $permission = '';

}
