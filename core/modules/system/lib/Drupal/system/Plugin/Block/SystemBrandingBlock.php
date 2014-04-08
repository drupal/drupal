<?php

/**
 * @file
 * Contains \Drupal\system\Plugin\Block\SystemBrandingBlock.
 */

namespace Drupal\system\Plugin\Block;

use Drupal\Component\Utility\NestedArray;
use Drupal\block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Utility\Xss;
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
   * The URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * Stores the configuration factory.
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
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, UrlGeneratorInterface $url_generator, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->urlGenerator = $url_generator;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('url_generator'),
      $container->get('current_user')
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
      // Modify the default max age for the 'Site branding' block: the site
      // logo, name and slogan are static for a given language, except when the
      // theme settings are updated (global theme settings or theme-specific
      // settings). Cache tags for those cases ensure that a cached version of
      // this block is invalidated automatically.
      'cache' => array('max_age' => \Drupal\Core\Cache\Cache::PERMANENT),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    // Get the theme.
    $theme = $form_state['block_theme'];

    // Get permissions.
    $administer_themes_access = $this->currentUser->hasPermission('administer themes');
    $administer_site_configuration_access = $this->currentUser->hasPermission('administer site configuration');

    if ($administer_themes_access) {
      // Get paths to theme settings pages.
      $appearance_settings_url = $this->urlGenerator->generateFromRoute('system.theme_settings');
      $theme_settings_url = $this->urlGenerator->generateFromRoute('system.theme_settings_theme', array('theme' => $theme));

      // Provide links to the Appearance Settings and Theme Settings pages
      // if the user has access to administer themes.
      $site_logo_description = $this->t('Defined on the <a href="@appearance">Appearance Settings</a> or <a href="@theme">Theme Settings</a> page.', array('@appearance' => $appearance_settings_url, '@theme' => $theme_settings_url));
    }
    else {
      // Explain that the user does not have access to the Appearance and Theme
      // Settings pages.
      $site_logo_description = $this->t('Defined on the Appearance or Theme Settings page. You do not have the appropriate permissions to change the site logo.');
    }
    if ($administer_site_configuration_access) {
      // Get paths to settings pages.
      $site_information_url = $this->urlGenerator->generateFromRoute('system.site_information_settings');

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
  public function blockSubmit($form, &$form_state) {
    $this->configuration['use_site_logo'] = $form_state['values']['block_branding']['use_site_logo'];
    $this->configuration['use_site_name'] = $form_state['values']['block_branding']['use_site_name'];
    $this->configuration['use_site_slogan'] = $form_state['values']['block_branding']['use_site_slogan'];
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
      '#alt' => t('Home'),
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
    // The theme-specific cache tag is set automatically for each block, but the
    // output of this block also depends on the global theme settings.
    $tags = array(
      'theme_global_setting' => TRUE,
    );
    return NestedArray::mergeDeep(parent::getCacheTags(), $tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function getRequiredCacheContexts() {
    // The 'Site branding' block must be cached per theme and per language: the
    // site logo, name and slogan are defined on a per-theme basis, and the name
    // and slogan may be translated.
    // We don't need to return 'cache_context.theme' also, because that cache
    // context is automatically applied to all blocks.
    // @see \Drupal\block\BlockViewBuilder::viewMultiple()
    return array('cache_context.language');
  }

}
