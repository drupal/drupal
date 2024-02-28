<?php

declare(strict_types=1);

namespace Drupal\Tests\layout_builder\Functional\Rest;

use Drupal\Tests\rest\Functional\AnonResourceTestTrait;

/**
 * @group layout_builder
 * @group rest
 */
class LayoutBuilderEntityViewDisplayJsonAnonTest extends LayoutBuilderEntityViewDisplayResourceTestBase {

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
