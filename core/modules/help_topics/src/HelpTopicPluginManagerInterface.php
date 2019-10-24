<?php

namespace Drupal\help_topics;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Defines an interface for managing help topics and storing their definitions.
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface HelpTopicPluginManagerInterface extends PluginManagerInterface {
}
