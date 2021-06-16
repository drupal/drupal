<?php

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides views argument validator plugin definitions for all entity types.
 *
 * @ingroup views_argument_validator_plugins
 *
 * @see \Drupal\views\Plugin\views\argument_validator\Entity
 */
class ViewsEntityArgumentValidator extends DeriverBase implements ContainerDeriverInterface {
  use StringTranslationTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = [];

  /**
   * Constructs a ViewsEntityArgumentValidator object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation) {
    $this->basePluginId = $base_plugin_id;
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $this->derivatives = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id] = [
        'id' => 'entity:' . $entity_type_id,
        'provider' => 'views',
        'title' => $entity_type->getLabel(),
        'help' => $this->t('Validate @label', ['@label' => $entity_type->getLabel()]),
        'entity_type' => $entity_type_id,
        'class' => $base_plugin_definition['class'],
      ];
    }

    return $this->derivatives;
  }

}
