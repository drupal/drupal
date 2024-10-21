<?php

declare(strict_types=1);

namespace Drupal\KernelTests\Core\Field\FieldType;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Password\PasswordInterface;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Field\Plugin\Field\FieldType\PasswordItem
 * @group Field
 */
class PasswordItemTest extends FieldKernelTestBase {

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fieldStorage = FieldStorageConfig::create([
      'field_name' => 'test_field',
      'entity_type' => 'entity_test',
      'type' => 'password',
    ]);
    $this->fieldStorage->save();

    $this->field = FieldConfig::create([
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();
  }

  /**
   * @covers ::preSave
   */
  public function testPreSavePreHashed(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->test_field = 'this_is_not_a_real_hash';
    $entity->test_field->pre_hashed = TRUE;

    $entity->save();
    $this->assertSame('this_is_not_a_real_hash', $entity->test_field->value);
    $this->assertFalse($entity->test_field->pre_hashed);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveNewNull(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->test_field = NULL;

    $entity->save();
    $this->assertNull($entity->test_field->value);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveNewEmptyString(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->test_field = '';

    $entity->save();

    // The string starts with the portable password string and is a hash of an
    // empty string.
    $this->assertStringStartsWith('$2y$', $entity->test_field->value);
    $this->assertTrue($this->container->get('password')->check('', $entity->test_field->value));
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveNewMultipleSpacesString(): void {
    $entity = EntityTest::create([
      'name' => $this->randomString(),
    ]);
    $entity->test_field = '       ';

    $entity->save();

    // The string starts with the portable password string and is a hash of an
    // empty string.
    $this->assertStringStartsWith('$2y$', $entity->test_field->value);
    $this->assertTrue($this->container->get('password')->check('', $entity->test_field->value));
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveExistingNull(): void {
    $entity = EntityTest::create();
    $entity->test_field = $this->randomString();
    $entity->save();

    $this->assertNotNull($entity->test_field->value);

    $entity->test_field = NULL;
    $entity->save();

    $this->assertNull($entity->test_field->value);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveExistingEmptyString(): void {
    $entity = EntityTest::create();
    $entity->test_field = $this->randomString();
    $entity->save();

    $hashed_password = $entity->test_field->value;

    $entity->test_field = '';
    $entity->save();

    $this->assertSame($hashed_password, $entity->test_field->value);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveExistingMultipleSpacesString(): void {
    $entity = EntityTest::create();
    $entity->test_field = $this->randomString();
    $entity->save();

    $entity->test_field = '     ';
    $entity->save();

    // @todo Fix this bug in https://www.drupal.org/project/drupal/issues/3238399.
    $this->assertSame('     ', $entity->test_field->value);
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveExceptionNew(): void {
    $entity = EntityTest::create();
    $entity->test_field = str_repeat('a', PasswordInterface::PASSWORD_MAX_LENGTH + 1);
    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Failed to hash the Test entity password.');
    $entity->save();
  }

  /**
   * @covers ::preSave
   */
  public function testPreSaveExceptionExisting(): void {
    $entity = EntityTest::create();
    $entity->test_field = 'will_be_hashed';
    $entity->save();

    $this->assertNotEquals('will_be_hashed', $entity->test_field->value);

    $this->expectException(EntityStorageException::class);
    $this->expectExceptionMessage('Failed to hash the Test entity password.');
    $entity->test_field = str_repeat('a', PasswordInterface::PASSWORD_MAX_LENGTH + 1);
    $entity->save();
  }

}
