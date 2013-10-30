<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\views\field\BulkFormBase.
 */

namespace Drupal\system\Plugin\views\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a generic bulk operation form element.
 */
abstract class BulkFormBase extends FieldPluginBase {

  /**
   * An array of actions that can be executed.
   *
   * @var array
   */
  protected $actions = array();

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->actions = $manager->getStorageController('action')->loadMultiple();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, array $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return '<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->';
  }

  /**
   * {@inheritdoc}
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function views_form(&$form, &$form_state) {
    // Add the tableselect javascript.
    $form['#attached']['library'][] = array('system', 'drupal.tableselect');

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $form[$this->options['id']][$row_index] = array(
          '#type' => 'checkbox',
          // We are not able to determine a main "title" for each row, so we can
          // only output a generic label.
          '#title' => t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state['values'][$this->options['id']][$row_index]) ? 1 : NULL,
        );
      }

      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = t('Apply');

      // Ensure a consistent container for filters/operations in the view header.
      $form['header'] = array(
        '#type' => 'container',
        '#weight' => -100,
      );

      // Build the bulk operations action widget for the header.
      // Allow themes to apply .container-inline on this separate container.
      $form['header'][$this->options['id']] = array(
        '#type' => 'container',
      );
      $form['header'][$this->options['id']]['action'] = array(
        '#type' => 'select',
        '#title' => t('With selection'),
        '#options' => $this->getBulkOptions(),
      );

      // Duplicate the form actions into the action container in the header.
      $form['header'][$this->options['id']]['actions'] = $form['actions'];
    }
    else {
      // Remove the default actions build array.
      unset($form['actions']);
    }
  }

  /**
   * Returns the available operations for this form.
   *
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions() {
    return array_map(function ($action) {
      return $action->label();
    }, $this->actions);
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param array $form_state
   *   An associative array containing the current state of the form.
   */
  public function views_form_submit(&$form, &$form_state) {
    if ($form_state['step'] == 'views_form_views_form') {
      // Filter only selected checkboxes.
      $selected = array_filter($form_state['values'][$this->options['id']]);
      $entities = array();
      foreach (array_intersect_key($this->view->result, $selected) as $row) {
        $entity = $this->getEntity($row);
        $entities[$entity->id()] = $entity;
      }

      $action = $this->actions[$form_state['values']['action']];
      $action->execute($entities);

      $operation_definition = $action->getPluginDefinition();
      if (!empty($operation_definition['confirm_form_path'])) {
        $form_state['redirect'] = $operation_definition['confirm_form_path'];
      }
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::query().
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}
