<?php

namespace Drupal\help_topics\Plugin\Search;

use Drupal\help\Plugin\Search\HelpSearch as CoreHelpSearch;

/**
 * Handles searching for help using the Search module index.
 *
 * Help items are indexed if their HelpSection plugin implements
 * \Drupal\help\HelpSearchInterface.
 *
 * @see \Drupal\help\HelpSearchInterface
 * @see \Drupal\help\HelpSectionPluginInterface
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
class HelpSearch extends CoreHelpSearch {

}
