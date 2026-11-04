<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the content_moderation views integration.
 *
 * @internal
 */
class ViewsData {

  use StringTranslationTrait;

  /**
   * Creates a new ViewsData instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderationInformation
   *   The moderation information.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ModerationInformationInterface $moderationInformation,
  ) {
  }

  /**
   * Returns the views data.
   *
   * @return array
   *   The views data.
   */
  public function getViewsData() {
    $data = [];

    $entity_types_with_moderation = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $this->moderationInformation->isModeratedEntityType($type);
    });

    foreach ($entity_types_with_moderation as $entity_type) {
      $table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      $data[$table]['moderation_state'] = [
        'title' => $this->t('Moderation state'),
        'field' => [
          'id' => 'moderation_state_field',
          'default_formatter' => 'content_moderation_state',
          'field_name' => 'moderation_state',
        ],
        'filter' => ['id' => 'moderation_state_filter', 'allow empty' => TRUE],
        'sort' => ['id' => 'moderation_state_sort'],
      ];

      $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
      if ($revision_table !== NULL) {
        $data[$revision_table]['moderation_state'] = [
          'title' => $this->t('Moderation state'),
          'field' => [
            'id' => 'moderation_state_field',
            'default_formatter' => 'content_moderation_state',
            'field_name' => 'moderation_state',
          ],
          'filter' => ['id' => 'moderation_state_filter', 'allow empty' => TRUE],
          'sort' => ['id' => 'moderation_state_sort'],
        ];
      }
    }

    return $data;
  }

}
