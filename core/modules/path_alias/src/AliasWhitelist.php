<?php

namespace Drupal\path_alias;

use Drupal\Core\Path\AliasWhitelist as CoreAliasWhitelist;

/**
 * Extends CacheCollector to build the path alias whitelist over time.
 */
class AliasWhitelist extends CoreAliasWhitelist implements AliasWhitelistInterface {}
