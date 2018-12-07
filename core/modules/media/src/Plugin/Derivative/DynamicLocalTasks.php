<?php

namespace Drupal\media\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates media-related local tasks.
 */
class DynamicLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The media settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Creates a DynamicLocalTasks object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(TranslationInterface $string_translation, EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    $this->stringTranslation = $string_translation;
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config_factory->get('media.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('string_translation'),
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Provide an edit_form task if standalone media URLs are enabled.
    $this->derivatives["entity.media.canonical"] = [
      'route_name' => "entity.media.canonical",
      'title' => $this->t('Edit'),
      'base_route' => "entity.media.canonical",
      'weight' => 1,
    ] + $base_plugin_definition;

    if ($this->config->get('standalone_url')) {
      $this->derivatives["entity.media.canonical"]['title'] = $this->t('View');

      $this->derivatives["entity.media.edit_form"] = [
        'route_name' => "entity.media.edit_form",
        'title' => $this->t('Edit'),
        'base_route' => 'entity.media.canonical',
        'weight' => 2,
      ] + $base_plugin_definition;
    }

    $this->derivatives["entity.media.delete_form"] = [
      'route_name' => "entity.media.delete_form",
      'title' => $this->t('Delete'),
      'base_route' => "entity.media.canonical",
      'weight' => 10,
    ] + $base_plugin_definition;

    return $this->derivatives;
  }

}
