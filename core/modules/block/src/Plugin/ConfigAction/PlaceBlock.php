<?php

declare(strict_types=1);

namespace Drupal\block\Plugin\ConfigAction;

use Drupal\block\BlockInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Places a block in either the admin or default theme.
 *
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'placeBlock',
  admin_label: new TranslatableMarkup('Place a block'),
  entity_types: ['block'],
  deriver: PlaceBlockDeriver::class,
)]
final class PlaceBlock implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    private readonly ConfigActionPluginInterface $createAction,
    private readonly string $whichTheme,
    private readonly ConfigFactoryInterface $configFactory,
    private readonly ConfigEntityStorageInterface $blockStorage,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('plugin.manager.config_action')->createInstance('entity_create:createIfNotExists'),
      $plugin_definition['which_theme'],
      $container->get(ConfigFactoryInterface::class),
      $container->get(EntityTypeManagerInterface::class)->getStorage('block'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    assert(is_array($value));

    $theme = $this->configFactory->get('system.theme')->get($this->whichTheme);
    $value['theme'] = $theme;

    if (array_key_exists('region', $value) && is_array($value['region'])) {
      // Since the recipe author might not know ahead of time what theme the
      // block is in, they should supply a map whose keys are theme names and
      // values are region names, so we know where to place this block. If the
      // target theme is not in the map, they should supply the name of a
      // fallback region. If all that fails, give up with an exception.
      $value['region'] = $value['region'][$theme] ?? $value['default_region'] ?? throw new ConfigActionException("Cannot determine which region to place this block into, because no default region was provided.");
    }

    // Allow the recipe author to position the block in the region without
    // needing to know exact weights.
    if (array_key_exists('position', $value)) {
      $blocks = $this->blockStorage->loadByProperties([
        'theme' => $theme,
        'region' => $value['region'],
      ]);
      if ($blocks) {
        // Sort the blocks by weight. Don't use \Drupal\block\Entity\Block::sort()
        // here because it seems to be intended to sort blocks in the UI, where
        // we really just want to get the weights right in this situation.
        uasort($blocks, fn (BlockInterface $a, BlockInterface $b) => $a->getWeight() <=> $b->getWeight());

        $value['weight'] = match ($value['position']) {
          'first' => reset($blocks)->getWeight() - 1,
          'last' => end($blocks)->getWeight() + 1,
        };
      }
    }
    // Remove values that are not valid properties of block entities.
    unset($value['position'], $value['default_region']);
    // Ensure a weight is set by default.
    $value += ['weight' => 0];

    $this->createAction->apply($configName, $value);
  }

}
