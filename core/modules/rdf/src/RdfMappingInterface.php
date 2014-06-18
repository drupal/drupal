<?php
/**
 * @file
 * Contains \Drupal\rdf\RdfMappingInterface
 */

namespace Drupal\rdf;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining an RDF mapping entity.
 */
interface RdfMappingInterface extends ConfigEntityInterface {

  /**
   * Gets the mapping for the bundle-level data.
   *
   * The prepared bundle mapping should be used when outputting data in RDF
   * serializations such as RDFa. In the prepared mapping, the mapping
   * configuration's CURIE arrays are processed into CURIE strings suitable for
   * output.
   *
   * @return array
   *   The bundle mapping.
   */
  public function getPreparedBundleMapping();

  /**
   * Gets the mapping config for the bundle-level data.
   *
   * This function returns the bundle mapping as stored in config, which may
   * contain CURIE arrays. If the mapping is needed for output in a
   * serialization format, such as RDFa, then getPreparedBundleMapping() should
   * be used instead.
   *
   * @return array
   *   The bundle mapping, or an empty array if there is no mapping.
   */
  public function getBundleMapping();

  /**
   * Sets the mapping config for the bundle-level data.
   *
   * This only sets bundle-level mappings, such as the RDF type. Mappings for
   * a bundle's fields should be handled with setFieldMapping.
   *
   * Example usage:
   * -Map the 'article' bundle to 'sioc:Post'.
   * @code
   * rdf_get_mapping('node', 'article')
   *   ->setBundleMapping(array(
   *     'types' => array('sioc:Post'),
   *   ))
   *   ->save();
   * @endcode
   *
   * @param array $mapping
   *   The bundle mapping.
   *
   * @return \Drupal\rdf\Entity\RdfMapping
   *   The RdfMapping object.
   */
  public function setBundleMapping(array $mapping);

  /**
   * Gets the prepared mapping for a field.
   *
   * The prepared field mapping should be used when outputting data in RDF
   * serializations such as RDFa. In the prepared mapping, the mapping
   * configuration's CURIE arrays are processed into CURIE strings suitable for
   * output.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @return array
   *   The prepared field mapping, or an empty array if there is no mapping.
   */
  public function getPreparedFieldMapping($field_name);

  /**
   * Gets the mapping config for a field.
   *
   * This function returns the field mapping as stored in config, which may
   * contain CURIE arrays. If the mapping is needed for output in a
   * serialization format, such as RDFa, then getPreparedFieldMapping() should
   * be used instead.
   *
   * @param string $field_name
   *   The name of the field.
   *
   * @return array
   *   The field mapping config array, or an empty array if there is no mapping.
   */
  public function getFieldMapping($field_name);

  /**
   * Sets the mapping config for a field.
   *
   * @param string $field_name
   *   The name of the field.
   * @param array $mapping
   *   The field mapping.
   *
   * @return \Drupal\rdf\Entity\RdfMapping
   *   The RdfMapping object.
   */
  public function setFieldMapping($field_name, array $mapping = array());
}
