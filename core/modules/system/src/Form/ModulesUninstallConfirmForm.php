<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Builds a confirmation form to uninstall selected modules.
 */
class ModulesUninstallConfirmForm extends ConfirmFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The expirable key value store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface
   */
  protected $keyValueExpirable;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * An array of modules to uninstall.
   *
   * @var array
   */
  protected $modules = array();

  /**
   * Constructs a ModulesUninstallConfirmForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, KeyValueStoreExpirableInterface $key_value_expirable, ConfigManagerInterface $config_manager, EntityManagerInterface $entity_manager) {
    $this->moduleHandler = $module_handler;
    $this->keyValueExpirable = $key_value_expirable;
    $this->configManager = $config_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('keyvalue.expirable')->get('modules_uninstall'),
      $container->get('config.manager'),
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Confirm uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('system.modules_uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('Would you like to continue with uninstalling the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_uninstall_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the list of modules from the key value store.
    $account = $this->currentUser()->id();
    $this->modules = $this->keyValueExpirable->get($account);

    // Prevent this page from showing when the module list is empty.
    if (empty($this->modules)) {
      return new RedirectResponse('/admin/modules/uninstall');
    }

    $data = system_rebuild_module_data();
    $form['text']['#markup'] = '<p>' . $this->t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>';
    $form['modules'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($module) use ($data) {
        return $data[$module]->info['name'];
      }, $this->modules),
    );

    $form['entities'] = array(
      '#type' => 'details',
      '#title' => $this->t('Configuration deletions'),
      '#description' => $this->t('The listed configuration will be deleted.'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#access' => FALSE,
    );

    // Get the dependent entities.
    $entity_types = array();
    $dependent_entities = $this->configManager->findConfigEntityDependentsAsEntities('module', $this->modules);
    foreach ($dependent_entities as $entity) {
      $entity_type_id = $entity->getEntityTypeId();
      if (!isset($form['entities'][$entity_type_id])) {
        $entity_type = $this->entityManager->getDefinition($entity_type_id);
        // Store the ID and label to sort the entity types and entities later.
        $label = $entity_type->getLabel();
        $entity_types[$entity_type_id] = $label;
        $form['entities'][$entity_type_id] = array(
          '#theme' => 'item_list',
          '#title' => $label,
          '#items' => array(),
        );
      }
      $form['entities'][$entity_type_id]['#items'][] = $entity->label();
    }
    if (!empty($dependent_entities)) {
      $form['entities']['#access'] = TRUE;

      // Add a weight key to the entity type sections.
      asort($entity_types, SORT_FLAG_CASE);
      $weight = 0;
      foreach ($entity_types as $entity_type_id => $label) {
        $form['entities'][$entity_type_id]['#weight'] = $weight;
        // Sort the list of entity labels alphabetically.
        sort($form['entities'][$entity_type_id]['#items'], SORT_FLAG_CASE);
        $weight++;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear the key value store entry.
    $account = $this->currentUser()->id();
    $this->keyValueExpirable->delete($account);

    // Uninstall the modules.
    $this->moduleHandler->uninstall($this->modules);

    drupal_set_message($this->t('The selected modules have been uninstalled.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
