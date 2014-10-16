<?php

namespace React\Promise;

use React\Promise\PromiseAdapter\CallbackPromiseAdapter;

class DeferredTest extends TestCase
{
    use PromiseTest\FullTestTrait;

    public function getPromiseTestAdapter(callable $canceller = null)
    {
        $d = new Deferred($canceller);

        return new CallbackPromiseAdapter([
            'promise'  => [$d, 'promise'],
            'resolve'  => [$d, 'resolve'],
            'reject'   => [$d, 'reject'],
            'progress' => [$d, 'progress'],
            'settle'   => [$d, 'resolve'],
        ]);
    }
}
