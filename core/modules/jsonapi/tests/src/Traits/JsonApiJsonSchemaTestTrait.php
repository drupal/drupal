<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Traits;

use Drupal\jsonapi\JsonApiSpec;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\Tests\serialization\Traits\JsonSchemaTestTrait;
use JsonSchema\Constraints\Factory;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;

/**
 * Support methods for testing JSON API schema.
 */
trait JsonApiJsonSchemaTestTrait {

  use JsonSchemaTestTrait {
    getNormalizationForValue as parentGetNormalizationForValue;
  }

  /**
   * {@inheritdoc}
   */
  protected function getJsonSchemaTestNormalizationFormat(): ?string {
    return 'api_json';
  }

  /**
   * {@inheritdoc}
   */
  protected function getValidator(): Validator {
    $uriRetriever = new UriRetriever();
    $uriRetriever->setTranslation(
      '|^' . JsonApiSpec::SUPPORTED_SPECIFICATION_JSON_SCHEMA . '#?|',
      sprintf('file://%s/schema.json', realpath(__DIR__ . '/../../..'))
    );
    return new Validator(new Factory(
      uriRetriever: $uriRetriever,
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function getNormalizationForValue(mixed $value): mixed {
    $normalization = $this->parentGetNormalizationForValue($value);
    if ($normalization instanceof CacheableNormalization) {
      return $normalization->getNormalization();
    }
    return $normalization;
  }

}
