<?php

namespace Drupal\system\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure System settings for this site.
 */
class MenuLinksetSettingsForm extends ConfigFormBase {

  /**
   * Constructs a MenuLinksetSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typedConfigManager
   *   The typed config manager.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routerBuilder
   *   The router builder service.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TypedConfigManagerInterface $typedConfigManager,
    protected readonly RouteBuilderInterface $routerBuilder
  ) {
    parent::__construct($config_factory, $typedConfigManager);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_linkset_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['system.feature_flags'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['linkset']['enable_endpoint'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable the menu linkset endpoint'),
      '#description' => $this->t('See the <a href="@docs-link">decoupled menus documentation</a> for more information.', [
        '@docs-link' => 'https://www.drupal.org/docs/develop/decoupled-drupal/decoupled-menus',
      ]),
      '#default_value' => $this->config('system.feature_flags')->get('linkset_endpoint'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('system.feature_flags')
      ->set('linkset_endpoint', $form_state->getValue('enable_endpoint'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
