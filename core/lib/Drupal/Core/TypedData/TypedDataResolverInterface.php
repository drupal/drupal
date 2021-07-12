<?php

namespace Drupal\Core\TypedData;

use Drupal\Core\Plugin\Context\ContextInterface;

/**
 * Defines an interface for typed data resolver.
 */
interface TypedDataResolverInterface {

  /**
   * Convert a property to a context.
   *
   * This method will respect the value of contexts as well, so if a context
   * object is pass that contains a value, the appropriate value will be
   * extracted and injected into the resulting context object if available.
   *
   * @param string $property_path
   *   The name of the property.
   * @param \Drupal\Core\Plugin\Context\ContextInterface $context
   *   The context from which we will extract values if available.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   A context object that represents the definition & value of the property.
   *
   * @throws \Exception
   */
  public function getContextFromProperty(string $property_path, ContextInterface $context): ContextInterface;

  /**
   * Extracts a context from an array of contexts by a tokenized pattern.
   *
   * This is more than simple isset/empty checks on the contexts array. The
   * pattern could be node:uid:name which will iterate over all provided
   * contexts in the array for one named 'node', it will then load the data
   * definition of 'node' and check for a property named 'uid'. This will then
   * set a new (temporary) context on the array and recursively call itself to
   * navigate through related properties all the way down until the request
   * property is located. At that point the property is passed to a
   * TypedDataResolver which will convert it to an appropriate ContextInterface
   * object.
   *
   * @param string $token
   *   A tokenized pattern.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of available contexts.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface
   *   The requested token as a full Context object.
   *
   * @throws \Drupal\Core\TypedData\Exception\ContextNotFoundException
   */
  public function convertTokenToContext(string $token, array $contexts): ContextInterface;

  /**
   * Provides an administrative label for a tokenized relationship.
   *
   * @param string $token
   *   The token related to a context in the contexts array.
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of contexts from which to extract our token's label.
   *
   * @return string|null
   *   The administrative label of $token.
   */
  public function getLabelByToken(string $token, array $contexts): ?string;

  /**
   * Extracts an array of tokens and labels.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   The array of contexts with which we are currently dealing.
   *
   * @return array
   *   An array of token keys and corresponding labels.
   */
  public function getTokensForContexts(array $contexts): array;

}
