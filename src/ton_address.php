<?php

class TonAddress {

  /**
   * Calculate CRC16-CCITT checksum.
   * @param string $data - Input data as a binary string.
   * @return string - Checksum as a 2-byte binary string.
   */
  public static function crc16($data) {
    $reg = 0;
    $message = $data."\x00\x00";
    for ($i=0;$i<strlen($message);$i++) {
      $byte = ord($message[$i]);
      $mask = 0x80;
      while ($mask>0) {
        $reg <<= 1;
        if ($byte&$mask) {
          $reg += 1;
        }
        $mask >>= 1;
        if ($reg>0xffff) {
          $reg &= 0xffff;
          $reg ^= 0x1021;
        }
      }
    }
    return chr($reg>>8).chr($reg&0xff);
  }

  /**
   * Convert raw address to friendly format.
   * @param string $raw - Raw address in "workchain:hex" format.
   * @param bool $bounceable - Whether to return bounceable or not.
   * @param bool $testnet - Whether for testnet or not.
   * @return string - Friendly address in base64 format.
   */
  public static function raw2friendly($raw,$bounceable = true,$testnet = false) {
    try {
      list($workchain,$hex) = explode(":",$raw);
      $bytes = [($bounceable?0x11:0x51)|($testnet?0x80:0),$workchain=="-1"?0xFF:intval($workchain)];
      for ($i=0;$i<32;$i++) {
        $bytes[] = hexdec(substr($hex,$i*2,2));
      }
      $crc = self::crc16(implode(array_map("chr",$bytes)));
      $bytes[] = ord($crc[0]);
      $bytes[] = ord($crc[1]);
      $base64 = base64_encode(implode(array_map("chr",$bytes)));
      return str_replace(["+","/"],["-","_"], $base64);
    } catch (Exception $e) {
      throw new Exception("Failed to parse address :(");
    }
  }

  /**
   * Parse a string into TON address.
   * @param string $input - String to parse.
   * @param int $testnet_flag - Flag to indicate if the address is for the testnet.
   * @return array|string - Parsed address or an error message.
   */
  public static function parse($input,$testnet_flag = 0) {
    try {
      $raw_format = false;
      if (strlen($input)>64) {
        $input = self::raw2friendly($input,true,$testnet_flag);
        $raw_format = true;
      }
      $bytes = array_map("ord",str_split(base64_decode(str_replace(["-","_"],["+","/"], $input))));
      $workchain = $bytes[1] == 0xff ? -1 : $bytes[1];
      $testnet = ($bytes[0] & 0x80)!=0;
      $bounceable = ($bytes[0] & 0x40)==0;
      $hex = implode(array_map(function($byte) {
        return str_pad(dechex($byte),2,"0",STR_PAD_LEFT);
      },array_slice($bytes,2,32)));
      $crc = self::crc16(implode(array_map("chr",array_slice($bytes,0,34))));
      if ($crc[0]==chr($bytes[34])&&$crc[1]==chr($bytes[35])) {
        $raw = "$workchain:$hex";
        return [
          "input_type" => ($workchain==-1?"Masterchain":"Basechain $workchain").($raw_format?" Raw":($bounceable?" Bounceable":" Unbounceable"))." TON Address",
          "workchain" => $workchain,
          "testnet" => $testnet,
          "raw" => $raw,
          "bounceable" => self::raw2friendly($raw,true,$testnet),
          "unbounceable" => self::raw2friendly($raw,false,$testnet)
        ];
      } else {
        return "Failed to parse address :(";
      }
    } catch (Exception $e) {
      return "Failed to parse address :(";
    }
  }
}

?>
