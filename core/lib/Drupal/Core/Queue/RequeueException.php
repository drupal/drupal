<?php

namespace Drupal\Core\Queue;

/**
 * Throw this exception to release the item allowing it to be processed again.
 */
class RequeueException extends \RuntimeException {}
