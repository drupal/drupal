<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Defines additional functionality for source plugins that use oEmbed.
 */
interface OEmbedInterface extends MediaSourceFieldConstraintsInterface {

  /**
   * Returns the oEmbed provider names.
   *
   * The allowed providers can be configured by the user. If it is not
   * configured, all providers supported by the plugin are returned.
   *
   * @return string[]
   *   A list of oEmbed provider names.
   */
  public function getProviders();

}
