<?php

namespace Drupal\views_ui;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Form controller for the Views preview form.
 *
 * @internal
 */
class ViewPreviewForm extends ViewFormBase {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $view = $this->entity;

    $form['#prefix'] = '<div id="views-preview-wrapper" class="views-preview-wrapper views-admin clearfix">';
    $form['#suffix'] = '</div>';
    $form['#id'] = 'views-ui-preview-form';

    $form_state->disableCache();

    $form['controls']['#attributes'] = ['class' => ['clearfix']];

    $form['controls']['title'] = [
      '#prefix' => '<h2 class="view-preview-form__title">',
      '#markup' => $this->t('Preview'),
      '#suffix' => '</h2>',
    ];

    // Add a checkbox controlling whether or not this display auto-previews.
    $form['controls']['live_preview'] = [
      '#type' => 'checkbox',
      '#id' => 'edit-displays-live-preview',
      '#title' => $this->t('Auto preview'),
      '#default_value' => \Drupal::config('views.settings')->get('ui.always_live_preview'),
    ];

    // Add the arguments textfield.
    $form['controls']['view_args'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Preview with contextual filters:'),
      '#description' => $this->t('Separate contextual filter values with a "/". For example, %example.', ['%example' => '40/12/10']),
      '#id' => 'preview-args',
    ];

    $args = [];
    if ($form_state->getValue('view_args', '') !== '') {
      $args = explode('/', $form_state->getValue('view_args'));
    }

    $user_input = $form_state->getUserInput();
    if ($form_state->get('show_preview') || !empty($user_input['js'])) {
      $form['preview'] = [
        '#weight' => 110,
        '#theme_wrappers' => ['container'],
        '#attributes' => ['id' => 'views-live-preview', 'class' => ['views-live-preview']],
        'preview' => $view->renderPreview($this->displayID, $args),
      ];
    }
    $uri = $view->toUrl('preview-form');
    $uri->setRouteParameter('display_id', $this->displayID);
    $form['#action'] = $uri->toString();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $view = $this->entity;
    return [
      '#attributes' => [
        'id' => 'preview-submit-wrapper',
        'class' => ['preview-submit-wrapper'],
      ],
      'button' => [
        '#type' => 'submit',
        '#value' => $this->t('Update preview'),
        '#attributes' => ['class' => ['arguments-preview']],
        '#submit' => ['::submitPreview'],
        '#id' => 'preview-submit',
        '#ajax' => [
          'url' => Url::fromRoute('entity.view.preview_form', ['view' => $view->id(), 'display_id' => $this->displayID]),
          'wrapper' => 'views-preview-wrapper',
          'event' => 'click',
          'progress' => ['type' => 'fullscreen'],
          'method' => 'replaceWith',
          'disable-refocus' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Form submission handler for the Preview button.
   */
  public function submitPreview($form, FormStateInterface $form_state) {
    $form_state->set('show_preview', TRUE);
    $form_state->setRebuild();
  }

}
