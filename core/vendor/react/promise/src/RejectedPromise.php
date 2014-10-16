<?php

namespace React\Promise;

class RejectedPromise implements CancellablePromiseInterface
{
    private $reason;

    public function __construct($reason = null)
    {
        if ($reason instanceof PromiseInterface) {
            throw new \InvalidArgumentException('You cannot create React\Promise\RejectedPromise with a promise. Use React\Promise\reject($promiseOrValue) instead.');
        }

        $this->reason = $reason;
    }

    public function then(callable $onFulfilled = null, callable $onRejected = null, callable $onProgress = null)
    {
        try {
            if (null === $onRejected) {
                return new RejectedPromise($this->reason);
            }

            return resolve($onRejected($this->reason));
        } catch (\Exception $exception) {
            return new RejectedPromise($exception);
        }
    }

    public function cancel()
    {
    }
}
