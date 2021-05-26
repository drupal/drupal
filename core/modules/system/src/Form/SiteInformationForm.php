<?php

namespace Drupal\system\Form;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 *
 * @internal
 */
class SiteInformationForm extends ConfigFormBase {

  /**
   * The path alias manager.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
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
   * The email validator.
   *
   * @var \Drupal\Component\Utility\EmailValidator
   */
  protected $emailValidator;

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AliasManagerInterface $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context, EmailValidatorInterface $email_validator) {
    parent::__construct($config_factory);
    $this->aliasManager = $alias_manager;
    $this->pathValidator = $path_validator;
    $this->requestContext = $request_context;
    $this->emailValidator = $email_validator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('email.validator')
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
  protected function getEditableConfigNames() {
    return ['system.site', 'system.advisories'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $site_config = $this->config('system.site');
    $advisories_config = $this->config('system.advisories');
    $site_mail = $site_config->get('mail');
    if (empty($site_mail)) {
      $site_mail = ini_get('sendmail_from');
    }

    $form['site_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Site details'),
      '#open' => TRUE,
    ];
    $form['site_information']['site_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];
    $form['site_information']['site_slogan'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Slogan'),
      '#default_value' => $site_config->get('slogan'),
      '#description' => $this->t("How this is used depends on your site's theme."),
      '#maxlength' => 255,
    ];
    $form['site_information']['site_mail'] = [
      '#type' => 'email',
      '#title' => $this->t('Email address'),
      '#default_value' => $site_mail,
      '#description' => $this->t("The <em>From</em> address in automated emails sent during registration and new password requests, and other notifications. (Use an address ending in your site's domain to help prevent this email being flagged as spam.)"),
      '#required' => TRUE,
    ];
    $advisories_emails = $advisories_config->get('emails');
    $form['site_information']['advisory_emails'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Email addresses to notify for critical security information about the site.'),
      '#rows' => 4,
      '#default_value' => implode("\n", $advisories_emails),
      '#description' => $this->t('These emails will be notified when critical security information such as highly critical security advisories are available. Put each address on a separate line. If blank, no emails will be sent.'),
    ];
    $form['front_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Front page'),
      '#open' => TRUE,
    ];
    $front_page = $site_config->get('page.front') != '/user/login' ? $this->aliasManager->getAliasByPath($site_config->get('page.front')) : '';
    $form['front_page']['site_frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default front page'),
      '#default_value' => $front_page,
      '#size' => 40,
      '#description' => $this->t('Optionally, specify a relative URL to display as the front page. Leave blank to display the default front page.'),
      '#field_prefix' => $this->requestContext->getCompleteBaseUrl(),
    ];
    $form['error_page'] = [
      '#type' => 'details',
      '#title' => $this->t('Error pages'),
      '#open' => TRUE,
    ];
    $form['error_page']['site_403'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 403 (access denied) page'),
      '#default_value' => $site_config->get('page.403'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when the requested document is denied to the current user. Leave blank to display a generic "access denied" page.'),
    ];
    $form['error_page']['site_404'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default 404 (not found) page'),
      '#default_value' => $site_config->get('page.404'),
      '#size' => 40,
      '#description' => $this->t('This page is displayed when no other content matches the requested document. Leave blank to display a generic "page not found" page.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Check for empty front page path.
    if ($form_state->isValueEmpty('site_frontpage')) {
      // Set to default "user/login".
      $form_state->setValueForElement($form['front_page']['site_frontpage'], '/user/login');
    }
    else {
      // Get the normal path of the front page.
      $form_state->setValueForElement($form['front_page']['site_frontpage'], $this->aliasManager->getPathByAlias($form_state->getValue('site_frontpage')));
    }
    // Validate front page path.
    if (($value = $form_state->getValue('site_frontpage')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_frontpage', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_frontpage')]));

    }
    if (!$this->pathValidator->isValid($form_state->getValue('site_frontpage'))) {
      $form_state->setErrorByName('site_frontpage', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_frontpage')]));
    }
    // Get the normal paths of both error pages.
    if (!$form_state->isValueEmpty('site_403')) {
      $form_state->setValueForElement($form['error_page']['site_403'], $this->aliasManager->getPathByAlias($form_state->getValue('site_403')));
    }
    if (!$form_state->isValueEmpty('site_404')) {
      $form_state->setValueForElement($form['error_page']['site_404'], $this->aliasManager->getPathByAlias($form_state->getValue('site_404')));
    }
    if (($value = $form_state->getValue('site_403')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_403', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_403')]));
    }
    if (($value = $form_state->getValue('site_404')) && $value[0] !== '/') {
      $form_state->setErrorByName('site_404', $this->t("The path '%path' has to start with a slash.", ['%path' => $form_state->getValue('site_404')]));
    }
    // Validate 403 error path.
    if (!$form_state->isValueEmpty('site_403') && !$this->pathValidator->isValid($form_state->getValue('site_403'))) {
      $form_state->setErrorByName('site_403', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_403')]));
    }
    // Validate 404 error path.
    if (!$form_state->isValueEmpty('site_404') && !$this->pathValidator->isValid($form_state->getValue('site_404'))) {
      $form_state->setErrorByName('site_404', $this->t("Either the path '%path' is invalid or you do not have access to it.", ['%path' => $form_state->getValue('site_404')]));
    }

    $form_state->set('advisory_emails', []);
    if (!$form_state->isValueEmpty('advisory_emails')) {
      $valid = [];
      $invalid = [];
      foreach (explode("\n", trim($form_state->getValue('advisory_emails'))) as $email) {
        $email = trim($email);
        if (!empty($email)) {
          if ($this->emailValidator->isValid($email)) {
            $valid[] = $email;
          }
          else {
            $invalid[] = $email;
          }
        }
      }
      if (empty($invalid)) {
        $form_state->set('advisory_emails', $valid);
      }
      else {
        $form_state->setErrorByName(
          'update_notify_emails',
          $this->formatPlural(
            count($invalid),
            '%emails is not a valid email address.',
            '%emails are not valid email addresses.',
            ['%emails' => implode(', ', $invalid)]
          )
        );
      }
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
    $this->config('system.advisories')
      ->set('emails', $form_state->get('advisory_emails'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
