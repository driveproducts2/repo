<?php
class ModelToolPhoneHelper extends Model {
  public function formatPhoneNumber($number) {
    if(isset($number) && !empty($number)) {
      $number = substr($number, 0, 10);
      return sprintf("(%s) %s-%s",
        substr($number, 0, 3),
        substr($number, 3, 3),
        substr($number, 6, 4)
      );
    }
    return $number;
  }

  public function formatPhoneNumberToOriginal($number) {
    if(isset($number) && !empty($number)) {
      $formatted_number = $number;
      $formatted_number = preg_replace('/[^0-9]/', '', $formatted_number);
      $formatted_number = $formatted_number . '0000';
      return $formatted_number;
    }
    return $number;
  }
}