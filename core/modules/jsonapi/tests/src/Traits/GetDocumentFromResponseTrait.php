<?php

declare(strict_types=1);

namespace Drupal\Tests\jsonapi\Traits;

use Drupal\Component\Serialization\Json;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

/**
 * Test trait for retrieving the JSON:API document from a response.
 */
trait GetDocumentFromResponseTrait {

  /**
   * Retrieve document from response, with basic validation.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   *   Response to extract JSON:API document from.
   * @param bool $validate
   *   Determines whether the data is validated or not. Defaults to TRUE.
   *
   * @return ?array
   *   JSON:API document extracted from the response, or NULL.
   *
   * @throws \PHPUnit\Framework\AssertionFailedError
   *   Thrown when the document does not pass basic validation against the spec.
   */
  protected function getDocumentFromResponse(ResponseInterface $response, bool $validate = TRUE): ?array {
    assert($this instanceof TestCase);

    $document = Json::decode((string) $response->getBody());

    if (isset($document['data']) && isset($document['errors'])) {
      $this->fail('Document contains both data and errors members; only one is allowed.');
    }

    if ($validate === TRUE && !isset($document['data'])) {
      if (isset($document['errors'])) {
        $errors = [];
        foreach ($document['errors'] as $error) {
          $errors[] = $error['title'] . ' (' . $error['status'] . '): ' . $error['detail'];
        }
        $this->fail('Missing expected data member in document. Error(s): ' . PHP_EOL . '  ' . implode('  ' . PHP_EOL, $errors));
      }
      $this->fail('Missing both data and errors members in document; either is required. Response body: ' . PHP_EOL . '  ' . $response->getBody());
    }
    return $document;
  }

}
