<?php

declare(strict_types=1);

namespace Drupal\claro\Hook;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\Form\ViewsForm;
use Drupal\views_ui\Form\Ajax\ViewsFormInterface;

/**
 * Form hooks for claro.
 */
class ClaroFormHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id): void {
    $build_info = $form_state->getBuildInfo();
    $form_object = $form_state->getFormObject();

    // Make entity forms delete link use the action-link component.
    if (isset($form['actions']['delete']['#type']) && $form['actions']['delete']['#type'] === 'link' && !empty($build_info['callback_object']) && $build_info['callback_object'] instanceof EntityForm) {
      $form['actions']['delete'] = _claro_convert_link_to_action_link($form['actions']['delete'], 'trash', 'default', 'danger');
    }

    if (isset($form['actions']['delete_translation']['#type']) && $form['actions']['delete_translation']['#type'] === 'link' && !empty($build_info['callback_object']) && $build_info['callback_object'] instanceof EntityForm) {
      $form['actions']['delete_translation'] = _claro_convert_link_to_action_link($form['actions']['delete_translation'], 'trash', 'default', 'danger');
    }

    if (($form_object instanceof ViewsForm || $form_object instanceof ViewsFormInterface) && isset($form['override']['#prefix'])) {
      // Replace form--inline class so positioning of override form elements
      // don't have to depend on floats.
      $form['override']['#prefix'] = str_replace('form--inline', 'form--flex', $form['override']['#prefix']);
    }

    if ($form_object instanceof ViewsForm && str_starts_with($form_object->getBaseFormId(), 'views_form_media_library')) {
      if (isset($form['header'])) {
        $form['header']['#attributes']['class'][] = 'media-library-views-form__header';
        $form['header']['media_bulk_form']['#attributes']['class'][] = 'media-library-views-form__bulk_form';
      }
      $form['actions']['submit']['#attributes']['class'] = ['media-library-select'];
      $form['#attributes']['class'][] = 'media-library-views-form';
    }

    if ($form_object instanceof ViewsForm && !empty($form['header'])) {
      $view = $form_state->getBuildInfo()['args'][0];
      $view_title = $view->getTitle();

      // Determine if the Views form includes a bulk operations form. If it
      // does, move it to the bottom and remove the second bulk operations
      // submit.
      foreach (Element::children($form['header']) as $key) {
        if (str_contains($key, '_bulk_form')) {
          // Move the bulk actions form from the header to its own container.
          $form['bulk_actions_container'] = $form['header'][$key];
          unset($form['header'][$key]);

          // Remove the supplementary bulk operations submit button as it
          // appears in the same location the form was moved to.
          unset($form['actions']);

          $form['bulk_actions_container']['#attributes']['data-drupal-views-bulk-actions'] = '';
          $form['bulk_actions_container']['#attributes']['class'][] = 'views-bulk-actions';
          $form['bulk_actions_container']['actions']['submit']['#button_type'] = 'primary';
          $form['bulk_actions_container']['actions']['submit']['#attributes']['class'][] = 'button--small';
          $label = $this->t('Perform actions on the selected items in the %view_title view', ['%view_title' => $view_title]);
          $label_id = $key . '_group_label';

          // Group the bulk actions select and submit elements, and add a label
          // that makes the purpose of these elements more clear to
          // screen readers.
          $form['bulk_actions_container']['#attributes']['role'] = 'group';
          $form['bulk_actions_container']['#attributes']['aria-labelledby'] = $label_id;
          $form['bulk_actions_container']['group_label'] = [
            '#type' => 'container',
            '#markup' => $label,
            '#attributes' => [
              'id' => $label_id,
              'class' => ['visually-hidden'],
            ],
            '#weight' => -1,
          ];

          // Add a status label for counting the number of items selected.
          $form['bulk_actions_container']['status'] = [
            '#type' => 'container',
            '#markup' => $this->t('No items selected'),
            '#weight' => -1,
            '#attributes' => [
              'class' => [
                'js-views-bulk-actions-status',
                'views-bulk-actions__item',
                'views-bulk-actions__item--status',
                'js-show',
              ],
              'data-drupal-views-bulk-actions-status' => '',
            ],
          ];

          // Loop through bulk actions items and add the needed CSS classes.
          $bulk_action_item_keys = Element::children($form['bulk_actions_container'], TRUE);
          $bulk_last_key = NULL;
          $bulk_child_before_actions_key = NULL;
          foreach ($bulk_action_item_keys as $bulk_action_item_key) {
            if (!empty($form['bulk_actions_container'][$bulk_action_item_key]['#type'])) {
              if ($form['bulk_actions_container'][$bulk_action_item_key]['#type'] === 'actions') {
                // We need the key of the element that precedes the actions
                // element.
                $bulk_child_before_actions_key = $bulk_last_key;
                $form['bulk_actions_container'][$bulk_action_item_key]['#attributes']['class'][] = 'views-bulk-actions__item';
              }

              if (!in_array($form['bulk_actions_container'][$bulk_action_item_key]['#type'], ['hidden', 'actions'])) {
                $form['bulk_actions_container'][$bulk_action_item_key]['#wrapper_attributes']['class'][] = 'views-bulk-actions__item';
                $bulk_last_key = $bulk_action_item_key;
              }
            }
          }

          if ($bulk_child_before_actions_key) {
            $form['bulk_actions_container'][$bulk_child_before_actions_key]['#wrapper_attributes']['class'][] = 'views-bulk-actions__item--preceding-actions';
          }
        }
      }
    }
  }

}
