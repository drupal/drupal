<?php

declare(strict_types=1);

namespace Drupal\session_test\Session;

use Symfony\Component\HttpFoundation\Session\SessionBagInterface;

/**
 * Test session container.
 */
class TestSessionBag implements SessionBagInterface {

  /**
   * The bag name.
   */
  const BAG_NAME = 'session_test';

  /**
   * Storage for data to save.
   *
   * @var array
   */
  protected $attributes = [];

  /**
   * Constructs a new TestSessionBag object.
   *
   * @param string $storageKey
   *   The key used to store test attributes.
   */
  public function __construct(
    protected $storageKey = '_dp_session_test',
  ) {
  }

  /**
   * Sets the test flag.
   */
  public function setFlag(): void {
    $this->attributes['test_flag'] = TRUE;
  }

  /**
   * Returns TRUE if the test flag is set.
   *
   * @return bool
   *   TRUE when flag is set, FALSE otherwise.
   */
  public function hasFlag() {
    return !empty($this->attributes['test_flag']);
  }

  /**
   * Clears the test flag.
   */
  public function clearFlag(): void {
    unset($this->attributes['test_flag']);
  }

  /**
   * {@inheritdoc}
   */
  public function getName(): string {
    return self::BAG_NAME;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$attributes): void {
    $this->attributes = &$attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorageKey(): string {
    return $this->storageKey;
  }

  /**
   * {@inheritdoc}
   */
  public function clear(): mixed {
    $return = $this->attributes;
    $this->attributes = [];

    return $return;
  }

}
