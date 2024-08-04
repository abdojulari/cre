# CRE

**CRE** is a web service designed for checking duplicate entries on ILS (Integrated Library Systems) before allowing a post request. It utilizes fuzzy logic, specifically the Levenshtein distance fuzzy matching algorithm.
The computational complexity of the Levenshtein distance algorithm is often expressed using Big-O notation. Specifically, it has a time complexity of O(n*m), where:
```
n is the length of one string.
m is the length of the other string
```

## Development Environment

- **Laravel Herd**
- **PHP 8.3***
- **MySQL**
- **Redis**

### NOTE
- If you are running windows, install redis using scoop `scoop install main/redis` and in your laravel project run `composer require predis/predis:^2.0`.
- To initiate the Redis
    - using powershell, you can run 
        - `redis-server` to start the server
        - `redis-cli` to use the CLI Commands
        - `redis-benchmark` for evaluating the performance of a Redis instance. 


<!--  copy .env.example into .env -->
- **Copy .env.example into .env**
    - Look for the following lines 
    ```
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=cre // Make sure the database name matches what is running on your MySQL server.
    DB_USERNAME=root // choose a username
    DB_PASSWORD= // choose a password
    ```
    - On your MySQL server, make sure you have the same configuration. 

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
        - With a 'POST' method, generate a token using the `client_id`, `client_secret`, and set `grant_type` to `client_credentials` with `x-www-form-urlencoded`. Endpoint: `https://cre.test/oauth/token`
     2. **Display Data**:
        - Set Authorization/Auth Type to `Bearer Token` and access the endpoint `https://cre.test/api/duplicates` using the 'GET' method.
     3. **Create Data**:
        - Set Authorization/Auth Type to `Bearer Token` and access the endpoint `https://cre.test/api/duplicates` using the 'POST' method.

## Commands
**Run Commands for processing ILS Data**
```
php artisan data:process
```