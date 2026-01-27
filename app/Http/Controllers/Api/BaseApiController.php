<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    /**
     * Return a success JSON response with consistent structure.
     *
     * @param  mixed  $data
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    protected function success($data = null, string $message = 'Operation successful', int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
            'message' => $message,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Return a paginated success JSON response.
     *
     * @param  \Illuminate\Contracts\Pagination\LengthAwarePaginator  $paginator
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    protected function successPaginated($paginator, string $message = 'Data retrieved successfully'): JsonResponse
    {
        return $this->success([
            'page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'items' => $paginator->items(),
        ], $message);
    }

    /**
     * Return an error JSON response with consistent structure.
     * Note: This is mainly for manual error responses. 
     * ValidationException and other exceptions are handled by Handler.php
     *
     * @param  int  $statusCode
     * @param  string  $message
     * @param  array|object  $errors
     * @return \Illuminate\Http\JsonResponse
     */
    protected function error(int $statusCode = 400, string $message = 'An error occurred', $errors = []): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'code' => $statusCode,
            'message' => $message,
            'errors' => $errors ?: (object) [],
        ], $statusCode);
    }
}