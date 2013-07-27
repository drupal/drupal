<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a form for uninstalling modules.
 */
class ModulesUninstallForm implements FormInterface, ControllerInterface {

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
   * The translation manager service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('keyvalue.expirable')->get('modules_uninstall'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a ModulesUninstallForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\StringTranslation\TranslationManager $translation_manager
   *   The translation manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, KeyValueStoreExpirableInterface $key_value_expirable, TranslationManager $translation_manager) {
    $this->moduleHandler = $module_handler;
    $this->keyValueExpirable = $key_value_expirable;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules_uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    // Store the request for use in the submit handler.
    $this->request = $request;

    // Make sure the install API is available.
    include_once DRUPAL_ROOT . '/core/includes/install.inc';

    // Get a list of disabled, installed modules.
    $modules = system_rebuild_module_data();
    $disabled = array_filter($modules, function ($module) {
      return empty($module->status) && drupal_get_installed_schema_version($module->name) > SCHEMA_UNINSTALLED;
    });

    $form['modules'] = array();

    // Only build the rest of the form if there are any modules available to
    // uninstall;
    if (empty($disabled)) {
      return $form;
    }

    $profile = drupal_get_profile();

    // Sort all modules by their name.
    $this->moduleHandler->loadInclude('system', 'inc', 'system.admin');
    uasort($disabled, 'system_sort_modules_by_info_name');

    $form['uninstall'] = array('#tree' => TRUE);
    foreach ($disabled as $module) {
      $name = $module->info['name'] ?: $module->name;
      $form['modules'][$module->name]['#module_name'] = $name;
      $form['modules'][$module->name]['name']['#markup'] = $name;
      $form['modules'][$module->name]['description']['#markup'] = $this->translationManager->translate($module->info['description']);

      $form['uninstall'][$module->name] = array(
        '#type' => 'checkbox',
        '#title' => $this->translationManager->translate('Uninstall @module module', array('@module' => $name)),
        '#title_display' => 'invisible',
      );

      // All modules which depend on this one must be uninstalled first, before
      // we can allow this module to be uninstalled. (The installation profile
      // is excluded from this list.)
      foreach (array_keys($module->required_by) as $dependent) {
        if ($dependent != $profile && drupal_get_installed_schema_version($dependent) != SCHEMA_UNINSTALLED) {
          $name = isset($modules[$dependent]->info['name']) ? $modules[$dependent]->info['name'] : $dependent;
          $form['modules'][$module->name]['#dependents'][] = $name;
          $form['uninstall'][$module->name]['#disabled'] = TRUE;
        }
      }
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->translationManager->translate('Uninstall'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Form submitted, but no modules selected.
    if (!array_filter($form_state['values']['uninstall'])) {
      drupal_set_message($this->translationManager->translate('No modules selected.'), 'error');
      $form_state['redirect'] = 'admin/modules/uninstall';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Save all the values in an expirable key value store.
    $modules = $form_state['values']['uninstall'];
    $uninstall = array_keys(array_filter($modules));
    $account = $this->request->attributes->get('_account')->id();
    $this->keyValueExpirable->setWithExpire($account, $uninstall, 60);

    // Redirect to the confirm form.
    $form_state['redirect'] = 'admin/modules/uninstall/confirm';
  }
}
