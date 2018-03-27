<?php

namespace Drupal\content_moderation\ParamConverter;

use Drupal\Core\ParamConverter\EntityConverter;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 *
 * @internal
 *   This class only exists to provide backwards compatibility with the
 *   load_pending_revision flag, the predecessor to load_latest_revision. The
 *   core entity converter now natively loads the latest revision of an entity
 *   when the load_latest_revision flag is present. This flag is also added
 *   automatically to all entity forms.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
<<<<<<< HEAD
=======
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
    parent::__construct($entity_manager);
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
      return $operation == 'edit' && $entity_type && $entity_type->isRevisionable();
    }
  }

  /**
>>>>>>> e6affc593631de76bc37f1e5340dde005ad9b0bd
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($definition['load_pending_revision'])) {
      @trigger_error('The load_pending_revision flag has been deprecated. You should use load_latest_revision instead.', E_USER_DEPRECATED);
      $definition['load_latest_revision'] = TRUE;
    }
    return parent::convert($value, $definition, $name, $defaults);
  }

}
