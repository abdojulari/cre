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
        $normalized = str_replace(['avenue', 'ave', 'nw'], ['ave', 'ave', 'nw'], $normalized);
        
        return $normalized;
    }

    // public function isDuplicate($record1, $record2) {
    //     // Age calculation
    //     $dob1 = new \DateTime($record1['dateofbirth']);
    //     $dob2 = new \DateTime($record2['dateofbirth']);
    //     $age1 = $dob1->diff(new \DateTime())->y;
    //     $age2 = $dob2->diff(new \DateTime())->y;
    
    //     $isMinor1 = $age1 < 18;
    //     $isMinor2 = $age2 < 18;
    
    //     // Normalize addresses before any comparison
    //     if (isset($record1['address']) && isset($record2['address'])) {
    //         $record1['address'] = $this->normalizeAddress($record1['address']);
    //         $record2['address'] = $this->normalizeAddress($record2['address']);
    //     }
    
    //     // If they share the same lastname
    //     if (strtolower($record1['lastname']) === strtolower($record2['lastname'])) {
    //         // Case 1: Both are minors with same lastname
    //         if ($isMinor1 && $isMinor2) {
    //             // Only compare firstname and DOB
    //             $fieldWeights = [
    //                 'firstname' => 1.0,
    //                 'dateofbirth' => 1.0
    //             ];
    //         }
    //         // Case 2: One is minor, one is adult (potential parent-child)
    //         else if ($isMinor1 xor $isMinor2) {
    //             // Allow shared contact details for parent-child
    //             return false;  // Not a duplicate
    //         }
    //         // Case 3: Both are adults with same lastname
    //         else {
    //             $fieldWeights = [
    //                 'firstname' => 1.0,
    //                 'lastname' => 1.0,
    //                 'phone' => 3.0,
    //                 'email' => 3.0,
    //                 'dateofbirth' => 1.0,
    //                 'address' => 3.0
    //             ];
    //         }
    //     }
    //     // Different lastnames
    //     else {
    //         // For different lastnames, require unique contact details
    //         if (
    //             strtolower($record1['email']) === strtolower($record2['email']) &&
    //             strtolower($record1['phone']) === strtolower($record2['phone'])
    //         ) {
    //             return true; // Consider it a duplicate if contact details match
    //         }
            
    //         $fieldWeights = [
    //             'firstname' => 1.0,
    //             'lastname' => 1.0,
    //             'dateofbirth' => 1.0,
    //             'address' => 1.0
    //         ];
    //     }
    
    //     $totalSimilarityScore = 0;
    //     $totalWeight = 0;
    
    //     foreach ($fieldWeights as $field => $weight) {
    //         if (!isset($record1[$field]) || !isset($record2[$field])) {
    //             continue;
    //         }
    //         $similarity = $this->similarity($record1[$field], $record2[$field], $weight);
    //         $totalSimilarityScore += $similarity;
    //         $totalWeight += $weight;
    //     }
    
    //     $averageSimilarity = $totalWeight > 0 ? $totalSimilarityScore / $totalWeight : 0;
        
    //     return $averageSimilarity > 75;
    // }

    public function isDuplicate($record1, $record2) {
        // Age calculation
        $dob1 = new \DateTime($record1['dateofbirth']);
        $dob2 = new \DateTime($record2['dateofbirth']);
        $age1 = $dob1->diff(new \DateTime())->y;
        $age2 = $dob2->diff(new \DateTime())->y;
    
        $isMinor1 = $age1 < 18;
        $isMinor2 = $age2 < 18;
    
        // Normalize addresses before comparison
        if (isset($record1['address']) && isset($record2['address'])) {
            $record1['address'] = $this->normalizeAddress($record1['address']);
            $record2['address'] = $this->normalizeAddress($record2['address']);
        }
    
        // If both are minors, check identifying information
        if ($isMinor1 && $isMinor2) {
            $fieldWeights = [
                'firstname' => 1.0,
                'lastname' => 1.0,
                'dateofbirth' => 1.0
            ];
        }
        // If one is minor and one is adult, focus heavily on date of birth
        else if ($isMinor1 xor $isMinor2) {
            $fieldWeights = [
                'dateofbirth' => 3.0  // Higher weight to ensure age difference is significant
            ];
        }
        // Both are adults - use all fields with higher weight on contact details
        else {
            $fieldWeights = [
                'firstname' => 1.0,
                'lastname' => 1.0,
                'phone' => 3.0,
                'email' => 3.0,
                'dateofbirth' => 1.0,
                'address' => 3.0
            ];
        }
    
        $totalSimilarityScore = 0;
        $totalWeight = 0;
    
        foreach ($fieldWeights as $field => $weight) {
            if (!isset($record1[$field]) || !isset($record2[$field])) {
                continue;
            }
            $similarity = $this->similarity($record1[$field], $record2[$field], $weight);
            $totalSimilarityScore += $similarity;
            $totalWeight += $weight;
        }
    
        $averageSimilarity = $totalWeight > 0 ? $totalSimilarityScore / $totalWeight : 0;
        
        return $averageSimilarity > 75;
    }
    
    public function similarity($str1, $str2, $weight = 1) {
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