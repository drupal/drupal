<?php
namespace GuzzleHttp\Ring\Future;

use React\Promise\PromiseInterface;
use React\Promise\PromisorInterface;

/**
 * Represents the result of a computation that may not have completed yet.
 *
 * You can use the future in a blocking manner using the wait() function, or
 * you can use a promise from the future to receive the result when the future
 * has been resolved.
 *
 * When the future is dereferenced using wait(), the result of the computation
 * is cached and returned for subsequent calls to wait(). If the result of the
 * computation has not yet completed when wait() is called, the call to wait()
 * will block until the future has completed.
 */
interface FutureInterface extends PromisorInterface
{
    /**
     * Returns the result of the future either from cache or by blocking until
     * it is complete.
     *
     * This method must block until the future has a result or is cancelled.
     * Throwing an exception in the wait() method will mark the future as
     * realized and will throw the exception each time wait() is called.
     * Throwing an instance of GuzzleHttp\Ring\CancelledException will mark
     * the future as realized, will not throw immediately, but will throw the
     * exception if the future's wait() method is called again.
     *
     * @return mixed
     */
    public function wait();

    /**
     * Cancels the future, if possible.
     */
    public function cancel();

    /**
     * Create and return a promise that invokes the given methods when the
     * future has a value, exception, or progress events.
     *
     * @param callable $onFulfilled Called when the promise is resolved.
     * @param callable $onRejected  Called when the promise is rejected.
     * @param callable $onProgress  Called on progress events.
     *
     * @return PromiseInterface
     */
    public function then(
        callable $onFulfilled = null,
        callable $onRejected = null,
        callable $onProgress = null
    );
}
