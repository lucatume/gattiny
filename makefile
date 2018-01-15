publish:
	rm -rf vendor
	composer install --no-dev
	composer dump-autoload --optimize