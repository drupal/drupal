<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\Entity\ConfigDependencyDeleteFormTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;

/**
 * Builds a confirmation form to uninstall selected modules.
 */
class ModulesUninstallConfirmForm extends ConfirmFormBase {
  use ConfigDependencyDeleteFormTrait;

  /**
   * The module installer service.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;

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
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   */
  public function __construct(ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable, ConfigManagerInterface $config_manager, EntityManagerInterface $entity_manager) {
    $this->moduleInstaller = $module_installer;
    $this->keyValueExpirable = $key_value_expirable;
    $this->configManager = $config_manager;
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_installer'),
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
      drupal_set_message($this->t('The selected modules could not be uninstalled, either due to a website problem or due to the uninstall confirmation form timing out. Please try again.'), 'error');
      return $this->redirect('system.modules_uninstall');
    }

    $data = system_rebuild_module_data();
    $form['text']['#markup'] = '<p>' . $this->t('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>';
    $form['modules'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($module) use ($data) {
        return $data[$module]->info['name'];
      }, $this->modules),
    );

    // List the dependent entities.
    $this->addDependencyListsToForm($form, 'module', $this->modules ,$this->configManager, $this->entityManager);

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
    $this->moduleInstaller->uninstall($this->modules);

    drupal_set_message($this->t('The selected modules have been uninstalled.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
