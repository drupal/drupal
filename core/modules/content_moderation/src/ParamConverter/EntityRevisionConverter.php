<?php

namespace Drupal\content_moderation\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\content_moderation\ModerationInformationInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * Moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected $moderationInformation;

  /**
   * EntityRevisionConverter constructor.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager, needed by the parent class.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_info
   *   The moderation info utility service.
   */
  public function __construct(EntityManagerInterface $entity_manager, ModerationInformationInterface $moderation_info) {
    parent::__construct($entity_manager, \Drupal::languageManager());
    $this->moderationInformation = $moderation_info;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->hasPendingRevisionFlag($definition) || $this->isEditFormPage($route);
  }

  /**
   * Determines if the route definition includes a pending revision flag.
   *
   * This is a custom flag defined by the Content Moderation module to load
   * pending revisions rather than the default revision on a given route.
   *
   * @param array $definition
   *   The parameter definition provided in the route options.
   *
   * @return bool
   *   TRUE if the pending revision flag is set, FALSE otherwise.
   */
  protected function hasPendingRevisionFlag(array $definition) {
    return (isset($definition['load_pending_revision']) && $definition['load_pending_revision']);
  }

  /**
   * Determines if a given route is the edit-form for an entity.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route definition.
   *
   * @return bool
   *   Returns TRUE if the route is the edit form of an entity, FALSE otherwise.
   */
  protected function isEditFormPage(Route $route) {
    if ($default = $route->getDefault('_entity_form')) {
      // If no operation is provided, use 'default'.
      $default .= '.default';
      list($entity_type_id, $operation) = explode('.', $default);
      if (!$this->entityManager->hasDefinition($entity_type_id)) {
        return FALSE;
      }
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      return in_array($operation, ['default', 'edit']) && $entity_type && $entity_type->isRevisionable();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity = parent::convert($value, $definition, $name, $defaults);

    if ($entity && $this->moderationInformation->isModeratedEntity($entity) && !$this->moderationInformation->isLatestRevision($entity)) {
      $entity_type_id = $this->getEntityTypeFromDefaults($definition, $name, $defaults);
      $latest_revision = $this->moderationInformation->getLatestRevision($entity_type_id, $value);

      if ($latest_revision instanceof EntityInterface) {
        // If the entity type is translatable, ensure we return the proper
        // translation object for the current context.
        if ($entity instanceof TranslatableInterface) {
          $latest_revision = $this->entityManager->getTranslationFromContext($latest_revision, NULL, ['operation' => 'entity_upcast']);
        }
        $entity = $latest_revision;
      }
    }

    return $entity;
  }

}
