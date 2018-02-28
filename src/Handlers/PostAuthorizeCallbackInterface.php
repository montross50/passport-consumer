<?php
namespace Montross50\PassportConsumer\Handlers;

interface PostAuthorizeCallbackInterface
{
    /**
     * @param array ...$args
     *
     * @return mixed
     */
    public function handle(...$args);
}
