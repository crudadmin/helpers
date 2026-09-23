<?php

namespace AdminHelpers\Auth\Models\Otp;

use Mail;
use Admin;
use Exception;
use AdminHelpers\Sms\SmartSms;
use Admin\Eloquent\AdminModel;
use AdminHelpers\Auth\Mail\OTPMail;
use AdminHelpers\Auth\Concerns\HasMaskedIdentifier;
use Illuminate\Support\Str;

class OtpToken extends AdminModel
{
    use HasMaskedIdentifier;

    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-03-19 12:34:46';

    // The column collides with the Eloquent property, its value is read with getAttribute()
    protected $ignoredReservedColumns = ['table'];

    /*
     * Template name
     */
    protected $name = 'Otp';

    /*
     * Template title
     */
    protected $title = '';

    protected $sortable = false;
    protected $publishable = false;
    protected $active = false;
    public $timestamps = false;

    // Raw token
    private $unecryptedToken = null;

    public $casts = [
        'row_id' => 'integer',
    ];

    /*
     * Automatic form and database generator by fields list (prettier-ignore)
     * :name - field name
     * :type - field type (string/text/editor/select/integer/decimal/file/password/date/datetime/time/checkbox/radio)
     * ... other validation methods from laravel
     */
    public function fields()
    {
        return [
            'table' => 'name:Table|index',
            'row_id' => 'name:ID|index',
            'verificator' => 'name:Typ verifikácie',
            'identifier' => 'name:Email/Phone|index',
            'token' => 'name:Token|max:64|index|required',
            'format' => 'name:Format|type:string|max:10|required',
            'valid_to' => 'name:Valid to|type:datetime|required',
            'masked' => 'name:Hidden identifier|type:checkbox|default:0',
            'created_at' => 'name:Created|type:datetime|default:CURRENT_TIMESTAMP',
        ];
    }

    /**
     * Returns the model row this token is bound to (based on table + row_id).
     * Crudadmin stores the table name in `table`, so we resolve it by table
     * instead of a native polymorphic relation (which expects a morph alias).
     *
     * @return \Admin\Eloquent\AdminModel|null
     */
    public function getParentableAttribute()
    {
        if ( !($table = $this->getAttribute('table')) || !$this->row_id ) {
            return null;
        }

        return Admin::getModelByTable($table)?->find($this->row_id);
    }

    public function setTokenResponse()
    {
        $data = $this->only([
            'id', 'verificator', 'identifier', 'row_id', 'valid_to', 'format', 'length', 'created_at'
        ]);

        // Row bound tokens (eg. login) carry an identifier derived from the account.
        // Mask it, so we don't leak the account's e-mail/phone to the requester.
        if ( $this->row_id && $this->masked === true ) {
            $data['identifier'] = $this->getMaskedIdentifier();
        }

        return $data;
    }

    /**
     * Returns array of token response
     *
     * @param bool $verified - In some cases, we may want to return verified token response.
     *
     * @return array
     */
    public function getTokenResponseArray($verified = false)
    {
        $data = $this->setTokenResponse() + [
            'token' => $this->getUnecryptedToken(),
            'verified' => $verified,
        ];

        // Store response support
        if ( config('autoajax.store', false) === true ) {
            return [
                'verified' => $verified,
                'otp' => $data,
            ];
        }

        return $data;
    }

    public function getLengthAttribute()
    {
        return strlen($this->format);
    }

    public function setUnecryptedToken($token)
    {
        $this->unecryptedToken = $token;

        return $this;
    }

    public function getUnecryptedToken($force = false)
    {
        //1. Allow only when force is requested
        //2. Or is test identifier (eg: whitelisted number)
        //3. Is debug mode enabled
        if ( $force === true || $this->isTestIdentifier() === true || app()->hasDebugModeEnabled() ) {
            return $this->unecryptedToken;
        }
    }

    public function isTestIdentifier()
    {
        return isTestIdentifier($this->identifier) === true;
    }

    public function generateTokenString($chars = 2, $numbers = 3)
    {
        $numbersGenerated = implode('', array_map(fn() => rand(0, 9), array_fill(0, $numbers, 1)));

        return strtoupper(Str::random($chars).str_pad($numbersGenerated, $numbers, '0'));
    }

    public function hashToken($token)
    {
        return hash('sha256', mb_strtoupper($token));
    }

    public function generateNewToken()
    {
        $tokenString = $this->generateTokenString(
            config('admin_helpers.auth.otp.length.chars', 2),
            config('admin_helpers.auth.otp.length.numbers', 3),
        );

        $format = implode('', array_map(function($char){
            return is_numeric($char) ? '0' : 'x';
        }, str_split($tokenString)));

        $this->fill([
            'token' => $this->hashToken($tokenString),
            'format' => $format,
            'created_at' => now(),
        ]);

        $this->setUnecryptedToken($tokenString);

        return $this;
    }

    public function sendToken()
    {
        //Do not send test identifier
        if ( $this->isTestIdentifier() || !$this->identifier ){
            return $this;
        }

        $token = $this->getUnecryptedToken(true);

        try {
            if ( $this->verificator == 'email' ) {
                Mail::to($this->identifier)->send(
                    new OTPMail($token)
                );
            } else if ( $this->verificator == 'phone' ) {
                (new SmartSms)->sendSMS(
                    $this->identifier,
                    _('Pre pokračovanie zadajte nasledujúci verifikačný kód:').' '.$token
                );
            }
        } catch (Exception $e) {
            report($e);

            throw new Exception(_('SMS kód sa nepodarilo odoslať. Skúste neskôr prosím.'));
        }

        return $this;
    }

    public function replicateToken($params = [])
    {
        $newToken = $this->replicate()->fill($params);

        $durationOfOldTokenInMinutes = ($this->created_at)->diffInMinutes($this->valid_to);

        $newToken->valid_to = now()->addMinutes($durationOfOldTokenInMinutes);

        $newToken->generateNewToken();
        $newToken->save();

        return $newToken;
    }
}
