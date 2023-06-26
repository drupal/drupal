<?php

namespace Drupal\help_topics;

use Drupal\help\HelpTopicDiscovery as CoreHelpTopicDiscovery;

/**
 * Discovers help topic plugins from Twig files in help_topics directories.
 *
 * @see \Drupal\help_topics\HelpTopicTwig
 * @see \Drupal\help_topics\HelpTopicTwigLoader
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicDiscovery extends CoreHelpTopicDiscovery {

}
