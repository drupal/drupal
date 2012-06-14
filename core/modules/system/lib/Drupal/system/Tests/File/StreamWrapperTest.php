<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\StreamWrapperTest.
 */

namespace Drupal\system\Tests\File;

use Drupal\simpletest\WebTestBase;

/**
 * Tests stream wrapper functions.
 */
class StreamWrapperTest extends WebTestBase {

  protected $scheme = 'dummy';
  protected $classname = 'Drupal\file_test\DummyStreamWrapper';

  public static function getInfo() {
    return array(
      'name' => 'Stream wrappers',
      'description' => 'Tests stream wrapper functions.',
      'group' => 'File API',
    );
  }

  function setUp() {
    parent::setUp('file_test');
    drupal_static_reset('file_get_stream_wrappers');
  }

  function tearDown() {
    parent::tearDown();
    stream_wrapper_unregister($this->scheme);
  }

  /**
   * Test the getClassName() function.
   */
  function testGetClassName() {
    // Check the dummy scheme.
    $this->assertEqual($this->classname, file_stream_wrapper_get_class($this->scheme), t('Got correct class name for dummy scheme.'));
    // Check core's scheme.
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', file_stream_wrapper_get_class('public'), t('Got correct class name for public scheme.'));
  }

  /**
   * Test the file_stream_wrapper_get_instance_by_scheme() function.
   */
  function testGetInstanceByScheme() {
    $instance = file_stream_wrapper_get_instance_by_scheme($this->scheme);
    $this->assertEqual($this->classname, get_class($instance), t('Got correct class type for dummy scheme.'));

    $instance = file_stream_wrapper_get_instance_by_scheme('public');
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), t('Got correct class type for public scheme.'));
  }

  /**
   * Test the URI and target functions.
   */
  function testUriFunctions() {
    $instance = file_stream_wrapper_get_instance_by_uri($this->scheme . '://foo');
    $this->assertEqual($this->classname, get_class($instance), t('Got correct class type for dummy URI.'));

    $instance = file_stream_wrapper_get_instance_by_uri('public://foo');
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), t('Got correct class type for public URI.'));

    // Test file_uri_target().
    $this->assertEqual(file_uri_target('public://foo/bar.txt'), 'foo/bar.txt', t('Got a valid stream target from public://foo/bar.txt.'));
    $this->assertFalse(file_uri_target('foo/bar.txt'), t('foo/bar.txt is not a valid stream.'));

    // Test file_build_uri() and
    // Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath().
    $this->assertEqual(file_build_uri('foo/bar.txt'), 'public://foo/bar.txt', t('Expected scheme was added.'));
    $this->assertEqual(file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath(), variable_get('file_public_path'), t('Expected default directory path was returned.'));
    $this->assertEqual(file_stream_wrapper_get_instance_by_scheme('temporary')->getDirectoryPath(), variable_get('file_temporary_path'), t('Expected temporary directory path was returned.'));

    variable_set('file_default_scheme', 'private');
    $this->assertEqual(file_build_uri('foo/bar.txt'), 'private://foo/bar.txt', t('Got a valid URI from foo/bar.txt.'));
  }

  /**
   * Test the scheme functions.
   */
  function testGetValidStreamScheme() {
    $this->assertEqual('foo', file_uri_scheme('foo://pork//chops'), t('Got the correct scheme from foo://asdf'));
    $this->assertTrue(file_stream_wrapper_valid_scheme(file_uri_scheme('public://asdf')), t('Got a valid stream scheme from public://asdf'));
    $this->assertFalse(file_stream_wrapper_valid_scheme(file_uri_scheme('foo://asdf')), t('Did not get a valid stream scheme from foo://asdf'));
  }
}
