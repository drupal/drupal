<?php

declare(strict_types=1);

namespace Drupal\layout_builder\Plugin\ConfigAction;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\Plugin\ConfigAction\Deriver\AddComponentDeriver;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds a component to a layout builder section.
 *
 * An example of using this in a recipe's config actions would be:
 * @code
 * dashboard.dashboard.welcome:
 *   addComponentToLayout:
 *     section: 0
 *     position: 4
 *     component:
 *       region:
 *         layout_twocol_section: 'second'
 *       default_region: content
 *       configuration:
 *         id: dashboard_text_block
 *         label: 'My new dashboard block'
 *         label_display: 'visible'
 *         provider: 'dashboard'
 *         context_mapping: { }
 *         text:
 *           value: '<p>My new block text</p>'
 *           format: 'basic_html'
 * @endcode
 * This will add a component to a layout region, given by the `section` index.
 * The `position` will determine where it will be inserted, starting at 0. If is
 * higher than the actual number of components in the region, it will be placed
 * last.
 * The `component` defines the actual component we are adding to the layout.
 * Sections can have multiple regions. A `region` mapping will determine which
 * region to use based on the id of the layout. If no matching is found, it will
 * use the `default_region`.
 * The `configuration` array will include the plugin configuration, including a
 * mandatory `id` for the plugin ID. It should validate against the config
 * schema of the plugin.
 * The `additional` array will be copied as is, as that is ignored by config
 * schema.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'add_layout_component',
  admin_label: new TranslatableMarkup('Add component to layout'),
  deriver: AddComponentDeriver::class,
)]
final class AddComponent implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigManagerInterface $configManager,
    private readonly UuidInterface $uuidGenerator,
    private readonly string $pluginId,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    assert(is_array($plugin_definition));
    return new static(
      $container->get(ConfigManagerInterface::class),
      $container->get(UuidInterface::class),
      $plugin_id,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    assert(is_array($value));
    $section_delta = $value['section'];
    $position = $value['position'];

    assert(is_int($section_delta));
    assert(is_int($position));

    $entity = $this->configManager->loadConfigEntityByName($configName);
    if (!$entity instanceof SectionListInterface) {
      throw new ConfigActionException("No entity found for applying the addComponentToLayout action.");
    }

    $section = $entity->getSection($section_delta);
    $component = $value['component'];
    $region = $component['default_region'] ?? NULL;
    if (array_key_exists('region', $component) && is_array($component['region'])) {
      // Since the recipe author might not know ahead of time what layout the
      // section is using, they should supply a map whose keys are layout IDs
      // and values are region names, so we know where to place this component.
      // If the section layout ID is not in the map, they should supply the
      // name of a fallback region. If all that fails, give up with an
      // exception.
      $region = $component['region'][$section->getLayoutId()] ??
        $component['default_region'] ??
        throw new ConfigActionException("Cannot determine which region of the section to place this component into, because no default region was provided.");
    }
    if ($region === NULL) {
      throw new ConfigActionException("Cannot determine which region of the section to place this component into, because no region was provided.");
    }
    if (!isset($value['component']['configuration']) || !isset($value['component']['configuration']['id'])) {
      throw new ConfigActionException("Cannot determine the component configuration, or misses a plugin ID.");
    }
    // If no weight were set, there would be a warning. So we set a
    // default, which will be overridden in insertComponent anyway.
    // We also need to generate the UUID here, or it could be null.
    $uuid = $component['uuid'] ?? $this->uuidGenerator->generate();
    $component = new SectionComponent($uuid, $region, $component['configuration'], $component['additional'] ?? []);
    // If the position is higher than the number of components, just put it last
    // instead of failing.
    $position = min($position, count($section->getComponentsByRegion($region)));
    $section->insertComponent($position, $component);
    $entity->setSection($section_delta, $section);
    $entity->save();
  }

}
