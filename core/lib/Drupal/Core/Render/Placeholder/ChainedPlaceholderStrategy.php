<?php

namespace Drupal\Core\Render\Placeholder;

/**
 * Renders placeholders using a chain of placeholder strategies.
 *
 * Render arrays may specify a denylist of placeholder strategies by using
 * $element['#placeholder_strategy_denylist'][ClassName::class] = TRUE at the
 * same level as #lazy_builder. When this is set, placeholder strategies
 * specified will be skipped.
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
    assert(!empty($this->placeholderStrategies), 'At least one placeholder strategy must be present; by default the fallback strategy \Drupal\Core\Render\Placeholder\SingleFlushStrategy is always present.');

    $new_placeholders = [];

    // Give each placeholder strategy a chance to replace all not-yet replaced
    // placeholders. The order of placeholder strategies is well defined and
    // this uses a variation of the "chain of responsibility" design pattern.
    foreach ($this->placeholderStrategies as $strategy) {
      $candidate_placeholders = [];
      foreach ($placeholders as $key => $placeholder) {
        if (empty($placeholder['#placeholder_strategy_denylist'][$strategy::class])) {
          $candidate_placeholders[$key] = $placeholder;
        }
      }

      $processed_placeholders = $strategy->processPlaceholders($candidate_placeholders);
      assert(array_intersect_key($processed_placeholders, $placeholders) === $processed_placeholders, 'Processed placeholders must be a subset of all placeholders.');
      $placeholders = array_diff_key($placeholders, $processed_placeholders);
      $new_placeholders += $processed_placeholders;

      if (empty($placeholders)) {
        break;
      }
    }
    assert(empty($placeholders), 'It was not possible to replace all placeholders in ChainedPlaceholderStrategy::processPlaceholders()');

    return $new_placeholders;
  }

}
