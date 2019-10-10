<?php

namespace Drupal\Tests\media_library\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests update to set 'media' view's table display as the administrative page.
 *
 * @group media_library
 * @group legacy
 */
class MediaLibrarySetAdministrativePageToTableDisplayTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.4.0.bare.standard.php.gz',
      __DIR__ . '/../../../../../media/tests/fixtures/update/drupal-8.4.0-media_installed.php',
      __DIR__ . '/../../../fixtures/update/drupal-8.7.2-media_library_installed.php',
    ];
  }

  /**
   * Tests that the update alters uncustomized path and menu settings.
   */
  public function testUpdateWithoutCustomizations() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media');
    $display = $view->getDisplay('media_page_list');
    $this->assertSame('admin/content/media-table', $display['display_options']['path']);
    $this->assertArrayNotHasKey('menu', $display['display_options']);

    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media', $display['display_options']['path']);
    $this->assertSame('tab', $display['display_options']['menu']['type']);
    $this->assertSame('Media', $display['display_options']['menu']['title']);

    $this->runUpdates();

    $view = View::load('media');
    $display = $view->getDisplay('media_page_list');
    $this->assertSame('admin/content/media', $display['display_options']['path']);
    $this->assertArrayNotHasKey('menu', $display['display_options']);

    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media-grid', $display['display_options']['path']);
    $this->assertArrayNotHasKey('menu', $display['display_options']);
  }

  /**
   * Tests that the update does not alter a custom 'media' view path.
   */
  public function testUpdateWithCustomizedMediaViewPath() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media');
    $display = &$view->getDisplay('media_page_list');
    $display['display_options']['path'] = 'admin/content/all-media';
    $view->save();

    $this->runUpdates();

    // The update should not have modified the path.
    $view = View::load('media');
    $display = $view->getDisplay('media_page_list');
    $this->assertSame('admin/content/all-media', $display['display_options']['path']);

    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media-grid', $display['display_options']['path']);
    $this->assertArrayNotHasKey('menu', $display['display_options']);
  }

  /**
   * Tests that the update does not alter custom 'media' view menu settings.
   */
  public function testUpdateWithCustomizedMediaViewMenuSettings() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media');
    $display = &$view->getDisplay('media_page_list');
    $display['display_options']['menu'] = [
      'type' => 'normal',
      'title' => 'All media',
      'parent' => 'system.admin_structure',
    ];
    $view->save();

    $this->runUpdates();

    // The update should not have modified the path.
    $view = View::load('media');
    $display = $view->getDisplay('media_page_list');
    $this->assertSame('admin/content/media', $display['display_options']['path']);
    $this->assertSame('normal', $display['display_options']['menu']['type']);
    $this->assertSame('All media', $display['display_options']['menu']['title']);
    $this->assertSame('system.admin_structure', $display['display_options']['menu']['parent']);

    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media-grid', $display['display_options']['path']);
    $this->assertArrayNotHasKey('menu', $display['display_options']);
  }

  /**
   * Tests that the update does not alter custom 'media' path and menu settings.
   */
  public function testUpdateWithCustomizedMediaLibraryViewPath() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media_library');
    $display = &$view->getDisplay('page');
    $display['display_options']['path'] = 'admin/content/media-pretty';
    $view->save();

    $this->runUpdates();

    // The update should not have modified the path or menu settings.
    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media-pretty', $display['display_options']['path']);
    $this->assertSame('tab', $display['display_options']['menu']['type']);
    $this->assertSame('Media', $display['display_options']['menu']['title']);
  }

  /**
   * Tests that the update preserves custom 'media_library' menu settings.
   */
  public function testUpdateWithCustomizedMediaLibraryMenuSettings() {
    /** @var \Drupal\views\ViewEntityInterface $view */
    $view = View::load('media_library');
    $display = &$view->getDisplay('page');
    $display['display_options']['menu'] = [
      'type' => 'normal',
      'title' => 'A treasure trove of interesting pictures',
      'parent' => 'system.admin_structure',
    ];
    $view->save();

    $this->runUpdates();

    // The update should have changed the path but preserved the menu settings.
    $view = View::load('media_library');
    $display = $view->getDisplay('page');
    $this->assertSame('admin/content/media-grid', $display['display_options']['path']);
    $this->assertSame('normal', $display['display_options']['menu']['type']);
    $this->assertSame('A treasure trove of interesting pictures', $display['display_options']['menu']['title']);
    $this->assertSame('system.admin_structure', $display['display_options']['menu']['parent']);
  }

}
