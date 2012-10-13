<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test;

use Assetic\Asset\FileAsset;

use Assetic\AssetManager;
use Assetic\AssetWriter;

class AssetWriterTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        $this->dir = sys_get_temp_dir().'/assetic_tests_'.rand(11111, 99999);
        mkdir($this->dir);
        $this->writer = new AssetWriter($this->dir, array(
            'locale' => array('en', 'de', 'fr'),
            'browser' => array('ie', 'firefox', 'other'),
            'gzip' => array('gzip', '')
        ));
    }

    protected function tearDown()
    {
        array_map('unlink', glob($this->dir.'/*'));
        rmdir($this->dir);
    }

    public function testWriteManagerAssets()
    {
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');
        $am = $this->getMock('Assetic\\AssetManager');

        $am->expects($this->once())
            ->method('getNames')
            ->will($this->returnValue(array('foo')));
        $am->expects($this->once())
            ->method('get')
            ->with('foo')
            ->will($this->returnValue($asset));
        $asset->expects($this->atLeastOnce())
            ->method('getTargetPath')
            ->will($this->returnValue('target_url'));
        $asset->expects($this->once())
            ->method('dump')
            ->will($this->returnValue('content'));
        $asset->expects($this->atLeastOnce())
            ->method('getVars')
            ->will($this->returnValue(array()));
        $asset->expects($this->atLeastOnce())
            ->method('getValues')
            ->will($this->returnValue(array()));

        $this->writer->writeManagerAssets($am);

        $this->assertFileExists($this->dir.'/target_url');
        $this->assertEquals('content', file_get_contents($this->dir.'/target_url'));
    }

    public function testWriteAssetWithVars()
    {
        $asset = $this->getMock('Assetic\Asset\AssetInterface');
        $asset->expects($this->atLeastOnce())
            ->method('getVars')
            ->will($this->returnValue(array('locale')));

        $self = $this;
        $expectedValues = array(
            array('locale' => 'en'),
            array('locale' => 'de'),
            array('locale' => 'fr'),
        );
        $asset->expects($this->exactly(3))
            ->method('setValues')
            ->will($this->returnCallback(function($values) use ($self, $expectedValues) {
                static $counter = 0;
                $self->assertEquals($expectedValues[$counter++], $values);
            }));
        $asset->expects($this->exactly(3))
            ->method('getValues')
            ->will($this->returnCallback(function() use ($expectedValues) {
                static $counter = 0;

                return $expectedValues[$counter++];
            }));

        $asset->expects($this->exactly(3))
            ->method('dump')
            ->will($this->onConsecutiveCalls('en', 'de', 'fr'));

        $asset->expects($this->atLeastOnce())
            ->method('getTargetPath')
            ->will($this->returnValue('target.{locale}'));

        $this->writer->writeAsset($asset);

        $this->assertFileExists($this->dir.'/target.en');
        $this->assertFileExists($this->dir.'/target.de');
        $this->assertFileExists($this->dir.'/target.fr');
        $this->assertEquals('en', file_get_contents($this->dir.'/target.en'));
        $this->assertEquals('de', file_get_contents($this->dir.'/target.de'));
        $this->assertEquals('fr', file_get_contents($this->dir.'/target.fr'));
    }

    public function testAssetWithInputVars()
    {
        $asset = new FileAsset(__DIR__.'/Fixture/messages.{locale}.js',
            array(), null, null, array('locale'));
        $asset->setTargetPath('messages.{locale}.js');

        $this->writer->writeAsset($asset);

        $this->assertFileExists($this->dir.'/messages.en.js');
        $this->assertFileExists($this->dir.'/messages.de.js');
        $this->assertFileExists($this->dir.'/messages.fr.js');
        $this->assertEquals('var messages = {"text.greeting": "Hello %name%!"};',
            file_get_contents($this->dir.'/messages.en.js'));
        $this->assertEquals('var messages = {"text.greeting": "Hallo %name%!"};',
            file_get_contents($this->dir.'/messages.de.js'));
        $this->assertEquals('var messages = {"text.greet": "All\u00f4 %name%!"};',
            file_get_contents($this->dir.'/messages.fr.js'));
    }

    /**
     * @dataProvider getCombinationTests
     */
    public function testGetCombinations($vars, $expectedCombinations)
    {
        $ref = new \ReflectionMethod($this->writer, 'getCombinations');
        $ref->setAccessible(true);

        $this->assertEquals($expectedCombinations, $ref->invoke($this->writer, $vars));
    }

    public function getCombinationTests()
    {
        $tests = array();

        // no variables
        $tests[] = array(
            array(),
            array(array())
        );

        // one variables
        $tests[] = array(
            array('locale'),
            array(
                array('locale' => 'en'),
                array('locale' => 'de'),
                array('locale' => 'fr'),
            )
        );

        // two variables
        $tests[] = array(
            array('locale', 'browser'),
            array(
                array('locale' => 'en', 'browser' => 'ie'),
                array('locale' => 'de', 'browser' => 'ie'),
                array('locale' => 'fr', 'browser' => 'ie'),
                array('locale' => 'en', 'browser' => 'firefox'),
                array('locale' => 'de', 'browser' => 'firefox'),
                array('locale' => 'fr', 'browser' => 'firefox'),
                array('locale' => 'en', 'browser' => 'other'),
                array('locale' => 'de', 'browser' => 'other'),
                array('locale' => 'fr', 'browser' => 'other'),
            )
        );

        // three variables
        $tests[] = array(
            array('locale', 'browser', 'gzip'),
            array(
                array('locale' => 'en', 'browser' => 'ie', 'gzip' => 'gzip'),
                array('locale' => 'de', 'browser' => 'ie', 'gzip' => 'gzip'),
                array('locale' => 'fr', 'browser' => 'ie', 'gzip' => 'gzip'),
                array('locale' => 'en', 'browser' => 'firefox', 'gzip' => 'gzip'),
                array('locale' => 'de', 'browser' => 'firefox', 'gzip' => 'gzip'),
                array('locale' => 'fr', 'browser' => 'firefox', 'gzip' => 'gzip'),
                array('locale' => 'en', 'browser' => 'other', 'gzip' => 'gzip'),
                array('locale' => 'de', 'browser' => 'other', 'gzip' => 'gzip'),
                array('locale' => 'fr', 'browser' => 'other', 'gzip' => 'gzip'),
                array('locale' => 'en', 'browser' => 'ie', 'gzip' => ''),
                array('locale' => 'de', 'browser' => 'ie', 'gzip' => ''),
                array('locale' => 'fr', 'browser' => 'ie', 'gzip' => ''),
                array('locale' => 'en', 'browser' => 'firefox', 'gzip' => ''),
                array('locale' => 'de', 'browser' => 'firefox', 'gzip' => ''),
                array('locale' => 'fr', 'browser' => 'firefox', 'gzip' => ''),
                array('locale' => 'en', 'browser' => 'other', 'gzip' => ''),
                array('locale' => 'de', 'browser' => 'other', 'gzip' => ''),
                array('locale' => 'fr', 'browser' => 'other', 'gzip' => ''),
            )
        );

        return $tests;
    }
}
