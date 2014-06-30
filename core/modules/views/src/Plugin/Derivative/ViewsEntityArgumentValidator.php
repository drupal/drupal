<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
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
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * List of derivative definitions.
   *
   * @var array
   */
  protected $derivatives = array();

  /**
   * Constructs an ViewsEntityArgumentValidator object.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation.
   */
  public function __construct($base_plugin_id, EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity.manager'),
      $container->get('string_translation')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = $this->entityManager->getDefinitions();
    $this->derivatives = array();
    foreach ($entity_types as $entity_type_id => $entity_type) {
      $this->derivatives[$entity_type_id] = array(
        'id' => 'entity:' . $entity_type_id,
        'provider' => 'views',
        'title' => $entity_type->getLabel(),
        'help' => $this->t('Validate @label', array('@label' => $entity_type->getLabel())),
        'entity_type' => $entity_type_id,
        'class' => $base_plugin_definition['class'],
      );
    }

    return $this->derivatives;
  }

}
