<?php

/**
 * @file
 * Contains Drupal\action\ActionEditFormController.
 */

namespace Drupal\action;

use Drupal\Core\Entity\EntityFormController;
use Drupal\Core\Entity\EntityStorageControllerInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base form controller for action forms.
 */
abstract class ActionFormControllerBase extends EntityFormController {

  /**
   * The action plugin being configured.
   *
   * @var \Drupal\Core\Action\ActionInterface
   */
  protected $plugin;

  /**
   * The action storage controller.
   *
   * @var \Drupal\Core\Entity\EntityStorageControllerInterface
   */
  protected $storageController;

  /**
   * Constructs a new action form.
   *
   * @param \Drupal\Core\Entity\EntityStorageControllerInterface $storage_controller
   *   The action storage controller.
   */
  public function __construct(EntityStorageControllerInterface $storage_controller) {
    $this->storageController = $storage_controller;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorageController('action')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $this->plugin = $this->entity->getPlugin();
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, array &$form_state) {
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $this->entity->label(),
      '#maxlength' => '255',
      '#description' => $this->t('A unique label for this advanced action. This label will be displayed in the interface of modules that integrate with actions.'),
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $this->entity->id(),
      '#disabled' => !$this->entity->isNew(),
      '#maxlength' => 64,
      '#description' => $this->t('A unique name for this action. It must only contain lowercase letters, numbers and underscores.'),
      '#machine_name' => array(
        'exists' => array($this, 'exists'),
      ),
    );
    $form['plugin'] = array(
      '#type' => 'value',
      '#value' => $this->entity->get('plugin'),
    );
    $form['type'] = array(
      '#type' => 'value',
      '#value' => $this->entity->getType(),
    );

    if ($this->plugin instanceof PluginFormInterface) {
      $form += $this->plugin->buildConfigurationForm($form, $form_state);
    }

    return parent::form($form, $form_state);
  }

  /**
   * Determines if the action already exists.
   *
   * @param string $id
   *   The action ID
   *
   * @return bool
   *   TRUE if the action exists, FALSE otherwise.
   */
  public function exists($id) {
    $action = $this->storageController->load($id);
    return !empty($action);
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, array &$form_state) {
    $actions = parent::actions($form, $form_state);
    unset($actions['delete']);
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validate(array $form, array &$form_state) {
    parent::validate($form, $form_state);

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->validateConfigurationForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submit(array $form, array &$form_state) {
    parent::submit($form, $form_state);

    if ($this->plugin instanceof PluginFormInterface) {
      $this->plugin->submitConfigurationForm($form, $form_state);
    }
    return $this->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, array &$form_state) {
    $this->entity->save();
    drupal_set_message($this->t('The action has been successfully saved.'));

    $form_state['redirect_route'] = array(
      'route_name' => 'action.admin',
    );
  }

}
