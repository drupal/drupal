<?php

namespace Drupal\Tests\quickedit\Functional\Rest;

use Drupal\Tests\layout_builder\Functional\Rest\LayoutBuilderEntityViewDisplayJsonBasicAuthTest;

/**
 * @group quickedit
 * @group layout_builder
 * @group rest
 */
class QuickEditLayoutBuilderEntityViewDisplayJsonBasicAuthTest extends LayoutBuilderEntityViewDisplayJsonBasicAuthTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['quickedit'];

}
