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
    public function transform(array $data): array
    {
        return [
            '@resource' => '/user/patron',
            'barcode' => $data['barcode'] ?? null,
            'lastName' => $data['lastname'] ?? null,
            'firstName' => $data['firstname'] ?? null,
            'library' => [
                '@resource' => '/policy/library',
                '@key' => $data['library'] ?? 'EPLMNA',
            ],
            'profile' => [
                '@resource' => '/policy/userProfile',
                '@key' => $data['profile'] ?? null,
            ],
            'pin' => $data['password'] ?? null,
            'privilegeExpiresDate' => '2030-10-03',
            'birthDate' => $data['dateofbirth'] ?? null,
            'category01' => [
                '@resource' => '/policy/patronCategory01',
                '@key' => $data['category1'] ?? null,
            ],
            'category03' => [
                '@resource' => '/policy/patronCategory03',
                '@key' => $data['category3'] ?? null,
            ],
            'category04' => [
                '@resource' => '/policy/patronCategory04',
                '@key' => $data['category4'] ?? null,
            ],
            'category05' => [
                '@resource' => '/policy/patronCategory05',
                '@key' => $data['category5'] ?? null,
            ],
            'category06' => [
                '@resource' => '/policy/patronCategory06',
                '@key' => $data['category6'] ?? null,
            ],
            'address1' => $this->transformAddress($data),
        ];
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
}
