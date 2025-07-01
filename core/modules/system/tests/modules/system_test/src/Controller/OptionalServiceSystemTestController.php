<?php

declare(strict_types=1);

namespace Drupal\system_test\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\dblog\Logger\DbLog;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * A controller that specifies an optional dependency.
 */
class OptionalServiceSystemTestController extends ControllerBase {

  public function __construct(
    #[Autowire('logger.dblog')]
    public readonly ?DbLog $dbLog,
  ) {}

}
