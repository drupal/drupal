<?php

namespace Drupal\migrate\Audit;

use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Defines an interface for migration auditors.
 *
 * A migration auditor is a class which can examine a migration to determine if
 * it will cause conflicts with data already existing in the destination system.
 * What kind of auditing it does, and how it does it, is up to the implementing
 * class.
 */
interface AuditorInterface {

  /**
   * Audits a migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to audit.
   *
   * @throws \Drupal\migrate\Audit\AuditException
   *   If the audit fails.
   *
   * @return \Drupal\migrate\Audit\AuditResult
   *   The result of the audit.
   */
  public function audit(MigrationInterface $migration);

  /**
   * Audits a set of migrations.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface[] $migrations
   *   The migrations to audit.
   *
   * @return \Drupal\migrate\Audit\AuditResult[]
   *   The audit results, keyed by migration ID.
   */
  public function auditMultiple(array $migrations);

}
