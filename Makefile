.PHONY: tests

build: cs tests phpstan

tests:
	rm -rf tmp/* && php vendor/bin/phpunit

cs:
	vendor/bin/phpcs src tests --standard=vendor/efabrica/coding-standard/eFabrica --extensions="php" -n

cs-fix:
	php vendor/bin/phpcbf src tests --standard=vendor/efabrica/coding-standard/eFabrica --extensions="php" -n

phpstan:
	vendor/bin/phpstan analyze src --level=max
