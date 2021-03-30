<?php

namespace Drupal\views\Form;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\views\Render\ViewsRenderPipelineMarkup;
use Drupal\views\ViewExecutable;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class ViewsFormMainForm implements FormInterface, TrustedCallbackInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
  }

  /**
   * Replaces views substitution placeholders.
   *
   * @param array $element
   *   An associative array containing the properties of the element.
   *   Properties used: #substitutions, #children.
   *
   * @return array
   *   The $element with prepared variables ready for #theme 'form'
   *   in views_form_views_form.
   */
  public static function preRenderViewsForm(array $element) {
    // Placeholders and their substitutions (usually rendered form elements).
    $search = [];
    $replace = [];

    // Add in substitutions provided by the form.
    foreach ($element['#substitutions']['#value'] as $substitution) {
      $field_name = $substitution['field_name'];
      $row_id = $substitution['row_id'];

      $search[] = $substitution['placeholder'];
      $replace[] = isset($element[$field_name][$row_id]) ? \Drupal::service('renderer')->render($element[$field_name][$row_id]) : '';
    }
    // Add in substitutions from hook_views_form_substitutions().
    $substitutions = \Drupal::moduleHandler()->invokeAll('views_form_substitutions');
    foreach ($substitutions as $placeholder => $substitution) {
      $search[] = Html::escape($placeholder);
      // Ensure that any replacements made are safe to make.
      if (!($substitution instanceof MarkupInterface)) {
        $substitution = Html::escape($substitution);
      }
      $replace[] = $substitution;
    }

    // Apply substitutions to the rendered output.
    $output = str_replace($search, $replace, \Drupal::service('renderer')->render($element['output']));
    $element['output'] = ['#markup' => ViewsRenderPipelineMarkup::create($output)];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['preRenderViewsForm'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ViewExecutable $view = NULL, $output = []) {
    $form['#prefix'] = '<div class="views-form">';
    $form['#suffix'] = '</div>';

    $form['#pre_render'][] = [static::class, 'preRenderViewsForm'];

    // Add the output markup to the form array so that it's included when the form
    // array is passed to the theme function.
    $form['output'] = $output;
    // This way any additional form elements will go before the view
    // (below the exposed widgets).
    $form['output']['#weight'] = 50;

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    $substitutions = [];
    foreach ($view->field as $field_name => $field) {
      $form_element_name = $field_name;
      if (method_exists($field, 'form_element_name')) {
        $form_element_name = $field->form_element_name();
      }
      $method_form_element_row_id_exists = FALSE;
      if (method_exists($field, 'form_element_row_id')) {
        $method_form_element_row_id_exists = TRUE;
      }

      // If the field provides a views form, allow it to modify the $form array.
      $has_form = FALSE;
      if (property_exists($field, 'views_form_callback')) {
        $callback = $field->views_form_callback;
        $callback($view, $field, $form, $form_state);
        $has_form = TRUE;
      }
      elseif (method_exists($field, 'viewsForm')) {
        $field->viewsForm($form, $form_state);
        $has_form = TRUE;
      }

      // Build the substitutions array for use in the theme function.
      if ($has_form) {
        foreach ($view->result as $row_id => $row) {
          if ($method_form_element_row_id_exists) {
            $form_element_row_id = $field->form_element_row_id($row_id);
          }
          else {
            $form_element_row_id = $row_id;
          }

          $substitutions[] = [
            'placeholder' => '<!--form-item-' . $form_element_name . '--' . $form_element_row_id . '-->',
            'field_name' => $form_element_name,
            'row_id' => $form_element_row_id,
          ];
        }
      }
    }

    // Give the area handlers a chance to extend the form.
    $area_handlers = array_merge(array_values($view->header), array_values($view->footer));
    $empty = empty($view->result);
    foreach ($area_handlers as $area) {
      if (method_exists($area, 'viewsForm') && !$area->viewsFormEmpty($empty)) {
        $area->viewsForm($form, $form_state);
      }
    }

    $form['#substitutions'] = [
      '#type' => 'value',
      '#value' => $substitutions,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->getBuildInfo()['args'][0];

    // Call the validation method on every field handler that has it.
    foreach ($view->field as $field) {
      if (method_exists($field, 'viewsFormValidate')) {
        $field->viewsFormValidate($form, $form_state);
      }
    }

    // Call the validate method on every area handler that has it.
    foreach (['header', 'footer'] as $area) {
      foreach ($view->{$area} as $area_handler) {
        if (method_exists($area_handler, 'viewsFormValidate')) {
          $area_handler->viewsFormValidate($form, $form_state);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->getBuildInfo()['args'][0];

    // Call the submit method on every field handler that has it.
    foreach ($view->field as $field) {
      if (method_exists($field, 'viewsFormSubmit')) {
        $field->viewsFormSubmit($form, $form_state);
      }
    }

    // Call the submit method on every area handler that has it.
    foreach (['header', 'footer'] as $area) {
      foreach ($view->{$area} as $area_handler) {
        if (method_exists($area_handler, 'viewsFormSubmit')) {
          $area_handler->viewsFormSubmit($form, $form_state);
        }
      }
    }
  }

}
