<?php

namespace Drupal\jsonapi\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\Routing\Route;

/**
 * Parameter converter for upcasting JSON:API resource type names to objects.
 *
 * @internal JSON:API maintains no PHP API since its API is the HTTP API. This
 *   class may change at any time and this will break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 */
class ResourceTypeConverter implements ParamConverterInterface {

  /**
   * The route parameter type to match.
   *
   * @var string
   */
  const PARAM_TYPE_ID = 'jsonapi_resource_type';

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * ResourceTypeConverter constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    return $this->resourceTypeRepository->getByTypeName($value);
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] === static::PARAM_TYPE_ID);
  }

}
