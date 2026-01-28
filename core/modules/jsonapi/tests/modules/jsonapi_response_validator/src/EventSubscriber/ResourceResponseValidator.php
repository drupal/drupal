<?php

declare(strict_types=1);

namespace Drupal\jsonapi_response_validator\EventSubscriber;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Extension\ModuleHandlerInterface;
use JsonSchema\Validator;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Response subscriber that validates a JSON:API response.
 *
 * This must run after ResourceResponseSubscriber.
 *
 * @internal JSON:API maintains no PHP API. The API is the HTTP API. This class
 *   may change at any time and could break any dependencies on it.
 *
 * @see https://www.drupal.org/project/drupal/issues/3032787
 * @see jsonapi.api.php
 *
 * @see \Drupal\rest\EventSubscriber\ResourceResponseSubscriber
 */
class ResourceResponseValidator implements EventSubscriberInterface {

  /**
   * The schema validator.
   *
   * @var \JsonSchema\Validator
   */
  protected Validator $validator;

  /**
   * Constructs a ResourceResponseValidator object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The JSON:API logger channel.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param string $appRoot
   *   The application's root file path.
   */
  public function __construct(protected LoggerInterface $logger, protected ModuleHandlerInterface $moduleHandler, protected string $appRoot) {
    $this->validator = new Validator();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['onResponse'];
    return $events;
  }

  /**
   * Validates JSON:API responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *   The event to process.
   */
  public function onResponse(ResponseEvent $event): void {
    $response = $event->getResponse();
    if (!str_contains($response->headers->get('Content-Type', ''), 'application/vnd.api+json')) {
      return;
    }

    // Wraps validation in an assert to prevent execution in production.
    assert($this->validateResponse($response, $event->getRequest()), 'A JSON:API response failed validation (see the logs for details). Report this in the Drupal issue queue at https://www.drupal.org/project/issues/drupal');
  }

  /**
   * Validates a response against the JSON:API specification.
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The response to validate.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request containing info about what to validate.
   *
   * @return bool
   *   FALSE if the response failed validation, otherwise TRUE.
   */
  protected function validateResponse(Response $response, Request $request): bool {
    // Do not use Json::decode here since it coerces the response into an
    // associative array, which creates validation errors.
    $response_data = json_decode($response->getContent());
    if (empty($response_data)) {
      return TRUE;
    }

    $schema_ref = sprintf(
      'file://%s/schema.json',
      implode('/', [
        $this->appRoot,
        $this->moduleHandler->getModule('jsonapi')->getPath(),
      ])
    );
    $generic_jsonapi_schema = (object) ['$ref' => $schema_ref];

    return $this->validateSchema($generic_jsonapi_schema, $response_data);
  }

  /**
   * Validates a string against a JSON Schema. It logs any possible errors.
   *
   * @param object $schema
   *   The JSON Schema object.
   * @param mixed $response_data
   *   The JSON string to validate.
   *
   * @return bool
   *   TRUE if the string is a valid instance of the schema. FALSE otherwise.
   */
  protected function validateSchema(object $schema, mixed $response_data): bool {
    // @phpstan-ignore method.deprecated
    $this->validator->check($response_data, $schema);
    $is_valid = $this->validator->isValid();
    if (!$is_valid) {
      $this->logger->debug("Response failed validation.\nResponse:\n@data\n\nErrors:\n@errors", [
        '@data' => Json::encode($response_data),
        '@errors' => Json::encode($this->validator->getErrors()),
      ]);
    }
    return $is_valid;
  }

}
