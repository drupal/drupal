<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\views\field\BulkForm.
 */

namespace Drupal\system\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\Plugin\views\field\UncacheableFieldHandlerTrait;
use Drupal\views\Plugin\views\style\Table;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a actions-based bulk operation form element.
 *
 * @ViewsField("bulk_form")
 */
class BulkForm extends FieldPluginBase {

  use RedirectDestinationTrait;
  use UncacheableFieldHandlerTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The action storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $actionStorage;

  /**
   * An array of actions that can be executed.
   *
   * @var \Drupal\system\ActionConfigEntityInterface[]
   */
  protected $actions = array();

  /**
   * Constructs a new BulkForm object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->entityManager = $entity_manager;
    $this->actionStorage = $entity_manager->getStorage('action');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('entity.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $entity_type = $this->getEntityType();
    // Filter the actions to only include those for this entity type.
    $this->actions = array_filter($this->actionStorage->loadMultiple(), function ($action) use ($entity_type) {
      return $action->getType() == $entity_type;
    });
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['action_title'] = array('default' => $this->t('With selection'));
    $options['include_exclude'] = array(
      'default' => 'exclude',
    );
    $options['selected_actions'] = array(
      'default' => array(),
    );
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['action_title'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Action title'),
      '#default_value' => $this->options['action_title'],
      '#description' => $this->t('The title shown above the actions dropdown.'),
    );

    $form['include_exclude'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Available actions'),
      '#options' => array(
        'exclude' => $this->t('All actions, except selected'),
        'include' => $this->t('Only selected actions'),
      ),
      '#default_value' => $this->options['include_exclude'],
    );
    $form['selected_actions'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Selected actions'),
      '#options' => $this->getBulkOptions(FALSE),
      '#default_value' => $this->options['selected_actions'],
    );

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateOptionsForm(&$form, FormStateInterface $form_state) {
    parent::validateOptionsForm($form, $form_state);

    $selected_actions = $form_state->getValue(array('options', 'selected_actions'));
    $form_state->setValue(array('options', 'selected_actions'), array_values(array_filter($selected_actions)));
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
   * {@inheritdoc}
   */
  public function getValue(ResultRow $row, $field = NULL) {
    return '<!--form-item-' . $this->options['id'] . '--' . $row->index . '-->';
  }

  /**
   * Form constructor for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function viewsForm(&$form, FormStateInterface $form_state) {
    // Add the tableselect javascript.
    $form['#attached']['library'][] = 'core/drupal.tableselect';

    // Only add the bulk form options and buttons if there are results.
    if (!empty($this->view->result)) {
      // Render checkboxes for all rows.
      $form[$this->options['id']]['#tree'] = TRUE;
      foreach ($this->view->result as $row_index => $row) {
        $entity = $this->getEntity($row);

        $form[$this->options['id']][$row_index] = array(
          '#type' => 'checkbox',
          // We are not able to determine a main "title" for each row, so we can
          // only output a generic label.
          '#title' => $this->t('Update this item'),
          '#title_display' => 'invisible',
          '#default_value' => !empty($form_state->getValue($this->options['id'])[$row_index]) ? 1 : NULL,
          '#return_value' => $this->calculateEntityBulkFormKey($entity),
        );
      }

      // Replace the form submit button label.
      $form['actions']['submit']['#value'] = $this->t('Apply');

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
        '#title' => $this->options['action_title'],
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
   * @param bool $filtered
   *   (optional) Whether to filter actions to selected actions.
   * @return array
   *   An associative array of operations, suitable for a select element.
   */
  protected function getBulkOptions($filtered = TRUE) {
    $options = array();
    // Filter the action list.
    foreach ($this->actions as $id => $action) {
      if ($filtered) {
        $in_selected = in_array($id, $this->options['selected_actions']);
        // If the field is configured to include only the selected actions,
        // skip actions that were not selected.
        if (($this->options['include_exclude'] == 'include') && !$in_selected) {
          continue;
        }
        // Otherwise, if the field is configured to exclude the selected
        // actions, skip actions that were selected.
        elseif (($this->options['include_exclude'] == 'exclude') && $in_selected) {
          continue;
        }
      }

      $options[$id] = $action->label();
    }

    return $options;
  }

  /**
   * Submit handler for the bulk form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user tried to access an action without access to it.
   */
  public function viewsFormSubmit(&$form, FormStateInterface $form_state) {
    if ($form_state->get('step') == 'views_form_views_form') {
      // Filter only selected checkboxes.
      $selected = array_filter($form_state->getValue($this->options['id']));
      $entities = array();
      $action = $this->actions[$form_state->getValue('action')];
      $count = 0;

      foreach ($selected as $bulk_form_key) {
        $entity = $this->loadEntityFromBulkFormKey($bulk_form_key);

        // Skip execution if the user did not have access.
        if (!$action->getPlugin()->access($entity, $this->view->getUser())) {
          $this->drupalSetMessage($this->t('No access to execute %action on the @entity_type_label %entity_label.', [
            '%action' => $action->label(),
            '@entity_type_label' => $entity->getEntityType()->getLabel(),
            '%entity_label' => $entity->label()
          ]), 'error');
          continue;
        }

        $count++;

        $entities[$entity->id()] = $entity;
      }

      $action->execute($entities);

      $operation_definition = $action->getPluginDefinition();
      if (!empty($operation_definition['confirm_form_route_name'])) {
        $options = array(
          'query' => $this->getDestinationArray(),
        );
        $form_state->setRedirect($operation_definition['confirm_form_route_name'], array(), $options);
      }
      else {
        // Don't display the message unless there are some elements affected and
        // there is no confirmation form.
        $count = count(array_filter($form_state->getValue($this->options['id'])));
        if ($count) {
          drupal_set_message($this->formatPlural($count, '%action was applied to @count item.', '%action was applied to @count items.', array(
            '%action' => $action->label(),
          )));
        }
      }
    }
  }

  /**
   * Returns the message to be displayed when there are no selected items.
   *
   * @return string
   *  Message displayed when no items are selected.
   */
  protected function emptySelectedMessage() {
    return $this->t('No items selected.');
  }

  /**
   * {@inheritdoc}
   */
  public function viewsFormValidate(&$form, FormStateInterface $form_state) {
    $selected = array_filter($form_state->getValue($this->options['id']));
    if (empty($selected)) {
      $form_state->setErrorByName('', $this->emptySelectedMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
  }

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

  /**
   * Wraps drupal_set_message().
   */
  protected function drupalSetMessage($message = NULL, $type = 'status', $repeat = FALSE) {
    drupal_set_message($message, $type, $repeat);
  }

  /**
   * Calculates a bulk form key.
   *
   * This generates a key that is used as the checkbox return value when
   * submitting a bulk form. This key allows the entity for the row to be loaded
   * totally independently of the executed view row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to calculate a bulk form key for.
   *
   * @return string
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @see self::loadEntityFromBulkFormKey()
   */
  protected function calculateEntityBulkFormKey(EntityInterface $entity) {
    $key_parts = [$entity->language()->getId(), $entity->id()];

    if ($entity instanceof RevisionableInterface) {
      $key_parts[] = $entity->getRevisionId();
    }

    return implode('-', $key_parts);
  }

  /**
   * Loads an entity based on a bulk form key.
   *
   * @param string $bulk_form_key
   *   The bulk form key representing the entity's id, language and revision (if
   *   applicable) as one string.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity loaded in the state (language, optionally revision) specified
   *   as part of the bulk form key.
   */
  protected function loadEntityFromBulkFormKey($bulk_form_key) {
    $key_parts = explode('-', $bulk_form_key);
    $vid = NULL;

    // If there are 3 items, vid will be last.
    if (count($key_parts) === 3) {
      $vid = array_pop($key_parts);
    }

    // The first two items will always be langcode and ID.
    $id = array_pop($key_parts);
    $langcode = array_pop($key_parts);

    if ($vid) {
      $entity = $this->entityManager->getStorage($this->getEntityType())->loadRevision($vid);
    }
    else {
      $entity = $this->entityManager->getStorage($this->getEntityType())->load($id);
    }

    if ($entity instanceof TranslatableInterface) {
      $entity = $entity->getTranslation($langcode);
    }

    return $entity;
  }

}
