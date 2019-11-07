<?php

namespace Drupal\Core\Path;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Cache the alias whitelist.
 *
 * @deprecated in drupal:8.8.0 and is removed from drupal:9.0.0.
 * Use \Drupal\path_alias\AliasWhitelistInterface.
 *
 * @see https://www.drupal.org/node/3092086
 */
interface AliasWhitelistInterface extends CacheCollectorInterface {}
