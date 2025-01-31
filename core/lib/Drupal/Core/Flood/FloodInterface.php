<?php

namespace Drupal\Core\Flood;

/**
 * Defines an interface for flood controllers.
 */
interface FloodInterface {

  /**
   * Registers an event for the current visitor to the flood control mechanism.
   *
   * @param string $name
   *   The name of an event. To prevent unintended name clashes, it is
   *   recommended to use the module name first in the event name, optionally
   *   followed by a dot and the actual event name (e.g. "my_module.my_event").
   * @param int $window
   *   (optional) Number of seconds before this event expires. Defaults to 3600
   *   (1 hour). Typically uses the same value as the isAllowed() $window
   *   parameter. Expired events are purged on cron run to prevent the flood
   *   table from growing indefinitely.
   * @param string $identifier
   *   (optional) Unique identifier of the current user. Defaults to the current
   *   user's IP address. The identifier can be given an additional prefix
   *   separated by "-". Flood backends may then optionally implement the
   *   PrefixFloodInterface which allows all flood events that share the same
   *   prefix to be cleared simultaneously.
   */
  public function register($name, $window = 3600, $identifier = NULL);

  /**
   * Makes the flood control mechanism forget an event for the current visitor.
   *
   * @param string $name
   *   The name of an event.
   * @param string $identifier
   *   (optional) Unique identifier of the current user. Defaults to the current
   *   user's IP address).
   */
  public function clear($name, $identifier = NULL);

  /**
   * Checks whether a user is allowed to proceed with the specified event.
   *
   * Events can have thresholds saying that each user can only do that event
   * a certain number of times in a time window. This function verifies that
   * the current user has not exceeded this threshold.
   *
   * @param string $name
   *   The name of an event.
   * @param int $threshold
   *   The maximum number of times each user can do this event per time window.
   * @param int $window
   *   (optional) Number of seconds in the time window for this event (default
   *   is 3600 seconds, or 1 hour).
   * @param string $identifier
   *   (optional) Unique identifier of the current user. Defaults to the current
   *   user's IP address).
   *
   * @return bool
   *   TRUE if the user is allowed to proceed. FALSE if they have exceeded the
   *   threshold and should not be allowed to proceed.
   */
  public function isAllowed($name, $threshold, $window = 3600, $identifier = NULL);

  /**
   * Cleans up expired flood events.
   *
   * This method is called automatically on cron run.
   *
   * @see system_cron()
   */
  public function garbageCollection();

}
