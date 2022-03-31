<?php

namespace App\Exceptions;

use Exception;

class NonCompletedAccount extends Exception
{
    //
    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function render($request)
    {
        return response()->json([
            'errors' => [
                'Non completed account, make sure that address, phone, username and surname is set',
            ]
        ], 403);
    }
}
