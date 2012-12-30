<?php

/**
 * @file
 * Contains JsonldTestSetupHelper.
 */

namespace Drupal\jsonld\Tests;

use Drupal\Core\Cache\DatabaseBackend;
use Drupal\jsonld\JsonldEncoder;
use Drupal\jsonld\JsonldEntityNormalizer;
use Drupal\jsonld\JsonldEntityReferenceNormalizer;
use Drupal\jsonld\JsonldFieldItemNormalizer;
use Drupal\rdf\RdfMappingManager;
use Drupal\rdf\EventSubscriber\MappingSubscriber;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Serializer\Serializer;

/**
 * Constructs services for JSON-LD tests.
 */
class JsonldTestSetupHelper {

  /**
   * The site schema manager.
   *
   * @var \Drupal\rdf\SiteSchema\SiteSchemaManager
   */
  protected $siteSchemaManager;

  /**
   * The RDF mapping manager.
   *
   * @var \Drupal\rdf\RdfMappingManager
   */
  protected $rdfMappingManager;

  /**
   * The Normalizer array.
   *
   * @var array
   */
  protected $normalizers;

  /**
   * Constructor.
   */
  public function __construct() {
    // Construct site schema manager.
    $this->siteSchemaManager = new SiteSchemaManager(new DatabaseBackend('cache'));
    // Construct RDF mapping manager.
    $dispatcher = new EventDispatcher();
    $dispatcher->addSubscriber(new MappingSubscriber());
    $this->rdfMappingManager = new RdfMappingManager($dispatcher, $this->siteSchemaManager);
    // Construct normalizers.
    $this->normalizers = array(
      'entityreference' => new JsonldEntityReferenceNormalizer($this->siteSchemaManager, $this->rdfMappingManager),
      'field_item' => new JsonldFieldItemNormalizer($this->siteSchemaManager, $this->rdfMappingManager),
      'entity' => new JsonldEntityNormalizer($this->siteSchemaManager, $this->rdfMappingManager),
    );
    $serializer = new Serializer($this->normalizers, array(new JsonldEncoder()));
    $this->normalizers['entity']->setSerializer($serializer);
  }

  /**
   * Get Normalizers.
   *
   * @return array
   *   An array of normalizers, keyed by supported class or interface.
   */
  public function getNormalizers() {
    return $this->normalizers;
  }

  /**
   * Get the SiteSchemaManager object.
   *
   * @return \Drupal\rdf\SiteSchema\SiteSchemaManager
   *   The SiteSchemaManager, which is also injected into the Normalizers.
   */
  public function getSiteSchemaManager() {
    return $this->siteSchemaManager;
  }

  /**
   * Get the RdfMappingManager object.
   *
   * @return \Drupal\rdf\RdfMappingManager
   *   The RdfMappingManager, which is also injected into the Normalizers.
   */
  public function getRdfMappingManager() {
    return $this->rdfMappingManager;
  }
}
