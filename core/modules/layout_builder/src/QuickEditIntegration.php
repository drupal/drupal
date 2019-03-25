<?php

namespace Drupal\layout_builder;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\EntityContext;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountInterface;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Helper methods for Quick Edit module integration.
 *
 * @internal
 */
class QuickEditIntegration implements ContainerInjectionInterface {

  use LoggerChannelTrait;

  /**
   * The section storage manager.
   *
   * @var \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface
   */
  protected $sectionStorageManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new QuickEditIntegration object.
   *
   * @param \Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface $section_storage_manager
   *   The section storage manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(SectionStorageManagerInterface $section_storage_manager, AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->sectionStorageManager = $section_storage_manager;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.layout_builder.section_storage'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * Alters the entity view build for Quick Edit compatibility.
   *
   * When rendering fields outside of normal view modes, Quick Edit requires
   * that modules identify themselves with a view mode ID in the format
   * [module_name]-[information the module needs to rerender], as prescribed by
   * hook_quickedit_render_field().
   *
   * @param array $build
   *   The built entity render array.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display.
   *
   * @see hook_quickedit_render_field()
   * @see layout_builder_quickedit_render_field()
   *
   * @internal
   */
  public function entityViewAlter(array &$build, EntityInterface $entity, EntityViewDisplayInterface $display) {
    if (!$entity instanceof FieldableEntityInterface || !isset($build['_layout_builder'])) {
      return;
    }

    $build['#cache']['contexts'][] = 'user.permissions';
    if (!$this->currentUser->hasPermission('access in-place editing')) {
      return;
    }

    $cacheable_metadata = CacheableMetadata::createFromRenderArray($build);
    $section_list = $this->sectionStorageManager->findByContext(
      [
        'display' => EntityContext::fromEntity($display),
        'entity' => EntityContext::fromEntity($entity),
        'view_mode' => new Context(new ContextDefinition('string'), $display->getMode()),
      ],
      $cacheable_metadata
    );
    $cacheable_metadata->applyTo($build);

    if (empty($section_list)) {
      return;
    }

    // Create a hash of the sections and use it in the unique Quick Edit view
    // mode ID. Any changes to the sections will result in a different hash,
    // forcing Quick Edit's JavaScript to recognize any changes and retrieve
    // up-to-date metadata.
    $sections_hash = hash('sha256', serialize($section_list->getSections()));

    // Track each component by their plugin ID, delta, region, and UUID.
    $plugin_ids_to_update = [];
    foreach (Element::children($build['_layout_builder']) as $delta) {
      $section = $build['_layout_builder'][$delta];
      /** @var \Drupal\Core\Layout\LayoutDefinition $layout */
      $layout = $section['#layout'];
      $regions = $layout->getRegionNames();

      foreach ($regions as $region) {
        if (isset($section[$region])) {
          foreach ($section[$region] as $uuid => $component) {
            if (isset($component['#plugin_id']) && $this->supportQuickEditOnComponent($component, $entity)) {
              $plugin_ids_to_update[$component['#plugin_id']][$delta][$region][$uuid] = $uuid;
            }
          }
        }
      }
    }

    // @todo Remove when https://www.drupal.org/node/3041850 is resolved.
    $plugin_ids_to_update = array_filter($plugin_ids_to_update, function ($info) {
      // Delta, region, and UUID each count as one.
      return count($info, COUNT_RECURSIVE) === 3;
    });

    $plugin_ids_to_update = NestedArray::mergeDeepArray($plugin_ids_to_update, TRUE);
    foreach ($plugin_ids_to_update as $delta => $regions) {
      foreach ($regions as $region => $uuids) {
        foreach ($uuids as $uuid => $component) {
          $build['_layout_builder'][$delta][$region][$uuid]['content']['#view_mode'] = static::getViewModeId($entity, $display, $delta, $uuid, $sections_hash);
        }
      }
    }
    // Alter the Quick Edit view mode ID of all fields outside of the Layout
    // Builder sections to force Quick Edit to request to the field metadata.
    // @todo Remove this logic in https://www.drupal.org/project/node/2966136.
    foreach (Element::children($build) as $field_name) {
      if ($field_name !== '_layout_builder') {
        $field_build = &$build[$field_name];
        if (isset($field_build['#view_mode'])) {
          $field_build['#view_mode'] = "layout_builder-{$display->getMode()}-non_component-$sections_hash";
        }
      }
    }
  }

  /**
   * Generates a Quick Edit view mode ID.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The entity view display.
   * @param int $delta
   *   The delta.
   * @param string $component_uuid
   *   The component UUID.
   * @param string $sections_hash
   *   The hash of the sections; must change whenever the sections change.
   *
   * @return string
   *   The Quick Edit view mode ID.
   *
   * @see \Drupal\layout_builder\QuickEditIntegration::deconstructViewModeId()
   */
  private static function getViewModeId(EntityInterface $entity, EntityViewDisplayInterface $display, $delta, $component_uuid, $sections_hash) {
    return implode('-', [
      'layout_builder',
      $display->getMode(),
      $delta,
      // Replace the dashes in the component UUID because we need to
      // use dashes to join the parts.
      str_replace('-', '_', $component_uuid),
      $entity->id(),
      $sections_hash,
    ]);
  }

  /**
   * Deconstructs the Quick Edit view mode ID into its constituent parts.
   *
   * @param string $quick_edit_view_mode_id
   *   The Quick Edit view mode ID.
   *
   * @return array
   *   An array containing the entity view mode ID, the delta, the component
   *   UUID, and the entity ID.
   *
   * @see \Drupal\layout_builder\QuickEditIntegration::getViewModeId()
   */
  public static function deconstructViewModeId($quick_edit_view_mode_id) {
    list(, $entity_view_mode_id, $delta, $component_uuid, $entity_id) = explode('-', $quick_edit_view_mode_id, 7);
    return [
      $entity_view_mode_id,
      // @todo Explicitly cast delta to an integer, remove this in
      //   https://www.drupal.org/project/drupal/issues/2984509.
      (int) $delta,
      // Replace the underscores with dash to get back the component UUID.
      str_replace('_', '-', $component_uuid),
      $entity_id,
    ];
  }

  /**
   * Re-renders a field rendered by Layout Builder, edited with Quick Edit.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   The field name.
   * @param string $quick_edit_view_mode_id
   *   The Quick Edit view mode ID.
   * @param string $langcode
   *   The language code.
   *
   * @return array
   *   The re-rendered field.
   *
   * @internal
   */
  public function quickEditRenderField(FieldableEntityInterface $entity, $field_name, $quick_edit_view_mode_id, $langcode) {
    list($entity_view_mode, $delta, $component_uuid) = static::deconstructViewModeId($quick_edit_view_mode_id);

    $entity_build = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId())->view($entity, $entity_view_mode, $langcode);
    $this->buildEntityView($entity_build);

    if (isset($entity_build['_layout_builder'][$delta])) {
      foreach (Element::children($entity_build['_layout_builder'][$delta]) as $region) {
        if (isset($entity_build['_layout_builder'][$delta][$region][$component_uuid])) {
          return $entity_build['_layout_builder'][$delta][$region][$component_uuid]['content'];
        }
      }
    }

    $this->getLogger('layout_builder')->warning('The field "%field" failed to render.', ['%field' => $field_name]);
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @todo Replace this hardcoded processing when
   *   https://www.drupal.org/project/drupal/issues/3041635 is resolved.
   *
   * @see \Drupal\Tests\EntityViewTrait::buildEntityView()
   */
  private function buildEntityView(array &$elements) {
    // If the default values for this element have not been loaded yet,
    // populate them.
    if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
      $elements += \Drupal::service('element_info')->getInfo($elements['#type']);
    }

    // Make any final changes to the element before it is rendered. This means
    // that the $element or the children can be altered or corrected before
    // the element is rendered into the final text.
    if (isset($elements['#pre_render'])) {
      foreach ($elements['#pre_render'] as $callable) {
        $elements = call_user_func($callable, $elements);
      }
    }

    // And recurse.
    $children = Element::children($elements, TRUE);
    foreach ($children as $key) {
      $this->buildEntityView($elements[$key]);
    }
  }

  /**
   * Determines whether a component has Quick Edit support.
   *
   * Only field_block components for display configurable fields should be
   * supported.
   *
   * @param array $component
   *   The component render array.
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The entity being displayed.
   *
   * @return bool
   *   Whether Quick Edit is supported on the component.
   *
   * @see \Drupal\layout_builder\Plugin\Block\FieldBlock
   */
  private function supportQuickEditOnComponent(array $component, FieldableEntityInterface $entity) {
    if (isset($component['content']['#field_name'], $component['#base_plugin_id']) && $component['#base_plugin_id'] === 'field_block') {
      return $entity->getFieldDefinition($component['content']['#field_name'])->isDisplayConfigurable('view');
    }
    return FALSE;
  }

}
