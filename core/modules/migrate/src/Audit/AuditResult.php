<?php

namespace Drupal\migrate\Audit;

use Drupal\Component\Render\MarkupInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Encapsulates the result of a migration audit.
 */
class AuditResult implements MarkupInterface, \Countable {

  /**
   * The audited migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The result of the audit (TRUE if passed, FALSE otherwise).
   *
   * @var bool
   */
  protected $status;

  /**
   * The reasons why the migration passed or failed the audit.
   *
   * @var string[]
   */
  protected $reasons = [];

  /**
   * AuditResult constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The audited migration.
   * @param bool $status
   *   The result of the audit (TRUE if passed, FALSE otherwise).
   * @param string[] $reasons
   *   (optional) The reasons why the migration passed or failed the audit.
   */
  public function __construct(MigrationInterface $migration, $status, array $reasons = []) {
    if (!is_bool($status)) {
      throw new \InvalidArgumentException('Audit results must have a boolean status.');
    }
    $this->migration = $migration;
    $this->status = $status;
    array_walk($reasons, [$this, 'addReason']);
  }

  /**
   * Returns the audited migration.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface
   *   The audited migration.
   */
  public function getMigration() {
    return $this->migration;
  }

  /**
   * Returns the boolean result of the audit.
   *
   * @return bool
   *   The result of the audit. TRUE if the migration passed the audit, FALSE
   *   otherwise.
   */
  public function passed() {
    return $this->status;
  }

  /**
   * Adds a reason why the migration passed or failed the audit.
   *
   * @param string|object $reason
   *   The reason to add. Can be a string or a string-castable object.
   *
   * @return $this
   */
  public function addReason($reason) {
    array_push($this->reasons, (string) $reason);
    return $this;
  }

  /**
   * Creates a passing audit result for a migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The audited migration.
   * @param string[] $reasons
   *   (optional) The reasons why the migration passed the audit.
   *
   * @return static
   */
  public static function pass(MigrationInterface $migration, array $reasons = []) {
    return new static($migration, TRUE, $reasons);
  }

  /**
   * Creates a failing audit result for a migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The audited migration.
   * @param array $reasons
   *   (optional) The reasons why the migration failed the audit.
   *
   * @return static
   */
  public static function fail(MigrationInterface $migration, array $reasons = []) {
    return new static($migration, FALSE, $reasons);
  }

  /**
   * Implements \Countable::count() for Twig template compatibility.
   *
   * @return int
   *
   * @see \Drupal\Component\Render\MarkupInterface
   */
  public function count(): int {
    return count($this->reasons);
  }

  /**
   * Returns the reasons the migration passed or failed, as a string.
   *
   * @return string
   *
   * @see \Drupal\Component\Render\MarkupInterface
   */
  public function __toString() {
    return implode("\n", $this->reasons);
  }

  /**
   * Returns the reasons the migration passed or failed, for JSON serialization.
   *
   * @return string[]
   */
  public function jsonSerialize(): string {
    return $this->reasons;
  }

}
