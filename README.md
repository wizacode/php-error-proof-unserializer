# PHP error proof unserializer

## Intro

```shell
Notice:  unserialize(): Error at offset ... of ... bytes ...
```

You can try this to unserialize a corrupted serialized string.

## Usage

```php
use Wizacode\ErrorProofUnserializer\ErrorProofUnserializer;

// Attempt to unserialize a corrupted serialized string:

$recoveredUnserializedData = ErrorProofUnserializer::process($brokenSerializedString);

// Or only fix the serialized string:

$fixedSerializedData = ErrorProofUnserialize::fix($brokenSerializedString);
```

P.S.: Don't record php serialized data in a RDBMS (or use   at least a binary safe storage type)
