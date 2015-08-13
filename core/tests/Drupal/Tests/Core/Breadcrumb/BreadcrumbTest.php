<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Breadcrumb\BreadcrumbTest.
 */

namespace Drupal\Tests\Core\Breadcrumb;

use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Breadcrumb\Breadcrumb
 * @group Breadcrumb
 */
class BreadcrumbTest extends UnitTestCase {

  /**
   * @covers ::setLinks
   * @expectedException \LogicException
   * @expectedExceptionMessage Once breadcrumb links are set, only additional breadcrumb links can be added.
   */
  public function testSetLinks() {
    $breadcrumb = new Breadcrumb();
    $breadcrumb->setLinks([new Link('Home', Url::fromRoute('<front>'))]);
    $breadcrumb->setLinks([new Link('None', Url::fromRoute('<none>'))]);
  }

}
