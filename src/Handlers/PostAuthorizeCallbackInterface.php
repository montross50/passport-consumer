<?php
namespace Montross50\PassportConsumer;

interface PostAuthorizeCallbackInterface {
    /**
     * @param array ...$args
     *
     * @return mixed
     */
    public function handle(...$args);

}