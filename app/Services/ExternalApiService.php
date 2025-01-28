<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class ExternalApiService 
{
    public function getSessionToken() {
        $url = config('cre.ils_base_url') . config('cre.endpoint');
        $body = [
            'login' => config('cre.symws_user'),
            'password' => config('cre.symws_pass')
        ];
      
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-sirs-clientID' => config('cre.symws_client_id'),
            'Content-Type' => 'application/x-www-form-urlencoded',
        ])->asForm()->post($url, $body);
        if ($response->successful()) {
            return $response->json()['sessionToken'];
        }
        return null;
    }

    public function postToILS($data) {
        //Call the ILS API endpoint to save the data
        $sessionToken = $this->getSessionToken();
        $url = config('cre.ils_base_url') . config('cre.patron_endpoint');
        $response = Http::withHeaders([
            'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
            'Content-Type' => 'application/vnd.sirsidynix.roa.resource.v2+json',
            'sd-originating-app-id' => config('cre.apps_id'),
            'x-sirs-clientID' => config('cre.symws_client_id'),
            'x-sirs-sessionToken' => $sessionToken,
            'SD-Prompt-Return' => 'USER_PRIVILEGE_OVRCD/Y'
        ])->post($url, $data);
        if ($response->successful()) {
            Log::info('ILS API response', $response->json());
            return $response->json();
        }
        Log::info('FAILURE', $response->json());
        return response()->json(['message' => 'Error posting to ILS API'], 500);
    }

    // retrieve data from ILS API
    public function retrieveILSData($data){
        $sessionToken = $this->getSessionToken();
        $barcode = $data['barcode'];
        $barcode_url = config('cre.ils_base_url') . config('cre.barcode_url');
       
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
            ])->get($barcode_url . $barcode);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error getting the response from Barcode: ' . $e->getMessage());
            return response()->json(['message' => 'Error getting response from staff url'], 500);
        }
    }
    // update request to ILS API
    public function updateToILS($data) {
        $sessionToken = $this->getSessionToken();
        $key = $this->retrieveILSData($data)['@key'];
        Log::info('Barcode response', ['key' => $key]);
        $url = config('cre.ils_base_url') . config('cre.patron_endpoint') .'/key/'.$key;
        try {
            
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'Content-Type' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
                'SD-Prompt-Return' => 'USER_PRIVILEGE_OVRCD/Y'
            ])->put($url, $data);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error updating to ILS API: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating to ILS API'], 500);
        }
    }

    public function userAuth($data) {
        $sessionToken = $this->getSessionToken();
        $url = config('cre.ils_base_url') . config('cre.user_auth');
        $data = [
            'barcode' => $data['barcode'],
            'password' => $data['password']
        ];
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
            ])->post($url, $data);
            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::error('Error authenticating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error authenticating user'], 500);
        }
        
    }
}