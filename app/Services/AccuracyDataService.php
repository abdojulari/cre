<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AccuracyDataService
{
    public function generateTestData()
    {
        return [
            [
                ['firstname' => 'Peter', 'lastname' => 'Edewor', 'email' => 'edes@example.com', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'address' => '123 Main St'],
                ['firstname' => 'Peter', 'lastname' => 'Edewor', 'email' => 'edes@example.com', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'address' => '123 Main St'],
                'expected_duplicate' => true,
            ],
            [
                ['firstname' => 'James', 'lastname' => 'Edewor', 'email' => 'yoursjames@example.com', 'dateofbirth' => '1930-01-01','phone' => '9034567890', 'address' => 'Old Sch Road'],
                ['firstname' => 'John', 'lastname' => 'Doe', 'email' => 'johndoe@example.com', 'dateofbirth' => '1940-01-01', 'phone' => '7809383344', 'address' => 'Neverland St'],
                'expected_duplicate' => false,
            ],[
                ['firstname' => 'Peter', 'lastname' => 'Edewor', 'email' => 'edes@example.com', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'address' => '123 Main St'],
                ['firstname' => 'Peter', 'lastname' => 'Edewor', 'email' => 'edes@example.com', 'dateofbirth' => '1990-01-01', 'phone' => '1234567890', 'address' => '123 Main St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'James', 'lastname' => 'Edewor', 'email' => 'yoursjames@example.com', 'dateofbirth' => '1930-01-01', 'phone' => '9034567890', 'address' => 'Old Sch Road'],
                ['firstname' => 'John', 'lastname' => 'Doe', 'email' => 'johndoe@example.com', 'dateofbirth' => '1940-01-01', 'phone' => '7809383344', 'address' => 'Neverland St'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Sophia', 'lastname' => 'Gonzalez', 'email' => 'sophia.gonzalez@example.com', 'dateofbirth' => '1989-12-05', 'phone' => '5551234567', 'address' => 'Sunset Blvd'],
                ['firstname' => 'Sophia', 'lastname' => 'Gonzalez', 'email' => 'sophia.gonzalez@example.com', 'dateofbirth' => '1989-12-05', 'phone' => '5551234567', 'address' => 'Sunset Blvd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Mason', 'lastname' => 'Smith', 'email' => 'mason.smith@example.com', 'dateofbirth' => '1995-03-10', 'phone' => '5552345678', 'address' => '123 Oak St'],
                ['firstname' => 'Mason', 'lastname' => 'Smith', 'email' => 'mason.smith@example.com', 'dateofbirth' => '1995-03-10', 'phone' => '5552345678', 'address' => '123 Oak St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Amelia', 'lastname' => 'Johnson', 'email' => 'amelia.johnson@example.com', 'dateofbirth' => '1987-06-15', 'phone' => '5553456789', 'address' => 'Elmwood Dr'],
                ['firstname' => 'Oliver', 'lastname' => 'Brown', 'email' => 'oliver.brown@example.com', 'dateofbirth' => '1992-04-23', 'phone' => '5554567890', 'address' => 'Greenhill Ave'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Charlotte', 'lastname' => 'Williams', 'email' => 'charlotte.williams@example.com', 'dateofbirth' => '1990-07-01', 'phone' => '5555678901', 'address' => 'Pine Tree Rd'],
                ['firstname' => 'Charlotte', 'lastname' => 'Williams', 'email' => 'charlotte.williams@example.com', 'dateofbirth' => '1990-07-01', 'phone' => '5555678901', 'address' => 'Pine Tree Rd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Lucas', 'lastname' => 'Martinez', 'email' => 'lucas.martinez@example.com', 'dateofbirth' => '1988-11-17', 'phone' => '5556789012', 'address' => 'Cedar Blvd'],
                ['firstname' => 'Lucas', 'lastname' => 'Martinez', 'email' => 'lucas.martinez@example.com', 'dateofbirth' => '1988-11-17', 'phone' => '5556789012', 'address' => 'Cedar Blvd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Jack', 'lastname' => 'Davis', 'email' => 'jack.davis@example.com', 'dateofbirth' => '1991-02-20', 'phone' => '5557890123', 'address' => 'River Rd'],
                ['firstname' => 'Sarah', 'lastname' => 'Johnson', 'email' => 'sarah.johnson@example.com', 'dateofbirth' => '1989-10-11', 'phone' => '5558901234', 'address' => 'Mountain View St'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Ella', 'lastname' => 'Miller', 'email' => 'ella.miller@example.com', 'dateofbirth' => '1993-05-06', 'phone' => '5559012345', 'address' => 'Maple Ave'],
                ['firstname' => 'Ella', 'lastname' => 'Miller', 'email' => 'ella.miller@example.com', 'dateofbirth' => '1993-05-06', 'phone' => '5559012345', 'address' => 'Maple Ave'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Sophia', 'lastname' => 'Taylor', 'email' => 'sophia.taylor@example.com', 'dateofbirth' => '1995-08-01', 'phone' => '5550123456', 'address' => 'Park St'],
                ['firstname' => 'Sophia', 'lastname' => 'Taylor', 'email' => 'sophia.taylor@example.com', 'dateofbirth' => '1995-08-01', 'phone' => '5550123456', 'address' => 'Park St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Aiden', 'lastname' => 'Hernandez', 'email' => 'aiden.hernandez@example.com', 'dateofbirth' => '1987-12-20', 'phone' => '5551239876', 'address' => 'Sunshine St'],
                ['firstname' => 'Mia', 'lastname' => 'Clark', 'email' => 'mia.clark@example.com', 'dateofbirth' => '1992-09-09', 'phone' => '5552340987', 'address' => 'Windy Rd'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Liam', 'lastname' => 'Martin', 'email' => 'liam.martin@example.com', 'dateofbirth' => '1993-03-25', 'phone' => '5553451098', 'address' => 'Woodland Dr'],
                ['firstname' => 'Liam', 'lastname' => 'Martin', 'email' => 'liam.martin@example.com', 'dateofbirth' => '1993-03-25', 'phone' => '5553451098', 'address' => 'Woodland Dr'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Ethan', 'lastname' => 'Lopez', 'email' => 'ethan.lopez@example.com', 'dateofbirth' => '1994-06-10', 'phone' => '5554562109', 'address' => 'Lakeside Blvd'],
                ['firstname' => 'Grace', 'lastname' => 'Moore', 'email' => 'grace.moore@example.com', 'dateofbirth' => '1991-02-15', 'phone' => '5555673210', 'address' => 'Beach St'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Maya', 'lastname' => 'Lopez', 'email' => 'maya.lopez@example.com', 'dateofbirth' => '1992-08-05', 'phone' => '5556784321', 'address' => 'Golden Gate St'],
                ['firstname' => 'Maya', 'lastname' => 'Lopez', 'email' => 'maya.lopez@example.com', 'dateofbirth' => '1992-08-05', 'phone' => '5556784321', 'address' => 'Golden Gate St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Oliver', 'lastname' => 'Thompson', 'email' => 'oliver.thompson@example.com', 'dateofbirth' => '1990-11-22', 'phone' => '5557895432', 'address' => 'Riverfront Ave'],
                ['firstname' => 'Emma', 'lastname' => 'Martinez', 'email' => 'emma.martinez@example.com', 'dateofbirth' => '1989-04-18', 'phone' => '5558906543', 'address' => 'Pine Hill Rd'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Chloe', 'lastname' => 'King', 'email' => 'chloe.king@example.com', 'dateofbirth' => '1993-05-20', 'phone' => '5559017654', 'address' => 'Spring St'],
                ['firstname' => 'Chloe', 'lastname' => 'King', 'email' => 'chloe.king@example.com', 'dateofbirth' => '1993-05-20', 'phone' => '5559017654', 'address' => 'Spring St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Luke', 'lastname' => 'Rodriguez', 'email' => 'luke.rodriguez@example.com', 'dateofbirth' => '1991-12-10', 'phone' => '5550128765', 'address' => 'Apple Blossom Ln'],
                ['firstname' => 'Luke', 'lastname' => 'Rodriguez', 'email' => 'luke.rodriguez@example.com', 'dateofbirth' => '1991-12-10', 'phone' => '5550128765', 'address' => 'Apple Blossom Ln'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Liam', 'lastname' => 'Lee', 'email' => 'liam.lee@example.com', 'dateofbirth' => '1985-04-18', 'phone' => '5552348976', 'address' => 'South St'],
                ['firstname' => 'Liam', 'lastname' => 'Lee', 'email' => 'liam.lee@example.com', 'dateofbirth' => '1985-04-18', 'phone' => '5552348976', 'address' => 'South St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Lily', 'lastname' => 'Scott', 'email' => 'lily.scott@example.com', 'dateofbirth' => '1995-07-22', 'phone' => '5557896543', 'address' => 'North Rd'],
                ['firstname' => 'Samuel', 'lastname' => 'Anderson', 'email' => 'samuel.anderson@example.com', 'dateofbirth' => '1988-01-10', 'phone' => '5551234567', 'address' => 'Cherry Blossom Ave'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Zoe', 'lastname' => 'Jackson', 'email' => 'zoe.jackson@example.com', 'dateofbirth' => '1992-06-12', 'phone' => '5553456789', 'address' => 'Maple St'],
                ['firstname' => 'Zoe', 'lastname' => 'Jackson', 'email' => 'zoe.jackson@example.com', 'dateofbirth' => '1992-06-12', 'phone' => '5553456789', 'address' => 'Maple St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Noah', 'lastname' => 'White', 'email' => 'noah.white@example.com', 'dateofbirth' => '1991-08-16', 'phone' => '5554567890', 'address' => 'Sunrise Blvd'],
                ['firstname' => 'Ella', 'lastname' => 'Harris', 'email' => 'ella.harris@example.com', 'dateofbirth' => '1994-12-20', 'phone' => '5555678901', 'address' => 'Greenwich Ave'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Ethan', 'lastname' => 'Carter', 'email' => 'ethan.carter@example.com', 'dateofbirth' => '1987-02-27', 'phone' => '5556789012', 'address' => 'Blueberry Rd'],
                ['firstname' => 'Ethan', 'lastname' => 'Carter', 'email' => 'ethan.carter@example.com', 'dateofbirth' => '1987-02-27', 'phone' => '5556789012', 'address' => 'Blueberry Rd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Chloe', 'lastname' => 'Parker', 'email' => 'chloe.parker@example.com', 'dateofbirth' => '1990-03-17', 'phone' => '5557890123', 'address' => 'Lakeside Dr'],
                ['firstname' => 'Mason', 'lastname' => 'Martinez', 'email' => 'mason.martinez@example.com', 'dateofbirth' => '1993-06-02', 'phone' => '5558901234', 'address' => 'Woodland Rd'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Ava', 'lastname' => 'Rodriguez', 'email' => 'ava.rodriguez@example.com', 'dateofbirth' => '1991-05-28', 'phone' => '5550123456', 'address' => 'Clearwater St'],
                ['firstname' => 'Ava', 'lastname' => 'Rodriguez', 'email' => 'ava.rodriguez@example.com', 'dateofbirth' => '1991-05-28', 'phone' => '5550123456', 'address' => 'Clearwater St'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Madison', 'lastname' => 'Nelson', 'email' => 'madison.nelson@example.com', 'dateofbirth' => '1989-07-13', 'phone' => '5551235678', 'address' => 'Palm Ave'],
                ['firstname' => 'Madison', 'lastname' => 'Nelson', 'email' => 'madison.nelson@example.com', 'dateofbirth' => '1989-07-13', 'phone' => '5551235678', 'address' => 'Palm Ave'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Benjamin', 'lastname' => 'Wright', 'email' => 'benjamin.wright@example.com', 'dateofbirth' => '1994-09-25', 'phone' => '5552346789', 'address' => 'Summit Rd'],
                ['firstname' => 'Benjamin', 'lastname' => 'Wright', 'email' => 'benjamin.wright@example.com', 'dateofbirth' => '1994-09-25', 'phone' => '5552346789', 'address' => 'Summit Rd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Mia', 'lastname' => 'Young', 'email' => 'mia.young@example.com', 'dateofbirth' => '1992-11-30', 'phone' => '5553457890', 'address' => 'Riverbend Rd'],
                ['firstname' => 'Liam', 'lastname' => 'King', 'email' => 'liam.king@example.com', 'dateofbirth' => '1988-04-14', 'phone' => '5554568901', 'address' => 'Mountain View Rd'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Harper', 'lastname' => 'Moore', 'email' => 'harper.moore@example.com', 'dateofbirth' => '1993-01-21', 'phone' => '5555679012', 'address' => 'Hilltop Dr'],
                ['firstname' => 'Harper', 'lastname' => 'Moore', 'email' => 'harper.moore@example.com', 'dateofbirth' => '1993-01-21', 'phone' => '5555679012', 'address' => 'Hilltop Dr'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Alexander', 'lastname' => 'Garcia', 'email' => 'alexander.garcia@example.com', 'dateofbirth' => '1991-10-09', 'phone' => '5556789123', 'address' => 'Highland Rd'],
                ['firstname' => 'Emma', 'lastname' => 'Lopez', 'email' => 'emma.lopez@example.com', 'dateofbirth' => '1990-02-14', 'phone' => '5557890234', 'address' => 'Forest Rd'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Isabella', 'lastname' => 'Hernandez', 'email' => 'isabella.hernandez@example.com', 'dateofbirth' => '1994-05-12', 'phone' => '5558901345', 'address' => 'Lake View Blvd'],
                ['firstname' => 'Isabella', 'lastname' => 'Hernandez', 'email' => 'isabella.hernandez@example.com', 'dateofbirth' => '1994-05-12', 'phone' => '5558901345', 'address' => 'Lake View Blvd'],
                'expected_duplicate' => true
            ],
            [
                ['firstname' => 'Jacob', 'lastname' => 'Gonzalez', 'email' => 'jacob.gonzalez@example.com', 'dateofbirth' => '1991-12-06', 'phone' => '5559012456', 'address' => 'Sunset Blvd'],
                ['firstname' => 'Aiden', 'lastname' => 'Taylor', 'email' => 'aiden.taylor@example.com', 'dateofbirth' => '1993-08-21', 'phone' => '5550123567', 'address' => 'River St'],
                'expected_duplicate' => false
            ],
            [
                ['firstname' => 'Ava', 'lastname' => 'Miller', 'email' => 'ava.miller@example.com', 'dateofbirth' => '1992-03-19', 'phone' => '5552346789', 'address' => 'Maple St'],
                ['firstname' => 'Ava', 'lastname' => 'Miller', 'email' => 'ava.miller@example.com', 'dateofbirth' => '1992-03-19', 'phone' => '5552346789', 'address' => 'Maple St'],
                'expected_duplicate' => false
           ]
        
        ];
    }
}