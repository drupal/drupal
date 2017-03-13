<?php

namespace Drupal\content_moderation;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides the content_moderation views integration.
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

    $data['content_revision_tracker']['table']['group'] = $this->t('Content moderation (tracker)');

    $data['content_revision_tracker']['entity_type'] = [
      'title' => $this->t('Entity type'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['content_revision_tracker']['entity_id'] = [
      'title' => $this->t('Entity ID'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['content_revision_tracker']['langcode'] = [
      'title' => $this->t('Entity language'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'language',
      ],
      'argument' => [
        'id' => 'language',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $data['content_revision_tracker']['revision_id'] = [
      'title' => $this->t('Latest revision ID'),
      'field' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'argument' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ];

    $entity_types_with_moderation = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $this->moderationInformation->canModerateEntitiesOfEntityType($type);
    });

    // Add a join for each entity type to the content_revision_tracker table.
    foreach ($entity_types_with_moderation as $entity_type_id => $entity_type) {
      /** @var \Drupal\views\EntityViewsDataInterface $views_data */
      // We need the views_data handler in order to get the table name later.
      if ($this->entityTypeManager->hasHandler($entity_type_id, 'views_data') && $views_data = $this->entityTypeManager->getHandler($entity_type_id, 'views_data')) {
        // Add a join from the entity base table to the revision tracker table.
        $base_table = $views_data->getViewsTableForEntityType($entity_type);
        $data['content_revision_tracker']['table']['join'][$base_table] = [
          'left_field' => $entity_type->getKey('id'),
          'field' => 'entity_id',
          'extra' => [
            [
              'field' => 'entity_type',
              'value' => $entity_type_id,
            ],
          ],
        ];

        // Some entity types might not be translatable.
        if ($entity_type->hasKey('langcode')) {
          $data['content_revision_tracker']['table']['join'][$base_table]['extra'][] = [
            'field' => 'langcode',
            'left_field' => $entity_type->getKey('langcode'),
            'operation' => '=',
          ];
        }

        // Add a relationship between the revision tracker table to the latest
        // revision on the entity revision table.
        $data['content_revision_tracker']['latest_revision__' . $entity_type_id] = [
          'title' => $this->t('@label latest revision', ['@label' => $entity_type->getLabel()]),
          'group' => $this->t('@label revision', ['@label' => $entity_type->getLabel()]),
          'relationship' => [
            'id' => 'standard',
            'label' => $this->t('@label latest revision', ['@label' => $entity_type->getLabel()]),
            'base' => $this->getRevisionViewsTableForEntityType($entity_type),
            'base field' => $entity_type->getKey('revision'),
            'relationship field' => 'revision_id',
            'extra' => [
              [
                'left_field' => 'entity_type',
                'value' => $entity_type_id,
              ],
            ],
          ],
        ];

        // Some entity types might not be translatable.
        if ($entity_type->hasKey('langcode')) {
          $data['content_revision_tracker']['latest_revision__' . $entity_type_id]['relationship']['extra'][] = [
            'left_field' => 'langcode',
            'field' => $entity_type->getKey('langcode'),
            'operation' => '=',
          ];
        }
      }
    }

    // Provides a relationship from moderated entity to its moderation state
    // entity.
    $content_moderation_state_entity_type = \Drupal::entityTypeManager()->getDefinition('content_moderation_state');
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
        'field' => ['default_formatter' => 'content_moderation_state'],
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
        'field' => ['default_formatter' => 'content_moderation_state'],
      ];
    }

    return $data;
  }

  /**
   * Alters the table and field information from hook_views_data().
   *
   * @param array $data
   *   An array of all information about Views tables and fields, collected from
   *   hook_views_data(), passed by reference.
   *
   * @see hook_views_data()
   */
  public function alterViewsData(array &$data) {
    $entity_types_with_moderation = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $this->moderationInformation->canModerateEntitiesOfEntityType($type);
    });
    foreach ($entity_types_with_moderation as $type) {
      $data[$type->getRevisionTable()]['latest_revision'] = [
        'title' => t('Is Latest Revision'),
        'help' => t('Restrict the view to only revisions that are the latest revision of their entity.'),
        'filter' => ['id' => 'latest_revision'],
      ];
    }
  }

  /**
   * Gets the table of an entity type to be used as revision table in views.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return string
   *   The revision base table.
   */
  protected function getRevisionViewsTableForEntityType(EntityTypeInterface $entity_type) {
    return $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
  }

}
