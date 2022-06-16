<?php

namespace Drupal\Tests\hal\Functional\quickedit;

use Drupal\Tests\hal\Functional\layout_builder\LayoutBuilderEntityViewDisplayHalJsonBasicAuthTest;

/**
 * @group hal
 * @group legacy
 */
class QuickEditLayoutBuilderEntityViewDisplayHalJsonBasicAuthTest extends LayoutBuilderEntityViewDisplayHalJsonBasicAuthTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['quickedit'];

}
