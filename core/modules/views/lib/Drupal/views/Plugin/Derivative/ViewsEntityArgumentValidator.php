<?php

/**
 * @file
 * Contains \Drupal\views\Plugin\Derivative\ViewsEntityArgumentValidator.
 */

namespace Drupal\views\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DerivativeBase;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides views argument validator plugin definitions for all entity types.
 *
 * @ingroup views_argument_validator_plugins
 *
 * @see \Drupal\views\Plugin\views\argument_validator\Entity
 */
class ViewsEntityArgumentValidator extends DerivativeBase implements ContainerDerivativeInterface {
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
   * The string translation.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

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
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation_manager
   *   The string translation.
   */
  public function __construct($base_plugin_id, EntityManagerInterface $entity_manager, TranslationInterface $translation_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->entityManager = $entity_manager;
    $this->translationManager = $translation_manager;
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

  /**
   * Translates a string to the current language or to a given language.
   *
   * See the t() documentation for details.
   */
  protected function t($string, array $args = array(), array $options = array()) {
    return $this->translationManager->translate($string, $args, $options);
  }

}
