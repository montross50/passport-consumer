<?php

namespace Montross50\PassportConsumer;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;

class UserRepository
{
    /**
     * @var Model
     */
    protected $model;
    /**
     * @var Config
     */
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $auth_provider = $this->config->get('passport-consumer.auth_provider_key');
        $class = $this->config->get($auth_provider);
        $this->model = new $class();
    }
    public function find($id)
    {
        return $this->model->find($id);
    }

    public function getByIdentifier($identifier,$value)
    {
        return $this->model->where($identifier,$value)->get()->first();
    }
}