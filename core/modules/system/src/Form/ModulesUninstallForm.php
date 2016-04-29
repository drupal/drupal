<?php

namespace Drupal\system\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for uninstalling modules.
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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('keyvalue.expirable')->get('modules_uninstall')
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
   */
  public function __construct(ModuleHandlerInterface $module_handler, ModuleInstallerInterface $module_installer, KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->keyValueExpirable = $key_value_expirable;
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

    // Get a list of all available modules.
    $modules = system_rebuild_module_data();
    $uninstallable = array_filter($modules, function ($module) use ($modules) {
      return empty($modules[$module->getName()]->info['required']) && $module->status;
    });

    // Include system.admin.inc so we can use the sort callbacks.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');

    $form['filters'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('table-filter', 'js-show'),
      ),
    );

    $form['filters']['text'] = array(
      '#type' => 'search',
      '#title' => $this->t('Filter modules'),
      '#title_display' => 'invisible',
      '#size' => 30,
      '#placeholder' => $this->t('Filter by name or description'),
      '#description' => $this->t('Enter a part of the module name or description'),
      '#attributes' => array(
        'class' => array('table-filter-text'),
        'data-table' => '#system-modules-uninstall',
        'autocomplete' => 'off',
      ),
    );

    $form['modules'] = array();

    // Only build the rest of the form if there are any modules available to
    // uninstall;
    if (empty($uninstallable)) {
      return $form;
    }

    $profile = drupal_get_profile();

    // Sort all modules by their name.
    uasort($uninstallable, 'system_sort_modules_by_info_name');
    $validation_reasons = $this->moduleInstaller->validateUninstall(array_keys($uninstallable));

    $form['uninstall'] = array('#tree' => TRUE);
    foreach ($uninstallable as $module_key => $module) {
      $name = $module->info['name'] ?: $module->getName();
      $form['modules'][$module->getName()]['#module_name'] = $name;
      $form['modules'][$module->getName()]['name']['#markup'] = $name;
      $form['modules'][$module->getName()]['description']['#markup'] = $this->t($module->info['description']);

      $form['uninstall'][$module->getName()] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Uninstall @module module', array('@module' => $name)),
        '#title_display' => 'invisible',
      );

      // If a validator returns reasons not to uninstall a module,
      // list the reasons and disable the check box.
      if (isset($validation_reasons[$module_key])) {
        $form['modules'][$module->getName()]['#validation_reasons'] = $validation_reasons[$module_key];
        $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
      }
      // All modules which depend on this one must be uninstalled first, before
      // we can allow this module to be uninstalled. (The installation profile
      // is excluded from this list.)
      foreach (array_keys($module->required_by) as $dependent) {
        if ($dependent != $profile && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED) {
          $name = isset($modules[$dependent]->info['name']) ? $modules[$dependent]->info['name'] : $dependent;
          $form['modules'][$module->getName()]['#required_by'][] = $name;
          $form['uninstall'][$module->getName()]['#disabled'] = TRUE;
        }
      }
    }

    $form['#attached']['library'][] = 'system/drupal.system.modules';
    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Uninstall'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state->getValue('uninstall'))) {
      $form_state->setErrorByName('uninstall', $this->t('No modules selected.'));
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
