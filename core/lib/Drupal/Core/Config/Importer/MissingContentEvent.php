<?php

namespace Drupal\Core\Config\Importer;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\ConfigImporterEvent;

/**
 * Wraps a configuration event for event listeners.
 *
 * @see \Drupal\Core\Config\ConfigEvents::IMPORT_MISSING_CONTENT
 */
class MissingContentEvent extends ConfigImporterEvent {

  /**
   * A list of missing content dependencies.
   *
   * @var array
   */
  protected $missingContent;

  /**
   * Constructs a configuration import missing content event object.
   *
   * @param array $missing_content
   *   Missing content information.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer that triggered this event.
   */
  public function __construct(array $missing_content, ConfigImporter $config_importer) {
    parent::__construct($config_importer);
    $this->missingContent = $missing_content;
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
