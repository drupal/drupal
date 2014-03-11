<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
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
      $container->get('keyvalue.expirable')->get('modules_uninstall')
    );
  }

  /**
   * Constructs a ModulesUninstallForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   */
  public function __construct(ModuleHandlerInterface $module_handler, KeyValueStoreExpirableInterface $key_value_expirable) {
    $this->moduleHandler = $module_handler;
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
  public function buildForm(array $form, array &$form_state) {
    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Get a list of disabled, installed modules.
    $modules = system_rebuild_module_data();
    $uninstallable = array_filter($modules, function ($module) use ($modules) {
      return empty($modules[$module->getName()]->info['required']) && drupal_get_installed_schema_version($module->getName()) > SCHEMA_UNINSTALLED;
    });

    $form['modules'] = array();

    // Only build the rest of the form if there are any modules available to
    // uninstall;
    if (empty($uninstallable)) {
      return $form;
    }

    $profile = drupal_get_profile();

    // Sort all modules by their name.
    uasort($uninstallable, 'system_sort_modules_by_info_name');

    $form['uninstall'] = array('#tree' => TRUE);
    foreach ($uninstallable as $module) {
      $name = $module->info['name'] ?: $module->getName();
      $form['modules'][$module->getName()]['#module_name'] = $name;
      $form['modules'][$module->getName()]['name']['#markup'] = $name;
      $form['modules'][$module->getName()]['description']['#markup'] = $this->t($module->info['description']);

      $form['uninstall'][$module->getName()] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Uninstall @module module', array('@module' => $name)),
        '#title_display' => 'invisible',
      );

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
  public function validateForm(array &$form, array &$form_state) {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state['values']['uninstall'])) {
      drupal_set_message($this->t('No modules selected.'), 'error');
      $form_state['redirect_route']['route_name'] = 'system.modules_uninstall';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Save all the values in an expirable key value store.
    $modules = $form_state['values']['uninstall'];
    $uninstall = array_keys(array_filter($modules));
    $account = $this->currentUser()->id();
    $this->keyValueExpirable->setWithExpire($account, $uninstall, 60);

    // Redirect to the confirm form.
    $form_state['redirect_route']['route_name'] = 'system.modules_uninstall_confirm';
  }
}
