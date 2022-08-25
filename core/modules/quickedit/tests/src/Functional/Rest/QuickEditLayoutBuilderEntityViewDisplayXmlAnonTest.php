<?php

namespace Drupal\Tests\quickedit\Functional\Rest;

use Drupal\Tests\layout_builder\Functional\Rest\LayoutBuilderEntityViewDisplayXmlAnonTest;

/**
 * @group quickedit
 * @group layout_builder
 * @group rest
 * @group legacy
 */
class QuickEditLayoutBuilderEntityViewDisplayXmlAnonTest extends LayoutBuilderEntityViewDisplayXmlAnonTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['quickedit'];

}
