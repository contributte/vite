.PHONY: install qa cs csf phpstan tests coverage-clover coverage-html

install:
	composer update

qa: phpstan cs

cs:
	vendor/bin/ecs check src

csf:
	vendor/bin/ecs check src --fix

phpstan:
	vendor/bin/phpstan analyze -l 8 src
