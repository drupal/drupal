<?php

declare(strict_types=1);

namespace Drupal\Core\Config\Action\Plugin\ConfigAction\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates derivatives for the create_for_each_bundle config action.
 *
 * @internal
 *   This API is experimental.
 */
final class CreateForEachBundleDeriver extends DeriverBase implements ContainerDeriverInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): static {
    return new static(
      $container->get(EntityTypeManagerInterface::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    // The action should only be available for entity types that are bundles of
    // another entity type, such as node types, media types, taxonomy
    // vocabularies, and so forth.
    $bundle_entity_types = array_filter(
      $this->entityTypeManager->getDefinitions(),
      fn (EntityTypeInterface $entity_type) => is_string($entity_type->getBundleOf()),
    );
    $base_plugin_definition['entity_types'] = array_keys($bundle_entity_types);

    $this->derivatives['createForEachIfNotExists'] = $base_plugin_definition + [
      'create_action' => 'createIfNotExists',
    ];
    $this->derivatives['createForEach'] = $base_plugin_definition + [
      'create_action' => 'create',
    ];
    return $this->derivatives;
  }

}
