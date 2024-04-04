<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Entity\Exception\AmbiguousBundleClassException;
use Drupal\Core\Entity\Exception\AmbiguousEntityClassException;
use Drupal\Core\Entity\Exception\NoCorrespondingEntityClassException;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides helper methods for loading entity types.
 *
 * @see \Drupal\Core\Entity\EntityTypeManagerInterface
 */
class EntityTypeRepository implements EntityTypeRepositoryInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Contains cached mappings of class names to entity types.
   *
   * @var array
   */
  protected $classNameEntityTypeMap = [];

  public function __construct(EntityTypeManagerInterface $entity_type_manager, protected ?EntityTypeBundleInfoInterface $entityTypeBundleInfo = NULL) {
    $this->entityTypeManager = $entity_type_manager;
    if (!isset($this->entityTypeBundleInfo)) {
      @trigger_error('Calling EntityTypeRepository::__construct() without the $entityTypeBundleInfo argument is deprecated in drupal:10.3.0 and is required in drupal:11.0.0. See https://www.drupal.org/node/3365164', E_USER_DEPRECATED);
      $this->entityTypeBundleInfo = \Drupal::service('entity_type.bundle.info');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels($group = FALSE) {
    $options = [];
    $definitions = $this->entityTypeManager->getDefinitions();

    foreach ($definitions as $entity_type_id => $definition) {
      if ($group) {
        $options[(string) $definition->getGroupLabel()][$entity_type_id] = $definition->getLabel();
      }
      else {
        $options[$entity_type_id] = $definition->getLabel();
      }
    }

    if ($group) {
      foreach ($options as &$group_options) {
        // Sort the list alphabetically by group label.
        array_multisort($group_options, SORT_ASC, SORT_NATURAL);
      }

      // Make sure that the 'Content' group is situated at the top.
      $content = $this->t('Content', [], ['context' => 'Entity type group']);
      $options = [(string) $content => $options[(string) $content]] + $options;
    }

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeFromClass($class_name) {
    // Check the already calculated classes first.
    if (isset($this->classNameEntityTypeMap[$class_name])) {
      return $this->classNameEntityTypeMap[$class_name];
    }

    $same_class = 0;
    $entity_type_id = NULL;
    $definitions = $this->entityTypeManager->getDefinitions();
    foreach ($definitions as $entity_type) {
      if ($entity_type->getOriginalClass() == $class_name || $entity_type->getClass() == $class_name) {
        $entity_type_id = $entity_type->id();
        if ($same_class++) {
          throw new AmbiguousEntityClassException($class_name);
        }
      }
    }

    // If no match was found check if it is a bundle class. This needs to be in
    // a separate loop to avoid false positives, since an entity class can
    // subclass another entity class.
    if (!$entity_type_id) {
      $bundle_info = $this->entityTypeBundleInfo->getAllBundleInfo();
      foreach ($bundle_info as $info_entity_type_id => $bundles) {
        foreach ($bundles as $info) {
          if (isset($info['class']) && $info['class'] === $class_name) {
            $entity_type_id = $info_entity_type_id;
            if ($same_class++) {
              throw new AmbiguousBundleClassException($class_name);
            }
          }
        }
      }
    }

    // Return the matching entity type ID if there is one.
    if ($entity_type_id) {
      $this->classNameEntityTypeMap[$class_name] = $entity_type_id;
      return $entity_type_id;
    }

    throw new NoCorrespondingEntityClassException($class_name);
  }

}
