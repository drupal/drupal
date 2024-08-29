<?php

namespace Drupal\path_alias;

use Drupal\Core\Cache\CacheCollectorInterface;

/**
 * Cache a list of valid alias prefixes.
 *
 * The list contains the first element of the router paths of all aliases. For
 * example, if /node/12345 has an alias then "node" is added to the prefix list.
 * This optimization allows skipping the lookup for every /user/{user} path if
 * "user" is not in the list.
 */
interface AliasPrefixListInterface extends CacheCollectorInterface {}
