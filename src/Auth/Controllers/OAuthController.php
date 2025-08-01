<?php

namespace AdminHelpers\Auth\Controllers;

use Illuminate\Http\Request;
use AdminHelpers\Auth\Concerns\HasOAuth;
use Admin;
use Laravel\Sanctum\PersonalAccessToken;

class OAuthController extends Controller
{
    use HasOAuth;

    /**
     * User will be moved to authorization modal page.
     * Then he need to accept and relogin.
     *
     * @param  mixed $request
     * @return void
     */
    public function oauthAuthorize(Request $request)
    {
        $this->checkApp($request->client_id);

        $code = strtolower(str_random(20));

        $this->saveAuthorizationRequest($code, $request);

        $name = $this->getOauthConfig($request->client_id, 'name');

        $promtedUrl = action([OAuthController::class, 'oauthAuthorizeRedirect'], ['code' => $code], false);

        $oauthParams = '?oauth='.$name.'&redirect='.urlencode($promtedUrl);

        return redirect('/admin'.$oauthParams);
    }

    /**
     * After successfull user reauthorization move him back to previous app
     *
     * @param  mixed $request
     * @return void
     */
    public function oauthAuthorizeRedirect(Request $request)
    {
        $params = $this->getAuthorizationRequest($request->code);

        $this->checkApp($params['client_id']);

        $token = admin()->createToken(
            'oauth.authorize',
            ['oauth.authorize'],
            now()->addMinutes(15)
        );

        return redirect($params['redirect_uri'] . '?' . http_build_query([
            'code' => $token->plainTextToken,
            'state' => $params['state'],
        ]));
    }

    /**
     * Generates access token for oauth provider
     *
     * Receives:
     * {
     *     "grant_type":"authorization_code",
     *     "client_id":"appname_...",
     *     "client_secret":"secret1234",
     *     "code":"12|...",
     *     "redirect_uri":"https://api.site.com/socialite/{driver}/callback"
     * }
     *
     * @param  mixed $request
     * @return void
     */
    public function token(Request $request)
    {
        $this->checkApp($request->client_id);

        $code = $request->code;

        if ( !($token = PersonalAccessToken::findToken($code)) || !$token->can('oauth.authorize') ) {
            abort(401, 'Invalid token');
        }

        if ( $token->expires_at?->isPast() || !($user = $token->tokenable) ){
            abort(401, 'Token expired');
        }

        // Delete old token
        $token->delete();

        $accessToken = $user->createToken('oauth');

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
            'provider' => $user->getTable(),
        ]);
    }
}