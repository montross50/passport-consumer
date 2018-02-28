<?php

namespace Montross50\PassportConsumer;

use Illuminate\Auth\Authenticatable;
use Illuminate\Config\Repository as Config;
use Illuminate\Database\Eloquent\Model;

class UserRepository
{
    /**
     * @var Authenticatable
     */
    protected $model;
    /**
     * @var Config
     */
    protected $config;
    protected $class;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $auth_provider = $this->config->get('passport-consumer.auth_provider_key');
        $class = $this->config->get($auth_provider);
        $this->model = new $class();
        $this->class = $class;
    }
    public function find($id)
    {
        return $this->model->find($id);
    }

    public function getByIdentifier($identifier, $value)
    {
        return $this->model->where($identifier, $value)->get()->first();
    }

    /**
     * @return Authenticatable
     */
    public function getModel()
    {
        return $this->model;
    }

    public function createUser(array $data)
    {
        $class = $this->class;
        $user = new $class();
        foreach ($user->getFillableFields() as $item) {
            if (isset($data[$item])) {
                $user->$item = $data[$item];
            }
        }
        foreach ($user->getDefaultFields() as $defaultField) {
            if (!isset($user->$defaultField)) {
                $user->$defaultField = $user->getDefaultValue($defaultField);
            }
        }
        $user->save();
        return $user;
    }
}
