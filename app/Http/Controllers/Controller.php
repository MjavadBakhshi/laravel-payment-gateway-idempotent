<?php

namespace App\Http\Controllers;

use Domain\Shared\Exceptions\ActionException;

abstract class Controller
{
    function successResponse(array $data = [], string $message = "", int $status = 200)
    {
        return response()->json([
                'ok' => true,
                'data' => $data,
                'message' => $message
            ], $status);
    }

    function failedResponse(array $data = [], string $message = "", int $status = 401)
    {
        return response()->json([
                'ok' => false,
                'data' => $data,
                'message' => $message
            ], $status);
    }



}
