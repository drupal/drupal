<?php

/**
 * @file
 * Definition of Drupal\system\Tests\File\StreamWrapperTest.
 */

namespace Drupal\system\Tests\File;

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Drupal\Core\StreamWrapper\PublicStream;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests stream wrapper functions.
 *
 * @group File
 */
class StreamWrapperTest extends FileTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('file_test');

  /**
   * A stream wrapper scheme to register for the test.
   *
   * @var string
   */
  protected $scheme = 'dummy';

  /**
   * A fully-qualified stream wrapper class name to register for the test.
   *
   * @var string
   */
  protected $classname = 'Drupal\file_test\StreamWrapper\DummyStreamWrapper';

  function setUp() {
    // Add file_private_path setting.
    $settings = Settings::getAll();
    $request = Request::create('/');;
    $site_path = DrupalKernel::findSitePath($request);
    $settings['file_private_path'] = $site_path . '/private';
    new Settings($settings + Settings::getAll());

    parent::setUp();
  }

  /**
   * Test the getClassName() function.
   */
  function testGetClassName() {
    // Check the dummy scheme.
    $this->assertEqual($this->classname, file_stream_wrapper_get_class($this->scheme), 'Got correct class name for dummy scheme.');
    // Check core's scheme.
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', file_stream_wrapper_get_class('public'), 'Got correct class name for public scheme.');
  }

  /**
   * Test the file_stream_wrapper_get_instance_by_scheme() function.
   */
  function testGetInstanceByScheme() {
    $instance = file_stream_wrapper_get_instance_by_scheme($this->scheme);
    $this->assertEqual($this->classname, get_class($instance), 'Got correct class type for dummy scheme.');

    $instance = file_stream_wrapper_get_instance_by_scheme('public');
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), 'Got correct class type for public scheme.');
  }

  /**
   * Test the URI and target functions.
   */
  function testUriFunctions() {
    $config = $this->config('system.file');

    $instance = file_stream_wrapper_get_instance_by_uri($this->scheme . '://foo');
    $this->assertEqual($this->classname, get_class($instance), 'Got correct class type for dummy URI.');

    $instance = file_stream_wrapper_get_instance_by_uri('public://foo');
    $this->assertEqual('Drupal\Core\StreamWrapper\PublicStream', get_class($instance), 'Got correct class type for public URI.');

    // Test file_uri_target().
    $this->assertEqual(file_uri_target('public://foo/bar.txt'), 'foo/bar.txt', 'Got a valid stream target from public://foo/bar.txt.');
    $this->assertEqual(file_uri_target('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='), 'image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==', t('Got a valid stream target from a data URI.'));
    $this->assertFalse(file_uri_target('foo/bar.txt'), 'foo/bar.txt is not a valid stream.');
    $this->assertFalse(file_uri_target('public://'), 'public:// has no target.');
    $this->assertFalse(file_uri_target('data:'), 'data: has no target.');

    // Test file_build_uri() and
    // Drupal\Core\StreamWrapper\LocalStream::getDirectoryPath().
    $this->assertEqual(file_build_uri('foo/bar.txt'), 'public://foo/bar.txt', 'Expected scheme was added.');
    $this->assertEqual(file_stream_wrapper_get_instance_by_scheme('public')->getDirectoryPath(), PublicStream::basePath(), 'Expected default directory path was returned.');
    $this->assertEqual(file_stream_wrapper_get_instance_by_scheme('temporary')->getDirectoryPath(), $config->get('path.temporary'), 'Expected temporary directory path was returned.');
    $config->set('default_scheme', 'private')->save();
    $this->assertEqual(file_build_uri('foo/bar.txt'), 'private://foo/bar.txt', 'Got a valid URI from foo/bar.txt.');

    // Test file_create_url()
    // TemporaryStream::getExternalUrl() uses Url::fromRoute(), which needs
    // route information to work.
    $this->installSchema('system', 'router');
    $this->container->get('router.builder')->rebuild();
    $this->assertTrue(strpos(file_create_url('temporary://test.txt'), 'system/temporary?file=test.txt'), 'Temporary external URL correctly built.');
    $this->assertTrue(strpos(file_create_url('public://test.txt'), Settings::get('file_public_path') . '/test.txt'), 'Public external URL correctly built.');
    $this->assertTrue(strpos(file_create_url('private://test.txt'), 'system/files/test.txt'), 'Private external URL correctly built.');
  }

  /**
   * Test the scheme functions.
   */
  function testGetValidStreamScheme() {
    $this->assertEqual('foo', file_uri_scheme('foo://pork//chops'), 'Got the correct scheme from foo://asdf');
    $this->assertEqual('data', file_uri_scheme('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg=='), 'Got the correct scheme from a data URI.');
    $this->assertFalse(file_uri_scheme('foo/bar.txt'), 'foo/bar.txt is not a valid stream.');
    $this->assertTrue(file_stream_wrapper_valid_scheme(file_uri_scheme('public://asdf')), 'Got a valid stream scheme from public://asdf');
    $this->assertFalse(file_stream_wrapper_valid_scheme(file_uri_scheme('foo://asdf')), 'Did not get a valid stream scheme from foo://asdf');
  }
}
