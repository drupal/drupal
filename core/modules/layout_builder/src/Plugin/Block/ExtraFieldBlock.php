<?php

namespace Drupal\layout_builder\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block that renders an extra field from an entity.
 *
 * This block handles fields that are provided by implementations of
 * hook_entity_extra_field_info().
 *
 * @see \Drupal\layout_builder\Plugin\Block\FieldBlock
 *   This block plugin handles all other field entities not provided by
 *   hook_entity_extra_field_info().
 *
 * @Block(
 *   id = "extra_field_block",
 *   deriver = "\Drupal\layout_builder\Plugin\Derivative\ExtraFieldBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class ExtraFieldBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ExtraFieldBlock.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    // Get field name from the plugin ID.
    list (, , , $field_name) = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 4);
    assert(!empty($field_name));
    $this->fieldName = $field_name;

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_display' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Gets the entity that has the field.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   */
  protected function getEntity() {
    return $this->getContextValue('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $entity = $this->getEntity();
    // Add a placeholder to replace after the entity view is built.
    // @see layout_builder_entity_view_alter().
    $extra_fields = $this->entityFieldManager->getExtraFields($entity->getEntityTypeId(), $entity->bundle());
    if (!isset($extra_fields['display'][$this->fieldName])) {
      $build = [];
    }
    else {
      $build = [
        '#extra_field_placeholder_field_name' => $this->fieldName,
        // Always provide a placeholder. The Layout Builder will NOT invoke
        // hook_entity_view_alter() so extra fields will not be added to the
        // render array. If the hook is invoked the placeholder will be
        // replaced.
        // @see ::replaceFieldPlaceholder()
        '#markup' => $this->t('Placeholder for the @preview_fallback', ['@preview_fallback' => $this->getPreviewFallbackString()]),
      ];
    }
    CacheableMetadata::createFromObject($this)->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviewFallbackString() {
    $entity = $this->getEntity();
    $extra_fields = $this->entityFieldManager->getExtraFields($entity->getEntityTypeId(), $entity->bundle());
    return new TranslatableMarkup('"@field" field', ['@field' => $extra_fields['display'][$this->fieldName]['label']]);
  }

  /**
   * Replaces all placeholders for a given field.
   *
   * @param array $build
   *   The built render array for the elements.
   * @param array $built_field
   *   The render array to replace the placeholder.
   * @param string $field_name
   *   The field name.
   *
   * @see ::build()
   */
  public static function replaceFieldPlaceholder(array &$build, array $built_field, $field_name) {
    foreach (Element::children($build) as $child) {
      if (isset($build[$child]['#extra_field_placeholder_field_name']) && $build[$child]['#extra_field_placeholder_field_name'] === $field_name) {
        $placeholder_cache = CacheableMetadata::createFromRenderArray($build[$child]);
        $built_cache = CacheableMetadata::createFromRenderArray($built_field);
        $merged_cache = $placeholder_cache->merge($built_cache);
        $build[$child] = $built_field;
        $merged_cache->applyTo($build);
      }
      else {
        static::replaceFieldPlaceholder($build[$child], $built_field, $field_name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return $this->getEntity()->access('view', $account, TRUE);
  }

}
