<?php

namespace Drupal\jsonapi\Exception;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifier;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface;
use Drupal\jsonapi\JsonApiResource\ResourceIdentifierTrait;

/**
 * Enhances the access denied exception with information about the entity.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class EntityAccessDeniedHttpException extends CacheableAccessDeniedHttpException implements ResourceIdentifierInterface {

  use DependencySerializationTrait;
  use ResourceIdentifierTrait;

  /**
   * The error which caused the 403.
   *
   * The error contains:
   *   - entity: The entity which the current user does not have access to.
   *   - pointer: A path in the JSON:API response structure pointing to the
   *     entity.
   *   - reason: (Optional) An optional reason for this failure.
   *
   * @var array
   */
  protected $error = [];

  /**
   * EntityAccessDeniedHttpException constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   The entity, or NULL when an entity is being created.
   * @param \Drupal\Core\Access\AccessResultInterface $entity_access
   *   The access result.
   * @param string $pointer
   *   (optional) The pointer.
   * @param string $message
   *   (Optional) The display to display.
   * @param string $relationship_field
   *   (Optional) A relationship field name if access was denied because the
   *   user does not have permission to view an entity's relationship field.
   * @param \Exception|null $previous
   *   The previous exception.
   * @param int $code
   *   The code.
   */
  public function __construct($entity, AccessResultInterface $entity_access, $pointer, $message = 'The current user is not allowed to GET the selected resource.', $relationship_field = NULL, \Exception $previous = NULL, $code = 0) {
    assert(is_null($entity) || $entity instanceof EntityInterface);
    parent::__construct(CacheableMetadata::createFromObject($entity_access), $message, $previous, $code);
    $error = [
      'entity' => $entity,
      'pointer' => $pointer,
      'reason' => NULL,
      'relationship_field' => $relationship_field,
    ];
    if ($entity_access instanceof AccessResultReasonInterface) {
      $error['reason'] = $entity_access->getReason();
    }
    $this->error = $error;
    // @todo: remove this ternary operation in https://www.drupal.org/project/drupal/issues/2997594.
    $this->resourceIdentifier = $entity ? ResourceIdentifier::fromEntity($entity) : NULL;
  }

  /**
   * Returns the error.
   *
   * @return array
   *   The error.
   */
  public function getError() {
    return $this->error;
  }

}
