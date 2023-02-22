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

    foreach ($field_type_plugin_manager->getGroupedDefinitions($field_type_plugin_manager->getUiDefinitions()) as $category => $field_types) {
      foreach ($field_types as $name => $field_type) {
        $field_type_options[$category][$name] = [
          '#type' => 'html_tag',
          '#tag' => 'a',
          '#attributes' => [
            'class' => ['field-option', 'use-ajax'],
            'role' => 'button',
            'tabindex' => '0',
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => '85vw',
              'title' => $this->t('Add @type Field', ['@type' => $field_type['label']]),
            ]),
            'href' => URL::fromRoute("field_ui.field_add_$entity_type_id", ['field_type' => $field_type['id'],'node_type' => $bundle])->toString(),
          ],
          '#description_length' => strlen($field_type['description']),
          'thumb' => [
            '#type' => 'container',
            '#attributes' => [
              'class' => ['field-option__thumb']
            ],
            '#markup' => '&nbsp;',
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
              '#value' => $field_type['label'],
            ],
            'description' => [
              '#type' => 'container',
              '#attributes' => [
                'class' => ['field-option__description']
              ],
              '#markup'=> $field_type['description']
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
        'add' => [
          '#type' => 'html_tag',
          '#tag' => 'h2',
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
      $sorted_options += ["header_$key" => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $key,
      ]];
      // Sort by shortest description to longest. Not exactly what we
      // want but surprisingly close.
      usort($field_type_options[$key], fn($a, $b) => $a['#description_length'] > $b['#description_length']);
      $sorted_options += [ $key => $field_type_options[$key] ];
    }
    return $sorted_options;
  }

}
