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
        
        // Log the request details
        Log::info('ILS API Request:', [
            'url' => $url,
            'sessionToken' => $sessionToken,
            'request_data' => $data
        ]);

        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'Content-Type' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
                'SD-Prompt-Return' => 'USER_PRIVILEGE_OVRCD/Y'
            ])->post($url, $data);

            // Log the raw response
            Log::info('ILS API Raw Response:', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body()
            ]);

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('ILS API Successful Response:', [
                    'response_data' => $responseData
                ]);
                return $responseData;
            } else {
                // Log unsuccessful response
                Log::error('ILS API Error Response:', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_data' => $data
                ]);
                Log::channel('slack')->error('ILS API Error Response', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'request_data' => $data
                ]);
                return null;
            }
        } catch (\Exception $e) {
            // Log the full exception details
            Log::error('ILS API Exception:', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data
            ]);
            
            Log::channel('slack')->critical('ILS API Exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'request_data' => $data
            ]);
            
            return null;
        }
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
            Log::error('ILS barcode lookup failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'barcode' => $barcode,
                'url' => $barcode_url . $barcode
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error getting the response from Barcode: ' . $e->getMessage());
            return null;
        }
    }
    // update request to ILS API
    public function updateToILS($data) {
        $sessionToken = $this->getSessionToken();
        // Prefer key supplied in payload; fallback to lookup by barcode
        $key = $data['@key'] ?? ($data['key'] ?? null);
        if (!$key) {
            $lookup = $this->retrieveILSData($data);
            if (is_array($lookup) && isset($lookup['@key'])) {
                $key = $lookup['@key'];
            }
        }

        // If no key after lookup, attempt create instead of update
        if (!$key) {
            Log::warning('No patron key found for update; attempting create', [
                'barcode' => $data['barcode'] ?? null
            ]);
            return $this->postToILS($data);
        }

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
            Log::error('ILS Update Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'url' => $url,
                'request_data' => $data
            ]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error updating to ILS API: ' . $e->getMessage());
            return null;
        }
    }

    public function userAuth($data) {
        $sessionToken = $this->getSessionToken();
        $url = config('cre.ils_base_url') . config('cre.user_auth');
        $data = [
            'barcode' => $data['barcode'],
            'password' => $data['password']
        ];
        $patronKey = '';
    
        // Attempt to authenticate user
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
            ])->post($url, $data);
    
            if ($response->successful()) {
                $patronKey = $response->json()['patronKey'] ?? null; // Safe extraction of patronKey
            } else {
                Log::error('Authentication failed. Status: ' . $response->status() . ', Response: ' . $response->body());
                return response()->json(['message' => 'Authentication failed'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error authenticating user: ' . $e->getMessage());
            return response()->json(['message' => 'Error authenticating user'], 500);
        }
    
        if (!$patronKey) {
            Log::error('Patron Key is missing.');
            return response()->json(['message' => 'Patron Key not found'], 400);
        }
    
        // Attempt to get patron data
        $patronUrl = config('cre.ils_base_url') . 'user/patron/key/' . $patronKey . '?includeFields=*,address1,birthDate,profile';
    
        try {
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.sirsidynix.roa.resource.v2+json',
                'sd-originating-app-id' => config('cre.apps_id'),
                'x-sirs-clientID' => config('cre.symws_client_id'),
                'x-sirs-sessionToken' => $sessionToken,
            ])->get($patronUrl);
    
            if ($response->successful()) {
                Log::info('Patron data: ' . json_encode($response->json()));
                $data = $this->extractUserInfo($response->json());  // Pass the array here
                return $data;
            } else {
                Log::error('Failed to fetch patron data. Status: ' . $response->status() . ', Response: ' . $response->body());
                return response()->json(['message' => 'Failed to fetch patron data'], 400);
            }
        } catch (\Exception $e) {
            Log::error('Error getting patron data: ' . $e->getMessage());
            return response()->json(['message' => 'Error getting patron data'], 500);
        }
    }
    
    function extractUserInfo(array $response): object
    {
        // Extracting the required fields from the decoded array
        $address = [];
        foreach ($response['address1'] as $addressItem) {
            $key = $addressItem['code']['@key'];
            $value = $addressItem['data'];
    
            switch ($key) {
                case 'CARE/OF':
                    $address['careOf'] = $value;
                    break;
                case 'PHONE':
                    $address['phone'] = $value;
                    break;
                case 'STREET':
                    $address['street'] = $value;
                    break;
                case 'CITY/STATE':
                    // Split the city and state
                    $cityState = explode(', ', $value);
                    if (count($cityState) == 2) {
                        $address['city'] = $cityState[0];
                        $address['province'] = $cityState[1];
                    }
                    break;
                case 'POSTALCODE':
                    $address['postalCode'] = $value;
                    break;
                case 'EMAIL':
                    $address['email'] = $value;
                    break;
            }
        }
    
        // Prepare the final result as an object
        $result = (object)[
            'lastName' => $response['lastName'],
            'firstName' => $response['firstName'],
            'middleName' => $response['middleName'] ?? '',  // This will be an empty string if not set
            'dateofbirth' => $response['birthDate'],
            'profile' => $response['profile']['@key'],
            'address' => (object)$address
        ];
    
        return $result;
    }
    
}