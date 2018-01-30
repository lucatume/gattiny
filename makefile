publish:
	rm -rf vendor
	composer install --no-dev
	composer dump-autoload --optimize
test:
	codecept run unit
	codecept run integration
	codecept run functional
	codecept run acceptance
	codecept run js
