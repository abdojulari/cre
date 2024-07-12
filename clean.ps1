# Define function to clear Laravel cache, config, routes, and dump autoload
function CleanLaravel {
    Write-Host "Clearing cache..."
    php artisan cache:clear
    Write-Host "Clearing config cache..."
    php artisan config:clear
    Write-Host "Clearing route cache..."
    php artisan route:clear
    Write-Host "Dumping autoloads..."
    composer dump-autoload
}

# # Check if artisan and composer are installed
# if (!(Test-Path "$env:ProgramFiles\Git\usr\bin\php.exe")) {
#     Write-Output "PHP was not found."
#     exit 1
# }

# if (!(Test-Path "$env:ProgramFiles\Git\usr\bin\composer.exe")) {
#     Write-Output "Composer was not found."
#     exit 1
# }

# Invoke the function
CleanLaravel
