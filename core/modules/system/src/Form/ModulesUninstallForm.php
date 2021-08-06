<?php

namespace Drupal\system\Form;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Update\UpdateHookRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for uninstalling modules.
 *
 * @internal
 */
class ModulesUninstallForm extends FormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

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
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The update registry service.
   *
   * @var \Drupal\Core\Update\UpdateHookRegistry
   */
  protected $updateRegistry;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('keyvalue.expirable')->get('modules_uninstall'),
      $container->get('extension.list.module'),
      $container->get('update.update_hook_registry')
    );
  }

  /**
   * Constructs a ModulesUninstallForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extension_list_module
   *   The module extension list.
   * @param \Drupal\Core\Update\UpdateHookRegistry|null $versioning_update_registry
   *   Versioning update registry service.
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable, ModuleExtensionList $extension_list_module, UpdateHookRegistry $versioning_update_registry = NULL) {
    $this->moduleExtensionList = $extension_list_module;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->keyValueExpirable = $key_value_expirable;
    if ($versioning_update_registry === NULL) {
      @trigger_error('The update.update_hook_registry service must be passed to ' . __NAMESPACE__ . '\ModulesUninstallForm::__construct(). It was added in drupal:9.3.0 and will be required before drupal:10.0.0.', E_USER_DEPRECATED);
      $versioning_update_registry = \Drupal::service('update.update_hook_registry');
    }
    $this->updateRegistry = $versioning_update_registry;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_modules_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Get a list of all available modules that can be uninstalled.
    $uninstallable = array_filter($this->moduleExtensionList->getList(), function ($module) {
       return empty($module->info['required']) && $module->status;
    });

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['table-filter', 'js-show'],
      ],
    ];

    $form['filters']['text'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter modules'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the module name or description'),
      '#attributes' => [
        'class' => ['table-filter-text'],
        'data-table' => '#system-modules-uninstall',
        'autocomplete' => 'off',
      ],
    ];

    $form['modules'] = [];

    // Only build the rest of the form if there are any modules available to
    // uninstall;
    if (empty($uninstallable)) {
      return $form;
    }

    // Sort all modules by their name.
    uasort($uninstallable, [ModuleExtensionList::class, 'sortByName']);
    $validation_reasons = $this->moduleInstaller->validateUninstall(array_keys($uninstallable));

    $form['uninstall'] = ['#tree' => TRUE];
    foreach ($uninstallable as $module_key => $module) {
      $name = $module->info['name'] ?: $module->getName();
      $form['modules'][$module->getName()]['#module_name'] = $name;
      $form['modules'][$module->getName()]['name']['#markup'] = $name;
      $form['modules'][$module->getName()]['description']['#markup'] = $this->t($module->info['description']);

      $form['uninstall'][$module->getName()] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Uninstall @module module', ['@module' => $name]),
        '#title_display' => 'invisible',
      ];

      // If a validator returns reasons not to uninstall a module,
      // list the reasons and disable the check box.
      if (isset($validation_reasons[$module_key])) {
        $form['modules'][$module->getName()]['#validation_reasons'] = $validation_reasons[$module_key];
        $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
      }
      // All modules which depend on this one must be uninstalled first, before
      // we can allow this module to be uninstalled.
      foreach (array_keys($module->required_by) as $dependent) {
        if ($this->updateRegistry->getInstalledVersion($dependent) !== $this->updateRegistry::SCHEMA_UNINSTALLED) {
          $form['modules'][$module->getName()]['#required_by'][] = $dependent;
          $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
        }
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Uninstall'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state->getValue('uninstall'))) {
      $form_state->setErrorByName('', $this->t('No modules selected.'));
      $form_state->setRedirect('system.modules_uninstall');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save all the values in an expirable key value store.
    $modules = $form_state->getValue('uninstall');
    $uninstall = array_keys(array_filter($modules));
    $account = $this->currentUser()->id();
    // Store the values for 6 hours. This expiration time is also used in
    // the form cache.
    $this->keyValueExpirable->setWithExpire($account, $uninstall, 6 * 60 * 60);

    // Redirect to the confirm form.
    $form_state->setRedirect('system.modules_uninstall_confirm');
  }

}
