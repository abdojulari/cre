<!-- resources/views/emails/library-card-welcome.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discover EPL with Your New Library Card</title>
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
        ul {
            list-style-type: disc;
            padding-left: 20px;
        }
        ul li {
            margin-bottom: 10px;
        }
        ul li a {
            color: #007BFF;
            text-decoration: none;
        }
        ul li a:hover {
            text-decoration: underline;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <img src="{{ $message->embed(public_path('images/logo-on-email.png')) }}" alt="Edmonton Public Library Logo" class="logo">
    
    <h1>Hello {{ $firstname }},</h1>
    
    <p>We're happy you joined the EPL family!</p>
    
    <div class="cta-links">
        <h3>Here is your account information:</h3>
        <p>
            <strong>Name:</strong> {{ $firstname }} {{ $lastname }}<br>
            <strong>Card Number:</strong> {{ $barcode }} <br>
            @if($source != 'CRP')
            <strong>Expiry Date:</strong> {{ $expiryDate }}
            @endif
        </p>
    </div>

    @if($source == 'OLR')
        <p>This library card gives you <b>immediate access</b> to all our <a href="https://www.epl.ca/resources/" target="_blank">online resources</a> 
        for the <b>next 45 days</b>. You can access these resources by logging into your account with your library card number and PIN.</p>
    @elseif(!in_array($source, ['CRP', 'OLR']))
    <p>
        This library card gives you <b>immediate access</b> to all our 
        <a href="https://www.epl.ca/resources/" target="_blank">online resources</a>. 
    </p>
    <p>
        You can access these resources by 
        <a href="https://epl.bibliocommons.com/user/registration" target="_blank"> logging into your account</a> 
        with your library card number and PIN.
    </p>
    @else
    <p> It’s time to start exploring: </p>
    <ul>
        <li>
            <a href="https://epl.bibliocommons.com/user/registration" target="_blank">Log into your account </a> to place holds on physical items, access thousands of books, magazines, newspapers, movies, and music or browse our events calendar for exciting programs, classes and events. </li>

        <li>Access all our <a href="https://www.epl.ca/resources/" target="_blank">online resources</a>. </li>

        <li>Check out our <a href="https://www.epl.ca/borrowing-guide/">Borrowing Guide</a>, to learn about borrowing and hold limits. </li>

        <li>Download our <a href="https://www.epl.ca/epl-app/" target="_blank">EPL App </a> for even more convenient access to our services and resources. </li>

        <li>Watch our <a href="https://www.youtube.com/playlist?list=PL2IiDBdO-aW57a1Hsrk8xb4xRgVXFtc6D" target="_blank">online tutorials</a> to learn more about getting started. </li> 
    </ul>
    @endif

    @if($source == 'OLR')
    <p>
        To continue enjoying EPL’s amazing resources after this period, please visit any 
        <a href="https://epl.bibliocommons.com/v2/locations" target="_blank">EPL location</a> 
        with proof of your Edmonton address and a valid picture ID. <strong>When you visit us to extend your 
        membership in branch, you’ll also receive a FREE tote bag!</strong> 
    </p>
    
    <p>In the meantime, start exploring!</p>
    <ul>
        <li> <a href="https://epl.bibliocommons.com/user/registration" target="_blank">Log into your account</a> to place holds on physical items, access thousands of books, magazines, 
            newspapers, movies, and music or browse our <a href="https://epl.bibliocommons.com/v2/events" target="_blank">events calendar</a> for exciting programs, classes, and events.
        </li>
        <li>Check out our <a href="https://www.epl.ca/borrowing-guide/" target="_blank">Borrowing Guide</a>, to learn about borrowing and hold limits.</li>
        <li>Download our <a href="https://www.epl.ca/epl-app/" target="_blank">EPL App</a> for even more convenient access to our services and resources.</li>
    </ul>
    @endif
    
    @if($source == 'OLR')
    <p>We’re thrilled to have you with us and look forward to seeing you soon at an EPL location.</p>
    @else
    <p>We’re thrilled to have you with us.</p>
    @endif
    
    <div class="footer">
        <p>The EPL team</p>
    </div>
</body>
</html>