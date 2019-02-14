# phpstan-junit

ErrorFormatter for PHPStan to output errors in JUnit format

## Usage

Add the following to your `phpstan.neon`:

```
services:
	errorFormatter.junit:
		class: PHPStan\Command\ErrorFormatter\JUnitErrorFormatter
```

Now you can format PHPStan's output using `--error-format=junit`.