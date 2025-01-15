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
            max-width: 250px;
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
    
    <p>This card provides you with <b>immediate access</b> to all our <a href="https://www.epl.ca/resources/">online resources</a> 
    for the <b>next 45 days</b>. You can even start placing holds on physical items before coming in by registering with <a href="https://epl.bibliocommons.com/user/registration">My EPL Account</a>.</p>
    
    <p>Soon you will have access to thousands of books, magazines, newspapers, movies, and music. 
    We encourage you to familiarize yourself with our <a href="https://www.epl.ca/borrowing-guide/">Borrowing Guide</a> which outlines the maximum number of physical and digital items you can borrow, place on hold, and the borrowing periods</p>
    <p>Download the <a href="https://www.epl.ca/epl-app/">EPL App</a> for even more convenient access!</p>

    <img src="{{ $message->embed(public_path('images/epl_app.png')) }}" alt="EPL App" class="tote-bag">
    
    <p>
        We're certain that you’ll enjoy exploring our collection, and we'd love for you to stay with us 
        after the 45 days are up. To extend your membership and continue to get access to all our 
        collection, please visit an <a href="https://epl.bibliocommons.com/v2/locations">EPL location</a> 
        with your Edmonton proof of address and a valid picture ID before then. 
    </p>
    <p>
        Plus, be sure to also check out our <a href="https://epl.bibliocommons.com/v2/events">EPL events calendar</a> 
        to find the program just right for you! 
        There's always something good cooking in <a href="https://www.epl.ca/the-kitchen/">the Kitchen</a> 
        and something wonderful being created in our <a href="https://www.epl.ca/makerspace/">Makerspaces</a>.
        Don’t miss out and register for a class today!
    </p>
    
    <p>Looking forward to seeing you soon.</p>
    
    <div class="footer">
        <p><a href="https://www.epl.ca/">epl.ca</a></p>
        <p><b>P.S. When you visit us to extend your membership in branch, you’ll also receive a FREE tote bag!</b></p
    </div>

</body>
</html>