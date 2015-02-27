<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ConfigHandlerExtra.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewEntityInterface;
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
  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL, $id = NULL) {
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');

    $form = array(
      'options' => array(
        '#tree' => true,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
      ),
    );
    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      $form['markup'] = array('#markup' => $this->t('Invalid display id @display', array('@display' => $display_id)));
      return $form;
    }
    $item = $executable->getHandler($display_id, $type, $id);

    if ($item) {
      $handler = $executable->display_handler->getHandler($type, $id);
      if (empty($handler)) {
        $form['markup'] = array('#markup' => $this->t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
      }
      else {
        $handler->init($executable, $executable->display_handler, $item);
        $types = ViewExecutable::getHandlerTypes();

        $form['#title'] = $this->t('Configure extra settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()));

        $form['#section'] = $display_id . '-' . $type . '-' . $id;

        // Get form from the handler.
        $handler->buildExtraOptionsForm($form['options'], $form_state);
        $form_state->set('handler', $handler);
      }

      $view->getStandardButtons($form, $form_state, 'views_ui_config_item_extra_form');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->get('handler')->validateExtraOptionsForm($form['options'], $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $handler = $form_state->get('handler');
    // Run it through the handler's submit function.
    $handler->submitExtraOptionsForm($form['options'], $form_state);
    $item = $handler->options;

    // Store the data we're given.
    foreach ($form_state->getValue('options') as $key => $value) {
      $item[$key] = $value;
    }

    // Store the item back on the view
    $view->getExecutable()->setHandler($form_state->get('display_id'), $form_state->get('type'), $form_state->get('id'), $item);

    // Write to cache
    $view->cacheSet();
  }

}
