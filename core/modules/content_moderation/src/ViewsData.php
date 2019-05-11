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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The moderation information.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * Creates a new ViewsData instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ModerationInformationInterface $moderation_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->moderationInformation = $moderation_information;
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

    // Provides a relationship from moderated entity to its moderation state
    // entity.
    $content_moderation_state_entity_type = $this->entityTypeManager->getDefinition('content_moderation_state');
    $content_moderation_state_entity_base_table = $content_moderation_state_entity_type->getDataTable() ?: $content_moderation_state_entity_type->getBaseTable();
    $content_moderation_state_entity_revision_base_table = $content_moderation_state_entity_type->getRevisionDataTable() ?: $content_moderation_state_entity_type->getRevisionTable();
    foreach ($entity_types_with_moderation as $entity_type_id => $entity_type) {
      $table = $entity_type->getDataTable() ?: $entity_type->getBaseTable();

      $data[$table]['moderation_state'] = [
        'title' => t('Moderation state'),
        'relationship' => [
          'id' => 'standard',
          'label' => $this->t('@label moderation state', ['@label' => $entity_type->getLabel()]),
          'base' => $content_moderation_state_entity_base_table,
          'base field' => 'content_entity_id',
          'relationship field' => $entity_type->getKey('id'),
          'extra' => [
            [
              'field' => 'content_entity_type_id',
              'value' => $entity_type_id,
            ],
          ],
        ],
        'field' => [
          'id' => 'moderation_state_field',
          'default_formatter' => 'content_moderation_state',
          'field_name' => 'moderation_state',
        ],
        'filter' => ['id' => 'moderation_state_filter', 'allow empty' => TRUE],
        'sort' => ['id' => 'moderation_state_sort'],
      ];

      $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
      $data[$revision_table]['moderation_state'] = [
        'title' => t('Moderation state'),
        'relationship' => [
          'id' => 'standard',
          'label' => $this->t('@label moderation state', ['@label' => $entity_type->getLabel()]),
          'base' => $content_moderation_state_entity_revision_base_table,
          'base field' => 'content_entity_revision_id',
          'relationship field' => $entity_type->getKey('revision'),
          'extra' => [
            [
              'field' => 'content_entity_type_id',
              'value' => $entity_type_id,
            ],
          ],
        ],
        'field' => [
          'id' => 'moderation_state_field',
          'default_formatter' => 'content_moderation_state',
          'field_name' => 'moderation_state',
        ],
        'filter' => ['id' => 'moderation_state_filter', 'allow empty' => TRUE],
        'sort' => ['id' => 'moderation_state_sort'],
      ];
    }

    return $data;
  }

}
