<?php

/**
 * @file
 * Contains \Drupal\Core\KeyValueStore\KeyValueExpirableFactory.
 */

namespace Drupal\Core\KeyValueStore;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the key/value store factory.
 */
class KeyValueExpirableFactory extends KeyValueFactory implements KeyValueExpirableFactoryInterface {

  const DEFAULT_SERVICE = 'keyvalue.expirable.database';

  const SPECIFIC_PREFIX = 'keyvalue_expirable_service_';

  const DEFAULT_SETTING = 'keyvalue_expirable_default';

}

