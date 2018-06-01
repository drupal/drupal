<?php

namespace Drupal\media\Plugin\media\Source;

use Drupal\media\MediaSourceFieldConstraintsInterface;

/**
 * Defines additional functionality for source plugins that use oEmbed.
 */
interface OEmbedInterface extends MediaSourceFieldConstraintsInterface {

  /**
   * Returns the allowed oEmbed provider names.
   *
   * The allowed providers will always be a subset of the supported providers.
   *
   * @return string[]
   *   A list of oEmbed provider names.
   */
  public function getAllowedProviderNames();

  /**
   * Returns the supported oEmbed provider names.
   *
   * @return string[]
   *   A list of oEmbed provider names.
   */
  public function getSupportedProviderNames();

}
