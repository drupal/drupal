<?php

namespace Drupal\Database;

/**
 * Exception for when popTransaction() is called with no active transaction.
 */
class TransactionNoActiveException extends TransactionException { }
