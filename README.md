# CRE

**CRE** is a web service designed for checking duplicate entries on ILS (Integrated Library Systems) before allowing a post request. It utilizes fuzzy logic, specifically the Levenshtein distance fuzzy matching algorithm.
The computational complexity of the Levenshtein distance algorithm is often expressed using Big-O notation. Specifically, it has a time complexity of O(n*m), where:
```
n is the length of one string.
m is the length of the other string
```

```
| Field         | Weight | Rationale                                                                 |
|---------------|--------|---------------------------------------------------------------------------|
| firstname     | 1.0    | Important but not the sole identifier (e.g., common names).               |
| lastname      | 1.0    | Important but not definitive (e.g., people with the same surname).        |
| phone         | 2.0    | Strong indicator of a single individual; exact matches are crucial.      |
| email         | 2.0    | Strong indicator; same email likely indicates the same person.           |
| dateofbirth   | 1.0    | Useful for differentiation but some flexibility is allowed (e.g., twins).|
| address       | 0.8    | Less strict due to possible variations (e.g., different apartments).      |

```
## Experiment

Experiment with these weights to tune the algorithm for your real-world data and specific requirements.

## Example Scenario

Let's say you have two records:

### Record 1:
- **firstname**: John
- **lastname**: Doe
- **phone**: 123-456-7890
- **email**: john.doe@example.com
- **dateofbirth**: 1990-01-01
- **address**: 123 Elm St

### Record 2:
- **firstname**: John
- **lastname**: Doe
- **phone**: 123-456-7890
- **email**: john.doe@example.com
- **dateofbirth**: 1990-01-01
- **address**: 123 Elm St, Apt 2

### Similarity Calculation:

- For **phone** and **email**, since they match exactly, they contribute significantly to the similarity score (because both fields have a weight of **2.0**).
- **firstname** and **lastname** are both the same, contributing normally (weight **1.0**).
- **address** is slightly different (Apt 2 is added in Record 2), so it will have a smaller contribution (since address has a weight of **0.8**).
- **dateofbirth** is the same, contributing as expected (weight **1.0**).

In this case, the fields that match perfectly will strongly drive the final score towards being a duplicate, even though there’s a slight variation in the address.

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
   - Run `php artisan key:generate` to generate APP_KEY
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

   ```
   php artisan cache:clear
   php artisan config:clear
   php artisan route:clear
   php artisan view:clear
   ```
3. **Migration and Seeder** 
   - Run the following to run migration `php artisan migrate`
   - To check the status of migration `php artisan migrate:status`
   - Run the following to seed `php artisan db:seed`
   - To run specific class `php artisan db:seed --class=UserSeeder`

4. **Testing API**
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
**Run Commands for processing ILS Data every 2 minutes**
```
php artisan data:process
```

**Run Commands for processing ILS Data at 12:45 am**
```
php artisan data:replace
```

## Local settings
**CORS**
```
php artisan config:publish cors
```


# Test

### Set up
**Migration**
```
php artisan migrate --env=testing
```
**Generate Authorization Token :**
Generate client_id and client_secret for the test environment 

```
php artisan passport:client --env=testing
```


**Logging**

```// Log different severity levels
Log::channel('slack')->emergency('System is down!');
Log::channel('slack')->alert('Database connection failed');
Log::channel('slack')->critical('Critical system error');
Log::channel('slack')->error('Application error');
Log::channel('slack')->warning('Something might be wrong');
Log::channel('slack')->notice('Normal but significant event');
Log::channel('slack')->info('Interesting event');
Log::channel('slack')->debug('Detailed debug information');

```

### Documentation 
[Check Duplicate Logic](https://epldotca-my.sharepoint.com/:w:/r/personal/abdul_ojulari_epl_ca/_layouts/15/Doc.aspx?sourcedoc=%7BFF3E42D6-702F-4374-8446-C5434C6EF0BF%7D&file=DUPLICATE%20CHECKThe%20logic.docx&wdLOR=c81D8B81D-8562-1348-B0F1-E796ABDF811B&fromShare=true&action=default&mobileredirect=true)


# Date range filtering
GET /api/library-statistics?start_date=2024-01-01&end_date=2024-12-31&filter_type=date_range

# Year only
GET /api/library-statistics?year=2024&filter_type=year

# Month and year
GET /api/library-statistics?year=2024&month=3&filter_type=month_year

# Profile filtering
GET /api/library-statistics?profile=EPL_SELF

# Combined filtering
GET /api/library-statistics?start_date=2024-01-01&end_date=2024-03-31&profile=EPL_SELFJ


### Example
http://your-domain/api/library-statistics?start_date=2024-01-01&end_date=2024-12-31&filter_type=date_range
http://your-domain/api/library-statistics?year=2024&filter_type=year
http://your-domain/api/library-statistics?year=2024&month=3&filter_type=month_year
http://your-domain/api/library-statistics?profile=EPL_SELF
http://your-domain/api/library-statistics?start_date=2024-01-01&end_date=2024-03-31&profile=EPL_SELFJ