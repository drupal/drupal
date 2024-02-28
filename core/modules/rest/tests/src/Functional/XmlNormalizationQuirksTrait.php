<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Functional;

/**
 * Trait for ResourceTestBase subclasses testing $format='xml'.
 */
trait XmlNormalizationQuirksTrait {

  /**
   * Applies the XML encoding quirks that remain after decoding.
   *
   * The XML encoding:
   * - maps empty arrays to the empty string
   * - maps single-item arrays to just that single item
   * - restructures multiple-item arrays that lives in a single-item array
   *
   * @param array $normalization
   *   A normalization.
   *
   * @return array
   *   The updated normalization.
   *
   * @see \Symfony\Component\Serializer\Encoder\XmlEncoder
   */
  protected function applyXmlDecodingQuirks(array $normalization) {
    foreach ($normalization as $key => $value) {
      if ($value === [] || $value === NULL) {
        $normalization[$key] = '';
      }
      elseif (is_array($value)) {
        // Collapse single-item numeric arrays to just the single item.
        if (count($value) === 1 && is_numeric(array_keys($value)[0]) && is_scalar($value[0])) {
          $value = $value[0];
        }
        // Restructure multiple-item arrays inside a single-item numeric array.
        // @see \Symfony\Component\Serializer\Encoder\XmlEncoder::buildXml()
        elseif (count($value) === 1 && is_numeric(array_keys($value)[0]) && is_array(reset($value))) {
          $rewritten_value = [];
          foreach ($value[0] as $child_key => $child_value) {
            if (is_numeric(array_keys(reset($value))[0])) {
              $rewritten_value[$child_key] = ['@key' => $child_key] + $child_value;
            }
            else {
              $rewritten_value[$child_key] = $child_value;
            }
          }
          $value = $rewritten_value;
        }

        // If the post-quirk value is still an array after the above, recurse.
        if (is_array($value)) {
          $value = $this->applyXmlDecodingQuirks($value);
        }

        // Store post-quirk value.
        $normalization[$key] = $value;
      }
    }
    return $normalization;
  }

}
