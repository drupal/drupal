<?php

declare(strict_types=1);

namespace Drupal\field_ui\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SortArray;
use Drupal\Core\Ajax\AjaxHelperTrait;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Field\FallbackFieldTypeCategory;
use Drupal\Core\Field\FieldTypeCategoryManagerInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\Url;
use Drupal\field_ui\FieldUI;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for building the field type links.
 *
 * @internal
 */
final class FieldStorageAddController extends ControllerBase {
  use AjaxHelperTrait;

  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * Constructs a new FieldStorageAddController.
   */
  public function __construct(
    protected FieldTypePluginManagerInterface $fieldTypePluginManager,
    protected FieldTypeCategoryManagerInterface $fieldTypeCategoryManager,
    protected PrivateTempStore $tempStore,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.field.field_type'),
      $container->get('plugin.manager.field.field_type_category'),
      $container->get('tempstore.private')->get('field_ui'),
    );
  }

  /**
   * Deletes stored field data and builds the field selection links.
   *
   * @param string $entity_type_id
   *   The name of the entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The field selection links.
   */
  public function resetField(string $entity_type_id, string $bundle, string $field_name) {
    // Delete stored field data in case user changes field type.
    $this->tempStore->delete("$entity_type_id:$field_name");
    return $this->getFieldSelectionLinks($entity_type_id, $bundle);
  }

  /**
   * Builds the field selection links.
   *
   * @param string $entity_type_id
   *   The name of the entity type.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return array
   *   The field selection links.
   */
  public function getFieldSelectionLinks(string $entity_type_id, string $bundle) {
    $build = [];
    $this->entityTypeId = $entity_type_id;
    $this->bundle = $bundle;
    $ui_definitions = $this->fieldTypePluginManager->getEntityTypeUiDefinitions($entity_type_id);
    $field_type_options = $unique_definitions = [];
    $grouped_definitions = $this->fieldTypePluginManager->getGroupedDefinitions($ui_definitions, 'label', 'id');
    $category_definitions = $this->fieldTypeCategoryManager->getDefinitions();
    // Invoke a hook to get category properties.
    foreach ($grouped_definitions as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $unique_definitions[$category][$name] = ['unique_identifier' => $name] + $field_type;
        if ($this->fieldTypeCategoryManager->hasDefinition($category)) {
          $category_plugin = $this->fieldTypeCategoryManager->createInstance($category, $unique_definitions[$category][$name], $category_definitions[$category]);
          $field_type_options[$category_plugin->getPluginId()] = ['unique_identifier' => $name] + $field_type;
        }
        else {
          $field_type_options[(string) $field_type['label']] = ['unique_identifier' => $name] + $field_type;
        }
      }
    }
    $build['add-label'] = [
      '#type' => 'label',
      '#title' => $this->t('Choose a type of field'),
      '#title_display' => 'before',
      '#required' => TRUE,
    ];

    $build['add'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'add-field-container',
      ],
    ];

    $field_type_options_radios = [];
    foreach ($field_type_options as $id => $field_type) {
      /** @var \Drupal\Core\Field\FieldTypeCategoryInterface $category_info */
      $category_info = $this->fieldTypeCategoryManager->createInstance($field_type['category'], $field_type);
      $entity_type = $this->entityTypeManager()->getDefinition($this->entityTypeId);
      $display_as_group = !($category_info instanceof FallbackFieldTypeCategory);
      $route_parameters = [
        'entity_type' => $this->entityTypeId,
        'bundle' => $this->bundle,
        'display_as_group' => $display_as_group ? 'true' : 'false',
        'selected_field_type' => $category_info->getPluginId(),
      ] + FieldUI::getRouteBundleParameter($entity_type, $this->bundle);
      $cleaned_class_name = Html::getClass($field_type['unique_identifier']);
      $field_type_options_radios[$id] = [
        '#type' => 'html_tag',
        '#tag' => 'a',
        '#attributes' => [
          'class' => ['field-option', 'use-ajax'],
          'role' => 'button',
          'tabindex' => '0',
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 1100,
            'title' => $this->t('Add field: @type', ['@type' => $category_info->getLabel()]),
          ]),
          'href' => Url::fromRoute("field_ui.field_storage_config_add_sub_{$this->entityTypeId}", $route_parameters)->toString(),
        ],
        '#weight' => $category_info->getWeight(),
        'thumb' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field-option__thumb'],
          ],
          'icon' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['field-option__icon', $display_as_group ?
                "field-icon-{$field_type['category']}" : "field-icon-$cleaned_class_name",
              ],
            ],
          ],
        ],
        // Store some data we later need.
        '#data' => [
          '#group_display' => $display_as_group,
        ],
        'words' => [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['field-option__words'],
          ],
          'label' => [
            '#attributes' => [
              'class' => ['field-option__label'],
            ],
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#value' => $category_info->getLabel(),
          ],
          'description' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['field-option__description'],
            ],
            '#markup' => $category_info->getDescription(),
          ],
        ],
      ];

      if ($libraries = $category_info->getLibraries()) {
        $field_type_options_radios[$id]['#attached']['library'] = $libraries;
      }
    }
    uasort($field_type_options_radios, [SortArray::class, 'sortByWeightProperty']);
    $build['add']['new_storage_type'] = $field_type_options_radios;
    $build['#attached']['library'][] = 'field_ui/drupal.field_ui';
    $build['#attached']['library'][] = 'field_ui/drupal.field_ui.manage_fields';
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $build;
  }

}
