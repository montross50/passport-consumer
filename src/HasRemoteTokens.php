<?php

namespace Montross50\PassportConsumer;

use Illuminate\Support\Facades\Session;

trait HasRemoteTokens
{

    protected $remoteAccessToken;
    protected $remoteRefreshToken;
    protected $expiresAt;
    protected $defaultFields = [
        'password'
    ];
    protected $fillableFields = [
        'api_token'
    ];
    /**
     * Get user access token from session if there is one
     *
     * @return string|null
     */
    public function remoteAccessToken()
    {

        $token = null === $this->remoteAccessToken ? null : $this->remoteAccessToken;
        if (null === $token && Session::isStarted()) {
            $this->remoteAccessToken = Session::get('remote_access_token');
        }
        return $this->remoteAccessToken;
    }

    /**
     * Get user refresh token from session if there is one
     *
     * @return string|null
     */
    public function remoteRefreshToken()
    {
        $token = null === $this->remoteRefreshToken ? null : $this->remoteRefreshToken;
        if (null === $token && Session::isStarted()) {
            $this->remoteRefreshToken = Session::get('remote_refresh_token');
        }
        return $this->remoteRefreshToken;
    }


    /**
     * Set the current access token for the user.
     *
     * @param  string  $accessToken
     * @return $this
     */
    public function withRemoteAccessToken($accessToken)
    {
        if (Session::isStarted()) {
            Session::put('remote_access_token', $accessToken);
        }
        $this->remoteAccessToken = $accessToken;

        return $this;
    }

    /**
     * Set the current refresh token for the user.
     *
     * @param  string  $refreshToken
     * @return $this
     */
    public function withRemoteRefreshToken($refreshToken)
    {
        if (Session::isStarted()) {
            Session::put('remote_refresh_token', $refreshToken);
        }
        $this->remoteRefreshToken = $refreshToken;

        return $this;
    }

    public function getDefaultFields()
    {
        return $this->defaultFields;
    }

    public function getDefaultValue($field)
    {
        $result = "";
        switch ($field) {
            case 'password':
                $result = bcrypt(uniqid("", true));
                break;
        }
        return $result;
    }

    /**
     * @return array
     */
    public function getFillableFields()
    {
        $field = config('passport-consumer.remote_user_identifier');
        $this->fillableFields[] = $field;
        return array_merge($this->getFillable(), $this->fillableFields);
    }
}
