<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteInformationForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Path\PathValidatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class SiteInformationForm extends ConfigFormBase {

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
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'system_site_information_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $form['site_information'] = array(
      '#type' => 'details',
      '#title' => t('Site details'),
      '#open' => TRUE,
    );
    $form['site_information']['site_name'] = array(
      '#type' => 'textfield',
      '#title' => t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    );
    $form['site_information']['site_slogan'] = array(
      '#type' => 'textfield',
      '#title' => t('Slogan'),
      '#default_value' => $site_config->get('slogan'),
      '#description' => t("How this is used depends on your site's theme."),
    );
    $form['site_information']['site_mail'] = array(
      '#type' => 'email',
      '#title' => t('Email address'),
      '#default_value' => $site_mail,
      '#description' => t("The <em>From</em> address in automated emails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this email being flagged as spam.)"),
      '#required' => TRUE,
    );
    $form['front_page'] = array(
      '#type' => 'details',
      '#title' => t('Front page'),
      '#open' => TRUE,
    );
    $front_page = $site_config->get('page.front') != 'user/login' ? $this->aliasManager->getAliasByPath($site_config->get('page.front')) : '';
    $form['front_page']['site_frontpage'] = array(
      '#type' => 'textfield',
      '#title' => t('Default front page'),
      '#default_value' => $front_page,
      '#size' => 40,
      '#description' => t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
      '#field_prefix' => $this->url('<none>', [], ['absolute' => TRUE]),
    );
    $form['error_page'] = array(
      '#type' => 'details',
      '#title' => t('Error pages'),
      '#open' => TRUE,
    );
    $form['error_page']['site_403'] = array(
      '#type' => 'textfield',
      '#title' => t('Default 403 (access denied) page'),
      '#default_value' => $site_config->get('page.403'),
      '#size' => 40,
      '#description' => t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
      '#field_prefix' => $this->url('<none>', [], ['absolute' => TRUE]),
    );
    $form['error_page']['site_404'] = array(
      '#type' => 'textfield',
      '#title' => t('Default 404 (not found) page'),
      '#default_value' => $site_config->get('page.404'),
      '#size' => 40,
      '#description' => t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
      '#field_prefix' => $this->url('<none>', [], ['absolute' => TRUE]),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for empty front page path.
    if ($form_state->isValueEmpty('site_frontpage')) {
      // Set to default "user/login".
      form_set_value($form['front_page']['site_frontpage'], 'user/login', $form_state);
    }
    else {
      // Get the normal path of the front page.
      form_set_value($form['front_page']['site_frontpage'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage')), $form_state);
    }
    // Validate front page path.
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state->getValue('site_frontpage'))));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('site_403')) {
      form_set_value($form['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state->getValue('site_403')), $form_state);
    }
    if (!$form_state->isValueEmpty('site_404')) {
      form_set_value($form['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state->getValue('site_404')), $form_state);
    }
    // Validate 403 error path.
    if (!$form_state->isValueEmpty('site_403') && !$this->pathValidator->isValid($form_state->getValue('site_403'))) {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state->getValue('site_403'))));
    }
    // Validate 404 error path.
    if (!$form_state->isValueEmpty('site_404') && !$this->pathValidator->isValid($form_state->getValue('site_404'))) {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state->getValue('site_404'))));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.site')
      ->set('name', $form_state->getValue('site_name'))
      ->set('mail', $form_state->getValue('site_mail'))
      ->set('slogan', $form_state->getValue('site_slogan'))
      ->set('page.front', $form_state->getValue('site_frontpage'))
      ->set('page.403', $form_state->getValue('site_403'))
      ->set('page.404', $form_state->getValue('site_404'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
