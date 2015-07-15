<?php

/**
 * @file
 * Contains \Drupal\Core\Path\AliasWhitelistInterface.
 */

namespace Drupal\Core\Path;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Cache the alias whitelist.
 *
 * The whitelist contains the first element of the router paths of all
 * aliases. For example, if /node/12345 has an alias then "node" is added to
 * the whitelist. This optimization allows skipping the lookup for every
 * /user/{user} path if "user" is not in the whitelist.
 */
interface AliasWhitelistInterface extends CacheCollectorInterface {}
