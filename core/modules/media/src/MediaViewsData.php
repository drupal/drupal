<?php

namespace Drupal\media;

use Drupal\views\EntityViewsData;

/**
 * Provides the Views data for the media entity type.
 */
class MediaViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    $data['media_field_data']['table']['wizard_id'] = 'media';
    $data['media_field_revision']['table']['wizard_id'] = 'media_revision';

    $data['media_field_data']['user_name']['filter'] = $data['media_field_data']['uid']['filter'];
    $data['media_field_data']['user_name']['filter']['title'] = $this->t('Authored by');
    $data['media_field_data']['user_name']['filter']['help'] = $this->t('The username of the content author.');
    $data['media_field_data']['user_name']['filter']['id'] = 'user_name';
    $data['media_field_data']['user_name']['filter']['real field'] = 'uid';

    $data['media_field_data']['status_extra'] = [
      'title' => $this->t('Published status or admin user'),
      'help' => $this->t('Filters out unpublished media if the current user cannot view it.'),
      'filter' => [
        'field' => 'status',
        'id' => 'media_status',
        'label' => $this->t('Published status or admin user'),
      ],
    ];

    return $data;
  }

}
