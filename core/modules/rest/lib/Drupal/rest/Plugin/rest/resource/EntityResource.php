<?php

/**
 * @file
 * Definition of Drupal\rest\Plugin\rest\resource\EntityResource.
 */

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\Plugin\ResourceBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Represents entities as resources.
 *
 * @Plugin(
 *  id = "entity",
 *  label = "Entity",
 *  derivative = "Drupal\rest\Plugin\Derivative\EntityDerivative"
 * )
 */
class EntityResource extends ResourceBase {

  /**
   * Responds to entity DELETE requests.
   *
   * @param mixed $id
   *   The entity ID.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete($id) {
    $definition = $this->getDefinition();
    $entity = entity_load($definition['entity_type'], $id);
    if ($entity) {
      try {
        $entity->delete();
        // Delete responses have an empty body.
        return new Response('', 204);
      }
      catch (EntityStorageException $e) {
        throw new HttpException(500, 'Internal Server Error', $e);
      }
    }
    throw new NotFoundHttpException(t('Entity with ID @id not found', array('@id' => $id)));
  }
}
