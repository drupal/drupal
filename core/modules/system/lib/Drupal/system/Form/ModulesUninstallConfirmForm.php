<?php

/**
 * @file
 * Contains \Drupal\system\Form\ModulesUninstallConfirmForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreExpirableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Builds a confirmation form to uninstall selected modules.
 */
class ModulesUninstallConfirmForm extends ConfirmFormBase implements ControllerInterface {

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
   * An array of modules to uninstall.
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
      $container->get('keyvalue.expirable')->get('modules_uninstall'),
      $container->get('string_translation')
    );
  }

  /**
   * Constructs a ModulesUninstallConfirmForm object.
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
    return $this->translationManager->translate('Confirm uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->translationManager->translate('Uninstall');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelPath() {
    return 'admin/modules/uninstall';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->translationManager->translate('Would you like to continue with uninstalling the above?');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'system_modules_uninstall_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, Request $request = NULL) {
    // Store the request for use in the submit handler.
    $this->request = $request;

    // Retrieve the list of modules from the key value store.
    $account = $request->attributes->get('_account')->id();
    $this->modules = $this->keyValueExpirable->get($account);

    // Prevent this page from showing when the module list is empty.
    if (empty($this->modules)) {
      return new RedirectResponse('/admin/modules/uninstall');
    }

    $data = system_rebuild_module_data();
    $form['text']['#markup'] = '<p>' . $this->translationManager->translate('The following modules will be completely uninstalled from your site, and <em>all data from these modules will be lost</em>!') . '</p>';
    $form['modules'] = array(
      '#theme' => 'item_list',
      '#items' => array_map(function ($module) use ($data) {
        return $data[$module]->info['name'];
      }, $this->modules),
    );

    return parent::buildForm($form, $form_state, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Clear the key value store entry.
    $account = $this->request->attributes->get('_account')->id();
    $this->keyValueExpirable->delete($account);

    // Uninstall the modules.
    $this->moduleHandler->uninstall($this->modules);

    drupal_set_message($this->translationManager->translate('The selected modules have been uninstalled.'));
    $form_state['redirect'] = 'admin/modules/uninstall';
  }

}
