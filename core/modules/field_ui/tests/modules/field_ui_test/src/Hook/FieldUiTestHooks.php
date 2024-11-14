<?php

declare(strict_types=1);

namespace Drupal\field_ui_test\Hook;

use Drupal\Core\Render\Element;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for field_ui_test.
 */
class FieldUiTestHooks {

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('field_config_access')]
  public function fieldConfigAccess(FieldConfigInterface $field) {
    return AccessResult::forbiddenIf($field->getName() == 'highlander');
  }

  /**
   * Implements hook_form_FORM_BASE_ID_alter().
   */
  #[Hook('form_entity_view_display_edit_form_alter')]
  public function formEntityViewDisplayEditFormAlter(&$form, FormStateInterface $form_state) : void {
    $table =& $form['fields'];
    foreach (Element::children($table) as $name) {
      $table[$name]['parent_wrapper']['parent']['#options'] = ['indent' => 'Indent'];
      $table[$name]['parent_wrapper']['parent']['#default_value'] = 'indent';
    }
    $table['indent'] = [
      '#attributes' => [
        'class' => [
          'draggable',
          'field-group',
        ],
        'id' => 'indent-id',
      ],
      '#row_type' => 'group',
      '#region_callback' => 'field_ui_test_region_callback',
      '#js_settings' => [
        'rowHandler' => 'group',
      ],
      'human_name' => [
        '#markup' => 'Indent',
        '#prefix' => '<span class="group-label">',
        '#suffix' => '</span>',
      ],
      'weight' => [
        '#type' => 'textfield',
        '#default_value' => 0,
        '#size' => 3,
        '#attributes' => [
          'class' => [
            'field-weight',
          ],
        ],
      ],
      'parent_wrapper' => [
        'parent' => [
          '#type' => 'select',
          '#options' => [
            'indent' => 'Indent',
          ],
          '#empty_value' => '',
          '#default_value' => '',
          '#attributes' => [
            'class' => [
              'field-parent',
            ],
          ],
          '#parents' => [
            'fields',
            'indent',
            'parent',
          ],
        ],
        'hidden_name' => [
          '#type' => 'hidden',
          '#default_value' => 'indent',
          '#attributes' => [
            'class' => [
              'field-name',
            ],
          ],
        ],
      ],
    ];
  }

}
