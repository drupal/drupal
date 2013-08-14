<?php

/**
 * @file
 * Contains Drupal\views_ui\ViewPreviewFormController.
 */

namespace Drupal\views_ui;

use Drupal\Core\Entity\EntityControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\user\TempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Views preview form.
 */
class ViewPreviewFormController extends ViewFormControllerBase implements EntityControllerInterface {

  /**
   * The views temp store.
   *
   * @var \Drupal\user\TempStore
   */
  protected $tempStore;

  /**
   * Constructs a new ViewPreviewFormController object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface
   *   The module handler service.
   * @param \Drupal\user\TempStoreFactory $temp_store_factory
   *   The factory for the temp store object.
   */
  public function __construct(ModuleHandlerInterface $module_handler, TempStoreFactory $temp_store_factory) {
    parent::__construct($module_handler);

    $this->tempStore = $temp_store_factory->get('views');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $container->get('module_handler'),
      $container->get('user.tempstore')
    );
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::form().
   */
  public function form(array $form, array &$form_state) {
    $view = $this->entity;

    $form['#prefix'] = '<div id="views-preview-wrapper" class="views-admin clearfix">';
    $form['#suffix'] = '</div>';
    $form['#id'] = 'views-ui-preview-form';

    // Reset the cache of IDs. Drupal rather aggressively prevents ID
    // duplication but this causes it to remember IDs that are no longer even
    // being used.
    $seen_ids_init = &drupal_static('drupal_html_id:init');
    $seen_ids_init = array();

    $form_state['no_cache'] = TRUE;

    $form['controls']['#attributes'] = array('class' => array('clearfix'));

    // Add a checkbox controlling whether or not this display auto-previews.
    $form['controls']['live_preview'] = array(
      '#type' => 'checkbox',
      '#id' => 'edit-displays-live-preview',
      '#title' => t('Auto preview'),
      '#default_value' => \Drupal::config('views.settings')->get('ui.always_live_preview'),
    );

    // Add the arguments textfield
    $form['controls']['view_args'] = array(
      '#type' => 'textfield',
      '#title' => t('Preview with contextual filters:'),
      '#description' => t('Separate contextual filter values with a "/". For example, %example.', array('%example' => '40/12/10')),
      '#id' => 'preview-args',
    );

    $args = array();
    if (!empty($form_state['values']['view_args'])) {
      $args = explode('/', $form_state['values']['view_args']);
    }

    if (!empty($form_state['show_preview']) || !empty($form_state['input']['js'])) {
      $form['preview'] = array(
        '#weight' => 110,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('id' => 'views-live-preview'),
        '#markup' => $view->renderPreview($this->displayID, $args),
      );
    }
    $form['#action'] = url('admin/structure/views/view/' . $view->id() .'/preview/' . $this->displayID);

    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityFormController::actions().
   */
  protected function actions(array $form, array &$form_state) {
    $view = $this->entity;
    return array(
      '#attributes' => array(
        'id' => 'preview-submit-wrapper',
      ),
      'button' => array(
        '#type' => 'submit',
        '#value' => t('Update preview'),
        '#attributes' => array('class' => array('arguments-preview')),
        '#submit' => array(array($this, 'submitPreview')),
        '#id' => 'preview-submit',
        '#ajax' => array(
          'path' => 'admin/structure/views/view/' . $view->id() . '/preview/' . $this->displayID,
          'wrapper' => 'views-preview-wrapper',
          'event' => 'click',
          'progress' => array('type' => 'throbber'),
          'method' => 'replaceWith',
        ),
      ),
    );
  }

  /**
   * Form submission handler for the Preview button.
   */
  public function submitPreview($form, &$form_state) {
    // Rebuild the form with a pristine $view object.
    $view = $this->entity;
    // Attempt to load the view from temp store, otherwise create a new one.
    if (!$new_view = $this->tempStore->get($view->id())) {
      $new_view = new ViewUI($view);
    }
    $form_state['build_info']['args'][0] = $new_view;
    $form_state['show_preview'] = TRUE;
    $form_state['rebuild'] = TRUE;
  }

}
