<?php

namespace Drupal\Component\Plugin\Attribute;

/**
 * Provides a base class for classed attributes.
 */
abstract class AttributeBase implements AttributeInterface {

  /**
   * The class used for this attribute class.
   *
   * @var class-string
   */
  protected string $class;

  /**
   * The provider of the attribute class.
   */
  protected string|null $provider = NULL;

  /**
   * @param string $id
   *   The attribute class ID.
   */
  public function __construct(
    protected readonly string $id,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getProvider(): ?string {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider(string $provider): void {
    $this->provider = $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass(): string {
    return $this->class;
  }

  /**
   * {@inheritdoc}
   */
  public function setClass(string $class): void {
    $this->class = $class;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): array|object {
    return array_filter(get_object_vars($this) + [
      'class' => $this->getClass(),
      'provider' => $this->getProvider(),
    ], function ($value, $key) {
      return !($value === NULL && ($key === 'deriver' || $key === 'provider'));
    }, ARRAY_FILTER_USE_BOTH);
  }

}
