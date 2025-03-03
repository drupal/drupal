<?php

declare(strict_types=1);

namespace Drupal\content_moderation\Plugin\ConfigAction;

// cspell:ignore inflector

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\String\Inflector\EnglishInflector;

/**
 * Deriver for moderation config actions plugins.
 */
final class AddModerationDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

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
  public function getDerivativeDefinitions($base_plugin_definition) {
    $inflector = new EnglishInflector();

    foreach ($this->entityTypeManager->getDefinitions() as $id => $entity_type) {
      if ($bundle_entity_type = $entity_type->getBundleEntityType()) {
        /** @var \Drupal\Core\Entity\EntityTypeInterface $bundle_entity_type */
        $bundle_entity_type = $this->entityTypeManager->getDefinition($bundle_entity_type);
        // Convert unique plugin IDs, like `taxonomy_vocabulary`, into strings
        // like `TaxonomyVocabulary`.
        $suffix = Container::camelize($bundle_entity_type->id());
        [$suffix] = $inflector->pluralize($suffix);
        $this->derivatives["add{$suffix}"] = [
          'target_entity_type' => $id,
          'admin_label' => $this->t('Add moderation to all @bundles', [
            '@bundles' => $bundle_entity_type->getPluralLabel() ?: $bundle_entity_type->id(),
          ]),
        ] + $base_plugin_definition;
      }
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
