<?php

declare(strict_types=1);

namespace Drupal\Tests\entity_test\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests EntityTest Label Json Anon.
 */
#[Group('rest')]
class EntityTestLabelJsonAnonTest extends EntityTestLabelResourceTestBase {

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
