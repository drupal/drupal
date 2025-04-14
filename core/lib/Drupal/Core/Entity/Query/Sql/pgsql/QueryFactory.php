<?php

namespace Drupal\Core\Entity\Query\Sql\pgsql;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\Query\Sql\QueryFactory as BaseQueryFactory;

/**
 * PostgreSQL specific entity query implementation.
 *
 * @deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The
 *   PostgreSQL override of the entity query has been moved to the pgsql module.
 *
 * @see https://www.drupal.org/node/3488580
 */
class QueryFactory extends BaseQueryFactory {

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $connection) {
    @trigger_error('\Drupal\Core\Entity\Query\Sql\pgsql\QueryFactory is deprecated in drupal:11.2.0 and is removed from drupal:12.0.0. The PostgreSQL override of the entity query has been moved to the pgsql module. See https://www.drupal.org/node/3488580', E_USER_DEPRECATED);
    parent::__construct($connection);
  }

}
