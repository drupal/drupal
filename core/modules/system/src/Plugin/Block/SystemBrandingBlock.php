<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemBrandingBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to display 'Site branding' elements.
 *
 * @Block(
 *   id = "system_branding_block",
 *   admin_label = @Translation("Site branding")
 * )
 */
class SystemBrandingBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a SystemBrandingBlock instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'use_site_logo' => TRUE,
      'use_site_name' => TRUE,
      'use_site_slogan' => TRUE,
      'label_display' => FALSE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    // Get the theme.
    $theme = $form_state->get('block_theme');

    // Get permissions.
    $url_system_theme_settings = new Url('system.theme_settings');
    $url_system_theme_settings_theme = new Url('system.theme_settings_theme', array('theme' => $theme));

    if ($url_system_theme_settings->access() && $url_system_theme_settings_theme->access()) {
      // Provide links to the Appearance Settings and Theme Settings pages
      // if the user has access to administer themes.
      $site_logo_description = $this->t('Defined on the <a href="@appearance">Appearance Settings</a> or <a href="@theme">Theme Settings</a> page.', array(
        '@appearance' => $url_system_theme_settings->toString(),
        '@theme' => $url_system_theme_settings_theme->toString(),
      ));
    }
    else {
      // Explain that the user does not have access to the Appearance and Theme
      // Settings pages.
      $site_logo_description = $this->t('Defined on the Appearance or Theme Settings page. You do not have the appropriate permissions to change the site logo.');
    }
    $url_system_site_information_settings = new Url('system.site_information_settings');
    if ($url_system_site_information_settings->access()) {
      // Get paths to settings pages.
      $site_information_url = $url_system_site_information_settings->toString();

      // Provide link to Site Information page if the user has access to
      // administer site configuration.
      $site_name_description = $this->t('Defined on the <a href="@information">Site Information</a> page.', array('@information' => $site_information_url));
      $site_slogan_description = $this->t('Defined on the <a href="@information">Site Information</a> page.', array('@information' => $site_information_url));
    }
    else {
      // Explain that the user does not have access to the Site Information
      // page.
      $site_name_description = $this->t('Defined on the Site Information page. You do not have the appropriate permissions to change the site logo.');
      $site_slogan_description = $this->t('Defined on the Site Information page. You do not have the appropriate permissions to change the site logo.');
    }

    $form['block_branding'] = array(
      '#type' => 'fieldset',
      '#title' => $this->t('Toggle branding elements'),
      '#description' => $this->t('Choose which branding elements you want to show in this block instance.'),
    );
    $form['block_branding']['use_site_logo'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Site logo'),
      '#description' => $site_logo_description,
      '#default_value' => $this->configuration['use_site_logo'],
    );

    $form['block_branding']['use_site_name'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Site name'),
      '#description' => $site_name_description,
      '#default_value' => $this->configuration['use_site_name'],
    );
    $form['block_branding']['use_site_slogan'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Site slogan'),
      '#description' => $site_slogan_description,
      '#default_value' => $this->configuration['use_site_slogan'],
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $block_branding = $form_state->getValue('block_branding');
    $this->configuration['use_site_logo'] = $block_branding['use_site_logo'];
    $this->configuration['use_site_name'] = $block_branding['use_site_name'];
    $this->configuration['use_site_slogan'] = $block_branding['use_site_slogan'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = array();
    $site_config = $this->configFactory->get('system.site');

    $logo = theme_get_setting('logo');
    $build['site_logo'] = array(
      '#theme' => 'image',
      '#uri' => $logo['url'],
      '#alt' => $this->t('Home'),
      '#access' => $this->configuration['use_site_logo'],
    );

    $build['site_name'] = array(
      '#markup' => $site_config->get('name'),
      '#access' => $this->configuration['use_site_name'],
    );

    $build['site_slogan'] = array(
      '#markup' => Xss::filterAdmin($site_config->get('slogan')),
      '#access' => $this->configuration['use_site_slogan'],
    );

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return Cache::mergeTags(
      parent::getCacheTags(),
      $this->configFactory->get('system.site')->getCacheTags()
    );
  }

}
