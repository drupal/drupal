<?php

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a form for configuring grouping information for a Views UI handler.
 *
 * @internal
 */
class ConfigHandlerGroup extends ViewsFormBase {

  /**
   * Constructs a new ConfigHandlerGroup object.
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
  public function getForm(ViewEntityInterface $view, $display_id, $js, $type = NULL, $id = NULL) {
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = $form_state->get('type');
    $id = $form_state->get('id');

    $form = [
      'options' => [
        '#tree' => TRUE,
        '#theme_wrappers' => ['container'],
        '#attributes' => ['class' => ['scroll'], 'data-drupal-views-scroll' => TRUE],
      ],
    ];
    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      $form['markup'] = ['#markup' => $this->t('Invalid display id @display', ['@display' => $display_id])];
      return $form;
    }

    $executable->initQuery();

    $item = $executable->getHandler($display_id, $type, $id);

    if ($item) {
      $handler = $executable->display_handler->getHandler($type, $id);
      if (empty($handler)) {
        $form['markup'] = ['#markup' => $this->t("Error: handler for @table > @field doesn't exist!", ['@table' => $item['table'], '@field' => $item['field']])];
      }
      else {
        $handler->init($executable, $executable->display_handler, $item);
        $types = ViewExecutable::getHandlerTypes();

        $form['#title'] = $this->t('Configure aggregation settings for @type %item', ['@type' => $types[$type]['lstitle'], '%item' => $handler->adminLabel()]);

        $handler->buildGroupByForm($form['options'], $form_state);
        $form_state->set('handler', $handler);
      }

      $view->getStandardButtons($form, $form_state, 'views_ui_config_item_group_form');
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $item = &$form_state->get('handler')->options;
    $type = $form_state->get('type');

    $handler = Views::handlerManager($type)->getHandler($item);
    $executable = $view->getExecutable();
    $handler->init($executable, $executable->display_handler, $item);

    $handler->submitGroupByForm($form, $form_state);

    // Store the item back on the view
    $executable->setHandler($form_state->get('display_id'), $form_state->get('type'), $form_state->get('id'), $item);

    // Write to cache
    $view->cacheSet();
  }

}
