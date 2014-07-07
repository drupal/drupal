<?php

/**
 * @file
 * Contains Drupal\Core\ParamConverter\EntityConverter.
 */

namespace Drupal\Core\ParamConverter;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting entity ids to full objects.
 */
class EntityConverter implements ParamConverterInterface {

  /**
   * Entity manager which performs the upcasting in the end.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new EntityConverter.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The entity manager.
   */
  public function __construct(EntityManagerInterface $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults, Request $request) {
    $entity_type = substr($definition['type'], strlen('entity:'));
    if ($storage = $this->entityManager->getStorage($entity_type)) {
      return $storage->load($value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    if (!empty($definition['type']) && strpos($definition['type'], 'entity:') === 0) {
      $entity_type = substr($definition['type'], strlen('entity:'));
      return $this->entityManager->hasDefinition($entity_type);
    }
    return FALSE;
  }

}
