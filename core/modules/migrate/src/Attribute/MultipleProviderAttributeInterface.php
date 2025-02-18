<?php

declare(strict_types=1);

namespace Drupal\migrate\Attribute;

use Drupal\Component\Plugin\Attribute\AttributeInterface;

/**
 * Defines a common interface for attributes with multiple providers.
 *
 * @internal
 *   This is a temporary solution to the fact that migration source plugins have
 *   more than one provider. This functionality will be moved to core in
 *   https://www.drupal.org/node/2786355.
 */
interface MultipleProviderAttributeInterface extends AttributeInterface {

  /**
   * Gets the name of the provider of the attribute class.
   *
   * @return string|null
   *   The provider of the attribute. If there are multiple providers the first
   *   is returned.
   */
  public function getProvider(): ?string;

  /**
   * Gets the provider names of the attribute class.
   *
   * @return string[]
   *   The providers of the attribute.
   */
  public function getProviders(): array;

  /**
   * Sets the provider names of the attribute class.
   *
   * @param string[] $providers
   *   The providers of the attribute.
   */
  public function setProviders(array $providers): void;

}
