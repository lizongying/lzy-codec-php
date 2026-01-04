<?php

require_once __DIR__ . '/../src/LZYCodec.php';

use LZYCodec\LZY;

try {
  $test_str = "Hello 世界！😀";
  $lzy_bytes = LZY::encode_from_string($test_str);
  $decoded_str = LZY::decode_to_string($lzy_bytes);
  var_dump($test_str === $decoded_str); // bool(true)
} catch (Exception $e) {
  echo "错误：" . $e->getMessage();
}
