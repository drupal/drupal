<?php

/**
 * @file
 * Definition of Drupal\jsonld\JsonldNormalizerBase.
 */

namespace Drupal\jsonld;

use ReflectionClass;
use Drupal\rdf\RdfMappingManager;
use Drupal\rdf\SiteSchema\SiteSchemaManager;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\SerializerAwareNormalizer;

/**
 * Provide a base class for JSON-LD Normalizers.
 */
abstract class JsonldNormalizerBase extends SerializerAwareNormalizer implements NormalizerInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass;

  /**
   * The formats that this Normalizer supports.
   *
   * @var array
   */
  static protected $format = array('jsonld', 'drupal_jsonld');

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
   * Constructor.
   *
   * @param \Drupal\rdf\SiteSchema\SiteSchemaManager $site_schema_manager
   *   The site schema manager.
   * @param \Drupal\rdf\RdfMappingManager $rdf_mapping_manager
   *   The RDF mapping manager.
   */
  public function __construct(SiteSchemaManager $site_schema_manager, RdfMappingManager $rdf_mapping_manager) {
    $this->siteSchemaManager = $site_schema_manager;
    $this->rdfMappingManager = $rdf_mapping_manager;
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\NormalizerInterface::normalize()
   */
  public function supportsNormalization($data, $format = NULL) {
    return is_object($data) && in_array($format, static::$format) && ($data instanceof $this->supportedInterfaceOrClass);
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::supportsDenormalization()
   *
   * This class doesn't implement DenormalizerInterface, but most of its child
   * classes do, so this method is implemented at this level to reduce code
   * duplication.
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    $reflection = new ReflectionClass($type);
    return in_array($format, static::$format) && $reflection->implementsInterface($this->supportedInterfaceOrClass);
  }
}
