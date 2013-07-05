<?php
/**
 * @file
 * Contains \Drupal\rdf\SchemaOrgDataConverter.
 */

namespace Drupal\rdf;


class SchemaOrgDataConverter {

  /**
   * Converts an interaction count to a string with the interaction type.
   *
   * Schema.org defines a number of different interaction types.
   *
   * @param int $count
   *   The interaction count.
   *
   * @return string
   *   The formatted string.
   *
   * @see http://schema.org/UserInteraction
   * @todo Support other interaction types, see https://drupal.org/node/2020001
   */
  static function interactionCount($count) {
    return "UserComment:$count";
  }
}
