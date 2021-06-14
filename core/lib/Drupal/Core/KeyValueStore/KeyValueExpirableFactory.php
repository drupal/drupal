<?php

namespace Drupal\Core\KeyValueStore;

/**
 * Defines the key/value store factory.
 */
class KeyValueExpirableFactory extends KeyValueFactory implements KeyValueExpirableFactoryInterface {

  const DEFAULT_SERVICE = 'keyvalue.expirable.database';

  const SPECIFIC_PREFIX = 'keyvalue_expirable_service_';

  const DEFAULT_SETTING = 'keyvalue_expirable_default';

}
