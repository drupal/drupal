<?php

namespace Drupal\outside_in\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * The off-canvas form handler for the SystemBrandingBlock.
 *
 * @see outside_in_block_alter()
 */
class SystemBrandingOffCanvasForm extends PluginFormBase implements ContainerInjectionInterface {

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
   * SystemBrandingOffCanvasForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
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
    $form['site_information'] = [
      '#type' => 'details',
      '#title' => t('Site details'),
      '#open' => TRUE,
    ];
    $form['site_information']['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Site name'),
      '#default_value' => $site_config->get('name'),
      '#required' => TRUE,
    ];
    $form['site_information']['site_slogan'] = [
      '#type' => 'textfield',
      '#title' => t('Slogan'),
      '#default_value' => $site_config->get('slogan'),
      '#description' => t("How this is used depends on your site's theme."),
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
    $site_info = $form_state->getValue('site_information');
    $this->configFactory->getEditable('system.site')
      ->set('name', $site_info['site_name'])
      ->set('slogan', $site_info['site_slogan'])
      ->save();
    $this->plugin->submitConfigurationForm($form, $form_state);
  }

}
