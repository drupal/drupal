<?php

namespace Drupal\block_content\Plugin\views\wizard;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\views\Attribute\ViewsWizard;
use Drupal\views\Plugin\views\wizard\WizardPluginBase;

/**
 * Used for creating 'block_content' views with the wizard.
 */
#[ViewsWizard(
  id: 'block_content',
  title: new TranslatableMarkup('Content Block'),
  base_table: 'block_content_field_data'
)]
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
