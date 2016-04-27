<?php

namespace Drupal\views_ui\Form\Ajax;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\ViewExecutable;

/**
 * Provides a rearrange form for Views filters.
 */
class RearrangeFilter extends ViewsFormBase {

  /**
   * Constructs a new RearrangeFilter object.
   */
  public function __construct($type = NULL) {
    $this->setType($type);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormKey() {
    return 'rearrange-filter';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_ui_rearrange_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $view = $form_state->get('view');
    $display_id = $form_state->get('display_id');
    $type = 'filter';

    $types = ViewExecutable::getHandlerTypes();
    $executable = $view->getExecutable();
    if (!$executable->setDisplay($display_id)) {
      $form['markup'] = array('#markup' => $this->t('Invalid display id @display', array('@display' => $display_id)));
      return $form;
    }
    $display = $executable->displayHandlers->get($display_id);
    $form['#title'] = Html::escape($display->display['display_title']) . ': ';
    $form['#title'] .= $this->t('Rearrange @type', array('@type' => $types[$type]['ltitle']));
    $form['#section'] = $display_id . 'rearrange-item';

    if ($display->defaultableSections($types[$type]['plural'])) {
      $section = $types[$type]['plural'];
      $form_state->set('section', $section);
      views_ui_standard_display_dropdown($form, $form_state, $section);
    }

    if (!empty($view->form_cache)) {
      $groups = $view->form_cache['groups'];
      $handlers = $view->form_cache['handlers'];
    }
    else {
      $groups = $display->getOption('filter_groups');
      $handlers = $display->getOption($types[$type]['plural']);
    }
    $count = 0;

    // Get relationship labels
    $relationships = array();
    foreach ($display->getHandlers('relationship') as $id => $handler) {
      $relationships[$id] = $handler->adminLabel();
    }

    $group_options = array();

    /**
     * Filter groups is an array that contains:
     * array(
     *   'operator' => 'and' || 'or',
     *   'groups' => array(
     *     $group_id => 'and' || 'or',
     *   ),
     * );
     */

    $grouping = count(array_keys($groups['groups'])) > 1;

    $form['filter_groups']['#tree'] = TRUE;
    $form['filter_groups']['operator'] = array(
      '#type' => 'select',
      '#options' => array(
        'AND' => $this->t('And'),
        'OR' => $this->t('Or'),
      ),
      '#default_value' => $groups['operator'],
      '#attributes' => array(
        'class' => array('warning-on-change'),
      ),
      '#title' => $this->t('Operator to use on all groups'),
      '#description' => $this->t('Either "group 0 AND group 1 AND group 2" or "group 0 OR group 1 OR group 2", etc'),
      '#access' => $grouping,
    );

    $form['remove_groups']['#tree'] = TRUE;

    foreach ($groups['groups'] as $id => $group) {
      $form['filter_groups']['groups'][$id] = array(
        '#title' => $this->t('Operator'),
        '#type' => 'select',
        '#options' => array(
          'AND' => $this->t('And'),
          'OR' => $this->t('Or'),
        ),
        '#default_value' => $group,
        '#attributes' => array(
          'class' => array('warning-on-change'),
        ),
      );

      $form['remove_groups'][$id] = array(); // to prevent a notice
      if ($id != 1) {
        $form['remove_groups'][$id] = array(
          '#type' => 'submit',
          '#value' => $this->t('Remove group @group', array('@group' => $id)),
          '#id' => "views-remove-group-$id",
          '#attributes' => array(
            'class' => array('views-remove-group'),
          ),
          '#group' => $id,
        );
      }
      $group_options[$id] = $id == 1 ? $this->t('Default group') : $this->t('Group @group', array('@group' => $id));
      $form['#group_renders'][$id] = array();
    }

    $form['#group_options'] = $group_options;
    $form['#groups'] = $groups;
    // We don't use getHandlers() because we want items without handlers to
    // appear and show up as 'broken' so that the user can see them.
    $form['filters'] = array('#tree' => TRUE);
    foreach ($handlers as $id => $field) {
      // If the group does not exist, move the filters to the default group.
      if (empty($field['group']) || empty($groups['groups'][$field['group']])) {
        $field['group'] = 1;
      }

      $handler = $display->getHandler($type, $id);
      if ($grouping && $handler && !$handler->canGroup()) {
        $field['group'] = 'ungroupable';
      }

      // If not grouping and the handler is set ungroupable, move it back to
      // the default group to prevent weird errors from having it be in its
      // own group:
      if (!$grouping && $field['group'] == 'ungroupable') {
        $field['group'] = 1;
      }

      // Place this item into the proper group for rendering.
      $form['#group_renders'][$field['group']][] = $id;

      $form['filters'][$id]['weight'] = array(
        '#title' => t('Weight for @id', array('@id' => $id)),
        '#title_display' => 'invisible',
        '#type' => 'textfield',
        '#default_value' => ++$count,
        '#size' => 8,
      );
      $form['filters'][$id]['group'] = array(
        '#title' => t('Group for @id', array('@id' => $id)),
        '#title_display' => 'invisible',
        '#type' => 'select',
        '#options' => $group_options,
        '#default_value' => $field['group'],
        '#attributes' => array(
          'class' => array('views-region-select', 'views-region-' . $id),
        ),
        '#access' => $field['group'] !== 'ungroupable',
      );

      if ($handler) {
        $name = $handler->adminLabel() . ' ' . $handler->adminSummary();
        if (!empty($field['relationship']) && !empty($relationships[$field['relationship']])) {
          $name = '(' . $relationships[$field['relationship']] . ') ' . $name;
        }

        $form['filters'][$id]['name'] = array(
          '#markup' => $name,
        );
      }
      else {
        $form['filters'][$id]['name'] = array('#markup' => $this->t('Broken field @id', array('@id' => $id)));
      }
      $form['filters'][$id]['removed'] = array(
        '#title' => t('Remove @id', array('@id' => $id)),
        '#title_display' => 'invisible',
        '#type' => 'checkbox',
        '#id' => 'views-removed-' . $id,
        '#attributes' => array('class' => array('views-remove-checkbox')),
        '#default_value' => 0,
      );
    }

    $view->getStandardButtons($form, $form_state, 'views_ui_rearrange_filter_form');
    $form['actions']['add_group'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Create new filter group'),
      '#id' => 'views-add-group',
      '#group' => 'add',
      '#attributes' => array(
        'class' => array('views-add-group'),
      ),
      '#ajax' => ['url' => NULL],
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $types = ViewExecutable::getHandlerTypes();
    $view = $form_state->get('view');
    $display = &$view->getExecutable()->displayHandlers->get($form_state->get('display_id'));
    $remember_groups = array();

    if (!empty($view->form_cache)) {
      $old_fields = $view->form_cache['handlers'];
    }
    else {
      $old_fields = $display->getOption($types['filter']['plural']);
    }

    $groups = $form_state->getValue('filter_groups');
    // Whatever button was clicked, re-calculate field information.
    $new_fields = $order = array();

    // Make an array with the weights
    foreach ($form_state->getValue('filters') as $field => $info) {
      // add each value that is a field with a weight to our list, but only if
      // it has had its 'removed' checkbox checked.
      if (is_array($info) && empty($info['removed'])) {
        if (isset($info['weight'])) {
          $order[$field] = $info['weight'];
        }

        if (isset($info['group'])) {
          $old_fields[$field]['group'] = $info['group'];
          $remember_groups[$info['group']][] = $field;
        }
      }
    }

    // Sort the array
    asort($order);

    // Create a new list of fields in the new order.
    foreach (array_keys($order) as $field) {
      $new_fields[$field] = $old_fields[$field];
    }

    // If the #group property is set on the clicked button, that means we are
    // either adding or removing a group, not actually updating the filters.
    $triggering_element = $form_state->getTriggeringElement();
    if (!empty($triggering_element['#group'])) {
      if ($triggering_element['#group'] == 'add') {
        // Add a new group
        $groups['groups'][] = 'AND';
      }
      else {
        // Renumber groups above the removed one down.
        foreach (array_keys($groups['groups']) as $group_id) {
          if ($group_id >= $triggering_element['#group']) {
            $old_group = $group_id + 1;
            if (isset($groups['groups'][$old_group])) {
              $groups['groups'][$group_id] = $groups['groups'][$old_group];
              if (isset($remember_groups[$old_group])) {
                foreach ($remember_groups[$old_group] as $id) {
                  $new_fields[$id]['group'] = $group_id;
                }
              }
            }
            else {
              // If this is the last one, just unset it.
              unset($groups['groups'][$group_id]);
            }
          }
        }
      }
      // Update our cache with values so that cancel still works the way
      // people expect.
      $view->form_cache = [
        'key' => 'rearrange-filter',
        'groups' => $groups,
        'handlers' => $new_fields,
      ];

      // Return to this form except on actual Update.
      $view->addFormToStack('rearrange-filter', $form_state->get('display_id'), 'filter');
    }
    else {
      // The actual update button was clicked. Remove the empty groups, and
      // renumber them sequentially.
      ksort($remember_groups);
      $groups['groups'] = static::arrayKeyPlus(array_values(array_intersect_key($groups['groups'], $remember_groups)));
      // Change the 'group' key on each field to match. Here, $mapping is an
      // array whose keys are the old group numbers and whose values are the new
      // (sequentially numbered) ones.
      $mapping = array_flip(static::arrayKeyPlus(array_keys($remember_groups)));
      foreach ($new_fields as &$new_field) {
        $new_field['group'] = $mapping[$new_field['group']];
      }

      // Write the changed handler values.
      $display->setOption($types['filter']['plural'], $new_fields);
      $display->setOption('filter_groups', $groups);
      if (isset($view->form_cache)) {
        unset($view->form_cache);
      }
    }

    // Store in cache.
    $view->cacheSet();
  }

  /**
   * Adds one to each key of an array.
   *
   * For example array(0 => 'foo') would be array(1 => 'foo').
   *
   * @param array
   *   The array to increment keys on.
   *
   * @return array
   *   The array with incremented keys.
   */
  public static function arrayKeyPlus($array) {
    $keys = array_keys($array);
    // Sort the keys in reverse order so incrementing them doesn't overwrite any
    // existing keys.
    rsort($keys);
    foreach ($keys as $key) {
      $array[$key + 1] = $array[$key];
      unset($array[$key]);
    }
    // Sort the keys back to ascending order.
    ksort($array);
    return $array;
  }

}
