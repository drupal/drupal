<?php

namespace Drupal\jsonapi\JsonApiResource;

/**
 * Represents a JSON:API document's "top level".
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see http://jsonapi.org/format/#document-top-level
 *
 * @todo Add support for the missing optional 'jsonapi' member or document why not.
 */
class JsonApiDocumentTopLevel {

  /**
   * The data to normalize.
   *
   * @var \Drupal\jsonapi\JsonApiResource\ResourceIdentifierInterface|\Drupal\jsonapi\JsonApiResource\Data|\Drupal\jsonapi\JsonApiResource\ErrorCollection|\Drupal\Core\Field\EntityReferenceFieldItemListInterface
   */
  protected $data;

  /**
   * The metadata to normalize.
   *
   * @var array
   */
  protected $meta;

  /**
   * The links.
   *
   * @var \Drupal\jsonapi\JsonApiResource\LinkCollection
   */
  protected $links;

  /**
   * The includes to normalize.
   *
   * @var \Drupal\jsonapi\JsonApiResource\IncludedData
   */
  protected $includes;

  /**
   * Resource objects that will be omitted from the response for access reasons.
   *
   * @var \Drupal\jsonapi\JsonApiResource\OmittedData
   */
  protected $omissions;

  /**
   * Instantiates a JsonApiDocumentTopLevel object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\TopLevelDataInterface|\Drupal\jsonapi\JsonApiResource\ErrorCollection $data
   *   The data to normalize. It can be either a ResourceObject, or a stand-in
   *   for one, or a collection of the same.
   * @param \Drupal\jsonapi\JsonApiResource\IncludedData $includes
   *   A JSON:API Data object containing resources to be included in the
   *   response document or NULL if there should not be includes.
   * @param \Drupal\jsonapi\JsonApiResource\LinkCollection $links
   *   A collection of links to resources related to the top-level document.
   * @param array $meta
   *   (optional) The metadata to normalize.
   */
  public function __construct($data, IncludedData $includes, LinkCollection $links, array $meta = []) {
    assert($data instanceof TopLevelDataInterface || $data instanceof ErrorCollection);
    assert(!$data instanceof ErrorCollection || $includes instanceof NullIncludedData);
    $this->data = $data instanceof TopLevelDataInterface ? $data->getData() : $data;
    $this->includes = $includes->getData();
    $this->links = $data instanceof TopLevelDataInterface ? $data->getMergedLinks($links->withContext($this)) : $links->withContext($this);
    $this->meta = $data instanceof TopLevelDataInterface ? $data->getMergedMeta($meta) : $meta;
    $this->omissions = $data instanceof TopLevelDataInterface
      ? OmittedData::merge($data->getOmissions(), $includes->getOmissions())
      : $includes->getOmissions();
  }

  /**
   * Gets the data.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Data|\Drupal\jsonapi\JsonApiResource\ErrorCollection
   *   The data.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Gets the links.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The top-level links.
   */
  public function getLinks() {
    return $this->links;
  }

  /**
   * Gets the metadata.
   *
   * @return array
   *   The metadata.
   */
  public function getMeta() {
    return $this->meta;
  }

  /**
   * Gets a JSON:API Data object of resources to be included in the response.
   *
   * @return \Drupal\jsonapi\JsonApiResource\IncludedData
   *   The includes.
   */
  public function getIncludes() {
    return $this->includes;
  }

  /**
   * Gets an OmittedData instance containing resources to be omitted.
   *
   * @return \Drupal\jsonapi\JsonApiResource\OmittedData
   *   The omissions.
   */
  public function getOmissions() {
    return $this->omissions;
  }

}
