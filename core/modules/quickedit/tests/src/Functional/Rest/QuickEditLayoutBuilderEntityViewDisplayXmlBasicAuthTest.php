<?php

namespace Drupal\Tests\quickedit\Functional\Rest;

use Drupal\Tests\layout_builder\Functional\Rest\LayoutBuilderEntityViewDisplayXmlBasicAuthTest;

/**
 * @group quickedit
 * @group layout_builder
 * @group rest
 * @group legacy
 */
class QuickEditLayoutBuilderEntityViewDisplayXmlBasicAuthTest extends LayoutBuilderEntityViewDisplayXmlBasicAuthTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['quickedit'];

}
