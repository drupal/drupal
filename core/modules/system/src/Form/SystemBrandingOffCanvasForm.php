<?php

namespace Drupal\system\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The settings_tray form handler for the SystemBrandingBlock.
 *
 * @internal
 */
class SystemBrandingOffCanvasForm extends PluginFormBase implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The block plugin.
   *
   * @var \Drupal\Core\Block\BlockPluginInterface
   */
  protected $plugin;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * SystemBrandingOffCanvasForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(ConfigFactoryInterface $config_factory, AccountInterface $current_user) {
    $this->configFactory = $config_factory;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = $this->plugin->buildConfigurationForm($form, $form_state);

    $form['block_branding']['#type'] = 'details';
    $form['block_branding']['#weight'] = 10;

    // Unset links to Site Information form, we can make these changes here.
    unset($form['block_branding']['use_site_name']['#description'], $form['block_branding']['use_site_slogan']['#description']);

    $site_config = $this->configFactory->getEditable('system.site');
    // Load the immutable config to load the overrides.
    $site_config_immutable = $this->configFactory->get('system.site');
    $form['site_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Site details'),
      '#open' => TRUE,
      '#access' => $this->currentUser->hasPermission('administer site configuration') && !$site_config_immutable->hasOverrides('name') && !$site_config_immutable->hasOverrides('slogan'),
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
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->plugin->validateConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $site_config = $this->configFactory->get('system.site');
    if (AccessResult::allowedIf(!$site_config->hasOverrides('name') && !$site_config->hasOverrides('slogan'))->isAllowed()) {
      $site_info = $form_state->getValue('site_information');
      $this->configFactory->getEditable('system.site')
        ->set('name', $site_info['site_name'])
        ->set('slogan', $site_info['site_slogan'])
        ->save();
    }

    $this->plugin->submitConfigurationForm($form, $form_state);
  }

}
