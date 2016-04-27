<?php

namespace Drupal\Core\Database;

/**
 * Interface for a database exception.
 *
 * All Database exceptions should implement this interface so that they can be
 * caught collectively.  Note that this applies only to Drupal-spawned
 * exceptions.  PDOException will not implement this interface and module
 * developers should account for it separately.
 */
interface DatabaseException { }
