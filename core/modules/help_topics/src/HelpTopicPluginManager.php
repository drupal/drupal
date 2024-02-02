<?php

namespace Drupal\help_topics;

use Drupal\help\HelpTopicPluginManager as CoreHelpTopicPluginManager;

/**
 * Provides the default help_topic manager.
 *
 * Modules and themes can provide help topics in .html.twig files called
 * provider.name_of_topic.html.twig inside the module or theme sub-directory
 * help_topics. The provider is validated to be the extension that provides the
 * help topic.
 *
 * The Twig file must contain YAML front matter with a key named 'label'. It can
 * also contain keys named 'top_level' and 'related'. For example:
 * @code
 * ---
 * label: 'Configuring error responses, including 403/404 pages'
 *
 * # Related help topics in an array.
 * related:
 *   - core.config_basic
 *   - core.maintenance
 *
 * # If the value is true then the help topic will appear on admin/help.
 * top_level: true
 * ---
 * @endcode
 *
 * In addition, modules wishing to add plugins can define them in a
 * module_name.help_topics.yml file, with the plugin ID as the heading for
 * each entry, and these properties:
 * - id: The plugin ID.
 * - class: The name of your plugin class, implementing
 *   \Drupal\help_topics\HelpTopicPluginInterface.
 * - top_level: TRUE if the topic is top-level.
 * - related: Array of IDs of topics this one is related to.
 * - Additional properties that your plugin class needs, such as 'label'.
 *
 * You can also provide an entry that designates a plugin deriver class in your
 * help_topics.yml file, with a heading giving a prefix ID for your group of
 * derived plugins, and a 'deriver' property giving the name of a class
 * implementing \Drupal\Component\Plugin\Derivative\DeriverInterface. Example:
 * @code
 * my_module_prefix:
 *   deriver: 'Drupal\my_module\Plugin\Deriver\HelpTopicDeriver'
 * @endcode
 *
 * @ingroup help_docs
 *
 * @see \Drupal\help_topics\HelpTopicDiscovery
 * @see \Drupal\help_topics\HelpTopicTwig
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 * @see \Drupal\help_topics\HelpTopicPluginInterface
 * @see \Drupal\help_topics\HelpTopicPluginBase
 * @see hook_help_topics_info_alter()
 * @see plugin_api
 * @see \Drupal\Component\Plugin\Derivative\DeriverInterface
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicPluginManager extends CoreHelpTopicPluginManager {

}
