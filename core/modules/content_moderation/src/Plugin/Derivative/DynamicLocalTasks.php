<?php

namespace Drupal\content_moderation\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates moderation-related local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The base plugin ID.
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
   * The moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInfo;

  /**
   * Creates a FieldUiLocalTask object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information
   *   The moderation information service.
   */
  public function __construct($base_plugin_id, EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, ModerationInformationInterface $moderation_information) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stringTranslation = $string_translation;
    $this->basePluginId = $base_plugin_id;
    $this->moderationInfo = $moderation_information;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('string_translation'),
      $container->get('content_moderation.moderation_information')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $this->derivatives = [];

    $latest_version_entities = array_filter($this->entityTypeManager->getDefinitions(), function (EntityTypeInterface $type) {
      return $this->moderationInfo->canModerateEntitiesOfEntityType($type) && $type->hasLinkTemplate('latest-version');
    });

    foreach ($latest_version_entities as $entity_type_id => $entity_type) {
      $this->derivatives["$entity_type_id.latest_version_tab"] = [
        'route_name' => "entity.$entity_type_id.latest_version",
        'title' => $this->t('Latest version'),
        'base_route' => "entity.$entity_type_id.canonical",
        'weight' => 1,
      ] + $base_plugin_definition;
    }

    return $this->derivatives;
  }

}
