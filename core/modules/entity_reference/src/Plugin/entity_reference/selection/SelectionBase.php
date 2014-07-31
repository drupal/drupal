<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\entity_reference\selection\SelectionBase.
 */

namespace Drupal\entity_reference\Plugin\entity_reference\selection;

use Drupal\Component\Utility\String;
use Drupal\Core\Database\Query\AlterableInterface;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface;

/**
 * Default plugin implementation of the Entity Reference Selection plugin.
 *
 * Also serves as a base class for specific types of Entity Reference
 * Selection plugins.
 *
 * @see \Drupal\entity_reference\Plugin\Type\SelectionPluginManager
 * @see \Drupal\entity_reference\Annotation\EntityReferenceSelection
 * @see \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface
 * @see \Drupal\entity_reference\Plugin\Derivative\SelectionBase
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "default",
 *   label = @Translation("Default"),
 *   group = "default",
 *   weight = 0,
 *   deriver = "Drupal\entity_reference\Plugin\Derivative\SelectionBase"
 * )
 */
class SelectionBase implements SelectionInterface {

  /**
   * The field definition.
   *
   * @var \Drupal\Core\Field\FieldDefinitionInterface
   */
  protected $fieldDefinition;

  /**
   * The entity object, or NULL
   *
   * @var \Drupal\Core\Entity\EntityInterface|null
   */
  protected $entity;

  /**
   * Constructs a SelectionBase object.
   */
  public function __construct(FieldDefinitionInterface $field_definition, EntityInterface $entity = NULL) {
    $this->fieldDefinition = $field_definition;
    $this->entity = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public static function settingsForm(FieldDefinitionInterface $field_definition) {
    $entity_manager = \Drupal::entityManager();
    $entity_type_id = $field_definition->getSetting('target_type');
    $selection_handler_settings = $field_definition->getSetting('handler_settings') ?: array();
    $entity_type = $entity_manager->getDefinition($entity_type_id);
    $bundles = $entity_manager->getBundleInfo($entity_type_id);

    // Merge-in default values.
    $selection_handler_settings += array(
      'target_bundles' => array(),
      'sort' => array(
        'field' => '_none',
      ),
      'auto_create' => FALSE,
    );

    if ($entity_type->hasKey('bundle')) {
      $bundle_options = array();
      foreach ($bundles as $bundle_name => $bundle_info) {
        $bundle_options[$bundle_name] = $bundle_info['label'];
      }

      $target_bundles_title = t('Bundles');
      // Default core entity types with sensible labels.
      if ($entity_type_id == 'node') {
        $target_bundles_title = t('Content types');
      }
      elseif ($entity_type_id == 'taxonomy_term') {
        $target_bundles_title = t('Vocabularies');
      }

      $form['target_bundles'] = array(
        '#type' => 'checkboxes',
        '#title' => $target_bundles_title,
        '#options' => $bundle_options,
        '#default_value' => (!empty($selection_handler_settings['target_bundles'])) ? $selection_handler_settings['target_bundles'] : array(),
        '#required' => TRUE,
        '#size' => 6,
        '#multiple' => TRUE,
        '#element_validate' => array('_entity_reference_element_validate_filter'),
      );
    }
    else {
      $form['target_bundles'] = array(
        '#type' => 'value',
        '#value' => array(),
      );
    }

    if ($entity_type->isSubclassOf('\Drupal\Core\Entity\ContentEntityInterface')) {
      $fields = array();
      foreach (array_keys($bundles) as $bundle) {
        $bundle_fields = array_filter($entity_manager->getFieldDefinitions($entity_type_id, $bundle), function ($field_definition) {
          return !$field_definition->isComputed();
        });
        foreach ($bundle_fields as $instance_name => $field_definition) {
          /* @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
          $columns = $field_definition->getFieldStorageDefinition()->getColumns();
          // If there is more than one column, display them all, otherwise just
          // display the field label.
          // @todo: Use property labels instead of the column name.
          if (count($columns) > 1) {
            foreach ($columns as $column_name => $column_info) {
              $fields[$instance_name . '.' . $column_name] = t('@label (@column)', array('@label' => $field_definition->getLabel(), '@column' => $column_name));
            }
          }
          else {
            $fields[$instance_name] = t('@label', array('@label' => $field_definition->getLabel()));
          }
        }
      }

      $form['sort']['field'] = array(
        '#type' => 'select',
        '#title' => t('Sort by'),
        '#options' => array(
          '_none' => t('- None -'),
        ) + $fields,
        '#ajax' => TRUE,
        '#limit_validation_errors' => array(),
        '#default_value' => $selection_handler_settings['sort']['field'],
      );

      $form['sort']['settings'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('entity_reference-settings')),
        '#process' => array('_entity_reference_form_process_merge_parent'),
      );

      if ($selection_handler_settings['sort']['field'] != '_none') {
        // Merge-in default values.
        $selection_handler_settings['sort'] += array(
          'direction' => 'ASC',
        );

        $form['sort']['settings']['direction'] = array(
          '#type' => 'select',
          '#title' => t('Sort direction'),
          '#required' => TRUE,
          '#options' => array(
            'ASC' => t('Ascending'),
            'DESC' => t('Descending'),
          ),
          '#default_value' => $selection_handler_settings['sort']['direction'],
        );
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferenceableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $target_type = $this->fieldDefinition->getSetting('target_type');

    $query = $this->buildEntityQuery($match, $match_operator);
    if ($limit > 0) {
      $query->range(0, $limit);
    }

    $result = $query->execute();

    if (empty($result)) {
      return array();
    }

    $options = array();
    $entities = entity_load_multiple($target_type, $result);
    foreach ($entities as $entity_id => $entity) {
      $bundle = $entity->bundle();
      $options[$bundle][$entity_id] = String::checkPlain($entity->label());
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function countReferenceableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $query = $this->buildEntityQuery($match, $match_operator);
    return $query
      ->count()
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids) {
    $result = array();
    if ($ids) {
      $target_type = $this->fieldDefinition->getSetting('target_type');
      $entity_type = \Drupal::entityManager()->getDefinition($target_type);
      $query = $this->buildEntityQuery();
      $result = $query
        ->condition($entity_type->getKey('id'), $ids, 'IN')
        ->execute();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function validateAutocompleteInput($input, &$element, FormStateInterface $form_state, $form, $strict = TRUE) {
    $bundled_entities = $this->getReferenceableEntities($input, '=', 6);
    $entities = array();
    foreach ($bundled_entities as $entities_list) {
      $entities += $entities_list;
    }
    $params = array(
      '%value' => $input,
      '@value' => $input,
    );
    if (empty($entities)) {
      if ($strict) {
        // Error if there are no entities available for a required field.
        form_error($element, $form_state, t('There are no entities matching "%value".', $params));
      }
    }
    elseif (count($entities) > 5) {
      $params['@id'] = key($entities);
      // Error if there are more than 5 matching entities.
      form_error($element, $form_state, t('Many entities are called %value. Specify the one you want by appending the id in parentheses, like "@value (@id)".', $params));
    }
    elseif (count($entities) > 1) {
      // More helpful error if there are only a few matching entities.
      $multiples = array();
      foreach ($entities as $id => $name) {
        $multiples[] = $name . ' (' . $id . ')';
      }
      $params['@id'] = $id;
      form_error($element, $form_state, t('Multiple entities match this reference; "%multiple". Specify the one you want by appending the id in parentheses, like "@value (@id)".', array('%multiple' => implode('", "', $multiples))));
    }
    else {
      // Take the one and only matching entity.
      return key($entities);
    }
  }

  /**
   * Builds an EntityQuery to get referenceable entities.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The EntityQuery object with the basic conditions and sorting applied to
   *   it.
   */
  public function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $target_type = $this->fieldDefinition->getSetting('target_type');
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    $entity_type = \Drupal::entityManager()->getDefinition($target_type);

    $query = \Drupal::entityQuery($target_type);
    if (!empty($handler_settings['target_bundles'])) {
      $query->condition($entity_type->getKey('bundle'), $handler_settings['target_bundles'], 'IN');
    }

    if (isset($match) && $label_key = $entity_type->getKey('label')) {
      $query->condition($label_key, $match, $match_operator);
    }

    // Add entity-access tag.
    $query->addTag($this->fieldDefinition->getSetting('target_type') . '_access');

    // Add the Selection handler for
    // entity_reference_query_entity_reference_alter().
    $query->addTag('entity_reference');
    $query->addMetaData('field_definition', $this->fieldDefinition);
    $query->addMetaData('entity_reference_selection_handler', $this);

    // Add the sort option.
    $handler_settings = $this->fieldDefinition->getSetting('handler_settings');
    if (!empty($handler_settings['sort'])) {
      $sort_settings = $handler_settings['sort'];
      if ($sort_settings['field'] != '_none') {
        $query->sort($sort_settings['field'], $sort_settings['direction']);
      }
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(SelectInterface $query) { }

  /**
   * Helper method: Passes a query to the alteration system again.
   *
   * This allows Entity Reference to add a tag to an existing query so it can
   * ask access control mechanisms to alter it again.
   */
  protected function reAlterQuery(AlterableInterface $query, $tag, $base_table) {
    // Save the old tags and metadata.
    // For some reason, those are public.
    $old_tags = $query->alterTags;
    $old_metadata = $query->alterMetaData;

    $query->alterTags = array($tag => TRUE);
    $query->alterMetaData['base_table'] = $base_table;
    \Drupal::moduleHandler()->alter(array('query', 'query_' . $tag), $query);

    // Restore the tags and metadata.
    $query->alterTags = $old_tags;
    $query->alterMetaData = $old_metadata;
  }
}
