<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ConfigHandlerExtra.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\ViewStorageInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a form for configuring extra information for a Views UI item.
 */
class ConfigHandlerExtra extends ViewsFormBase {

  /**
   * Constucts a new ConfigHandlerExtra object.
   */
  public function __construct($type = NULL, $id = NULL) {
    $this->setType($type);
    $this->setID($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'handler-extra';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL, $id = NULL) {
    $this->setType($type);
    $this->setID($id);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_config_item_extra_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];
    $type = $form_state['type'];
    $id = $form_state['id'];

    $form = array(
      'options' => array(
        '#tree' => true,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
      ),
    );
    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);
    $item = $executable->getHandler($display_id, $type, $id);

    if ($item) {
      $handler = $executable->display_handler->getHandler($type, $id);
      if (empty($handler)) {
        $form['markup'] = array('#markup' => $this->t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
      }
      else {
        $handler->init($executable, $executable->display_handler, $item);
        $types = ViewExecutable::viewsHandlerTypes();

        $form['#title'] = $this->t('Configure extra settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()));

        $form['#section'] = $display_id . '-' . $type . '-' . $id;

        // Get form from the handler.
        $handler->buildExtraOptionsForm($form['options'], $form_state);
        $form_state['handler'] = $handler;
      }

      $view->getStandardButtons($form, $form_state, 'views_ui_config_item_extra_form');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $form_state['handler']->validateExtraOptionsForm($form['options'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Run it through the handler's submit function.
    $form_state['handler']->submitExtraOptionsForm($form['options'], $form_state);
    $item = $form_state['handler']->options;

    // Store the data we're given.
    foreach ($form_state['values']['options'] as $key => $value) {
      $item[$key] = $value;
    }

    // Store the item back on the view
    $form_state['view']->getExecutable()->setHandler($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

    // Write to cache
    $form_state['view']->cacheSet();
  }

}
