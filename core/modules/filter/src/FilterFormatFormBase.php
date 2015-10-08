<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatFormBase.
 */

namespace Drupal\filter;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Form\FormStateInterface;
use Drupal\filter\Plugin\Filter\FilterNull;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form for a filter format.
 */
abstract class FilterFormatFormBase extends EntityForm {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * Constructs a new FilterFormatFormBase.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(QueryFactory $query_factory) {
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $format = $this->entity;
    $is_fallback = ($format->id() == $this->config('filter.settings')->get('fallback_format'));

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'filter/drupal.filter.admin';

    $form['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $format->label(),
      '#required' => TRUE,
      '#weight' => -30,
    );
    $form['format'] = array(
      '#type' => 'machine_name',
      '#required' => TRUE,
      '#default_value' => $format->id(),
      '#maxlength' => 255,
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
        'source' => array('name'),
      ),
      '#disabled' => !$format->isNew(),
      '#weight' => -20,
    );

    // Add user role access selection.
    $form['roles'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#options' => array_map('\Drupal\Component\Utility\Html::escape', user_role_names()),
      '#disabled' => $is_fallback,
      '#weight' => -10,
    );
    if ($is_fallback) {
      $form['roles']['#description'] = $this->t('All roles for this text format must be enabled and cannot be changed.');
    }
    if (!$format->isNew()) {
      // If editing an existing text format, pre-select its current permissions.
      $form['roles']['#default_value'] = array_keys(filter_get_roles_by_format($format));
    }

    // Create filter plugin instances for all available filters, including both
    // enabled/configured ones as well as new and not yet unconfigured ones.
    $filters = $format->filters();
    foreach ($filters as $filter_id => $filter) {
      // When a filter is missing, it is replaced by the null filter. Remove it
      // here, so that saving the form will remove the missing filter.
      if ($filter instanceof FilterNull) {
        drupal_set_message($this->t('The %filter filter is missing, and will be removed once this format is saved.', array('%filter' => $filter_id)), 'warning');
        $filters->removeInstanceID($filter_id);
      }
    }

    // Filter status.
    $form['filters']['status'] = array(
      '#type' => 'item',
      '#title' => $this->t('Enabled filters'),
      '#prefix' => '<div id="filters-status-wrapper">',
      '#suffix' => '</div>',
      // This item is used as a pure wrapping container with heading. Ignore its
      // value, since 'filters' should only contain filter definitions.
      // See https://www.drupal.org/node/1829202.
      '#input' => FALSE,
    );
    // Filter order (tabledrag).
    $form['filters']['order'] = array(
      '#type' => 'table',
      // For filter.admin.js
      '#attributes' => array('id' => 'filter-order'),
      '#title' => $this->t('Filter processing order'),
      '#tabledrag' => array(
        array(
         'action' => 'order',
         'relationship' => 'sibling',
         'group' => 'filter-order-weight',
        ),
      ),
      '#tree' => FALSE,
      '#input' => FALSE,
      '#theme_wrappers' => array('form_element'),
    );
    // Filter settings.
    $form['filter_settings'] = array(
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Filter settings'),
    );

    foreach ($filters as $name => $filter) {
      $form['filters']['status'][$name] = array(
        '#type' => 'checkbox',
        '#title' => $filter->getLabel(),
        '#default_value' => $filter->status,
        '#parents' => array('filters', $name, 'status'),
        '#description' => $filter->getDescription(),
        '#weight' => $filter->weight,
      );

      $form['filters']['order'][$name]['#attributes']['class'][] = 'draggable';
      $form['filters']['order'][$name]['#weight'] = $filter->weight;
      $form['filters']['order'][$name]['filter'] = array(
        '#markup' => $filter->getLabel(),
      );
      $form['filters']['order'][$name]['weight'] = array(
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', array('@title' => $filter->getLabel())),
        '#title_display' => 'invisible',
        '#delta' => 50,
        '#default_value' => $filter->weight,
        '#parents' => array('filters', $name, 'weight'),
        '#attributes' => array('class' => array('filter-order-weight')),
      );

      // Retrieve the settings form of the filter plugin. The plugin should not be
      // aware of the text format. Therefore, it only receives a set of minimal
      // base properties to allow advanced implementations to work.
      $settings_form = array(
        '#parents' => array('filters', $name, 'settings'),
        '#tree' => TRUE,
      );
      $settings_form = $filter->settingsForm($settings_form, $form_state);
      if (!empty($settings_form)) {
        $form['filters']['settings'][$name] = array(
          '#type' => 'details',
          '#title' => $filter->getLabel(),
          '#open' => TRUE,
          '#weight' => $filter->weight,
          '#parents' => array('filters', $name, 'settings'),
          '#group' => 'filter_settings',
        );
        $form['filters']['settings'][$name] += $settings_form;
      }
    }
    return parent::form($form, $form_state);
  }

  /**
   * Determines if the format already exists.
   *
   * @param string $format_id
   *   The format ID
   *
   * @return bool
   *   TRUE if the format exists, FALSE otherwise.
   */
  public function exists($format_id) {
    return (bool) $this->queryFactory
      ->get('filter_format')
      ->condition('format', $format_id)
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @todo Move trimming upstream.
    $format_format = trim($form_state->getValue('format'));
    $format_name = trim($form_state->getValue('name'));

    // Ensure that the values to be saved later are exactly the ones validated.
    $form_state->setValueForElement($form['format'], $format_format);
    $form_state->setValueForElement($form['name'], $format_name);

    $format_exists = $this->queryFactory
      ->get('filter_format')
      ->condition('format', $format_format, '<>')
      ->condition('name', $format_name)
      ->execute();
    if ($format_exists) {
      $form_state->setErrorByName('name', $this->t('Text format names must be unique. A format named %name already exists.', array('%name' => $format_name)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    // Add the submitted form values to the text format, and save it.
    $format = $this->entity;
    foreach ($form_state->getValues() as $key => $value) {
      if ($key != 'filters') {
        $format->set($key, $value);
      }
      else {
        foreach ($value as $instance_id => $config) {
          $format->setFilterConfig($instance_id, $config);
        }
      }
    }
    $format->save();

    // Save user permissions.
    if ($permission = $format->getPermissionName()) {
      foreach ($form_state->getValue('roles') as $rid => $enabled) {
        user_role_change_permissions($rid, array($permission => $enabled));
      }
    }

    $form_state->setRedirect('filter.admin_overview');

    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Save configuration');
    return $actions;
  }

}
