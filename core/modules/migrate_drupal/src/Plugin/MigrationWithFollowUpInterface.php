<?php

namespace Drupal\migrate_drupal\Plugin;

/**
 * Interface for migrations with follow-up migrations.
 *
 * Some migrations need to be derived and executed after other migrations have
 * been successfully executed. For example, a migration might need to be derived
 * based on previously migrated data. For such a case, the migration dependency
 * system is not enough since all migrations would still be derived before any
 * one of them has been executed.
 *
 * Those "follow-up" migrations need to be tagged with the "Follow-up migration"
 * tag (or any tag in the "follow_up_migration_tags" configuration) and thus
 * they won't be derived with the other migrations.
 *
 * To get those follow-up migrations derived at the right time, the migrations
 * on which they depend must implement this interface and generate them in the
 * generateFollowUpMigrations() method.
 *
 * When the migrations implementing this interface have been successfully
 * executed, the follow-up migrations will then be derived having access to the
 * now migrated data.
 */
interface MigrationWithFollowUpInterface {

  /**
   * Generates follow-up migrations.
   *
   * When the migration implementing this interface has been succesfully
   * executed, this method will be used to generate the follow-up migrations
   * which depends on the now migrated data.
   *
   * @return \Drupal\migrate\Plugin\MigrationInterface[]
   *   The follow-up migrations.
   */
  public function generateFollowUpMigrations();

}
