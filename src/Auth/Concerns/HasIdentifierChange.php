<?php

namespace AdminHelpers\Auth\Concerns;

use AdminHelpers\Auth\Concerns\HasOTPAuthorization;

trait HasIdentifierChange
{
    use HasOTPAuthorization;

    public function sendOtp()
    {
        return $this->sendIdentifierOtp($this->getIdentifierField());
    }

    public function verifyOtp()
    {
        $field = $this->getIdentifierField();

        return $this->verifyIdentifier($field, $this->getIdentifierChangedMessage($field));
    }

    /**
     * Which identifiers can be changed through this flow.
     *
     * @return array
     */
    public function changeableIdentifiers()
    {
        return ['email', 'phone'];
    }

    /**
     * Returns validated identifier field (verificator) from the request.
     *
     * @return string
     */
    protected function getIdentifierField()
    {
        $field = request('verificator');

        if ( !in_array($field, $this->changeableIdentifiers(), true) ) {
            autoAjax()->error(_('Neplatný spôsob overenia.'))->throw();
        }

        return $field;
    }

    /**
     * Returns success message for the changed identifier.
     *
     * @param  string  $field
     * @return string
     */
    protected function getIdentifierChangedMessage($field)
    {
        return [
            'email' => _('E-mail bol úspešne zmenený.'),
            'phone' => _('Tel. číslo bolo úspešne zmenené.'),
        ][$field] ?? _('Údaj bol úspešne zmenený.');
    }

    /**
     * Send OTP verification code to the new identifier of the logged user.
     *
     * @param  string  $field
     * @return \AutoAjax\AutoAjax
     */
    protected function sendIdentifierOtp($field)
    {
        $user = $this->getIdentifierUser();

        // Validate new value using the model rules (format, uniqueness, ...)
        $data = $user->validator()->only([$field])->validate()->getData();

        $value = $data[$field];

        // No point in changing to the same, already verified value
        if ( $user->{$field} === $value && $user->isVerified($field, $value) ) {
            return autoAjax()->error(_('Zadali ste rovnakú hodnotu, akú už máte nastavenú.'));
        }

        // Create and send OTP token, same mechanism as registration
        $token = $this->createToken($value, $field, 60)->sendToken();

        return $this->tokenSendResponse($token);
    }

    /**
     * Verify OTP code for the new identifier and persist it on the logged user.
     *
     * @param  string  $field
     * @param  string  $successMessage
     * @return \AutoAjax\AutoAjax
     */
    protected function verifyIdentifier($field, $successMessage)
    {
        $user = $this->getIdentifierUser();

        $this->validate(request(), [
            'identifier' => 'required',
            'token' => 'required',
        ]);

        if ( !($token = $this->findToken(request('token'), request('identifier'))) ) {
            return autoAjax()->error(_('Overovací kód nie je správny.'), 401);
        }

        // Use the trusted identifier the token was actually sent to
        $value = $token->identifier;

        // Make sure the value was not taken by someone else meanwhile
        $isTaken = $user->newQuery()
                        ->where($field, $value)
                        ->where($user->getKeyName(), '!=', $user->getKey())
                        ->exists();

        if ( $isTaken ) {
            return autoAjax()->error(_('Túto hodnotu medzičasom začal používať iný účet.'));
        }

        $user->{$field} = $value;
        $user->addVerified($field, $value);
        $user->save();

        $token->forceDelete();

        return $this->getIdentifierChangedResponse($user, $successMessage);
    }

    /**
     * Returns the currently authenticated user whose identifier is being changed.
     *
     * @return \Admin\Eloquent\AdminModel
     */
    protected function getIdentifierUser()
    {
        if ( !($user = auth()->user()) ) {
            autoAjax()->error(_('Pre pokračovanie sa musíte prihlásiť.'), 401)->throw();
        }

        return $user;
    }

    /**
     * Response returned after the identifier has been successfully changed.
     * Projects may override this to inject their own auth/bootstrap payload.
     *
     * @param  \Admin\Eloquent\AdminModel  $user
     * @param  string  $message
     * @return \AutoAjax\AutoAjax
     */
    protected function getIdentifierChangedResponse($user, $message)
    {
        return autoAjax()->message($message);
    }
}
