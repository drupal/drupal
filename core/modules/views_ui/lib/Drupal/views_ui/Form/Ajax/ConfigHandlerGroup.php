<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\ConfigHandlerGroup.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\Views;
use Drupal\views\ViewStorageInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a form for configuring grouping information for a Views UI handler.
 */
class ConfigHandlerGroup extends ViewsFormBase {

  /**
   * Constucts a new ConfigHandlerGroup object.
   */
  public function __construct($type = NULL, $id = NULL) {
    $this->setType($type);
    $this->setID($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'handler-group';
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
    return 'views_ui_config_item_group_form';
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
        '#tree' => TRUE,
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
      ),
    );
    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      views_ajax_render($this->t('Invalid display id @display', array('@display' => $display_id)));
    }

    $executable->initQuery();

    $item = $executable->getHandler($display_id, $type, $id);

    if ($item) {
      $handler = $executable->display_handler->getHandler($type, $id);
      if (empty($handler)) {
        $form['markup'] = array('#markup' => $this->t("Error: handler for @table > @field doesn't exist!", array('@table' => $item['table'], '@field' => $item['field'])));
      }
      else {
        $handler->init($executable, $executable->display_handler, $item);
        $types = ViewExecutable::viewsHandlerTypes();

        $form['#title'] = $this->t('Configure aggregation settings for @type %item', array('@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()));

        $handler->buildGroupByForm($form['options'], $form_state);
        $form_state['handler'] = $handler;
      }

      $view->getStandardButtons($form, $form_state, 'views_ui_config_item_group_form');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $item = &$form_state['handler']->options;
    $type = $form_state['type'];

    $handler = Views::handlerManager($type)->getHandler($item);
    $executable = $form_state['view']->getExecutable();
    $handler->init($executable, $executable->display_handler, $item);

    $handler->submitGroupByForm($form, $form_state);

    // Store the item back on the view
    $executable->setHandler($form_state['display_id'], $form_state['type'], $form_state['id'], $item);

    // Write to cache
    $form_state['view']->cacheSet();
  }

}
