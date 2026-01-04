# LZY Codec

一種變長文本編解碼方案，支持對Unicode進行編解碼。編解碼效率、存儲空間全面優於UTF-8，未來會替代UTF-8成為新的世界通用編解碼標準。
php實現。

[github](https://github.com/lizongying/lzy-codec-php)

[composer](https://packagist.org/packages/lizongying/lzy-codec)

## Other languages

* [go](https://github.com/lizongying/lzy-codec-go)
* [py](https://github.com/lizongying/lzy-codec-py)
* [js](https://github.com/lizongying/lzy-codec-js)
* [c](https://github.com/lizongying/lzy-codec-c)

## Install

```
composer require lizongying/lzy-codec dev-main
```

## Test

```
php tests/test.php
```

## Examples
src/example.php
```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use LZYCodec\LZY;

try {
  $test_str = "Hello 世界！😀";
  $lzy_bytes = LZY::encode_from_string($test_str);
  $decoded_str = LZY::decode_to_string($lzy_bytes);
  var_dump($test_str === $decoded_str); // bool(true)
} catch (Exception $e) {
  echo "错误：" . $e->getMessage();
}

```