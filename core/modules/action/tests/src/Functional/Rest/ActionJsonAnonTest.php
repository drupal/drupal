<?php

declare(strict_types=1);

namespace Drupal\Tests\action\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group action
 * @group legacy
 */
class ActionJsonAnonTest extends ActionResourceTestBase {

  use AnonResourceTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $format = 'json';

  /**
   * {@inheritdoc}
   */
  protected static $mimeType = 'application/json';

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

}
