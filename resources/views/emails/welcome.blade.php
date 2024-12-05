<!-- resources/views/emails/library-card-welcome.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Edmonton Public Library</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .logo {
            max-width: 250px;
            margin-bottom: 20px;
        }
        .tote-bag {
            max-width: 150px;
            margin: 15px 0;
        }
        .cta-links {
            background-color: #f4f4f4;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <img src="{{ $message->embed(public_path('images/logo-on-email.png')) }}" alt="Edmonton Public Library Logo" class="logo">
    
    <h1>Dear {{ $firstname }},</h1>
    
    <p>We're happy you joined the EPL family!</p>
    
    <div class="cta-links">
        <h3>Your Account Information:</h3>
        <p><strong>Name:</strong> {{ $firstname }} {{ $lastname }}<br>
        <strong>Card Number:</strong> {{ $barcode }} <br>
        <strong>Expiry Date:</strong> {{ $expiryDate }}</p>
    </div>
    
    <p>This card provides you with immediate access to all our <a href="https://www.epl.ca/resources/">online resources</a> for the next 90 days. You can even start placing holds before coming in by registering with <a href="https://epl.bibliocommons.com/user/registration">My EPL Account</a>.</p>
    
    <p>Soon you will have access to thousands of books, magazines, newspapers, movies, and music. Please ensure to read our <a href="https://www.epl.ca/borrowing-guide/">Borrowing Guide</a> to familiarize yourself with the number of physical and digital items you can borrow, place on hold, and for how long.</p>
    
    <p>You will need to bring proof of address and picture ID to an <a href="https://epl.bibliocommons.com/v2/locations">EPL location</a> to begin physical borrowing and to extend your membership from 90 days to as long as you live in Edmonton. Plus, you'll receive a FREE tote bag when you visit us the first time!</p>
    
    <img src="{{ $message->embed(public_path('images/tote_bag.jpg')) }}" alt="EPL Tote Bag" class="tote-bag">
    
    <p>Be sure to also checkout our <a href="https://epl.bibliocommons.com/v2/events">EPL events calendar</a> to find the program just right for you! There's always something good cooking in <a href="https://www.epl.ca/the-kitchen/">the Kitchen</a> and something wonderful being created in our <a href="https://www.epl.ca/makerspace/">Makerspaces</a>.</p>
    
    <p>Download the <a href="https://www.epl.ca/epl-app/">EPL App</a> for even more convenient access!</p>
    
    <p>We look forward to seeing you soon.</p>
    
    <p>Welcome to the EPL family!</p>
    
    <div class="footer">
        <p><a href="https://www.epl.ca/">epl.ca</a></p>
    </div>
</body>
</html>