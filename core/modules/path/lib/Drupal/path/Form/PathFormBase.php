<?php

/**
 * @file
 * Contains \Drupal\path\Form\PathFormBase.
 */

namespace Drupal\path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Language\Language;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base class for path add/edit forms.
 */
abstract class PathFormBase extends FormBase {

  /**
   * An array containing the path ID, source, alias, and language code.
   *
   * @var array
   */
  protected $path;

  /**
   * The path alias storage.
   *
   * @var \Drupal\Core\Path\AliasStorageInterface
   */
  protected $aliasStorage;

  /**
   * The path alias manager.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(AliasStorageInterface $alias_storage, AliasManagerInterface $alias_manager) {
    $this->aliasStorage = $alias_storage;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_storage'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * Builds the path used by the form.
   *
   * @param int|null $pid
   *   Either the unique path ID, or NULL if a new one is being created.
   */
  abstract protected function buildPath($pid);

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state, $pid = NULL) {
    $this->path = $this->buildPath($pid);
    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Existing system path'),
      '#default_value' => $this->path['source'],
      '#maxlength' => 255,
      '#size' => 45,
      '#description' => $this->t('Specify the existing path you wish to alias. For example: node/28, forum/1, taxonomy/term/1.'),
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
      '#required' => TRUE,
    );
    $form['alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path alias'),
      '#default_value' => $this->path['alias'],
      '#maxlength' => 255,
      '#size' => 45,
      '#description' => $this->t('Specify an alternative path by which this data can be accessed. For example, type "about" when writing an about page. Use a relative path and don\'t add a trailing slash or the URL alias won\'t work.'),
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
      '#required' => TRUE,
    );

    // A hidden value unless language.module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $languages = \Drupal::languageManager()->getLanguages();
      $language_options = array();
      foreach ($languages as $langcode => $language) {
        $language_options[$langcode] = $language->name;
      }

      $form['langcode'] = array(
        '#type' => 'select',
        '#title' => $this->t('Language'),
        '#options' => $language_options,
        '#empty_value' => Language::LANGCODE_NOT_SPECIFIED,
        '#empty_option' => $this->t('- None -'),
        '#default_value' => $this->path['langcode'],
        '#weight' => -10,
        '#description' => $this->t('A path alias set for a specific language will always be used when displaying this page in that language, and takes precedence over path aliases set as <em>- None -</em>.'),
      );
    }
    else {
      $form['langcode'] = array(
        '#type' => 'value',
        '#value' => $this->path['langcode']
      );
    }

    $form['actions'] = array('#type' => 'actions');
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    $source = &$form_state['values']['source'];
    $source = $this->aliasManager->getPathByAlias($source);
    $alias = $form_state['values']['alias'];
    // Language is only set if language.module is enabled, otherwise save for all
    // languages.
    $langcode = isset($form_state['values']['langcode']) ? $form_state['values']['langcode'] : Language::LANGCODE_NOT_SPECIFIED;

    if ($this->aliasStorage->aliasExists($alias, $langcode, $source)) {
      $this->setFormError('alias', $form_state, t('The alias %alias is already in use in this language.', array('%alias' => $alias)));
    }
    if (!drupal_valid_path($source)) {
      $this->setFormError('source', $form_state, t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $source)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    // Remove unnecessary values.
    form_state_values_clean($form_state);

    $pid = isset($form_state['values']['pid']) ? $form_state['values']['pid'] : 0;
    $source = &$form_state['values']['source'];
    $source = $this->aliasManager->getPathByAlias($source);
    $alias = $form_state['values']['alias'];
    // Language is only set if language.module is enabled, otherwise save for all
    // languages.
    $langcode = isset($form_state['values']['langcode']) ? $form_state['values']['langcode'] : Language::LANGCODE_NOT_SPECIFIED;

    $this->aliasStorage->save($source, $alias, $langcode, $pid);

    drupal_set_message($this->t('The alias has been saved.'));
    $form_state['redirect_route'] = new Url('path.admin_overview');
  }

}
