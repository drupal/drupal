<?php

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Represents entities as resources.
 *
 * @see \Drupal\rest\Plugin\Deriver\EntityDeriver
 *
 * @RestResource(
 *   id = "entity",
 *   label = @Translation("Entity"),
 *   serialization_class = "Drupal\Core\Entity\Entity",
 *   deriver = "Drupal\rest\Plugin\Deriver\EntityDeriver",
 *   uri_paths = {
 *     "canonical" = "/entity/{entity_type}/{entity}",
 *     "https://www.drupal.org/link-relations/create" = "/entity/{entity_type}"
 *   }
 * )
 */
class EntityResource extends ResourceBase implements DependentPluginInterface {

  /**
   * The entity type targeted by this resource.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * Constructs a Drupal\rest\Plugin\rest\resource\EntityResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityType = $entity_type_manager->getDefinition($plugin_definition['entity_type']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(EntityInterface $entity) {
    $entity_access = $entity->access('view', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException();
    }

    $response = new ResourceResponse($entity, 200);
    $response->addCacheableDependency($entity);
    $response->addCacheableDependency($entity_access);

    if ($entity instanceof FieldableEntityInterface) {
      foreach ($entity as $field_name => $field) {
        /** @var \Drupal\Core\Field\FieldItemListInterface $field */
        $field_access = $field->access('view', NULL, TRUE);
        $response->addCacheableDependency($field_access);

        if (!$field_access->isAllowed()) {
          $entity->set($field_name, NULL);
        }
      }
    }

    return $response;
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post(EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    if (!$entity->access('create')) {
      throw new AccessDeniedHttpException();
    }
    $definition = $this->getPluginDefinition();
    // Verify that the deserialized entity is of the type that we expect to
    // prevent security issues.
    if ($entity->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }
    // POSTed entities must not have an ID set, because we always want to create
    // new entities here.
    if (!$entity->isNew()) {
      throw new BadRequestHttpException('Only new entities can be created');
    }

    // Only check 'edit' permissions for fields that were actually
    // submitted by the user. Field access makes no difference between 'create'
    // and 'update', so the 'edit' operation is used here.
    foreach ($entity->_restSubmittedFields as $key => $field_name) {
      if (!$entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on creating field '$field_name'");
      }
    }

    // Validate the received data before saving.
    $this->validate($entity);
    try {
      $entity->save();
      $this->logger->notice('Created entity %type with ID %id.', array('%type' => $entity->getEntityTypeId(), '%id' => $entity->id()));

      // 201 Created responses return the newly created entity in the response
      // body. These responses are not cacheable, so we add no cacheability
      // metadata here.
      $url = $entity->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
      $response = new ModifiedResourceResponse($entity, 201, ['Location' => $url->getGeneratedUrl()]);
      return $response;
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Responds to entity PATCH requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original_entity
   *   The original entity object.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }
    $definition = $this->getPluginDefinition();
    if ($entity->getEntityTypeId() != $definition['entity_type']) {
      throw new BadRequestHttpException('Invalid entity type');
    }
    if (!$original_entity->access('update')) {
      throw new AccessDeniedHttpException();
    }

    // Overwrite the received properties.
    $entity_keys = $entity->getEntityType()->getKeys();
    foreach ($entity->_restSubmittedFields as $field_name) {
      $field = $entity->get($field_name);

      // Entity key fields need special treatment: together they uniquely
      // identify the entity. Therefore it does not make sense to modify any of
      // them. However, rather than throwing an error, we just ignore them as
      // long as their specified values match their current values.
      if (in_array($field_name, $entity_keys, TRUE)) {
        // Unchanged values for entity keys don't need access checking.
        if ($original_entity->get($field_name)->getValue() === $entity->get($field_name)->getValue()) {
          continue;
        }
        // It is not possible to set the language to NULL as it is automatically
        // re-initialized. As it must not be empty, skip it if it is.
        elseif (isset($entity_keys['langcode']) && $field_name === $entity_keys['langcode'] && $field->isEmpty()) {
          continue;
        }
      }

      if (!$original_entity->get($field_name)->access('edit')) {
        throw new AccessDeniedHttpException("Access denied on updating field '$field_name'.");
      }
      $original_entity->set($field_name, $field->getValue());
    }

    // Validate the received data before saving.
    $this->validate($original_entity);
    try {
      $original_entity->save();
      $this->logger->notice('Updated entity %type with ID %id.', array('%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id()));

      // Update responses have an empty body.
      return new ModifiedResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Responds to entity DELETE requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete(EntityInterface $entity) {
    if (!$entity->access('delete')) {
      throw new AccessDeniedHttpException();
    }
    try {
      $entity->delete();
      $this->logger->notice('Deleted entity %type with ID %id.', array('%type' => $entity->getEntityTypeId(), '%id' => $entity->id()));

      // Delete responses have an empty body.
      return new ModifiedResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Verifies that the whole entity does not violate any validation constraints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   If validation errors are found.
   */
  protected function validate(EntityInterface $entity) {
    // @todo Remove when https://www.drupal.org/node/2164373 is committed.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }
    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if (count($violations) > 0) {
      $message = "Unprocessable Entity: validation failed.\n";
      foreach ($violations as $violation) {
        $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . "\n";
      }
      // Instead of returning a generic 400 response we use the more specific
      // 422 Unprocessable Entity code from RFC 4918. That way clients can
      // distinguish between general syntax errors in bad serializations (code
      // 400) and semantic errors in well-formed requests (code 422).
      throw new HttpException(422, $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    $definition = $this->getPluginDefinition();

    $parameters = $route->getOption('parameters') ?: array();
    $parameters[$definition['entity_type']]['type'] = 'entity:' . $definition['entity_type'];
    $route->setOption('parameters', $parameters);

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function availableMethods() {
    $methods = parent::availableMethods();
    if ($this->isConfigEntityResource()) {
      // Currently only GET is supported for Config Entities.
      // @todo Remove when supported https://www.drupal.org/node/2300677
      $unsupported_methods = ['POST', 'PUT', 'DELETE', 'PATCH'];
      $methods = array_diff($methods, $unsupported_methods);
    }
    return $methods;
  }

  /**
   * Checks if this resource is for a Config Entity.
   *
   * @return bool
   *   TRUE if the entity is a Config Entity, FALSE otherwise.
   */
  protected function isConfigEntityResource() {
    return $this->entityType instanceof ConfigEntityType;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    if (isset($this->entityType)) {
      return ['module' => [$this->entityType->getProvider()]];
    }
  }

}
