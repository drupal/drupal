<?php

namespace Drupal\Tests\hal\Functional\EntityResource;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use GuzzleHttp\RequestOptions;

/**
 * Trait for EntityResourceTestBase subclasses testing formats using HAL.
 */
trait HalEntityNormalizationTrait {

  /**
   * Applies the HAL entity field normalization to an entity normalization.
   *
   * The HAL normalization:
   * - adds a 'lang' attribute to every translatable field
   * - omits reference fields, since references are stored in _links & _embedded
   * - omits empty fields (fields without value)
   *
   * @param array $normalization
   *   An entity normalization.
   *
   * @return array
   *   The updated entity normalization.
   */
  protected function applyHalFieldNormalization(array $normalization) {
    if (!$this->entity instanceof FieldableEntityInterface) {
      throw new \LogicException('This trait should only be used for fieldable entity types.');
    }

    // In the HAL normalization, all translatable fields get a 'lang' attribute.
    $translatable_non_reference_fields = array_keys(array_filter($this->entity->getTranslatableFields(), function (FieldItemListInterface $field) {
      return !$field instanceof EntityReferenceFieldItemListInterface;
    }));
    foreach ($translatable_non_reference_fields as $field_name) {
      if (isset($normalization[$field_name])) {
        $normalization[$field_name][0]['lang'] = 'en';
      }
    }

    // In the HAL normalization, reference fields are omitted, except for the
    // bundle field.
    $bundle_key = $this->entity->getEntityType()->getKey('bundle');
    $reference_fields = array_keys(array_filter($this->entity->getFields(), function (FieldItemListInterface $field) use ($bundle_key) {
      return $field instanceof EntityReferenceFieldItemListInterface && $field->getName() !== $bundle_key;
    }));
    foreach ($reference_fields as $field_name) {
      unset($normalization[$field_name]);
    }

    // In the HAL normalization, the bundle field  omits the 'target_type' and
    // 'target_uuid' properties, because it's encoded in the '_links' section.
    if ($bundle_key) {
      unset($normalization[$bundle_key][0]['target_type']);
      unset($normalization[$bundle_key][0]['target_uuid']);
    }

    // In the HAL normalization, empty fields are omitted.
    $empty_fields = array_keys(array_filter($this->entity->getFields(), function (FieldItemListInterface $field) {
      return $field->isEmpty();
    }));
    foreach ($empty_fields as $field_name) {
      unset($normalization[$field_name]);
    }

    return $normalization;
  }

  /**
   * {@inheritdoc}
   */
  protected function removeFieldsFromNormalization(array $normalization, $field_names) {
    $normalization = parent::removeFieldsFromNormalization($normalization, $field_names);
    foreach ($field_names as $field_name) {
      $relation_url = Url::fromUri('base:rest/relation/' . static::$entityTypeId . '/' . $this->entity->bundle() . '/' . $field_name)
        ->setAbsolute(TRUE)
        ->toString();
      $normalization['_links'] = array_diff_key($normalization['_links'], [$relation_url => TRUE]);
      if (isset($normalization['_embedded'])) {
        $normalization['_embedded'] = array_diff_key($normalization['_embedded'], [$relation_url => TRUE]);
      }
    }

    return array_diff_key($normalization, array_flip($field_names));
  }

  /**
   * {@inheritdoc}
   */
  protected function assertNormalizationEdgeCases($method, Url $url, array $request_options) {
    // \Drupal\serialization\Normalizer\EntityNormalizer::denormalize(): entity
    // types with bundles MUST send their bundle field to be denormalizable.
    if ($this->entity->getEntityType()->hasKey('bundle')) {
      $normalization = $this->getNormalizedPostEntity();


      $normalization['_links']['type'] = Url::fromUri('base:rest/type/' . static::$entityTypeId . '/bad_bundle_name');
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);

      // DX: 400 when incorrect entity type bundle is specified.
      $response = $this->request($method, $url, $request_options);
      // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813853.
      // $this->assertResourceErrorResponse(400, 'No entity type(s) specified', $response);
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
      $this->assertSame($this->serializer->encode(['error' => 'No entity type(s) specified'], static::$format), (string) $response->getBody());


      unset($normalization['_links']['type']);
      $request_options[RequestOptions::BODY] = $this->serializer->encode($normalization, static::$format);


      // DX: 400 when no entity type bundle is specified.
      $response = $this->request($method, $url, $request_options);
      // @todo Uncomment, remove next 3 in https://www.drupal.org/node/2813853.
      // $this->assertResourceErrorResponse(400, 'The type link relation must be specified.', $response);
      $this->assertSame(400, $response->getStatusCode());
      $this->assertSame([static::$mimeType], $response->getHeader('Content-Type'));
      $this->assertSame($this->serializer->encode(['error' => 'The type link relation must be specified.'], static::$format), (string) $response->getBody());
    }
  }

}
