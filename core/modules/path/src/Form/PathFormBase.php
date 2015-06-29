<?php

/**
 * @file
 * Contains \Drupal\path\Form\PathFormBase.
 */

namespace Drupal\path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Path\AliasStorageInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
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
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructs a new PathController.
   *
   * @param \Drupal\Core\Path\AliasStorageInterface $alias_storage
   *   The path alias storage.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   */
  public function __construct(AliasStorageInterface $alias_storage, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context) {
    $this->aliasStorage = $alias_storage;
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('path.alias_storage'),
      $container->get('path.alias_manager'),
      $container->get('path.validator'),
      $container->get('router.request_context')
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
  public function buildForm(array $form, FormStateInterface $form_state, $pid = NULL) {
    $this->path = $this->buildPath($pid);
    $form['source'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Existing system path'),
      '#default_value' => $this->path['source'],
      '#maxlength' => 255,
      '#size' => 45,
      '#description' => $this->t('Specify the existing path you wish to alias. For example: /node/28, /forum/1, /taxonomy/term/1.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
      '#required' => TRUE,
    );
    $form['alias'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path alias'),
      '#default_value' => $this->path['alias'],
      '#maxlength' => 255,
      '#size' => 45,
      '#description' => $this->t('Specify an alternative path by which this data can be accessed. For example, type "/about" when writing an about page. Use a relative path with a slash in front..'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
      '#required' => TRUE,
    );

    // A hidden value unless language.module is enabled.
    if (\Drupal::moduleHandler()->moduleExists('language')) {
      $languages = \Drupal::languageManager()->getLanguages();
      $language_options = array();
      foreach ($languages as $langcode => $language) {
        $language_options[$langcode] = $language->getName();
      }

      $form['langcode'] = array(
        '#type' => 'select',
        '#title' => $this->t('Language'),
        '#options' => $language_options,
        '#empty_value' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $source = &$form_state->getValue('source');
    $source = $this->aliasManager->getPathByAlias($source);
    $alias = &$form_state->getValue('alias');

    // Trim the submitted value of whitespace and slashes. Ensure to not trim
    // the slash on the left side.
    $alias = rtrim(trim(trim($alias), ''), "\\/");

    if ($source[0] !== '/') {
      $form_state->setErrorByName('source', 'The source path has to start with a slash.');
    }
    if ($alias[0] !== '/') {
      $form_state->setErrorByName('alias', 'The alias path has to start with a slash.');
    }

    // Language is only set if language.module is enabled, otherwise save for all
    // languages.
    $langcode = $form_state->getValue('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);

    if ($this->aliasStorage->aliasExists($alias, $langcode, $this->path['source'])) {
      $form_state->setErrorByName('alias', t('The alias %alias is already in use in this language.', array('%alias' => $alias)));
    }
    if (!$this->pathValidator->isValid(trim($source, '/'))) {
      $form_state->setErrorByName('source', t("The path '@link_path' is either invalid or you do not have access to it.", array('@link_path' => $source)));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Remove unnecessary values.
    $form_state->cleanValues();

    $pid = $form_state->getValue('pid', 0);
    $source = $form_state->getValue('source');
    $alias = $form_state->getValue('alias');
    // Language is only set if language.module is enabled, otherwise save for all
    // languages.
    $langcode = $form_state->getValue('langcode', LanguageInterface::LANGCODE_NOT_SPECIFIED);

    $this->aliasStorage->save($source, $alias, $langcode, $pid);

    drupal_set_message($this->t('The alias has been saved.'));
    $form_state->setRedirect('path.admin_overview');
  }

}
