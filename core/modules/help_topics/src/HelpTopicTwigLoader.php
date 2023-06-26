<?php

namespace Drupal\help_topics;

use Drupal\help\HelpTopicTwigLoader as CoreHelpTopicTwigLoader;

/**
 * Loads help topic Twig files from the filesystem.
 *
 * This loader adds module and theme help topic paths to a help_topics namespace
 * to the Twig filesystem loader so that help_topics can be referenced, using
 * '@help-topic/pluginId.html.twig'.
 *
 * @see \Drupal\help_topics\HelpTopicDiscovery
 * @see \Drupal\help_topics\HelpTopicTwig
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpTopicTwigLoader extends CoreHelpTopicTwigLoader {

}
