<?php

/**
 * @file
 * Contains \Drupal\views\Form\ViewsExposedForm.
 */

namespace Drupal\views\Form;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\String;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\views\ExposedFormCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the views exposed form.
 */
class ViewsExposedForm extends FormBase {

  /**
   * The exposed form cache.
   *
   * @var \Drupal\views\ExposedFormCache
   */
  protected $exposedFormCache;

  /**
   * Constructs a new ViewsExposedForm
   *
   * @param \Drupal\views\ExposedFormCache $exposed_form_cache
   *   The exposed form cache.
   */
  public function __construct(ExposedFormCache $exposed_form_cache) {
    $this->exposedFormCache = $exposed_form_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('views.exposed_form_cache'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_exposed_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Don't show the form when batch operations are in progress.
    if ($batch = batch_get() && isset($batch['current_set'])) {
      return array(
        // Set the theme callback to be nothing to avoid errors in template_preprocess_views_exposed_form().
        '#theme' => '',
      );
    }

    // Make sure that we validate because this form might be submitted
    // multiple times per page.
    $form_state->setValidationEnforced();
    /** @var \Drupal\views\ViewExecutable $view */
    $view = $form_state->get('view');
    $display = &$form_state->get('display');

    $form_state->setUserInput($view->getExposedInput());

    // Let form plugins know this is for exposed widgets.
    $form_state->set('exposed', TRUE);
    // Check if the form was already created
    if ($cache = $this->exposedFormCache->getForm($view->storage->id(), $view->current_display)) {
      return $cache;
    }

    $form['#info'] = array();

    // Go through each handler and let it generate its exposed widget.
    foreach ($view->display_handler->handlers as $type => $value) {
      /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface $handler */
      foreach ($view->$type as $id => $handler) {
        if ($handler->canExpose() && $handler->isExposed()) {
          // Grouped exposed filters have their own forms.
          // Instead of render the standard exposed form, a new Select or
          // Radio form field is rendered with the available groups.
          // When an user choose an option the selected value is split
          // into the operator and value that the item represents.
          if ($handler->isAGroup()) {
            $handler->groupForm($form, $form_state);
            $id = $handler->options['group_info']['identifier'];
          }
          else {
            $handler->buildExposedForm($form, $form_state);
          }
          if ($info = $handler->exposedInfo()) {
            $form['#info']["$type-$id"] = $info;
          }
        }
      }
    }

    $form['actions'] = array(
      '#type' => 'actions'
    );
    $form['actions']['submit'] = array(
      // Prevent from showing up in \Drupal::request()->query.
      '#name' => '',
      '#type' => 'submit',
      '#value' => $this->t('Apply'),
      '#id' => drupal_html_id('edit-submit-' . $view->storage->id()),
    );

    $form['#action'] = $view->hasUrl() ? $view->getUrl()->toString() : Url::fromRoute('<current>')->toString();
    $form['#theme'] = $view->buildThemeFunctions('views_exposed_form');
    $form['#id'] = Html::cleanCssIdentifier('views_exposed_form-' . String::checkPlain($view->storage->id()) . '-' . String::checkPlain($display['id']));

    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form_plugin */
    $exposed_form_plugin = $form_state->get('exposed_form_plugin');
    $exposed_form_plugin->exposedFormAlter($form, $form_state);

    // Save the form.
    $this->exposedFormCache->setForm($view->storage->id(), $view->current_display, $form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach (array('field', 'filter') as $type) {
      /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface[] $handlers */
      $handlers = &$form_state->get('view')->$type;
      foreach ($handlers as $key => $handler) {
        $handlers[$key]->validateExposed($form, $form_state);
      }
    }
    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form_plugin */
    $exposed_form_plugin = $form_state->get('exposed_form_plugin');
    $exposed_form_plugin->exposedFormValidate($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach (array('field', 'filter') as $type) {
      /** @var \Drupal\views\Plugin\views\ViewsHandlerInterface[] $handlers */
      $handlers = &$form_state->get('view')->$type;
      foreach ($handlers as $key => $info) {
        $handlers[$key]->submitExposed($form, $form_state);
      }
    }
    $view = $form_state->get('view');
    $view->exposed_data = $form_state->getValues();
    $view->exposed_raw_input = [];

    $exclude = array('submit', 'form_build_id', 'form_id', 'form_token', 'exposed_form_plugin', '', 'reset');
    /** @var \Drupal\views\Plugin\views\exposed_form\ExposedFormPluginBase $exposed_form_plugin */
    $exposed_form_plugin = $form_state->get('exposed_form_plugin');
    $exposed_form_plugin->exposedFormSubmit($form, $form_state, $exclude);

    foreach ($form_state->getValues() as $key => $value) {
      if (!in_array($key, $exclude)) {
        $view->exposed_raw_input[$key] = $value;
      }
    }
  }

}
