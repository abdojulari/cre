# Makefile for setting up the 'cre' project

.PHONY: cre

cre:
	@echo "Checking if SSH is set up..."
	@if ssh -T git@github.com 2>&1 | grep -q "successfully authenticated"; then \
		echo "Cloning via SSH..."; \
		git clone git@github.com:abdojulari/cre.git; \
	else \
		echo "Cloning via HTTPS..."; \
		git clone https://github.com/abdojulari/cre.git; \
	fi
	@cd cre && cp .env.example .env
	@echo "Please set up the database with the following credentials:"
	@echo "- Database name: cre"
	@echo "- Username: cre"
	@echo "- Password: password123@"
	@echo "- Grant privileges to 'cre'"
	@read -p "Have you set up the database? (yes/no): " db_setup; \
	if [ "$$db_setup" = "yes" ]; then \
		cd cre && composer install; \
		php artisan key:generate; \
		php artisan migrate; \
	else \
		echo "Please set up the database and start over."; \
	fi

.PHONY: clean

clean:
	@echo "Clearing cache..."
	@php artisan cache:clear
	@echo "Clearing config cache..."
	@php artisan config:clear
	@echo "Clearing route cache..."
	@php artisan route:clear
	@echo "Dumping autoloads..."
	@composer dump-autoload
