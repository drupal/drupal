<?php

namespace Drupal\rest\Plugin\rest\resource;

use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Http\Exception\CacheableAccessDeniedHttpException;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\rest\ModifiedResourceResponse;
use Symfony\Component\HttpFoundation\Response;
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
 *     "create" = "/entity/{entity_type}"
 *   }
 * )
 */
class EntityResource extends ResourceBase implements DependentPluginInterface {

  use EntityResourceValidationTrait;
  use EntityResourceAccessTrait;

  /**
   * The entity type targeted by this resource.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The link relation type manager used to create HTTP header links.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $linkRelationTypeManager;

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
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $link_relation_type_manager
   *   The link relation type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);
    $this->entityType = $entity_type_manager->getDefinition($plugin_definition['entity_type']);
    $this->configFactory = $config_factory;
    $this->linkRelationTypeManager = $link_relation_type_manager;
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
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('plugin.manager.link_relation_type')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the entity with its accessible fields.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get(EntityInterface $entity) {
    $entity_access = $entity->access('view', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new CacheableAccessDeniedHttpException($entity_access, $entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'view'));
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

    $this->addLinkHeaders($entity, $response);

    return $response;
  }

  /**
   * Responds to entity POST requests and saves the new entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function post(EntityInterface $entity = NULL) {
    if ($entity == NULL) {
      throw new BadRequestHttpException('No entity content received.');
    }

    $entity_access = $entity->access('create', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'create'));
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

    $this->checkEditFieldAccess($entity);

    // Validate the received data before saving.
    $this->validate($entity);
    try {
      $entity->save();
      $this->logger->notice('Created entity %type with ID %id.', ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id()]);

      // 201 Created responses return the newly created entity in the response
      // body. These responses are not cacheable, so we add no cacheability
      // metadata here.
      $headers = [];
      if (in_array('canonical', $entity->uriRelationships(), TRUE)) {
        $url = $entity->urlInfo('canonical', ['absolute' => TRUE])->toString(TRUE);
        $headers['Location'] = $url->getGeneratedUrl();
      }
      return new ModifiedResourceResponse($entity, 201, $headers);
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
   * @return \Drupal\rest\ModifiedResourceResponse
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
    $entity_access = $original_entity->access('update', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'update'));
    }

    // Overwrite the received fields.
    // @todo Remove $changed_fields in https://www.drupal.org/project/drupal/issues/2862574.
    $changed_fields = [];
    foreach ($entity->_restSubmittedFields as $field_name) {
      $field = $entity->get($field_name);
      // It is not possible to set the language to NULL as it is automatically
      // re-initialized. As it must not be empty, skip it if it is.
      // @todo Remove in https://www.drupal.org/project/drupal/issues/2933408.
      if ($entity->getEntityType()->hasKey('langcode') && $field_name === $entity->getEntityType()->getKey('langcode') && $field->isEmpty()) {
        continue;
      }
      if ($this->checkPatchFieldAccess($original_entity->get($field_name), $field)) {
        $changed_fields[] = $field_name;
        $original_entity->set($field_name, $field->getValue());
      }
    }

    // If no fields are changed, we can send a response immediately!
    if (empty($changed_fields)) {
      return new ModifiedResourceResponse($original_entity, 200);
    }

    // Validate the received data before saving.
    $this->validate($original_entity, $changed_fields);
    try {
      $original_entity->save();
      $this->logger->notice('Updated entity %type with ID %id.', ['%type' => $original_entity->getEntityTypeId(), '%id' => $original_entity->id()]);

      // Return the updated entity in the response body.
      return new ModifiedResourceResponse($original_entity, 200);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Checks whether the given field should be PATCHed.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $original_field
   *   The original (stored) value for the field.
   * @param \Drupal\Core\Field\FieldItemListInterface $received_field
   *   The received value for the field.
   *
   * @return bool
   *   Whether the field should be PATCHed or not.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   *   Thrown when the user sending the request is not allowed to update the
   *   field. Only thrown when the user could not abuse this information to
   *   determine the stored value.
   *
   * @internal
   */
  protected function checkPatchFieldAccess(FieldItemListInterface $original_field, FieldItemListInterface $received_field) {
    // The user might not have access to edit the field, but still needs to
    // submit the current field value as part of the PATCH request. For
    // example, the entity keys required by denormalizers. Therefore, if the
    // received value equals the stored value, return FALSE without throwing an
    // exception. But only for fields that the user has access to view, because
    // the user has no legitimate way of knowing the current value of fields
    // that they are not allowed to view, and we must not make the presence or
    // absence of a 403 response a way to find that out.
    if ($original_field->access('view') && $original_field->equals($received_field)) {
      return FALSE;
    }

    // If the user is allowed to edit the field, it is always safe to set the
    // received value. We may be setting an unchanged value, but that is ok.
    $field_edit_access = $original_field->access('edit', NULL, TRUE);
    if ($field_edit_access->isAllowed()) {
      return TRUE;
    }

    // It's helpful and safe to let the user know when they are not allowed to
    // update a field.
    $field_name = $received_field->getName();
    $error_message = "Access denied on updating field '$field_name'.";
    if ($field_edit_access instanceof AccessResultReasonInterface) {
      $reason = $field_edit_access->getReason();
      if ($reason) {
        $error_message .= ' ' . $reason;
      }
    }
    throw new AccessDeniedHttpException($error_message);
  }

  /**
   * Responds to entity DELETE requests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   *
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function delete(EntityInterface $entity) {
    $entity_access = $entity->access('delete', NULL, TRUE);
    if (!$entity_access->isAllowed()) {
      throw new AccessDeniedHttpException($entity_access->getReason() ?: $this->generateFallbackAccessDeniedMessage($entity, 'delete'));
    }
    try {
      $entity->delete();
      $this->logger->notice('Deleted entity %type with ID %id.', ['%type' => $entity->getEntityTypeId(), '%id' => $entity->id()]);

      // DELETE responses have an empty body.
      return new ModifiedResourceResponse(NULL, 204);
    }
    catch (EntityStorageException $e) {
      throw new HttpException(500, 'Internal Server Error', $e);
    }
  }

  /**
   * Generates a fallback access denied message, when no specific reason is set.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity object.
   * @param string $operation
   *   The disallowed entity operation.
   *
   * @return string
   *   The proper message to display in the AccessDeniedHttpException.
   */
  protected function generateFallbackAccessDeniedMessage(EntityInterface $entity, $operation) {
    $message = "You are not authorized to {$operation} this {$entity->getEntityTypeId()} entity";

    if ($entity->bundle() !== $entity->getEntityTypeId()) {
      $message .= " of bundle {$entity->bundle()}";
    }
    return "{$message}.";
  }

  /**
   * {@inheritdoc}
   */
  public function permissions() {
    // @see https://www.drupal.org/node/2664780
    if ($this->configFactory->get('rest.settings')->get('bc_entity_resource_permissions')) {
      // The default Drupal 8.0.x and 8.1.x behavior.
      return parent::permissions();
    }
    else {
      // The default Drupal 8.2.x behavior.
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $route = parent::getBaseRoute($canonical_path, $method);
    $definition = $this->getPluginDefinition();

    $parameters = $route->getOption('parameters') ?: [];
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

  /**
   * Adds link headers to a response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response.
   *
   * @see https://tools.ietf.org/html/rfc5988#section-5
   */
  protected function addLinkHeaders(EntityInterface $entity, Response $response) {
    foreach ($entity->uriRelationships() as $relation_name) {
      if ($this->linkRelationTypeManager->hasDefinition($relation_name)) {
        /** @var \Drupal\Core\Http\LinkRelationTypeInterface $link_relation_type */
        $link_relation_type = $this->linkRelationTypeManager->createInstance($relation_name);

        $generator_url = $entity->toUrl($relation_name)
          ->setAbsolute(TRUE)
          ->toString(TRUE);
        if ($response instanceof CacheableResponseInterface) {
          $response->addCacheableDependency($generator_url);
        }
        $uri = $generator_url->getGeneratedUrl();

        $relationship = $link_relation_type->isRegistered()
          ? $link_relation_type->getRegisteredName()
          : $link_relation_type->getExtensionUri();

        $link_header = '<' . $uri . '>; rel="' . $relationship . '"';
        $response->headers->set('Link', $link_header, FALSE);
      }
    }
  }

}
