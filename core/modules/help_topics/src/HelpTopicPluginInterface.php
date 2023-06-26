<?php

namespace Drupal\help_topics;

use Drupal\help\HelpTopicPluginInterface as CoreHelpTopicPluginInterface;

/**
 * Defines an interface for help topic plugin classes.
 *
 * @see \Drupal\help_topics\HelpTopicPluginManager
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface HelpTopicPluginInterface extends CoreHelpTopicPluginInterface {

}
