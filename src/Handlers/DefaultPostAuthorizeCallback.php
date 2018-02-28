<?php
namespace Montross50\PassportConsumer\Handlers;

/**
 * This is the default post authorize callback. It is expected that you override this either with you rown implemntation of the interface or your own closure. By default it will return the json of the token info
 *
 * Class PostAuthorizeCallback
 *
 * @package Montross50\PassportConsumer
 */
class DefaultPostAuthorizeCallback implements PostAuthorizeCallbackInterface
{

    private $closure;

    /**
     * PostAuthorizeCallback constructor.
     *
     * @param \Closure $closure
     */
    public function __construct(\Closure $closure)
    {
        $this->closure = $closure;
    }

    /**
     * @param array ...$args
     *
     * @return mixed
     */
    public function handle(...$args)
    {
        $closure = $this->closure;
        //closureception
        return $closure($args);
    }
}
