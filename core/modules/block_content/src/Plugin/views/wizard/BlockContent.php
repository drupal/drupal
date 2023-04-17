<?php

namespace Drupal\block_content\Plugin\views\wizard;

use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Used for creating 'block_content' views with the wizard.
 *
 * @ViewsWizard(
 *   id = "block_content",
 *   base_table = "block_content_field_data",
 *   title = @Translation("Content Block"),
 * )
 */
class BlockContent extends WizardPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    $filters = parent::getFilters();
    $filters['reusable'] = [
      'id' => 'reusable',
      'plugin_id' => 'boolean',
      'table' => $this->base_table,
      'field' => 'reusable',
      'value' => '1',
      'entity_type' => $this->entityTypeId,
      'entity_field' => 'reusable',
    ];
    return $filters;
  }

}
