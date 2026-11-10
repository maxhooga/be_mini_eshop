.PHONY: install dev test

install: ## Install PHP dependencies
	composer install

dev: ## Start built-in PHP server (http://localhost:8000)
	php -S localhost:8000 -t www

test: ## Run Nette Tester
	composer tester
