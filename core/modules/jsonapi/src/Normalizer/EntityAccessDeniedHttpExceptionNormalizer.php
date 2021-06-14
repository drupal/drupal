<?php

namespace Drupal\jsonapi\Normalizer;

use Drupal\Core\Url;
use Drupal\jsonapi\Exception\EntityAccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Normalizes an EntityAccessDeniedException.
 *
 * Normalizes an EntityAccessDeniedException in compliance with the JSON:API
 * specification. A source pointer is added to help client applications report
 * which entity was access denied.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format/#error-objects
 */
class EntityAccessDeniedHttpExceptionNormalizer extends HttpExceptionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedInterfaceOrClass = EntityAccessDeniedHttpException::class;

  /**
   * {@inheritdoc}
   */
  protected function buildErrorObjects(HttpException $exception) {
    $errors = parent::buildErrorObjects($exception);

    if ($exception instanceof EntityAccessDeniedHttpException) {
      $error = $exception->getError();
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $error['entity'];
      $pointer = $error['pointer'];
      $reason = $error['reason'];
      $relationship_field = isset($error['relationship_field'])
        ? $error['relationship_field']
        : NULL;

      if (isset($entity)) {
        $entity_type_id = $entity->getEntityTypeId();
        $bundle = $entity->bundle();
        /** @var \Drupal\jsonapi\ResourceType\ResourceType $resource_type */
        $resource_type = \Drupal::service('jsonapi.resource_type.repository')->get($entity_type_id, $bundle);
        $resource_type_name = $resource_type->getTypeName();
        $route_name = !is_null($relationship_field)
          ? "jsonapi.$resource_type_name.$relationship_field.related"
          : "jsonapi.$resource_type_name.individual";
        $url = Url::fromRoute($route_name, ['entity' => $entity->uuid()]);
        $errors[0]['links']['via']['href'] = $url->setAbsolute()->toString(TRUE)->getGeneratedUrl();
      }
      $errors[0]['source']['pointer'] = $pointer;

      if ($reason) {
        $errors[0]['detail'] = isset($errors[0]['detail']) ? $errors[0]['detail'] . ' ' . $reason : $reason;
      }
    }

    return $errors;
  }

}
