<?php

declare(strict_types=1);

namespace Drupal\Tests\search\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Search Page Json Anon.
 */
#[Group('rest')]
class SearchPageJsonAnonTest extends SearchPageResourceTestBase {

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
