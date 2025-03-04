<?php

namespace AdminHelpers\Auth\Models\Otp;

use Admin\Eloquent\AdminModel;
use Admin\Helpers\SmartSms;
use App\Mail\ClientOTPMail; //Todo:
use Mail;

class OtpToken extends AdminModel
{
    /*
     * Model created date, for ordering tables in database and in user interface
     */
    protected $migration_date = '2024-03-19 12:34:46';

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

    private $unecryptedToken;

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
            'created_at' => 'name:Created|type:datetime|default:CURRENT_TIMESTAMP',
        ];
    }

    public function setTokenResponse()
    {
        return $this->only([
            'id', 'verificator', 'identifier', 'row_id', 'valid_to', 'format', 'length', 'created_at'
        ]);
    }

    public function getTokenResponseArray()
    {
        return $this->setTokenResponse() + [
            'token' => $this->getUnecryptedToken(),
        ];
    }

    public function getLengthAttribute()
    {
        return strlen($this->format);
    }

    public function setUnecryptedToken($token)
    {
        $this->unecryptedToken = $token;
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

    private function generateTokenString($chars = 2, $numbers = 3)
    {
        $numbersGenerated = implode('', array_map(fn() => rand(0, 9), array_fill(0, $numbers, 1)));

        return strtoupper(str_random($chars).str_pad($numbersGenerated, $numbers, '0'));
    }

    public function hashToken($token)
    {
        return hash('sha256', mb_strtoupper($token));
    }

    public function generateNewToken()
    {
        $tokenString = $this->generateTokenString();

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
        if ( $this->isTestIdentifier() ){
            return $this;
        }

        $token = $this->getUnecryptedToken(true);

        if ( $this->verificator == 'email' ) {
            Mail::to($this->identifier)->send(
                new ClientOTPMail($token)
            );
        } else if ( $this->verificator == 'phone' ) {
            (new SmartSms)->sendSMS(
                $this->identifier,
                'Pre pokračovanie zadajte nasledujúci verifikačný kód: '.$token
            );
        }

        return $this;
    }

    public function replicateToken()
    {
        $newToken = $this->replicate();

        $durationOfOldTokenInMinutes = ($this->created_at)->diffInMinutes($this->valid_to);

        $newToken->valid_to = now()->addMinutes($durationOfOldTokenInMinutes);

        $newToken->generateNewToken();
        $newToken->save();

        return $newToken;
    }
}
