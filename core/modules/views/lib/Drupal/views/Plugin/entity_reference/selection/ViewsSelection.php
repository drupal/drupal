<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\entity_reference\selection\ViewsSelection.
 */

namespace Drupal\views\Plugin\entity_reference\selection;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface;

/**
 * Plugin implementation of the 'selection' entity_reference.
 *
 * @Plugin(
 *   id = "views",
 *   module = "views",
 *   label = @Translation("Views: Filter by an entity reference view"),
 *   group = "views",
 *   weight = 0
 * )
 */
class ViewsSelection implements SelectionInterface {

  /**
   * The loaded View object.
   *
   * @var \Drupal\views\ViewExecutable;
   */
  protected $view;

  /**
   * Constructs a View selection handler.
   */
  public function __construct($field, $instance = NULL, EntityInterface $entity = NULL) {
    $this->field = $field;
    $this->instance = $instance;
    $this->entity = $entity;
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::settingsForm().
   */
  public static function settingsForm(&$field, &$instance) {
    $view_settings = empty($instance['settings']['handler_settings']['view']) ? array() : $instance['settings']['handler_settings']['view'];
    $displays = views_get_applicable_views('entity_reference_display');
    // Filter views that list the entity type we want, and group the separate
    // displays by view.
    $entity_info = entity_get_info($field['settings']['target_type']);
    $options = array();
    foreach ($displays as $data) {
      list($view, $display_id) = $data;
      if ($view->storage->get('base_table') == $entity_info['base_table']) {
        $name = $view->storage->get('id');
        $display = $view->storage->get('display');
        $options[$name . ':' . $display_id] = $name . ' - ' . $display[$display_id]['display_title'];
      }
    }

    // The value of the 'view_and_display' select below will need to be split
    // into 'view_name' and 'view_display' in the final submitted values, so
    // we massage the data at validate time on the wrapping element (not
    // ideal).
    $plugin = new static($field, $instance);
    $form['view']['#element_validate'] = array(array($plugin, 'settingsFormValidate'));

    if ($options) {
      $default = !empty($view_settings['view_name']) ? $view_settings['view_name'] . ':' . $view_settings['display_name'] : NULL;
      $form['view']['view_and_display'] = array(
        '#type' => 'select',
        '#title' => t('View used to select the entities'),
        '#required' => TRUE,
        '#options' => $options,
        '#default_value' => $default,
        '#description' => '<p>' . t('Choose the view and display that select the entities that can be referenced.<br />Only views with a display of type "Entity Reference" are eligible.') . '</p>',
      );

      $default = !empty($view_settings['arguments']) ? implode(', ', $view_settings['arguments']) : '';
      $form['view']['arguments'] = array(
        '#type' => 'textfield',
        '#title' => t('View arguments'),
        '#default_value' => $default,
        '#required' => FALSE,
        '#description' => t('Provide a comma separated list of arguments to pass to the view.'),
      );
    }
    else {
      $form['view']['no_view_help'] = array(
        '#markup' => '<p>' . t('No eligible views were found. <a href="@create">Create a view</a> with an <em>Entity Reference</em> display, or add such a display to an <a href="@existing">existing view</a>.', array(
          '@create' => url('admin/structure/views/add'),
          '@existing' => url('admin/structure/views'),
        )) . '</p>',
      );
    }
    return $form;
  }

  /**
   * Initializes a view.
   *
   * @param string|null $match
   *   (Optional) Text to match the label against. Defaults to NULL.
   * @param string $match_operator
   *   (Optional) The operation the matching should be done with. Defaults
   *   to "CONTAINS".
   * @param int $limit
   *   Limit the query to a given number of items. Defaults to 0, which
   *   indicates no limiting.
   * @param array|null $ids
   *   Array of entity IDs. Defaults to NULL.
   *
   * @return bool
   *   Return TRUE if the view was initialized, FALSE otherwise.
   */
  protected function initializeView($match = NULL, $match_operator = 'CONTAINS', $limit = 0, $ids = NULL) {
    $view_name = $this->instance['settings']['handler_settings']['view']['view_name'];
    $display_name = $this->instance['settings']['handler_settings']['view']['display_name'];

    // Check that the view is valid and the display still exists.
    $this->view = views_get_view($view_name);
    if (!$this->view || !$this->view->access($display_name)) {
      drupal_set_message(t('The reference view %view_name used in the %field_name field cannot be found.', array('%view_name' => $view_name, '%field_name' => $this->instance['label'])), 'warning');
      return FALSE;
    }
    $this->view->setDisplay($display_name);

    // Pass options to the display handler to make them available later.
    $entity_reference_options = array(
      'match' => $match,
      'match_operator' => $match_operator,
      'limit' => $limit,
      'ids' => $ids,
    );
    $this->view->displayHandlers->get($display_name)->setOption('entity_reference_options', $entity_reference_options);
    return TRUE;
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::getReferencableEntities().
   */
  public function getReferencableEntities($match = NULL, $match_operator = 'CONTAINS', $limit = 0) {
    $display_name = $this->instance['settings']['handler_settings']['view']['display_name'];
    $arguments = $this->instance['settings']['handler_settings']['view']['arguments'];
    $result = array();
    if ($this->initializeView($match, $match_operator, $limit)) {
      // Get the results.
      $result = $this->view->executeDisplay($display_name, $arguments);
    }

    $return = array();
    if ($result) {
      foreach($this->view->result as $row) {
        $entity = $row->_entity;
        $return[$entity->bundle()][$entity->id()] = $entity->label();
      }
    }
    return $return;
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::countReferencableEntities().
   */
  public function countReferencableEntities($match = NULL, $match_operator = 'CONTAINS') {
    $this->getReferencableEntities($match, $match_operator);
    return $this->view->pager->getTotalItems();
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::validateReferencableEntities().
   */
  public function validateReferencableEntities(array $ids) {
    $display_name = $this->instance['settings']['handler_settings']['view']['display_name'];
    $arguments = $this->instance['settings']['handler_settings']['view']['arguments'];
    $result = array();
    if ($this->initializeView(NULL, 'CONTAINS', 0, $ids)) {
      // Get the results.
      $entities = $this->view->executeDisplay($display_name, $arguments);
      $result = array_keys($entities);
    }
    return $result;
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::validateAutocompleteInput().
   */
  public function validateAutocompleteInput($input, &$element, &$form_state, $form, $strict = TRUE) {
    return NULL;
  }

  /**
   * Implements \Drupal\entity_reference\Plugin\Type\Selection\SelectionInterface::entityQueryAlter().
   */
  public function entityQueryAlter(SelectInterface $query) {}

  /**
   * Element validate; Check View is valid.
   */
  public function settingsFormValidate($element, &$form_state, $form) {
    // Split view name and display name from the 'view_and_display' value.
    if (!empty($element['view_and_display']['#value'])) {
      list($view, $display) = explode(':', $element['view_and_display']['#value']);
    }
    else {
      form_error($element, t('The views entity selection mode requires a view.'));
      return;
    }

    // Explode the 'arguments' string into an actual array. Beware, explode()
    // turns an empty string into an array with one empty string. We'll need an
    // empty array instead.
    $arguments_string = trim($element['arguments']['#value']);
    if ($arguments_string === '') {
      $arguments = array();
    }
    else {
      // array_map() is called to trim whitespaces from the arguments.
      $arguments = array_map('trim', explode(',', $arguments_string));
    }

    $value = array('view_name' => $view, 'display_name' => $display, 'arguments' => $arguments);
    form_set_value($element, $value, $form_state);
  }
}
