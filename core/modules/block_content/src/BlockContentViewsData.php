<?php

namespace Drupal\block_content;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the block_content entity type.
 */
class BlockContentViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {

    $data = parent::getViewsData();

    $data['block_content_field_data']['id']['field']['id'] = 'field';

    $data['block_content_field_data']['info']['field']['id'] = 'field';
    $data['block_content_field_data']['info']['field']['link_to_entity default'] = TRUE;

    $data['block_content_field_data']['type']['field']['id'] = 'field';

    $data['block_content_field_data']['table']['wizard_id'] = 'block_content';

    $data['block_content']['block_content_listing_empty'] = [
      'title' => $this->t('Empty block library behavior'),
      'help' => $this->t('Provides a link to add a new block.'),
      'area' => [
        'id' => 'block_content_listing_empty',
      ],
    ];
    // Advertise this table as a possible base table.
    $data['block_content_field_revision']['table']['base']['help'] = $this->t('Block Content revision is a history of changes to block content.');
    $data['block_content_field_revision']['table']['base']['defaults']['title'] = 'info';

    return $data;
  }

}
