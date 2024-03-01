<?php

namespace Drupal\Core\Entity\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\Plugin\Condition\Deriver\EntityBundle as EntityBundleDeriver;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the 'Entity Bundle' condition.
 */
#[Condition(
  id: "entity_bundle",
  deriver: EntityBundleDeriver::class,
)]
class EntityBundle extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Creates a new EntityBundle instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeBundleInfoInterface $entity_type_bundle_info) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundles = $this->entityTypeBundleInfo->getBundleInfo($this->getDerivativeId());
    $form['bundles'] = [
      '#title' => $this->pluginDefinition['label'],
      '#type' => 'checkboxes',
      '#options' => array_combine(array_keys($bundles), array_column($bundles, 'label')),
      '#default_value' => $this->configuration['bundles'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Returns true if no bundles are selected and negate option is disabled.
    if (empty($this->configuration['bundles']) && !$this->isNegated()) {
      return TRUE;
    }
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->getContextValue($this->getDerivativeId());
    return !empty($this->configuration['bundles'][$entity->bundle()]);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if (count($this->configuration['bundles']) > 1) {
      $bundles = $this->configuration['bundles'];
      $last = array_pop($bundles);
      $bundles = implode(', ', $bundles);

      if (empty($this->configuration['negate'])) {
        return $this->t('@bundle_type is @bundles or @last', [
          '@bundle_type' => $this->pluginDefinition['label'],
          '@bundles' => $bundles,
          '@last' => $last,
        ]);
      }
      else {
        return $this->t('@bundle_type is not @bundles or @last', [
          '@bundle_type' => $this->pluginDefinition['label'],
          '@bundles' => $bundles,
          '@last' => $last,
        ]);
      }
    }
    $bundle = reset($this->configuration['bundles']);

    if (empty($this->configuration['negate'])) {
      return $this->t('@bundle_type is @bundle', [
        '@bundle_type' => $this->pluginDefinition['label'],
        '@bundle' => $bundle,
      ]);
    }
    else {
      return $this->t('@bundle_type is not @bundle', [
        '@bundle_type' => $this->pluginDefinition['label'],
        '@bundle' => $bundle,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'bundles' => [],
    ] + parent::defaultConfiguration();
  }

}
