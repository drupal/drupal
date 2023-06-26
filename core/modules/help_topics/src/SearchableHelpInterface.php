<?php

namespace Drupal\help_topics;

use Drupal\help\SearchableHelpInterface as CoreSearchableHelpInterface;

/**
 * Provides an interface for a HelpSection plugin that also supports search.
 *
 * @see \Drupal\help\HelpSectionPluginInterface
 *
 * @internal
 *   Help Topics is currently experimental and should only be leveraged by
 *   experimental modules and development releases of contributed modules.
 *   See https://www.drupal.org/core/experimental for more information.
 */
interface SearchableHelpInterface extends CoreSearchableHelpInterface {

}
