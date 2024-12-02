<?php

declare(strict_types=1);

namespace Drupal\navigation\Plugin\ConfigAction;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\Action\Attribute\ConfigAction;
use Drupal\Core\Config\Action\ConfigActionException;
use Drupal\Core\Config\Action\ConfigActionPluginInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\layout_builder\SectionComponent;
use Drupal\layout_builder\SectionStorage\SectionStorageManagerInterface;
use Drupal\layout_builder\SectionStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @internal
 *   This API is experimental.
 */
#[ConfigAction(
  id: 'addNavigationBlock',
  admin_label: new TranslatableMarkup('Add navigation block'),
)]
final class AddNavigationBlock implements ConfigActionPluginInterface, ContainerFactoryPluginInterface {

  public function __construct(
    protected readonly SectionStorageManagerInterface $sectionStorageManager,
    protected readonly UuidInterface $uuidGenerator,
  ) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $container->get(SectionStorageManagerInterface::class),
      $container->get(UuidInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function apply(string $configName, mixed $value): void {
    if ($configName !== 'navigation.block_layout') {
      throw new ConfigActionException('addNavigationBlock can only be executed for the navigation.block_layout config.');
    }
    // Load the navigation section storage.
    $navigation_storage = $this->sectionStorageManager->load('navigation', [
      'navigation' => new Context(new ContextDefinition('string'), 'navigation'),
    ]);
    if (!$navigation_storage instanceof SectionStorageInterface) {
      throw new ConfigActionException('Unable to load Navigation Layout storage.');
    }

    $section = $navigation_storage->getSection(0);
    // Create the component from the recipe values.
    $delta = $value['delta'] ?? 0;
    // Weight is set to 0 because it is irrelevant now. It will be adjusted to
    // its final value in insertComponent() or appendComponent().
    $component = [
      'uuid' => $this->uuidGenerator->generate(),
      'region' => $section->getDefaultRegion(),
      'weight' => 0,
      'configuration' => $value['configuration'] ?? [],
      'additional' => $value['additional'] ?? [],
    ];

    // Insert the new component in Navigation.
    $new_component = SectionComponent::fromArray($component);
    try {
      $section->insertComponent($delta, $new_component);
    }
    catch (\OutOfBoundsException) {
      $section->appendComponent($new_component);
    }
    $navigation_storage->save();
  }

}
