<?php

namespace Drupal\field_layout;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\field_layout\Display\EntityDisplayWithLayoutInterface;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds a field layout.
 */
class FieldLayoutBuilder implements ContainerInjectionInterface {

  /**
   * The layout plugin manager.
   *
   * @var \Drupal\Core\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new FieldLayoutBuilder.
   *
   * @param \Drupal\Core\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   *   The layout plugin manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(LayoutPluginManagerInterface $layout_plugin_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.core.layout'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Applies the layout to an entity build.
   *
   * @param array $build
   *   A renderable array representing the entity content or form.
   * @param \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface $display
   *   The entity display holding the display options configured for the entity
   *   components.
   */
  public function buildView(array &$build, EntityDisplayWithLayoutInterface $display) {
    $layout_definition = $this->layoutPluginManager->getDefinition($display->getLayoutId(), FALSE);
    if ($layout_definition && $fields = $this->getFields($build, $display, 'view')) {
      // Add the regions to the $build in the correct order.
      $regions = array_fill_keys($layout_definition->getRegionNames(), []);

      foreach ($fields as $name => $field) {
        // Move the field from the top-level of $build into a region-specific
        // section.
        // @todo Ideally the array structure would remain unchanged, see
        //   https://www.drupal.org/node/2846393.
        $regions[$field['region']][$name] = $build[$name];
        unset($build[$name]);
      }
      // Ensure this will not conflict with any existing array elements by
      // prefixing with an underscore.
      $build['_field_layout'] = $display->getLayout()->build($regions);
    }
  }

  /**
   * Applies the layout to an entity form.
   *
   * @param array $build
   *   A renderable array representing the entity content or form.
   * @param \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface $display
   *   The entity display holding the display options configured for the entity
   *   components.
   */
  public function buildForm(array &$build, EntityDisplayWithLayoutInterface $display) {
    $layout_definition = $this->layoutPluginManager->getDefinition($display->getLayoutId(), FALSE);
    if ($layout_definition && $fields = $this->getFields($build, $display, 'form')) {
      $fill = [];
      $fill['#process'][] = '\Drupal\Core\Render\Element\RenderElement::processGroup';
      $fill['#pre_render'][] = '\Drupal\Core\Render\Element\RenderElement::preRenderGroup';
      // Add the regions to the $build in the correct order.
      $regions = array_fill_keys($layout_definition->getRegionNames(), $fill);

      foreach ($fields as $name => $field) {
        // As this is a form, #group can be used to relocate the fields. This
        // avoids breaking hook_form_alter() implementations by not actually
        // moving the field in the form structure. If a #group is already set,
        // do not overwrite it.
        if (!isset($build[$name]['#group'])) {
          $build[$name]['#group'] = $field['region'];
        }
      }
      // Ensure this will not conflict with any existing array elements by
      // prefixing with an underscore.
      $build['_field_layout'] = $display->getLayout()->build($regions);
    }
  }

  /**
   * Gets the fields that need to be processed.
   *
   * @param array $build
   *   A renderable array representing the entity content or form.
   * @param \Drupal\field_layout\Display\EntityDisplayWithLayoutInterface $display
   *   The entity display holding the display options configured for the entity
   *   components.
   * @param string $display_context
   *   The display context, either 'form' or 'view'.
   *
   * @return array
   *   An array of configurable fields present in the build.
   */
  protected function getFields(array $build, EntityDisplayWithLayoutInterface $display, $display_context) {
    $components = $display->getComponents();

    // Ignore any extra fields from the list of field definitions. Field
    // definitions can have a non-configurable display, but all extra fields are
    // always displayed.
    $field_definitions = array_diff_key(
      $this->entityFieldManager->getFieldDefinitions($display->getTargetEntityTypeId(), $display->getTargetBundle()),
      $this->entityFieldManager->getExtraFields($display->getTargetEntityTypeId(), $display->getTargetBundle())
    );

    $fields_to_exclude = array_filter($field_definitions, function (FieldDefinitionInterface $field_definition) use ($display_context) {
      // Remove fields with a non-configurable display.
      return !$field_definition->isDisplayConfigurable($display_context);
    });
    $components = array_diff_key($components, $fields_to_exclude);

    // Only include fields present in the build.
    $components = array_intersect_key($components, $build);

    return $components;
  }

}
