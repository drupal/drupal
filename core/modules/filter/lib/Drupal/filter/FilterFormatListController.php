<?php

/**
 * @file
 * Contains \Drupal\filter\FilterFormatListController.
 */

namespace Drupal\filter;

use Drupal\Component\Utility\String;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\Entity\ConfigEntityListController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the filter format list controller.
 */
class FilterFormatListController extends ConfigEntityListController implements FormInterface {

  /**
   * The entities being listed.
   *
   * @var array
   */
  protected $entities;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs a new FilterFormatListController.
   *
   * @param string $entity_type
   *   The type of entity to be listed.
   * @param array $entity_info
   *   An array of entity info for the entity type.
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage
   *   The entity storage controller class.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke hooks on.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct($entity_type, array $entity_info, EntityStorageControllerInterface $storage, ModuleHandlerInterface $module_handler, ConfigFactory $config_factory) {
    parent::__construct($entity_type, $entity_info, $storage, $module_handler);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type, array $entity_info) {
    return new static(
      $entity_type,
      $entity_info,
      $container->get('plugin.manager.entity')->getStorageController($entity_type),
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'filter_admin_overview';
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return drupal_get_form($this);
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // Only list enabled filters.
    return array_filter(parent::load(), function ($entity) {
      return $entity->status();
    });
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    // Overview of all formats.
    $this->entities = $this->load();
    $filter_settings = $this->configFactory->get('filter.settings');
    $fallback_format = $filter_settings->get('fallback_format');

    $form['#tree'] = TRUE;
    $form['formats'] = array(
      '#type' => 'table',
      '#header' => array(t('Name'), t('Roles'), t('Weight'), t('Operations')),
      '#tabledrag' => array(
        array('order', 'sibling', 'text-format-order-weight'),
      ),
    );
    $fallback_choice = $filter_settings->get('always_show_fallback_choice');
    foreach ($this->entities as $id => $format) {
      $form['formats'][$id]['#attributes']['class'][] = 'draggable';
      $form['formats'][$id]['#weight'] = $format->weight;

      $links = array();
      $links['configure'] = array(
        'title' => t('configure'),
        'href' => "admin/config/content/formats/manage/$id",
      );
      // Check whether this is the fallback text format. This format is available
      // to all roles and cannot be disabled via the admin interface.
      $form['formats'][$id]['#is_fallback'] = ($id == $fallback_format);
      if ($form['formats'][$id]['#is_fallback']) {
        $form['formats'][$id]['name'] = array('#markup' => String::placeholder($format->name));
        if ($fallback_choice) {
          $roles_markup = String::placeholder(t('All roles may use this format'));
        }
        else {
          $roles_markup = String::placeholder(t('This format is shown when no other formats are available'));
        }
      }
      else {
        $form['formats'][$id]['name'] = array('#markup' => String::checkPlain($format->name));
        $roles = array_map('\Drupal\Component\Utility\String::checkPlain', filter_get_roles_by_format($format));
        $roles_markup = $roles ? implode(', ', $roles) : t('No roles may use this format');
        $links['disable'] = array(
          'title' => t('disable'),
          'href' => "admin/config/content/formats/manage/$id/disable",
        );
      }

      $form['formats'][$id]['roles'] = array('#markup' => $roles_markup);

      $form['formats'][$id]['weight'] = array(
        '#type' => 'weight',
        '#title' => t('Weight for @title', array('@title' => $format->name)),
        '#title_display' => 'invisible',
        '#default_value' => $format->weight,
        '#attributes' => array('class' => array('text-format-order-weight')),
      );

      $form['formats'][$id]['operations'] = array(
        '#type' => 'operations',
        '#links' => $links,
      );
    }
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array('#type' => 'submit', '#value' => t('Save changes'));
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    foreach ($form_state['values']['formats'] as $id => $data) {
      // Only update if this is a form element with weight.
      if (is_array($data) && isset($data['weight'])) {
        $this->entities[$id]->set('weight', $data['weight']);
        $this->entities[$id]->save();
      }
    }
    filter_formats_reset();
    drupal_set_message(t('The text format ordering has been saved.'));
  }

}
