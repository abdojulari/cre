# CRE

**CRE** is a web service designed for checking duplicate entries on ILS (Integrated Library Systems) before allowing a post request. It utilizes fuzzy logic, specifically the Levenshtein distance fuzzy matching algorithm.

## Development Environment

- **Laravel Herd**
- **PHP 8.3***
- **MySQL**

## Libraries

- **Laravel Passport**: We use Laravel Passport to ensure machine-to-machine authentication and provide a security wrapper for our endpoints.

## Steps

1. **Initialize Keys**
   - Run `php artisan passport:keys` to generate keys and load them from the environment.
   - Additionally, publish the Passport configuration using:
     ```
     php artisan vendor:publish --tag=passport-config
     ```
   - Set the following environment variables:
     ```
     PASSPORT_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----
     <private key here>
     -----END RSA PRIVATE KEY-----"

     PASSPORT_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----
     <public key here>
     -----END PUBLIC KEY-----"
     ```

2. **Clear Cache and Dump Autoloads**
   - In your root directory, execute `.\clean.ps1` to clear the cache and dump autoloads.

3. **Testing API**
   - Use Postman or any other API testing tool.
   - Follow these steps:
     1. **Generate Authorization Token**:
        - Run `php artisan passport:client --client` to obtain the `client_id` and `client_secret`.
        - With a 'POST' method, generate a token using the `client_id`, `client_secret`, and set `grant_type` to `client_credentials`. Endpoint: `https://cre.test/oauth/token`
     2. **Display Data**:
        - Set Authorization/Auth Type to `Bearer Token` and access the endpoint `https://cre.test/api/duplicates` using the 'GET' method.
     3. **Create Data**:
        - Set Authorization/Auth Type to `Bearer Token` and access the endpoint `https://cre.test/api/duplicates` using the 'POST' method.
