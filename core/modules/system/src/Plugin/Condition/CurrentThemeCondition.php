<?php

namespace Drupal\system\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Theme\ThemeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Current Theme' condition.
 */
#[Condition(
  id: "current_theme",
  label: new TranslatableMarkup("Current Theme"),
)]
class CurrentThemeCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The theme manager.
   *
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a CurrentThemeCondition condition plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Theme\ThemeManagerInterface $theme_manager
   *   The theme manager.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeManagerInterface $theme_manager, ThemeHandlerInterface $theme_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->themeManager = $theme_manager;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme.manager'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['theme' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['theme'] = [
      '#type' => 'select',
      '#title' => $this->t('Theme'),
      '#default_value' => $this->configuration['theme'],
      '#options' => array_map(function ($theme_info) {
        return $theme_info->info['name'];
      }, $this->themeHandler->listInfo()),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['theme'] = $form_state->getValue('theme');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    if (!$this->configuration['theme']) {
      return TRUE;
    }

    return $this->themeManager->getActiveTheme()->getName() == $this->configuration['theme'];
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->isNegated()) {
      return $this->t('The current theme is not @theme', ['@theme' => $this->configuration['theme']]);
    }

    return $this->t('The current theme is @theme', ['@theme' => $this->configuration['theme']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    $contexts = parent::getCacheContexts();
    $contexts[] = 'theme';
    return $contexts;
  }

}
