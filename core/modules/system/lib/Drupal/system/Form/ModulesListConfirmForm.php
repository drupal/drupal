<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesListConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Builds a confirmation form for enabling modules with dependencies.
 */
class ModulesListConfirmForm extends ConfirmFormBase implements ControllerInterface {

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
   * An associative list of modules to enable or disable.
   *
   * @var array
   */
  protected $modules = array();

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('keyvalue.expirable')->get('module_list'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a ModulesListConfirmForm object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface $key_value_expirable
   *   The key value expirable factory.
   * @param \Drupal\Core\StringTranslation\TranslationManager
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
  public function getQuestion() {
    return $this->translationManager->translate('Some required modules must be enabled');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/modules';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->translationManager->translate('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->translationManager->translate('Would you like to continue with the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    $account = $request->attributes->get('_account')->id();
    $this->modules = $this->keyValueExpirable->get($account);

    // Redirect to the modules list page if the key value store is empty.
    if (!$this->modules) {
      return new RedirectResponse(url($this->getCancelPath(), array('absolute' => TRUE)));
    }

    // Store the request for use in the submit handler.
    $this->request = $request;

    $items = array();
    // Display a list of required modules that have to be installed as well but
    // were not manually selected.
    foreach ($this->modules['dependencies'] as $module => $dependencies) {
      $items[] = format_plural(count($dependencies), 'You must enable the @required module to install @module.', 'You must enable the @required modules to install @module.', array(
        '@module' => $this->modules['enable'][$module],
        '@required' => implode(', ', $dependencies),
      ));
    }

    foreach ($this->modules['missing'] as $name => $dependents) {
      $items[] = format_plural(count($dependents), 'The @module module is missing, so the following module will be disabled: @depends.', 'The @module module is missing, so the following modules will be disabled: @depends.', array(
        '@module' => $name,
        '@depends' => implode(', ', $dependents),
      ));
    }

    $form['message'] = array(
      '#theme' => 'item_list',
      '#items' => $items,
    );

    return parent::buildForm($form, $form_state, $this->request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Remove the key value store entry.
    $account = $this->request->attributes->get('_account')->id();
    $this->keyValueExpirable->delete($account);

    // Gets list of modules prior to install process.
    $before = $this->moduleHandler->getModuleList();

    // Installs, enables, and disables modules.
    if (!empty($this->modules['enable'])) {
      $this->moduleHandler->enable(array_keys($this->modules['enable']));
    }
    if (!empty($this->modules['disable'])) {
      $this->moduleHandler->disable(array_keys($this->modules['disable']));
    }

    // Gets module list after install process, flushes caches and displays a
    // message if there are changes.
    if ($before != $this->moduleHandler->getModuleList()) {
      drupal_flush_all_caches();
      drupal_set_message($this->translationManager->translate('The configuration options have been saved.'));
    }

    $form_state['redirect'] = $this->getCancelPath();
  }

}
