<?php

namespace Drupal\serialization\Encoder;

use Symfony\Component\Serializer\Encoder\JsonDecode;
use Symfony\Component\Serializer\Encoder\JsonEncode;
use Symfony\Component\Serializer\Encoder\JsonEncoder as BaseJsonEncoder;

/**
 * Adds 'ajax' to the supported content types of the JSON encoder.
 *
 * @internal
 *   This encoder should not be used directly. Rather, use the `serializer`
 *   service.
 */
class JsonEncoder extends BaseJsonEncoder {

  /**
   * The formats that this Encoder supports.
   *
   * @var array
   */
  protected static $format = ['json', 'ajax'];

  /**
   * {@inheritdoc}
   */
  public function __construct(?JsonEncode $encodingImpl = NULL, ?JsonDecode $decodingImpl = NULL) {
    // Encode <, >, ', &, and " for RFC4627-compliant JSON, which may also be
    // embedded into HTML.
    // @see \Symfony\Component\HttpFoundation\JsonResponse
    // Additionally, substitute invalid UTF-8 sequences to ensure control
    // characters and malformed data are properly handled.
    // @see https://www.drupal.org/project/drupal/issues/3549107
    $json_encoding_options = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE;
    $this->encodingImpl = $encodingImpl ?: new JsonEncode([JsonEncode::OPTIONS => $json_encoding_options]);
    $this->decodingImpl = $decodingImpl ?: new JsonDecode([JsonDecode::ASSOCIATIVE => TRUE]);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsEncoding(string $format, array $context = []): bool {
    return in_array($format, static::$format);
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDecoding(string $format, array $context = []): bool {
    return in_array($format, static::$format);
  }

}
