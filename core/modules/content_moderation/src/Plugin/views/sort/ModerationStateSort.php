<?php

namespace Drupal\content_moderation\Plugin\views\sort;

use Drupal\content_moderation\Plugin\views\ModerationStateJoinViewsHandlerTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Attribute\ViewsSort;
use Drupal\views\Plugin\views\sort\SortPluginBase;

/**
 * Enables sorting for the computed moderation_state field.
 *
 * @ingroup views_sort_handlers
 */
#[ViewsSort("moderation_state_sort")]
class ModerationStateSort extends SortPluginBase {

  use ModerationStateJoinViewsHandlerTrait;

  /**
   * Creates an instance of ModerationStateFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

}
