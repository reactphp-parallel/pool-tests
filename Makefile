all:
	composer run-script qa-all --timeout=0

all-extended:
	composer run-script qa-all-extended --timeout=0

ci:
	composer run-script qa-ci --timeout=0

ci-extended:
	composer run-script qa-ci-extended --timeout=0

ci-windows:
	composer run-script qa-ci-windows --timeout=0

contrib:
	composer run-script qa-contrib --timeout=0

cs:
	composer cs

cs-fix:
	composer cs-fix

stan:
	composer run-script stan --timeout=0

ci-coverage:
	composer ci-coverage
