<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\entity_test\Entity\EntityTestBundle;
use Drupal\entity_test\Entity\EntityTestWithBundle;

/**
 * Tests that entity bundle conditions works properly.
 *
 * @group Entity
 */
class EntityBundleConditionTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('entity_test_with_bundle');

    // Create the entity bundles required for testing.
    $bundle = EntityTestBundle::create(['id' => 'page', 'label' => 'page']);
    $bundle->save();
    $bundle = EntityTestBundle::create(['id' => 'article', 'label' => 'article']);
    $bundle->save();
    $bundle = EntityTestBundle::create(['id' => 'test', 'label' => 'test']);
    $bundle->save();
  }

  /**
   * Tests conditions.
   */
  public function testConditions() {
    $this->createUser();
    // Get some entities of various bundles to check against.
    $page = EntityTestWithBundle::create(['type' => 'page', 'name' => $this->randomMachineName()]);
    $page->save();
    $article = EntityTestWithBundle::create(['type' => 'article', 'name' => $this->randomMachineName()]);
    $article->save();
    $test = EntityTestWithBundle::create(['type' => 'test', 'name' => $this->randomMachineName()]);
    $test->save();

    // Grab the bundle condition and configure it to check against bundle of
    // 'article' and set the context to the page type entity.
    /** @var \Drupal\Core\Entity\Plugin\Condition\EntityBundle $condition */
    $condition = $this->container->get('plugin.manager.condition')->createInstance('entity_bundle:entity_test_with_bundle')
      ->setConfig('bundles', ['article' => 'article'])
      ->setContextValue('entity_test_with_bundle', $page);
    $this->assertFalse($condition->execute(), 'Page type entities fail bundle checks for articles.');
    // Check for the proper summary.
    $this->assertEquals('Test entity bundle is article', $condition->summary());
    $this->assertEquals('entity_test', $condition->getPluginDefinition()['provider']);

    // Set the bundle check to page.
    $condition->setConfig('bundles', ['page' => 'page']);
    $this->assertTrue($condition->execute(), 'Page type entities pass bundle checks for pages');
    // Check for the proper summary.
    $this->assertEquals('Test entity bundle is page', $condition->summary());

    // Set the bundle check to page or article.
    $condition->setConfig('bundles', ['page' => 'page', 'article' => 'article']);
    $this->assertTrue($condition->execute(), 'Page type entities pass bundle checks for pages or articles');
    // Check for the proper summary.
    $this->assertEquals('Test entity bundle is page or article', $condition->summary());

    // Set the context to the article entity.
    $condition->setContextValue('entity_test_with_bundle', $article);
    $this->assertTrue($condition->execute(), 'Article type entities pass bundle checks for pages or articles');

    // Set the context to the test entity.
    $condition->setContextValue('entity_test_with_bundle', $test);
    $this->assertFalse($condition->execute(), 'Test type entities pass bundle checks for pages or articles');

    // Check a greater than 2 bundles summary scenario.
    $condition->setConfig('bundles', [
      'page' => 'page',
      'article' => 'article',
      'test' => 'test',
    ]);
    $this->assertEquals('Test entity bundle is page, article or test', $condition->summary());
  }

}
