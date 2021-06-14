<?php

namespace Drupal\Core\Entity\Query\Sql\pgsql;

use Drupal\Core\Entity\Query\Sql\QueryFactory as BaseQueryFactory;

/**
 * PostgreSQL specific entity query implementation.
 *
 * To add a new query implementation extending the default SQL one, add
 * a service definition like pgsql.entity.query.sql and a factory class like
 * this. The system will automatically find the relevant Query, QueryAggregate,
 * Condition, ConditionAggregate, Tables classes in this namespace, in the
 * namespace of the parent class and so on. So after creating an empty query
 * factory class like this, it is possible to just drop in a class extending
 * the base class in this namespace and it will be used automatically but it
 * is optional: if a class is not extended the relevant default is used.
 *
 * @see \Drupal\Core\Entity\Query\QueryBase::getNamespaces()
 * @see \Drupal\Core\Entity\Query\QueryBase::getClass()
 */
class QueryFactory extends BaseQueryFactory {

}
