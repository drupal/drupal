<?php

namespace Drupal\Core\Config\Importer;

use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a configuration event for event listeners.
 *
 * @see \Drupal\Core\Config\ConfigEvents::IMPORT_MISSING_CONTENT
 */
class MissingContentEvent extends Event {

  /**
   * Constructs a configuration import missing content event object.
   *
   * @param array $missingContent
   *   Missing content information.
   */
  public function __construct(
    protected array $missingContent,
  ) {
  }

  /**
   * Gets missing content information.
   *
   * @return array
   *   A list of missing content dependencies. The array is keyed by UUID. Each
   *   value is an array with the following keys: 'entity_type', 'bundle' and
   *   'uuid'.
   */
  public function getMissingContent() {
    return $this->missingContent;
  }

  /**
   * Resolves the missing content by removing it from the list.
   *
   * @param string $uuid
   *   The UUID of the content entity to mark resolved.
   *
   * @return $this
   *   The MissingContentEvent object.
   */
  public function resolveMissingContent($uuid) {
    if (isset($this->missingContent[$uuid])) {
      unset($this->missingContent[$uuid]);
    }
    return $this;
  }

}
