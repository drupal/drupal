<?php

/**
 * @file
 * Contains \Drupal\Core\Render\Placeholder\ChainedPlaceholderStrategy.
 */

namespace Drupal\Core\Render\Placeholder;

/**
 * Renders placeholders using a chain of placeholder strategies.
 */
class ChainedPlaceholderStrategy implements PlaceholderStrategyInterface {

  /**
   * An ordered list of placeholder strategy services.
   *
   * Ordered according to service priority.
   *
   * @var \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface[]
   */
  protected $placeholderStrategies = [];

  /**
   * Adds a placeholder strategy to use.
   *
   * @param \Drupal\Core\Render\Placeholder\PlaceholderStrategyInterface $strategy
   *   The strategy to add to the placeholder strategies.
   */
  public function addPlaceholderStrategy(PlaceholderStrategyInterface $strategy) {
    $this->placeholderStrategies[] = $strategy;
  }

  /**
   * {@inheritdoc}
   */
  public function processPlaceholders(array $placeholders) {
    if (empty($placeholders)) {
      return [];
    }

    // Assert that there is at least one strategy.
    assert('!empty($this->placeholderStrategies)', 'At least one placeholder strategy must be present; by default the fallback strategy \Drupal\Core\Render\Placeholder\SingleFlushStrategy is always present.');

    $new_placeholders = [];

    // Give each placeholder strategy a chance to replace all not-yet replaced
    // placeholders. The order of placeholder strategies is well defined
    // and this uses a variation of the "chain of responsibility" design pattern.
    foreach ($this->placeholderStrategies as $strategy) {
      $processed_placeholders = $strategy->processPlaceholders($placeholders);
      assert('array_intersect_key($processed_placeholders, $placeholders) === $processed_placeholders', 'Processed placeholders must be a subset of all placeholders.');
      $placeholders = array_diff_key($placeholders, $processed_placeholders);
      $new_placeholders += $processed_placeholders;

      if (empty($placeholders)) {
        break;
      }
    }

    return $new_placeholders;
  }

}
