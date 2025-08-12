<?php

class ModelExtensionEncryptionEncryptionModel extends Model {

  public function encrypt_data(string $data): string
  { 
    //echo $data; die;
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', ENC_KEY, 0, $iv);
    $result = base64_encode($encrypted . '::' . $iv);

    // Use URL-safe base64 encoding
    $urlSafeResult = str_replace(['+', '/', '='], ['-', '_', ''], $result);

    return $urlSafeResult;
  }

  public function decrypt_data(string $data): string
  {
    // Convert URL-safe base64 encoding back to standard base64 encoding
    $base64 = str_replace(['-', '_'], ['+', '/'], $data);
    // Add padding if necessary
    $padding = 4 - (strlen($base64) % 4);
    if ($padding !== 4) {
      $base64 .= str_repeat('=', $padding);
    }

    $decoded = base64_decode($base64);
    list($encrypted_data, $iv) = explode('::', $decoded, 2);

    $decrypted = openssl_decrypt($encrypted_data, 'aes-256-cbc', ENC_KEY, 0, $iv);

    return $decrypted;
  }

}
