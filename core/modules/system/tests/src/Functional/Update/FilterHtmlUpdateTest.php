<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests that the allowed html configutations are updated with attributes.
 *
 * @group Entity
 * @group legacy
 */
class FilterHtmlUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  public function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.bare.standard.php.gz',
    ];
  }

  /**
   * Tests system_update_8009().
   */
  public function testAllowedHtmlUpdate() {
    // Make sure we have the expected values before the update.
    $filters_before = [
      'basic_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h4> <h5> <h6> <p> <br> <span> <img>',
      'restricted_html' => '<a> <em> <strong> <cite> <blockquote> <code> <ul> <ol> <li> <dl> <dt> <dd> <h4> <h5> <h6>',
    ];
    foreach ($filters_before as $name => $before) {
      $config = FilterFormat::load($name)->toArray();
      $this->assertIdentical($before, $config['filters']['filter_html']['settings']['allowed_html']);
    }

    $this->runUpdates();

    // Make sure we have the expected values after the update.
    $filters_after = [
      'basic_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <h4 id> <h5 id> <h6 id> <p> <br> <span> <img src alt height width data-align data-caption>',
      'restricted_html' => '<a href hreflang> <em> <strong> <cite> <blockquote cite> <code> <ul type> <ol start type> <li> <dl> <dt> <dd> <h4 id> <h5 id> <h6 id>',
    ];
    foreach ($filters_after as $name => $after) {
      $config = FilterFormat::load($name)->toArray();
      $this->assertIdentical($after, $config['filters']['filter_html']['settings']['allowed_html']);
    }
  }

}
