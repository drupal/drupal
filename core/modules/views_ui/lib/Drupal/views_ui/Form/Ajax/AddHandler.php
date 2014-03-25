<?php

/**
 * @file
 * Contains \Drupal\views_ui\Form\Ajax\AddHandler.
 */

namespace Drupal\views_ui\Form\Ajax;

use Drupal\views\ViewExecutable;
use Drupal\views\ViewStorageInterface;
use Drupal\views\Views;

/**
 * Provides a form for adding an item in the Views UI.
 */
class AddHandler extends ViewsFormBase {

  /**
   * Constucts a new AddHandler object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'add-handler';
  }

  /**
   * {@inheritdoc}
   */
  public function getForm(ViewStorageInterface $view, $display_id, $js, $type = NULL) {
    $this->setType($type);
    return parent::getForm($view, $display_id, $js);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_add_handler_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $view = $form_state['view'];
    $display_id = $form_state['display_id'];
    $type = $form_state['type'];

    $form = array(
      'options' => array(
        '#theme_wrappers' => array('container'),
        '#attributes' => array('class' => array('scroll'), 'data-drupal-views-scroll' => TRUE),
      ),
    );

    $executable = $view->getExecutable();
    $executable->setDisplay($display_id);
    $display = &$executable->displayHandlers->get($display_id);

    $types = ViewExecutable::getHandlerTypes();
    $ltitle = $types[$type]['ltitle'];
    $section = $types[$type]['plural'];

    if (!empty($types[$type]['type'])) {
      $type = $types[$type]['type'];
    }

    $form['#title'] = $this->t('Add @type', array('@type' => $ltitle));
    $form['#section'] = $display_id . 'add-handler';

    // Add the display override dropdown.
    views_ui_standard_display_dropdown($form, $form_state, $section);

    // Figure out all the base tables allowed based upon what the relationships provide.
    $base_tables = $executable->getBaseTables();
    $options = Views::viewsDataHelper()->fetchFields(array_keys($base_tables), $type, $display->useGroupBy(), $form_state['type']);

    if (!empty($options)) {
      $form['override']['controls'] = array(
        '#theme_wrappers' => array('container'),
        '#id' => 'views-filterable-options-controls',
        '#attributes' => array('class' => array('container-inline')),
      );
      $form['override']['controls']['options_search'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Search'),
      );

      $groups = array('all' => $this->t('- All -'));
      $form['override']['controls']['group'] = array(
        '#type' => 'select',
        '#title' => $this->t('Type'),
        '#options' => array(),
      );

      $form['options']['name'] = array(
        '#prefix' => '<div class="views-radio-box form-checkboxes views-filterable-options">',
        '#suffix' => '</div>',
        '#tree' => TRUE,
        '#default_value' => 'all',
      );

      // Group options first to simplify the usage of #states.
      $grouped_options = array();
      foreach ($options as $key => $option) {
        $group = preg_replace('/[^a-z0-9]/', '-', strtolower($option['group']));
        $groups[$group] = $option['group'];
        $grouped_options[$group][$key] = $option;
        if (!empty($option['aliases']) && is_array($option['aliases'])) {
          foreach ($option['aliases'] as $id => $alias) {
            if (empty($alias['base']) || !empty($base_tables[$alias['base']])) {
              $copy = $option;
              $copy['group'] = $alias['group'];
              $copy['title'] = $alias['title'];
              if (isset($alias['help'])) {
                $copy['help'] = $alias['help'];
              }

              $group = preg_replace('/[^a-z0-9]/', '-', strtolower($copy['group']));
              $groups[$group] = $copy['group'];
              $grouped_options[$group][$key . '$' . $id] = $copy;
            }
          }
        }
      }

      foreach ($grouped_options as $group => $group_options) {
        foreach ($group_options as $key => $option) {
          $form['options']['name'][$key] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('!group: !field', array('!group' => $option['group'], '!field' => $option['title'])),
            '#description' => $option['help'],
            '#return_value' => $key,
            '#prefix' => "<div class='filterable-option'>",
            '#suffix' => '</div>',
            '#states' => array(
              'visible' => array(
                array(
                  ':input[name="override[controls][group]"]' => array('value' => 'all'),
                ),
                array(
                  ':input[name="override[controls][group]"]' => array('value' => $group),
                ),
              )
            )
          );
        }
      }

      $form['override']['controls']['group']['#options'] = $groups;
    }
    else {
      $form['options']['markup'] = array(
        '#markup' => '<div class="form-item">' . $this->t('There are no @types available to add.', array('@types' =>  $ltitle)) . '</div>',
      );
    }
    // Add a div to show the selected items
    $form['selected'] = array(
      '#type' => 'item',
      '#markup' => '<span class="views-ui-view-title">' . $this->t('Selected:') . '</span> ' . '<div class="views-selected-options"></div>',
      '#theme_wrappers' => array('form_element', 'views_ui_container'),
      '#attributes' => array(
        'class' => array('container-inline', 'views-add-form-selected'),
        'data-drupal-views-offset' => 'bottom',
      ),
    );
    $view->getStandardButtons($form, $form_state, 'views_ui_add_handler_form', $this->t('Add and configure @types', array('@types' => $ltitle)));

    // Remove the default submit function.
    $form['actions']['submit']['#submit'] = array_filter($form['actions']['submit']['#submit'], function($var) {
      return !(is_array($var) && isset($var[1]) && $var[1] == 'standardSubmit');
    });
    $form['actions']['submit']['#submit'][] = array($view, 'submitItemAdd');

    return $form;
  }

}
