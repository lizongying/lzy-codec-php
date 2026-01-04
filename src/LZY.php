<?php

namespace LZYCodec;

use Exception;

class LZY
{

  const SURROGATE_MIN = 0xD800;
  const SURROGATE_MAX = 0xDFFF;
  const UNICODE_MAX = 0x10FFFF;
  const ERROR_UNICODE = 'invalid unicode';

  /**
   * Unicode 有效性校验
   * @param int $r Unicode 码点
   * @return bool 校验结果
   */
  public static function valid_unicode(int $r): bool
  {
    return (0 <= $r && $r < self::SURROGATE_MIN) || (self::SURROGATE_MAX < $r && $r <= self::UNICODE_MAX);
  }

  /**
   * 核心编码：Unicode 码点序列 → LZY 字节序列
   * @param array $input_runes Unicode 码点数组
   * @return string LZY 字节序列（PHP 中用 string 存储字节流）
   */
  public static function encode(array $input_runes): string
  {
    $output = '';
    foreach ($input_runes as $r) {
      if ($r < 0x80) {
        // 单字节编码
        $output .= chr($r & 0xFF);
      } elseif ($r < 0x4000) {
        // 双字节编码
        $byte1 = chr(($r >> 7) & 0xFF);
        $byte2 = chr((0x80 | ($r & 0x7F)) & 0xFF);
        $output .= $byte1 . $byte2;
      } else {
        // 三字节编码
        $byte1 = chr(($r >> 14) & 0xFF);
        $byte2 = chr((0x80 | (($r >> 7) & 0x7F)) & 0xFF);
        $byte3 = chr((0x80 | ($r & 0x7F)) & 0xFF);
        $output .= $byte1 . $byte2 . $byte3;
      }
    }
    return $output;
  }

  /**
   * 辅助编码：PHP 原生字符串 → LZY 字节序列
   * @param string $input_str PHP 字符串（UTF-8 编码）
   * @return string LZY 字节序列
   */
  public static function encode_from_string(string $input_str): string
  {
    // 将 UTF-8 字符串转换为 Unicode 码点数组
    $input_str = mb_convert_encoding($input_str, 'UTF-32BE', 'UTF-8');
    $runes = [];
    $len = strlen($input_str);
    for ($i = 0; $i < $len; $i += 4) {
      $byte4 = ord($input_str[$i]);
      $byte3 = ord($input_str[$i + 1]);
      $byte2 = ord($input_str[$i + 2]);
      $byte1 = ord($input_str[$i + 3]);
      $rune = ($byte4 << 24) | ($byte3 << 16) | ($byte2 << 8) | $byte1;
      $runes[] = $rune;
    }
    return self::encode($runes);
  }

  /**
   * 辅助编码：UTF-8 字节序列 → LZY 字节序列
   * @param string $input_bytes UTF-8 字节序列（PHP string）
   * @return string LZY 字节序列
   */
  public static function encode_from_bytes(string $input_bytes): string
  {
    // PHP 中 string 直接存储字节流，无需额外转换，直接解码为 UTF-8 字符串
    return self::encode_from_string($input_bytes);
  }

  /**
   * 核心解码：LZY 字节序列 → Unicode 码点序列
   * @param string $input_bytes LZY 字节序列（PHP string）
   * @return array Unicode 码点数组
   * @throws Exception 解码失败抛出异常
   */
  public static function decode(string $input_bytes): array
  {
    $len = strlen($input_bytes);
    if ($len === 0) {
      throw new Exception(self::ERROR_UNICODE);
    }

    // 寻找第一个最高位为 0 的字节（有效起始索引）
    $start_idx = -1;
    for ($i = 0; $i < $len; $i++) {
      $byte = ord($input_bytes[$i]);
      if (($byte & 0x80) === 0) {
        $start_idx = $i;
        break;
      }
    }
    if ($start_idx === -1) {
      throw new Exception(self::ERROR_UNICODE);
    }

    $output = [];
    $r = 0;

    // 从有效起始索引遍历
    for ($i = $start_idx; $i < $len; $i++) {
      $byte = ord($input_bytes[$i]);
      $b = $byte & 0xFF; // 转换为无符号值

      if (($b >> 7) === 0) {
        // 处理上一个累积的码点
        if ($i > $start_idx) {
          if (!self::valid_unicode($r)) {
            throw new Exception(self::ERROR_UNICODE);
          }
          $output[] = $r;
        }
        $r = $b;
      } else {
        // 累积码点计算
        if ($r > (self::UNICODE_MAX >> 7)) {
          throw new Exception(self::ERROR_UNICODE);
        }
        $r = ($r << 7) | ($b & 0x7F);
      }
    }

    // 处理最后一个码点
    if (!self::valid_unicode($r)) {
      throw new Exception(self::ERROR_UNICODE);
    }
    $output[] = $r;

    return $output;
  }

  /**
   * 辅助解码：LZY 字节序列 → PHP 原生字符串（UTF-8 编码）
   * @param string $input_bytes LZY 字节序列
   * @return string PHP UTF-8 字符串
   * @throws Exception 解码失败抛出异常
   */
  public static function decode_to_string(string $input_bytes): string
  {
    $runes = self::decode($input_bytes);
    // 将 Unicode 码点数组转换为 UTF-8 字符串
    $output_str = '';
    foreach ($runes as $rune) {
      // 处理 UTF-32 到 UTF-8 的转换
      $utf32 = pack('N', $rune);
      $utf8 = mb_convert_encoding($utf32, 'UTF-8', 'UTF-32BE');
      $output_str .= $utf8;
    }
    return $output_str;
  }

  /**
   * 辅助解码：LZY 字节序列 → UTF-8 字节序列
   * @param string $input_bytes LZY 字节序列
   * @return string UTF-8 字节序列（PHP string）
   * @throws Exception 解码失败抛出异常
   */
  public static function decode_to_bytes(string $input_bytes): string
  {
    // PHP 中 UTF-8 字符串直接对应字节序列，无需额外转换
    return self::decode_to_string($input_bytes);
  }
}