<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Access\AccessResultReasonInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\Normalizer\HttpExceptionNormalizer;
use Drupal\jsonapi\ResourceResponse;
use Psr\Http\Message\ResponseInterface;

/**
 * Utility methods for handling resource responses.
 *
 * @internal
 */
trait ResourceResponseTestTrait {

  /**
   * Merges individual responses into a collection response.
   *
   * Here, a collection response refers to a response with multiple resource
   * objects. Not necessarily to a response to a collection route. In both
   * cases, the document should indistinguishable.
   *
   * @param \Drupal\jsonapi\ResourceResponse[] $responses
   *   An array or ResourceResponses to be merged.
   * @param string|null $self_link
   *   The self link for the merged document if one should be set.
   * @param bool $is_multiple
   *   Whether the responses are for a multiple cardinality field. This cannot
   *   be deduced from the number of responses, because a multiple cardinality
   *   field may have only one value.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The merged ResourceResponse.
   */
  protected static function toCollectionResourceResponse(array $responses, $self_link, $is_multiple) {
    assert(count($responses) > 0);
    $merged_document = [];
    $merged_cacheability = new CacheableMetadata();
    foreach ($responses as $response) {
      $response_document = $response->getResponseData();
      // If any of the response documents had top-level errors, we should later
      // expect the merged document to have all errors as omitted links under
      // the 'meta.omitted' member.
      if (!empty($response_document['errors'])) {
        static::addOmittedObject($merged_document, static::errorsToOmittedObject($response_document['errors']));
      }
      if (!empty($response_document['meta']['omitted'])) {
        static::addOmittedObject($merged_document, $response_document['meta']['omitted']);
      }
      elseif (isset($response_document['data'])) {
        $response_data = $response_document['data'];
        if (!isset($merged_document['data'])) {
          $merged_document['data'] = static::isResourceIdentifier($response_data) && $is_multiple
            ? [$response_data]
            : $response_data;
        }
        else {
          $response_resources = static::isResourceIdentifier($response_data)
            ? [$response_data]
            : $response_data;
          foreach ($response_resources as $response_resource) {
            $merged_document['data'][] = $response_resource;
          }
        }
      }
      $merged_cacheability->addCacheableDependency($response->getCacheableMetadata());
    }
    $merged_document['jsonapi'] = [
      'meta' => [
        'links' => [
          'self' => ['href' => 'http://jsonapi.org/format/1.0/'],
        ],
      ],
      'version' => '1.0',
    ];
    // Until we can reasonably know what caused an error, we shouldn't include
    // 'self' links in error documents. For example, a 404 shouldn't have a
    // 'self' link because HATEOAS links shouldn't point to resources which do
    // not exist.
    if (isset($merged_document['errors'])) {
      unset($merged_document['links']);
    }
    else {
      if (!isset($merged_document['data'])) {
        $merged_document['data'] = $is_multiple ? [] : NULL;
      }
      $merged_document['links'] = [
        'self' => [
          'href' => $self_link,
        ],
      ];
    }
    // All collections should be 200, without regard for the status of the
    // individual resources in those collections, which means any '4xx-response'
    // cache tags on the individual responses should also be omitted.
    $merged_cacheability->setCacheTags(array_diff($merged_cacheability->getCacheTags(), ['4xx-response']));
    return (new ResourceResponse($merged_document, 200))->addCacheableDependency($merged_cacheability);
  }

  /**
   * Gets an array of expected ResourceResponses for the given include paths.
   *
   * @param array $include_paths
   *   The list of relationship include paths for which to get expected data.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The expected ResourceResponse.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getExpectedIncludedResourceResponse(array $include_paths, array $request_options) {
    $resource_type = $this->resourceType;
    $resource_data = array_reduce($include_paths, function ($data, $path) use ($request_options, $resource_type) {
      $field_names = explode('.', $path);
      /* @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entity;
      $collected_responses = [];
      foreach ($field_names as $public_field_name) {
        $resource_type = $this->container->get('jsonapi.resource_type.repository')->get($entity->getEntityTypeId(), $entity->bundle());
        $field_name = $resource_type->getInternalName($public_field_name);
        $field_access = static::entityFieldAccess($entity, $field_name, 'view', $this->account);
        if (!$field_access->isAllowed()) {
          if (!$entity->access('view') && $entity->access('view label') && $field_access instanceof AccessResultReasonInterface && empty($field_access->getReason())) {
            $field_access->setReason("The user only has authorization for the 'view label' operation.");
          }
          $via_link = Url::fromRoute(
            sprintf('jsonapi.%s.%s.related', $entity->getEntityTypeId() . '--' . $entity->bundle(), $public_field_name),
            ['entity' => $entity->uuid()]
          );
          $collected_responses[] = static::getAccessDeniedResponse($entity, $field_access, $via_link, $field_name, 'The current user is not allowed to view this relationship.', $field_name);
          break;
        }
        if ($target_entity = $entity->{$field_name}->entity) {
          $target_access = static::entityAccess($target_entity, 'view', $this->account);
          if (!$target_access->isAllowed()) {
            $target_access = static::entityAccess($target_entity, 'view label', $this->account)->addCacheableDependency($target_access);
          }
          if (!$target_access->isAllowed()) {
            $resource_identifier = static::toResourceIdentifier($target_entity);
            if (!static::collectionHasResourceIdentifier($resource_identifier, $data['already_checked'])) {
              $data['already_checked'][] = $resource_identifier;
              $via_link = Url::fromRoute(
                sprintf('jsonapi.%s.individual', $resource_identifier['type']),
                ['entity' => $resource_identifier['id']]
              );
              $collected_responses[] = static::getAccessDeniedResponse($entity, $target_access, $via_link, NULL, NULL, '/data');
            }
            break;
          }
        }
        $psr_responses = $this->getResponses([static::getRelatedLink(static::toResourceIdentifier($entity), $public_field_name)], $request_options);
        $collected_responses[] = static::toCollectionResourceResponse(static::toResourceResponses($psr_responses), NULL, TRUE);
        $entity = $entity->{$field_name}->entity;
      }
      if (!empty($collected_responses)) {
        $data['responses'][$path] = static::toCollectionResourceResponse($collected_responses, NULL, TRUE);
      }
      return $data;
    }, ['responses' => [], 'already_checked' => []]);

    $individual_document = $this->getExpectedDocument();

    $expected_base_url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), ['entity' => $this->entity->uuid()])->setAbsolute();
    $include_url = clone $expected_base_url;
    $query = ['include' => implode(',', $include_paths)];
    $include_url->setOption('query', $query);
    $individual_document['links']['self']['href'] = $include_url->toString();

    // The test entity reference field should always be present.
    if (!isset($individual_document['data']['relationships']['field_jsonapi_test_entity_ref'])) {
      if (static::$resourceTypeIsVersionable) {
        assert($this->entity instanceof RevisionableInterface);
        $version_identifier = 'id:' . $this->entity->getRevisionId();
        $version_query_string = '?resourceVersion=' . urlencode($version_identifier);
      }
      else {
        $version_query_string = '';
      }
      $individual_document['data']['relationships']['field_jsonapi_test_entity_ref'] = [
        'data' => [],
        'links' => [
          'related' => [
            'href' => $expected_base_url->toString() . '/field_jsonapi_test_entity_ref' . $version_query_string,
          ],
          'self' => [
            'href' => $expected_base_url->toString() . '/relationships/field_jsonapi_test_entity_ref' . $version_query_string,
          ],
        ],
      ];
    }

    $basic_cacheability = (new CacheableMetadata())
      ->addCacheTags($this->getExpectedCacheTags())
      ->addCacheContexts($this->getExpectedCacheContexts());
    return static::decorateExpectedResponseForIncludedFields(ResourceResponse::create($individual_document), $resource_data['responses'])
      ->addCacheableDependency($basic_cacheability);
  }

  /**
   * Maps an array of PSR responses to JSON:API ResourceResponses.
   *
   * @param \Psr\Http\Message\ResponseInterface[] $responses
   *   The PSR responses to be mapped.
   *
   * @return \Drupal\jsonapi\ResourceResponse[]
   *   The ResourceResponses.
   */
  protected static function toResourceResponses(array $responses) {
    return array_map([self::class, 'toResourceResponse'], $responses);
  }

  /**
   * Maps a response object to a JSON:API ResourceResponse.
   *
   * This helper can be used to ease comparing, recording and merging
   * cacheable responses and to have easier access to the JSON:API document as
   * an array instead of a string.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   A PSR response to be mapped.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The ResourceResponse.
   */
  protected static function toResourceResponse(ResponseInterface $response) {
    $cacheability = new CacheableMetadata();
    if ($cache_tags = $response->getHeader('X-Drupal-Cache-Tags')) {
      $cacheability->addCacheTags(explode(' ', $cache_tags[0]));
    }
    if (!empty($response->getHeaderLine('X-Drupal-Cache-Contexts'))) {
      $cacheability->addCacheContexts(explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
    }
    if ($dynamic_cache = $response->getHeader('X-Drupal-Dynamic-Cache')) {
      $cacheability->setCacheMaxAge(($dynamic_cache[0] === 'UNCACHEABLE' && $response->getStatusCode() < 400) ? 0 : Cache::PERMANENT);
    }
    $related_document = Json::decode($response->getBody());
    $resource_response = new ResourceResponse($related_document, $response->getStatusCode());
    return $resource_response->addCacheableDependency($cacheability);
  }

  /**
   * Maps an entity to a resource identifier.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to map to a resource identifier.
   *
   * @return array
   *   A resource identifier for the given entity.
   */
  protected static function toResourceIdentifier(EntityInterface $entity) {
    return [
      'type' => $entity->getEntityTypeId() . '--' . $entity->bundle(),
      'id' => $entity->uuid(),
    ];
  }

  /**
   * Checks if a given array is a resource identifier.
   *
   * @param array $data
   *   An array to check.
   *
   * @return bool
   *   TRUE if the array has a type and ID, FALSE otherwise.
   */
  protected static function isResourceIdentifier(array $data) {
    return array_key_exists('type', $data) && array_key_exists('id', $data);
  }

  /**
   * Sorts a collection of resources or resource identifiers.
   *
   * This is useful for asserting collections or resources where order cannot
   * be known in advance.
   *
   * @param array $resources
   *   The resource or resource identifier.
   */
  protected static function sortResourceCollection(array &$resources) {
    usort($resources, function ($a, $b) {
      return strcmp("{$a['type']}:{$a['id']}", "{$b['type']}:{$b['id']}");
    });
  }

  /**
   * Determines if a given resource exists in a list of resources.
   *
   * @param array $needle
   *   The resource or resource identifier.
   * @param array $haystack
   *   The list of resources or resource identifiers to search.
   *
   * @return bool
   *   TRUE if the needle exists is present in the haystack, FALSE otherwise.
   */
  protected static function collectionHasResourceIdentifier(array $needle, array $haystack) {
    foreach ($haystack as $resource) {
      if ($resource['type'] == $needle['type'] && $resource['id'] == $needle['id']) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Turns a list of relationship field names into an array of link paths.
   *
   * @param array $relationship_field_names
   *   The relationships field names for which to build link paths.
   * @param string $type
   *   The type of link to get. Either 'relationship' or 'related'.
   *
   * @return array
   *   An array of link paths, keyed by relationship field name.
   */
  protected static function getLinkPaths(array $relationship_field_names, $type) {
    assert($type === 'relationship' || $type === 'related');
    return array_reduce($relationship_field_names, function ($link_paths, $relationship_field_name) use ($type) {
      $tail = $type === 'relationship' ? 'self' : $type;
      $link_paths[$relationship_field_name] = "data.relationships.$relationship_field_name.links.$tail.href";
      return $link_paths;
    }, []);
  }

  /**
   * Extracts links from a document using a list of relationship field names.
   *
   * @param array $link_paths
   *   A list of paths to link values keyed by a name.
   * @param array $document
   *   A JSON:API document.
   *
   * @return array
   *   The extracted links, keyed by the original associated key name.
   */
  protected static function extractLinks(array $link_paths, array $document) {
    return array_map(function ($link_path) use ($document) {
      $link = array_reduce(
        explode('.', $link_path),
        'array_column',
        [$document]
      );
      return ($link) ? reset($link) : NULL;
    }, $link_paths);
  }

  /**
   * Creates individual resource links for a list of resource identifiers.
   *
   * @param array $resource_identifiers
   *   A list of resource identifiers for which to create links.
   *
   * @return string[]
   *   The resource links.
   */
  protected static function getResourceLinks(array $resource_identifiers) {
    return array_map([static::class, 'getResourceLink'], $resource_identifiers);
  }

  /**
   * Creates an individual resource link for a given resource identifier.
   *
   * @param array $resource_identifier
   *   A resource identifier for which to create a link.
   *
   * @return string
   *   The resource link.
   */
  protected static function getResourceLink(array $resource_identifier) {
    assert(static::isResourceIdentifier($resource_identifier));
    $resource_type = $resource_identifier['type'];
    $resource_id = $resource_identifier['id'];
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', $resource_type), ['entity' => $resource_id]);
    return $url->setAbsolute()->toString();
  }

  /**
   * Creates a relationship link for a given resource identifier and field.
   *
   * @param array $resource_identifier
   *   A resource identifier for which to create a link.
   * @param string $relationship_field_name
   *   The relationship field for which to create a link.
   *
   * @return string
   *   The relationship link.
   */
  protected static function getRelationshipLink(array $resource_identifier, $relationship_field_name) {
    return static::getResourceLink($resource_identifier) . "/relationships/$relationship_field_name";
  }

  /**
   * Creates a related resource link for a given resource identifier and field.
   *
   * @param array $resource_identifier
   *   A resource identifier for which to create a link.
   * @param string $relationship_field_name
   *   The relationship field for which to create a link.
   *
   * @return string
   *   The related resource link.
   */
  protected static function getRelatedLink(array $resource_identifier, $relationship_field_name) {
    return static::getResourceLink($resource_identifier) . "/$relationship_field_name";
  }

  /**
   * Gets an array of related responses for the given field names.
   *
   * @param array $relationship_field_names
   *   The list of relationship field names for which to get responses.
   * @param array $request_options
   *   Request options to apply.
   * @param \Drupal\Core\Entity\EntityInterface|null $entity
   *   (optional) The entity for which to get expected related responses.
   *
   * @return array
   *   The related responses, keyed by relationship field names.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getRelatedResponses(array $relationship_field_names, array $request_options, EntityInterface $entity = NULL) {
    $entity = $entity ?: $this->entity;
    $links = array_map(function ($relationship_field_name) use ($entity) {
      return static::getRelatedLink(static::toResourceIdentifier($entity), $relationship_field_name);
    }, array_combine($relationship_field_names, $relationship_field_names));
    return $this->getResponses($links, $request_options);
  }

  /**
   * Gets an array of relationship responses for the given field names.
   *
   * @param array $relationship_field_names
   *   The list of relationship field names for which to get responses.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return array
   *   The relationship responses, keyed by relationship field names.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getRelationshipResponses(array $relationship_field_names, array $request_options) {
    $links = array_map(function ($relationship_field_name) {
      return static::getRelationshipLink(static::toResourceIdentifier($this->entity), $relationship_field_name);
    }, array_combine($relationship_field_names, $relationship_field_names));
    return $this->getResponses($links, $request_options);
  }

  /**
   * Gets responses from an array of links.
   *
   * @param array $links
   *   A keyed array of links.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return array
   *   The fetched array of responses, keys are preserved.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getResponses(array $links, array $request_options) {
    return array_reduce(array_keys($links), function ($related_responses, $key) use ($links, $request_options) {
      $related_responses[$key] = $this->request('GET', Url::fromUri($links[$key]), $request_options);
      return $related_responses;
    }, []);
  }

  /**
   * Gets a generic forbidden response.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which to generate the forbidden response.
   * @param \Drupal\Core\Access\AccessResultInterface $access
   *   The denied AccessResult. This can carry a reason and cacheability data.
   * @param \Drupal\Core\Url $via_link
   *   The source URL for the errors of the response.
   * @param string|null $relationship_field_name
   *   (optional) The field name to which the forbidden result applies. Useful
   *   for testing related/relationship routes and includes.
   * @param string|null $detail
   *   (optional) Details for the JSON:API error object.
   * @param string|bool|null $pointer
   *   (optional) Document pointer for the JSON:API error object. FALSE to omit
   *   the pointer.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The forbidden ResourceResponse.
   */
  protected static function getAccessDeniedResponse(EntityInterface $entity, AccessResultInterface $access, Url $via_link, $relationship_field_name = NULL, $detail = NULL, $pointer = NULL) {
    $detail = ($detail) ? $detail : 'The current user is not allowed to GET the selected resource.';
    if ($access instanceof AccessResultReasonInterface && ($reason = $access->getReason())) {
      $detail .= ' ' . $reason;
    }
    $error = [
      'status' => '403',
      'title' => 'Forbidden',
      'detail' => $detail,
      'links' => [
        'info' => ['href' => HttpExceptionNormalizer::getInfoUrl(403)],
      ],
    ];
    if ($pointer || $pointer !== FALSE && $relationship_field_name) {
      $error['source']['pointer'] = ($pointer) ? $pointer : $relationship_field_name;
    }
    if ($via_link) {
      $error['links']['via']['href'] = $via_link->setAbsolute()->toString();
    }

    return (new ResourceResponse([
      'jsonapi' => static::$jsonApiMember,
      'errors' => [$error],
    ], 403))
      ->addCacheableDependency((new CacheableMetadata())->addCacheTags(['4xx-response', 'http_response'])->addCacheContexts(['url.site']))
      ->addCacheableDependency($access);
  }

  /**
   * Gets a generic empty collection response.
   *
   * @param int $cardinality
   *   The cardinality of the resource collection. 1 for a to-one related
   *   resource collection; -1 for an unlimited cardinality.
   * @param string $self_link
   *   The self link for collection ResourceResponse.
   *
   * @return \Drupal\jsonapi\ResourceResponse
   *   The empty collection ResourceResponse.
   */
  protected function getEmptyCollectionResponse($cardinality, $self_link) {
    // If the entity type is revisionable, add a resource version cache context.
    $cache_contexts = Cache::mergeContexts([
      // Cache contexts for JSON:API URL query parameters.
      'url.query_args:fields',
      'url.query_args:include',
      // Drupal defaults.
      'url.site',
    ], $this->entity->getEntityType()->isRevisionable() ? ['url.query_args:resourceVersion'] : []);
    $cacheability = (new CacheableMetadata())->addCacheContexts($cache_contexts)->addCacheTags(['http_response']);
    return (new ResourceResponse([
      // Empty to-one relationships should be NULL and empty to-many
      // relationships should be an empty array.
      'data' => $cardinality === 1 ? NULL : [],
      'jsonapi' => static::$jsonApiMember,
      'links' => ['self' => ['href' => $self_link]],
    ]))->addCacheableDependency($cacheability);
  }

  /**
   * Add the omitted object to the document or merges it if one already exists.
   *
   * @param array $document
   *   The JSON:API response document.
   * @param array $omitted
   *   The omitted object.
   */
  protected static function addOmittedObject(array &$document, array $omitted) {
    if (isset($document['meta']['omitted'])) {
      $document['meta']['omitted'] = static::mergeOmittedObjects($document['meta']['omitted'], $omitted);
    }
    else {
      $document['meta']['omitted'] = $omitted;
    }
  }

  /**
   * Maps error objects into an omitted object.
   *
   * @param array $errors
   *   An array of error objects.
   *
   * @return array
   *   A new omitted object.
   */
  protected static function errorsToOmittedObject(array $errors) {
    $omitted = [
      'detail' => 'Some resources have been omitted because of insufficient authorization.',
      'links' => [
        'help' => [
          'href' => 'https://www.drupal.org/docs/8/modules/json-api/filtering#filters-access-control',
        ],
      ],
    ];
    foreach ($errors as $error) {
      $omitted['links']['item--' . substr(Crypt::hashBase64($error['links']['via']['href']), 0, 7)] = [
        'href' => $error['links']['via']['href'],
        'meta' => [
          'detail' => $error['detail'],
          'rel' => 'item',
        ],
      ];
    }
    return $omitted;
  }

  /**
   * Merges the links of two omitted objects and returns a new omitted object.
   *
   * @param array $a
   *   The first omitted object.
   * @param array $b
   *   The second omitted object.
   *
   * @return mixed
   *   A new, merged omitted object.
   */
  protected static function mergeOmittedObjects(array $a, array $b) {
    $merged['detail'] = 'Some resources have been omitted because of insufficient authorization.';
    $merged['links']['help']['href'] = 'https://www.drupal.org/docs/8/modules/json-api/filtering#filters-access-control';
    $a_links = array_diff_key($a['links'], array_flip(['help']));
    $b_links = array_diff_key($b['links'], array_flip(['help']));
    foreach (array_merge(array_values($a_links), array_values($b_links)) as $link) {
      $merged['links'][$link['href'] . $link['meta']['detail']] = $link;
    }
    static::resetOmittedLinkKeys($merged);
    return $merged;
  }

  /**
   * Sorts an omitted link object array by href.
   *
   * @param array $omitted
   *   An array of JSON:API omitted link objects.
   */
  protected static function sortOmittedLinks(array &$omitted) {
    $help = $omitted['links']['help'];
    $links = array_diff_key($omitted['links'], array_flip(['help']));
    uasort($links, function ($a, $b) {
      return strcmp($a['href'], $b['href']);
    });
    $omitted['links'] = ['help' => $help] + $links;
  }

  /**
   * Resets omitted link keys.
   *
   * Omitted link keys are a link relation type + a random string. This string
   * is meaningless and only serves to differentiate link objects. Given that
   * these are random, we can't assert their value.
   *
   * @param array $omitted
   *   An array of JSON:API omitted link objects.
   */
  protected static function resetOmittedLinkKeys(array &$omitted) {
    $help = $omitted['links']['help'];
    $reindexed = [];
    $links = array_diff_key($omitted['links'], array_flip(['help']));
    foreach (array_values($links) as $index => $link) {
      $reindexed['item--' . $index] = $link;
    }
    $omitted['links'] = ['help' => $help] + $reindexed;
  }

}
