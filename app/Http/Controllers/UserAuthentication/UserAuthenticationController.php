<?php

namespace App\Http\Controllers\UserAuthentication;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Services\ExternalApiService;
use Illuminate\Support\Facades\Http;


class UserAuthenticationController extends Controller
{
    protected $externalApiService;

    public function __construct(
        ExternalApiService $externalApiService
    )
    {
        $this->externalApiService = $externalApiService;
    }

    public function authenticateUser(Request $request)
    {
        $data = $request->all();
    
        if (!isset($data['barcode']) || !isset($data['password'])) {
            return response()->json(['message' => 'Barcode and password are required'], 400);
        }
        $response = $this->externalApiService->userAuth($data);

        return response()->json($response);
    }


}