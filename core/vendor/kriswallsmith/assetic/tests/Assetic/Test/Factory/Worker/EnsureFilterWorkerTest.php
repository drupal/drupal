<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2012 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Test\Factory\Worker;

use Assetic\Factory\Worker\EnsureFilterWorker;

class EnsureFilterWorkerTest extends \PHPUnit_Framework_TestCase
{
    public function testMatch()
    {
        $filter = $this->getMock('Assetic\\Filter\\FilterInterface');
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $asset->expects($this->once())
            ->method('getTargetPath')
            ->will($this->returnValue('css/main.css'));
        $asset->expects($this->once())
            ->method('ensureFilter')
            ->with($filter);

        $worker = new EnsureFilterWorker('/\.css$/', $filter);
        $worker->process($asset);
    }

    public function testNonMatch()
    {
        $filter = $this->getMock('Assetic\\Filter\\FilterInterface');
        $asset = $this->getMock('Assetic\\Asset\\AssetInterface');

        $asset->expects($this->once())
            ->method('getTargetPath')
            ->will($this->returnValue('js/all.js'));
        $asset->expects($this->never())->method('ensureFilter');

        $worker = new EnsureFilterWorker('/\.css$/', $filter);
        $worker->process($asset);
    }
}
