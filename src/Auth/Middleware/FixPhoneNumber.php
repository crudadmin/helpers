<?php

namespace AdminHelpers\Auth\Middleware;

use Closure;
use Exception;
use Throwable;
use Illuminate\Http\Request;
use AdminHelpers\Auth\Concerns\HasPhoneFormat;
use Symfony\Component\HttpFoundation\Response;

class FixPhoneNumber
{
    use HasPhoneFormat;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /**
         * FIX Phone numbers located in request
         */
        foreach (['phone'] as $field) {
            try {
                if ( $phone = $request->get($field) ) {
                    $request->merge([
                        $field => $this->toPhoneFormat($phone),
                    ]);
                }
            } catch (Exception $e){
                // ...
            } catch (Throwable $e){
                // ...
            }
        }

        return $next($request);
    }
}
