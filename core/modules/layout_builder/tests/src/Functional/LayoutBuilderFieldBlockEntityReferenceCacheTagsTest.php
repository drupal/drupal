<?php

namespace Drupal\Tests\layout_builder\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;

/**
 * Tests cache tags on entity reference field blocks in Layout Builder.
 *
 * @group layout_builder
 */
class LayoutBuilderFieldBlockEntityReferenceCacheTagsTest extends BrowserTestBase {

  use ContentTypeCreationTrait;
  use EntityReferenceTestTrait;
  use NodeCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'layout_builder',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Enable page caching.
    $config = $this->config('system.performance');
    $config->set('cache.page.max_age', 3600);
    $config->save();

    // Create two content types, with one content type containing a field that
    // references entities of the second content type.
    $this->createContentType([
      'type' => 'bundle_with_reference_field',
      'name' => 'bundle_with_reference_field',
    ]);
    $this->createContentType([
      'type' => 'bundle_referenced',
      'name' => 'bundle_referenced',
    ]);

    $this->createEntityReferenceField('node', 'bundle_with_reference_field', 'field_reference', 'Reference field', 'node', 'default', [
      'target_bundles' => ['bundle_referenced'],
    ]);

    // Enable layout builder to the content type with the reference field, and
    // add the reference field to the layout builder display.
    $this->container->get('entity_display.repository')
      ->getViewDisplay('node', 'bundle_with_reference_field', 'full')
      ->enableLayoutBuilder()
      ->setComponent('field_reference', ['type' => 'entity_reference_label'])
      ->save();
  }

  /**
   * Tests cache tags on field block for entity reference field.
   */
  public function testEntityReferenceFieldBlockCaching() {
    $assert_session = $this->assertSession();

    // Create two nodes, one of the referenced content type and one of the
    // referencing content type, with the first node being referenced by the
    // second. Set the referenced node to be unpublished so anonymous user will
    // not have view access.
    $referenced_node = $this->createNode([
      'type' => 'bundle_referenced',
      'title' => 'The referenced node title',
      'status' => 0,
    ]);

    $referencing_node = $this->createNode([
      'type' => 'bundle_with_reference_field',
      'title' => 'The referencing node title',
      'field_reference' => ['entity' => $referenced_node],
    ]);

    // When user does not have view access to referenced entities in entity
    // reference field blocks, test that the cache tags of the referenced entity
    // are still bubbled to page cache.
    $referencing_node_url = $referencing_node->toUrl();
    $this->verifyPageCacheContainsTags($referencing_node_url, 'MISS');
    $this->verifyPageCacheContainsTags($referencing_node_url, 'HIT', $referenced_node->getCacheTags());
    // Since the referenced node is inaccessible, it should not appear on the
    // referencing node.
    $this->drupalGet($referencing_node_url);
    $assert_session->linkNotExists('The referenced node title');

    // Publish the referenced entity.
    $referenced_node->setPublished()
      ->save();

    // Revisit the node with the reference field without clearing cache. Now
    // that the referenced node is published, it should appear.
    $this->verifyPageCacheContainsTags($referencing_node_url, 'MISS');
    $this->verifyPageCacheContainsTags($referencing_node_url, 'HIT', $referenced_node->getCacheTags());
    $this->drupalGet($referencing_node_url);
    $assert_session->linkExists('The referenced node title');
  }

  /**
   * Verify that when loading a given page, it's a page cache hit or miss.
   *
   * @param \Drupal\Core\Url $url
   *   The page for this URL will be loaded.
   * @param string $hit_or_miss
   *   'HIT' if a page cache hit is expected, 'MISS' otherwise.
   * @param array|false $tags
   *   When expecting a page cache hit, you may optionally specify an array of
   *   expected cache tags. While FALSE, the cache tags will not be verified.
   *   This tests whether all expected tags are in the page cache tags, not that
   *   expected tags and page cache tags are identical.
   */
  protected function verifyPageCacheContainsTags(Url $url, $hit_or_miss, $tags = FALSE) {
    $this->drupalGet($url);
    $message = new FormattableMarkup('Page cache @hit_or_miss for %path.', ['@hit_or_miss' => $hit_or_miss, '%path' => $url->toString()]);
    $this->assertEqual($this->drupalGetHeader('X-Drupal-Cache'), $hit_or_miss, $message);

    if ($hit_or_miss === 'HIT' && is_array($tags)) {
      $cache_tags = explode(' ', $this->drupalGetHeader('X-Drupal-Cache-Tags'));
      $tags = array_unique($tags);
      $this->assertEmpty(array_diff($tags, $cache_tags), 'Page cache tags contains all expected tags.');
    }
  }

}
