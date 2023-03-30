<?php

namespace Drupal\field_ui\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\Controller\EntityListController;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;

/**
 * Defines a controller to list field instances.
 */
class FieldConfigListController extends EntityListController {

  /**
   * Shows the 'Manage fields' page.
   *
   * @param string $entity_type_id
   *   The entity type.
   * @param string $bundle
   *   The entity bundle.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($entity_type_id = NULL, $bundle = NULL, RouteMatchInterface $route_match = NULL) {
    $field_type_plugin_manager = \Drupal::service('plugin.manager.field.field_type');

    $field_type_options = [];
    foreach ($field_type_plugin_manager->getGroupedDefinitions($field_type_plugin_manager->getUiDefinitions()) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        if (!$field_type['group_display']) {
          $key = $name;
          $label = $field_type['label'];
          $route_param = $field_type['id'];
        } else {
          $key = $category;
          $label = $category;
          $route_param = $category;
        }
        $icon = $this->getIcon($field_type['id']);
          $field_type_options[$category][$key] = [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'class' => ['field-option', 'use-ajax'],
            'role' => 'button',
            'tabindex' => '0',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '85vw',
              'title' => $this->t('Add @type Field', ['@type' => $label]),
            ]),
            // @todo: change parameter name to convey it takes field_type or category
            'href' => URL::fromRoute("field_ui.field_add_$entity_type_id", ['field_type' => $route_param,'node_type' => $bundle])->toString(),
          ],
          '#total_length' => strlen($field_type['description']) + strlen($field_type['label']),
          'thumb' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['field-option__thumb'],
            ],
            'icon' => [
              '#theme' => 'image',
              '#uri' =>  $icon['uri'],
              '#alt' => $icon['alt'],
              '#width' => 40,
            ],
          ],
          'words' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['field-option__words']
            ],
            'label' => [
              '#attributes' => [
                'class' => ['field-option__label']
              ],
              '#type' => 'html_tag',
              '#tag' => 'strong',
              '#value' => $label,
            ],
            'description' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['field-option__description'],
              ],
              '#markup'=> $field_type['group_display'] ? null : $field_type['description']
            ],
          ],
        ];
      }
    }

    $sorted_options = $this->optionsForSidebar($field_type_options);

    $build = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'manage-fields-container'
      ],
      'table' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'manage-fields-table'
        ],
        'table_contents' =>  $this->entityTypeManager()->getListBuilder('field_config')->render($entity_type_id, $bundle),
      ],
      'sidebar' => [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'manage-fields-add-field'
        ],
        'existing' => [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#value' => $this->t('Re-use existing field'),
          '#attributes' => [
            'class' => ['button', 'use-ajax'],
            'role' => 'button',
            'tabindex' => '0',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '85vw',
              'title' => $this->t('Re-use existing field'),
            ]),
            'href' => URL::fromRoute("field_ui.field_storage_config_add_$entity_type_id.reuse", ['node_type' => $bundle])->toString(),
          ],
        ],
        'add' => [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $this->t('Add a new field'),
        ],
        'options' => $sorted_options,
      ],
      '#attached' => ['library' =>
        [
          'field_ui/drupal.field_ui.manage_fields',
          'core/drupal.dialog.ajax',
        ],
      ],
    ];
    return $build;
  }

  private function optionsForSidebar($field_type_options) {
    $order = ['Text', 'Number', 'General', 'Reference', 'Reference revisions', 'Other'];
    $sorted_options = [];
    foreach ($order as $key) {
      if (!isset($field_type_options[$key])) {
        continue;
      }

      $sorted_options += ["header_$key" => [
        '#type' => 'html_tag',
        '#tag' => 'h4',
        '#value' => $key,
      ]];
      // Sort by shortest description + title to longest. Not exactly what we
      // want but surprisingly close.
      usort($field_type_options[$key], fn($a, $b) => $a['#total_length'] <=> $b['#total_length']);
      $sorted_options += [ $key => $field_type_options[$key] ];
    }
    return $sorted_options;
  }

  private function getIcon($field_name) {
    // Switch is used for fields that share the same icon.
    switch($field_name) {
      case 'decimal':
      case 'float':
        $icon_name = 'integer';
        break;
      case 'entity_reference_subclass':
        $icon_name = 'entity_reference';
        break;
      case 'list_float':
      $icon_name = 'list_integer';
        break;
      case 'shape_required':
        $icon_name = 'shape';
        break;
      case 'string':
      case 'string_long':
      case 'text_long':
      case 'text_with_summary':
      $icon_name = 'text';
        break;
      case 'boolean':
      case 'comment':
      case 'daterange':
      case 'datetime':
      case 'email':
      case 'entity_reference':
      case 'file':
      case 'image':
      case 'integer':
      case 'link':
      case 'list_integer':
      case 'list_string':
      case 'shape':
      case 'telephone':
      case 'text':
      case 'timestamp':
        $icon_name = $field_name;
        break;
      default:
        // Fallback icon for fields without one.
        $icon_name = 'fallback';
        break;
    }

    $icon['uri'] = "core/modules/field_ui/icons/$icon_name.svg";
    $icon['alt'] = "Icon for $field_name in add new field options.";
    return $icon;
  }



}
