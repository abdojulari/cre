<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class PatronDataTransformer
{
    /**
     * Transform the patron data into the required format.
     *
     * @param array $data
     * @return array
     */
    public function transform(array $data, string $product): array
    {
        // Validate the profile value
        $allowedProfiles = ['EPL_SELF', 'EPL_SELFJ'];
        if (!in_array($data['profile'] ?? '', $allowedProfiles, true)) {
            throw new \InvalidArgumentException("Invalid profile value. Allowed values are: " . implode(', ', $allowedProfiles));
        }

        // Initialize the categories and remove null values
        $categories = [];

        // Add categories if they are not null
        if (!empty($data['category1'])) {
            $categories['category01'] = [
                '@resource' => '/policy/patronCategory01',
                '@key' => $data['category1']
            ];
        }

        if (!empty($data['category3'])) {
            $categories['category03'] = [
                '@resource' => '/policy/patronCategory03',
                '@key' => $data['category3']
            ];
        }

        if (!empty($data['category4'])) {
            $categories['category04'] = [
                '@resource' => '/policy/patronCategory04',
                '@key' => $data['category4']
            ];
        }

        if (!empty($data['category5'])) {
            $categories['category05'] = [
                '@resource' => '/policy/patronCategory05',
                '@key' => $data['category5']
            ];
        }

        if (!empty($data['category6'])) {
            $categories['category06'] = [
                '@resource' => '/policy/patronCategory06',
                '@key' => $data['category6']
            ];
        }

        // Prepare the main array with required fields
        $transformedData = [
            '@resource' => '/user/patron',
            '@key' => $product === 'LPASS' ? $data['key'] ?? null : null,
            'barcode' => $data['barcode'] ?? null,
            'lastName' => $data['lastname'] ?? null,
            'firstName' => $data['firstname'] ?? null,
            // Conditionally add middleName if it's not empty
            'middleName' => !empty($data['middlename']) ? $data['middlename'] : null,
            'library' => [
                '@resource' => '/policy/library',
                '@key' => $data['library'] ?? 'EPLMNA',
            ],
            'profile' => [
                '@resource' => '/policy/userProfile',
                '@key' => $data['profile'] ?? 'EPL_SELF',
            ],
            'pin' => $data['password'] ?? null,
            'privilegeExpiresDate' => $data['expirydate'] ?? null,
            'birthDate' => $data['dateofbirth'] ?? null,
            'address1' => $this->transformAddress($data),
        ];

        // Remove middleName from array if it's null or empty
        if (empty($transformedData['middleName'])) {
            unset($transformedData['middleName']);
        }

        // Merge categories into the result
        return array_merge($transformedData, $categories);
    }

    /**
     * Transform address data.
     *
     * @param array $data
     * @return array
     */
    private function transformAddress(array $data): array
    {
        return [
            [
                '@resource' => '/user/patron/address1',
                '@key' => '1',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'CITY/STATE'
                ],
                'data' => ($data['city'] ?? '') . ',' . ($data['province'] ?? ''),
            ],
            [
                '@resource' => '/user/patron/address1',
                '@key' => '5',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'POSTALCODE'
                ],
                'data' => $data['postalcode'] ?? null,
            ],
            [
                '@resource' => '/user/patron/address1',
                '@key' => '2',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'PHONE'
                ],
                'data' => $data['phone'] ?? null,
            ],
            [
                '@resource' => '/user/patron/address1',
                '@key' => '6',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'EMAIL'
                ],
                'data' => $data['email'] ?? null,
            ],
            [
                '@resource' => '/user/patron/address1',
                '@key' => '3',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'CARE/OF'
                ],
                'data' => $data['careof'] ?? null,
            ],
            [
                '@resource' => '/user/patron/address1',
                '@key' => '4',
                'code' => [
                    '@resource' => '/policy/patronAddress1',
                    '@key' => 'STREET'
                ],
                'data' => $data['address'] ?? null,
            ]
        ];
    }

    public function transformUserData(array $input): array 
    {
        // Initialize the output array
        $transformedData = [];
    
        // Ensure that input is an array
        if (empty($input)) {
            return $transformedData; // Return an empty array if input is empty
        }
    
        // Create a single object with the required fields
        $userObject = [
            'firstname' => $input['firstName'] ?? null,
            'lastname' => $input['lastName'] ?? null,
            'middlename' => $input['middleName'] ?: null, // Convert empty string to null
            'dateofbirth' => $input['birthDate'] ?? null,
            'profile' => $input['profile']['@key'] ?? null,
            'city' => null, // Not present in input
            'barcode' => $input['barcode'] ?? null,
            'category5' => null // Not present in input
        ];
    
        // Add the object to the array
        $transformedData[] = $userObject;
    
        return $transformedData;
    }
    
    public function dataHasChanged(array $existingData, array $newData) {
        $fieldsToCompare = [
            'firstname', 'lastname', 'email', 'phone', 'address', 
            'postalcode', 'city'
        ];
    
        foreach ($fieldsToCompare as $field) {
            if (($existingData[$field] ?? null) !== ($newData[$field] ?? null)) {
                return true;
            }
        }
    
        return false;
    }
    
}
