<?php

declare(strict_types=1);

namespace Drupal\Tests\link\Kernel;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\link\LinkItemInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests the new entity API for the link field type.
 */
#[Group('link')]
#[RunTestsInSeparateProcesses]
class LinkItemTest extends FieldKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['link'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a generic, external, and internal link fields for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_GENERIC],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_test_external',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_external',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_EXTERNAL],
    ])->save();
    FieldStorageConfig::create([
      'field_name' => 'field_test_internal',
      'entity_type' => 'entity_test',
      'type' => 'link',
    ])->save();
    FieldConfig::create([
      'entity_type' => 'entity_test',
      'field_name' => 'field_test_internal',
      'bundle' => 'entity_test',
      'settings' => ['link_type' => LinkItemInterface::LINK_INTERNAL],
    ])->save();
  }

  /**
   * Tests using entity fields of the link field type.
   */
  public function testLinkItem(): void {
    // Create entity.
    $entity = EntityTest::create();
    $url = 'https://www.drupal.org?test_param=test_value';
    $parsed_url = UrlHelper::parse($url);
    $title = $this->randomMachineName();
    $class = $this->randomMachineName();
    $entity->field_test->uri = $parsed_url['path'];
    $entity->field_test->title = $title;
    $entity->field_test->first()->get('options')->set('query', $parsed_url['query']);
    $entity->field_test->first()->get('options')->set('attributes', ['class' => $class]);
    $this->assertEquals([
      'query' => $parsed_url['query'],
      'attributes' => [
        'class' => $class,
      ],
      'external' => TRUE,
    ], $entity->field_test->first()->getUrl()->getOptions());
    $this->assertEquals($url, $entity->field_test->resolvable_uri);
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test[0]);
    $this->assertEquals($url, $entity->field_test->resolvable_uri);
    $this->assertEquals($parsed_url['path'], $entity->field_test->uri);
    $this->assertEquals($parsed_url['path'], $entity->field_test[0]->uri);
    $this->assertEquals($title, $entity->field_test->title);
    $this->assertEquals($title, $entity->field_test[0]->title);
    $this->assertEquals($title, $entity->field_test[0]->getTitle());
    $this->assertEquals($class, $entity->field_test->options['attributes']['class']);
    $this->assertEquals($parsed_url['query'], $entity->field_test->options['query']);

    // Update only the entity name property to check if the link field data will
    // remain intact.
    $entity->name->value = $this->randomMachineName();
    $entity->save();
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertEquals($parsed_url['path'], $entity->field_test->uri);
    $this->assertEquals($class, $entity->field_test->options['attributes']['class']);
    $this->assertEquals($parsed_url['query'], $entity->field_test->options['query']);

    // Verify changing the field value.
    $new_url = 'https://www.drupal.org';
    $new_title = $this->randomMachineName();
    $new_class = $this->randomMachineName();
    $entity->field_test->uri = $new_url;
    $entity->field_test->title = $new_title;
    $entity->field_test->first()->get('options')->set('query', []);
    $entity->field_test->first()->get('options')->set('attributes', ['class' => $new_class]);
    $this->assertEquals($new_url, $entity->field_test->uri);
    $this->assertEquals($new_title, $entity->field_test->title);
    $this->assertEquals($new_class, $entity->field_test->options['attributes']['class']);
    $this->assertEmpty($entity->field_test->options['query']);

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = EntityTest::load($id);
    $this->assertEquals($new_url, $entity->field_test->uri);
    $this->assertEquals($entity->field_test->resolvable_uri, $new_url);
    $this->assertEquals($new_title, $entity->field_test->title);
    $this->assertEquals($new_class, $entity->field_test->options['attributes']['class']);

    // Check that if we only set uri the default values for url, title, and
    // options are also initialized.
    $entity->field_test = ['uri' => 'internal:/node/add'];
    $this->assertEquals('internal:/node/add', $entity->field_test->uri);
    $this->assertEquals($entity->field_test->resolvable_uri, '/node/add');
    $this->assertNull($entity->field_test->title);
    $this->assertSame([], $entity->field_test->options);

    // Check that if we set uri and options then the default values are properly
    // initialized.
    $entity->field_test = [
      'uri' => 'internal:/node/add',
      'options' => ['query' => NULL],
    ];
    $this->assertEquals('internal:/node/add', $entity->field_test->uri);
    $this->assertEquals($entity->field_test->resolvable_uri, '/node/add');
    $this->assertNull($entity->field_test->title);
    $this->assertNull($entity->field_test->options['query']);

    // Check that if we set the direct value of link field it correctly set the
    // uri and the default values of the field.
    $entity->field_test = 'internal:/node/add';
    $this->assertEquals('internal:/node/add', $entity->field_test->uri);
    $this->assertNull($entity->field_test->title);
    $this->assertSame([], $entity->field_test->options);

    // Check that setting options to NULL does not trigger an error when
    // calling getUrl();
    $entity->field_test->options = NULL;
    $this->assertInstanceOf(Url::class, $entity->field_test[0]->getUrl());

    // Check that setting LinkItem value NULL doesn't generate any error or
    // warning.
    $entity->field_test[0] = NULL;
    $this->assertNull($entity->field_test[0]->getValue());

    // Test the generateSampleValue() method for generic, external, and internal
    // link types.
    $entity = EntityTest::create();
    $entity->field_test->generateSampleItems();
    $entity->field_test_external->generateSampleItems();
    $entity->field_test_internal->generateSampleItems();
    $this->entityValidateAndSave($entity);

    // Test setting up computed property also sets up other values.
    $entity = EntityTest::create();
    $url = 'https://www.drupal.org?test_param=test_value#top';
    $parsed_url = UrlHelper::parse($url);
    $entity->field_test->resolvable_uri = $url;
    $this->assertEquals($parsed_url['path'], $entity->field_test->uri);
    $this->assertEquals([
      'query' => $parsed_url['query'],
      'fragment' => $parsed_url['fragment'],
      'external' => TRUE,
    ], $entity->field_test->first()->getUrl()->getOptions());
    $entity->name->value = $this->randomMachineName();
    $entity->save();

    // Verify that the field value is changed.
    $id = $entity->id();
    $entity = EntityTest::load($id);
    $this->assertInstanceOf(FieldItemListInterface::class, $entity->field_test);
    $this->assertInstanceOf(FieldItemInterface::class, $entity->field_test[0]);
    $this->assertEquals($url, $entity->field_test->resolvable_uri);
    $this->assertEquals($entity->field_test->uri, $parsed_url['path']);
    $this->assertEquals($entity->field_test[0]->uri, $parsed_url['path']);
    $this->assertEquals($entity->field_test->options['query'], $parsed_url['query']);
    $this->assertEquals($entity->field_test->options['fragment'], $parsed_url['fragment']);

    // Check that if we only set url the default values for uri, title, and
    // options are also initialized.
    $entity->field_test = ['resolvable_uri' => '/node/add'];
    $this->assertEquals($entity->field_test->uri, 'internal:/node/add');
    $this->assertEquals($entity->field_test->resolvable_uri, '/node/add');
    $this->assertNull($entity->field_test->title);
  }

}
