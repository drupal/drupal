<?php

/**
 * @file
 * Contains \Drupal\system\Form\SiteInformationForm.
 */

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
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
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager) {
    parent::__construct($config_factory);

    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.alias_manager')
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
  public function buildForm(array $form, array &$form_state) {
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
      '#required' => TRUE
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
    $front_page = $site_config->get('page.front') != 'user' ? $this->aliasManager->getAliasByPath($site_config->get('page.front')) : '';
    $form['front_page']['site_frontpage'] = array(
      '#type' => 'textfield',
      '#title' => t('Default front page'),
      '#default_value' => $front_page,
      '#size' => 40,
      '#description' => t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
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
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );
    $form['error_page']['site_404'] = array(
      '#type' => 'textfield',
      '#title' => t('Default 404 (not found) page'),
      '#default_value' => $site_config->get('page.404'),
      '#size' => 40,
      '#description' => t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
      '#field_prefix' => url(NULL, array('absolute' => TRUE)),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, array &$form_state) {
    // Check for empty front page path.
    if (empty($form_state['values']['site_frontpage'])) {
      // Set to default "user".
      form_set_value($form['front_page']['site_frontpage'], 'user', $form_state);
    }
    else {
      // Get the normal path of the front page.
      form_set_value($form['front_page']['site_frontpage'], $this->aliasManager->getPathByAlias($form_state['values']['site_frontpage']), $form_state);
    }
    // Validate front page path.
    if (!drupal_valid_path($form_state['values']['site_frontpage'])) {
      $this->setFormError('site_frontpage', $form_state, $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_frontpage'])));
    }
    // Get the normal paths of both error pages.
    if (!empty($form_state['values']['site_403'])) {
      form_set_value($form['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state['values']['site_403']), $form_state);
    }
    if (!empty($form_state['values']['site_404'])) {
      form_set_value($form['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state['values']['site_404']), $form_state);
    }
    // Validate 403 error path.
    if (!empty($form_state['values']['site_403']) && !drupal_valid_path($form_state['values']['site_403'])) {
      $this->setFormError('site_403', $form_state, $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_403'])));
    }
    // Validate 404 error path.
    if (!empty($form_state['values']['site_404']) && !drupal_valid_path($form_state['values']['site_404'])) {
      $this->setFormError('site_404', $form_state, $this->t("The path '%path' is either invalid or you do not have access to it.", array('%path' => $form_state['values']['site_404'])));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, array &$form_state) {
    $this->config('system.site')
      ->set('name', $form_state['values']['site_name'])
      ->set('mail', $form_state['values']['site_mail'])
      ->set('slogan', $form_state['values']['site_slogan'])
      ->set('page.front', $form_state['values']['site_frontpage'])
      ->set('page.403', $form_state['values']['site_403'])
      ->set('page.404', $form_state['values']['site_404'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
