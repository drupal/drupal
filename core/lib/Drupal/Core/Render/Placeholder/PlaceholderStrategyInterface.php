<?php

namespace Drupal\Core\Render\Placeholder;

/**
 * Provides an interface for defining a placeholder strategy service.
 */
interface PlaceholderStrategyInterface {

  /**
   * Processes placeholders to render them with different strategies.
   *
   * @param array $placeholders
   *   The placeholders to process, with the keys being the markup for the
   *   placeholders and the values the corresponding render array describing the
   *   data to be rendered.
   *
   * @return array
   *   The resulting placeholders, with a subset of the keys of $placeholders
   *   (and those being the markup for the placeholders) but with the
   *   corresponding render array being potentially modified to render e.g. an
   *   ESI or BigPipe placeholder.
   */
  public function processPlaceholders(array $placeholders);

}
