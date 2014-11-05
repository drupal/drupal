<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2013 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Tests\Candidates;

use Symfony\Cmf\Component\Routing\Candidates\Candidates;
use Symfony\Component\HttpFoundation\Request;

class CandidatesTest extends \PHPUnit_Framework_Testcase
{
    /**
     * Everything is a candidate
     */
    public function testIsCandidate()
    {
        $candidates = new Candidates();
        $this->assertTrue($candidates->isCandidate('/routes'));
        $this->assertTrue($candidates->isCandidate('/routes/my/path'));
    }

    /**
     * Nothing should be called on the query builder
     */
    public function testRestrictQuery()
    {
        $candidates = new Candidates();
        $candidates->restrictQuery(null);
    }

    public function testGetCandidates()
    {
        $request = Request::create('/my/path.html');

        $candidates = new Candidates();
        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/my/path.html',
                '/my/path',
                '/my',
                '/',
            ),
            $paths
        );
    }

    public function testGetCandidatesLocales()
    {
        $candidates = new Candidates(array('de', 'fr'));

        $request = Request::create('/fr/path.html');
        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/fr/path.html',
                '/fr/path',
                '/fr',
                '/',
                '/path.html',
                '/path'
            ),
            $paths
        );

        $request = Request::create('/it/path.html');
        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/it/path.html',
                '/it/path',
                '/it',
                '/',
            ),
            $paths
        );
    }

    public function testGetCandidatesLimit()
    {
        $candidates = new Candidates(array(), 1);

        $request = Request::create('/my/path/is/deep.html');

        $paths = $candidates->getCandidates($request);

        $this->assertEquals(
            array(
                '/my/path/is/deep.html',
                '/my/path/is/deep',
            ),
            $paths
        );
    }
}
