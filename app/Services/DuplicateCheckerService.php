<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DuplicateCheckerService 
{
    public function retrieveDuplicateUsingCache($data, $redis) {
        // Retrieve duplicates from Redis using Predis
        foreach ($redis as $duplicate) {
            if ($this->isDuplicate($duplicate, $data)) {
                return $duplicate; // Return the duplicate record details
            }
        }
        return null;
    }
    public function normalizeAddress($address) {
        // Convert to lowercase
        $normalized = strtolower($address);
        
        // Remove punctuation
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        
        // Standardize common abbreviations
        $normalized = str_replace(
            ['avenue', 'ave', 'nw', 'street', 'st', 'str', 'boulevard', 'blvd', 'road', 'rd', 'drive', 'dr'],
            ['ave', 'ave', 'nw', 'st', 'st', 'st', 'blvd', 'blvd', 'rd', 'rd', 'dr', 'dr'],
            $normalized
        );
        
        return $normalized;
    }
    
    //TODO: get rid of this function when everything is working
    public function isDuplicate($record1, $record2) {

        // data source from the record2
        $dataSource = $record2['source'];
        // Age calculation
        $dob1 = new \DateTime($record1['dateofbirth']);
        $dob2 = new \DateTime($record2['dateofbirth']);
        $age1 = $dob1->diff(new \DateTime())->y;
        $age2 = $dob2->diff(new \DateTime())->y;
    
        $isMinor1 = $age1 < 18;
        $isMinor2 = $age2 < 18;
    
        // Exempt CIC minors from duplicate checking
        if ($dataSource === 'CIC' && $isMinor2) {
            return false;
        }
    
        // Normalize addresses before any comparison
        if (isset($record1['address']) && isset($record2['address'])) {
            $record1['address'] = $this->normalizeAddress($record1['address']);
            $record2['address'] = $this->normalizeAddress($record2['address']);
        }
    
        // If they share the same lastname
        if (strtolower($record1['lastname']) === strtolower($record2['lastname'])) {
            $includeContactEither = false; // treat phone/email as an either-group when true
            // Case 1: Both are minors with same lastname
            if ($isMinor1 && $isMinor2) {
                // Only compare firstname and DOB
                $fieldWeights = [
                    'firstname' => 1.0,
                    'dateofbirth' => 1.0
                ];
            }
            // Case 2: One is minor, one is adult (potential parent-child)
            else if ($isMinor1 xor $isMinor2) {
                // Allow shared contact details for parent-child
                return false;  // Not a duplicate
            }
            // Case 3: Both are adults with same lastname
            else {
                // if the data source is CRP, return false
                Log::info('Data source:', ['dataSource' => $dataSource]);
                if ($dataSource === 'CRP') {
                    // phone/email are handled as an either-group worth 3.0 total
                    $fieldWeights = [
                        'firstname' => 1.0,
                        'lastname' => 1.0,
                        'dateofbirth' => 1.0,
                        'address' => 3.0
                    ];
                    $includeContactEither = true;
                } else {
                    // Default adult-same-lastname case: phone and email counted separately
                    $fieldWeights = [
                        'firstname' => 1.0,
                        'lastname' => 1.0,
                        'phone' => 3.0,
                        'email' => 3.0,
                        'dateofbirth' => 1.0,
                        'address' => 3.0
                    ];
                    $includeContactEither = false;
                }
            }
        }
        // Different lastnames
        else {
            // Case 4: Both are minors with different lastnames
            if ($isMinor1 && $isMinor2) {
                // Only compare firstname and DOB
                // $fieldWeights = [
                //     'firstname' => 1.0,
                //     'dateofbirth' => 1.0
                // ];
                return false;
            }
            
            // Case 5: One is minor, one is adult with different lastnames
            else if ($isMinor1 xor $isMinor2) {
                // Allow shared contact details for parent-child
                return false;  // Not a duplicate
            }
            // For different lastnames, require unique contact details
            else {
                // Only treat email/phone as matching when both sides are present and non-empty
                $emailsPresent = isset($record1['email'], $record2['email']) && $record1['email'] !== '' && $record2['email'] !== '';
                $phonesPresent = isset($record1['phone'], $record2['phone']) && $record1['phone'] !== '' && $record2['phone'] !== '';

                if (
                    ($emailsPresent && strcasecmp($record1['email'], $record2['email']) === 0) ||
                    ($phonesPresent && $this->normalizePhone($record1['phone']) === $this->normalizePhone($record2['phone']))
                ) {
                    return true; // Consider it a duplicate if contact details match
                }
                
                $fieldWeights = [
                    'firstname' => 1.0,
                    'lastname' => 1.0,
                    'dateofbirth' => 1.0,
                    'address' => 1.0
                ];
            }
            
        }
    
        $totalSimilarityScore = 0;
        $totalWeight = 0;

        // Handle phone/email as an either-group with combined weight 3.0 when enabled
        if (isset($includeContactEither) && $includeContactEither === true) {
            $hasPhone = isset($record1['phone'], $record2['phone']) && $record1['phone'] !== '' && $record2['phone'] !== '';
            $hasEmail = isset($record1['email'], $record2['email']) && $record1['email'] !== '' && $record2['email'] !== '';

            // Require at least one of phone or email to be present
            if (!$hasPhone && !$hasEmail) {
                return false;
            }

            $contactWeight = 3.0;
            $maxContactSimWeighted = 0;

            if ($hasPhone) {
                $maxContactSimWeighted = max($maxContactSimWeighted, $this->similarity($record1['phone'], $record2['phone'], $contactWeight));
            }
            if ($hasEmail) {
                $maxContactSimWeighted = max($maxContactSimWeighted, $this->similarity($record1['email'], $record2['email'], $contactWeight));
            }

            // Apply only the best of phone/email once and count weight once
            $totalSimilarityScore += $maxContactSimWeighted;
            $totalWeight += $contactWeight;
        }
    
        foreach ($fieldWeights as $field => $weight) {
            if (!isset($record1[$field]) || !isset($record2[$field])) {
                continue;
            }
            // Skip empty values to avoid inflating similarity (e.g., "" vs "" should not count)
            $value1 = is_string($record1[$field]) ? trim($record1[$field]) : $record1[$field];
            $value2 = is_string($record2[$field]) ? trim($record2[$field]) : $record2[$field];
            if ($value1 === '' || $value2 === '') {
                continue;
            }
            $similarity = $this->similarity($value1, $value2, $weight);
            $totalSimilarityScore += $similarity;
            $totalWeight += $weight;
        }
    
        $averageSimilarity = $totalWeight > 0 ? $totalSimilarityScore / $totalWeight : 0;
        
        return $averageSimilarity > 75;
    }

    private function normalizePhone($phone) {
        // Keep digits only for reliable comparison
        return preg_replace('/\D+/', '', (string)$phone);
    }

    public function similarity($str1, $str2, $weight = 1) {
         // Convert both strings to lowercase to avoid case sensitivity issues
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
            
        // Calculate Levenshtein distance
        $levenshtein = levenshtein($str1, $str2);
        $maxLen = max(strlen($str1), strlen($str2));
        
        // If maxLen is 0, return 100 similarity (i.e., same string)
        if ($maxLen == 0) {
            return 100 * $weight;
        }
    
        // Calculate normalized similarity score
        $normalizedSimilarity = (1 - $levenshtein / $maxLen) * 100;
        
        // Apply the weight to the similarity score
        return $normalizedSimilarity * $weight;
    }

    public function checkCareofRegistrationLimit($redisService, $careofName, $phone, $email) {
        // Retrieve the record from Redis using the key "cre_registration_record"
        $record = $redisService->get('cre_registration_record');
        
        // Convert to JSON if not already converted
        $data = is_string($record) ? json_decode($record, true) : $record;
        
        if (!is_array($data)) {
            Log::error('Invalid data format retrieved from Redis.');
            return false;
        }
        
        // Count the number of occurrences of the specific "careof" name, phone, and email
        $careofCount = 0;
        foreach ($data as $entry) {
            if (
                isset($entry['careof'], $entry['phone'], $entry['email']) &&
                strtolower($entry['careof']) === strtolower($careofName) &&
                strtolower($entry['phone']) === strtolower($phone) &&
                strtolower($entry['email']) === strtolower($email)
            ) {
                $careofCount++;
            }
        }
        
        // Check if the count exceeds the maximum allowed
        $maxAllowed = 10;
        if ($careofCount >= $maxAllowed) {
            Log::info("Registration limit reached for careof: $careofName with phone: $phone and email: $email");
            return false; // Cannot register more minors
        }
        
        return true; // Can register more minors
    }
}