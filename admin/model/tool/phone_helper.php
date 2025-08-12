<?php
class ModelToolPhoneHelper extends Model {
  public function formatPhoneNumber($number) {
    $number = substr($number, 0, 10);
      return sprintf("(%s) %s-%s",
        substr($number, 0, 3),
        substr($number, 3, 3),
        substr($number, 6, 4)
    );
  }

  public function formatPhoneNumberToOriginal($number) {
    $formatted_number = $number;
    $formatted_number = preg_replace('/[^0-9]/', '', $formatted_number);
    $formatted_number = $formatted_number . '0000';
    return $formatted_number;
  }

  public function validatePhoneNumber($phone): bool {
    $phoneRegex = '/^(\+1|1)?\s*(\([2-9][0-9]{2}\)|[2-9][0-9]{2})[-.\s]?[2-9][0-9]{2}[-.\s]?[0-9]{4}$/';
    return preg_match($phoneRegex, $phone) === 1;
  }

  function validatePostalCode($postalCode): bool {
    $regex = '/^(\d{5}(-\d{4})?|[A-Za-z]\d[A-Za-z][ -]?\d[A-Za-z]\d)$/';
    return preg_match($regex, $postalCode) === 1;
  }
}
