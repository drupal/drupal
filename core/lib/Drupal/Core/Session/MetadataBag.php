<?php

/**
 * @file
 * Contains \Drupal\Core\Session\MetadataBag.
 */

namespace Drupal\Core\Session;

use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Session\Storage\MetadataBag as SymfonyMetadataBag;

/**
 * Provides a container for application specific session metadata.
 */
class MetadataBag extends SymfonyMetadataBag {

  /**
   * Constructs a new metadata bag instance.
   *
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   */
  public function __construct(Settings $settings) {
    $update_threshold = $settings->get('session_write_interval', 180);
    parent::__construct('_sf2_meta', $update_threshold);
  }

}
