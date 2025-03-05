<?php

namespace AdminHelpers\Auth\Utilities;

use Illuminate\Contracts\Support\Arrayable;

class AuthResponse implements Arrayable
{
    /**
     * Constructor
     *
     * @param  mixed $user
     * @param  string $tokenName
     * @param  string $userResponse
     */
    public function __construct(
        public $user,
        public $tokenName = 'default',
        public $userResponse = 'setAuthResponse',
    ) {}

    /**
     * Converts the response to an array
     *
     * @return array
     */
    public function toArray()
    {
        $data = [
            'driver' => $this->user->getTable(),
            'user' => $this->toUserResponse($this->user),
        ];

        // Add notification tokens
        if ( config('admin_helpers.notifications.enabled') && method_exists($this->user, 'notificationTokens') ) {
            $data['device_tokens'] = $this->user->notificationTokens()->pluck('token');
        }

        //We does not want create token if false has been given
        if ( $this->tokenName ) {
            $token = $this->user->createToken($this->tokenName);

            $data['token'] = [
                'token' => $token->plainTextToken,
                'expiration' => null,
            ];
        }

        return $data;
    }

    /**
     * Determines in which format should be user response
     *
     * @param  mixed $user
     * @return void
     */
    public function toUserResponse($user)
    {
        if ( $this->userResponse ) {
            return $user->{$this->userResponse}();
        }

        return $user;
    }
}
