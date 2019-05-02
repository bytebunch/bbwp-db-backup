<?php
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BBWPSanitization{

  /******************************************/
  /***** SanitizeInt function start from here *********/
  /******************************************/
  static function Int($int, $negative = false){
    if(isset($int) && is_numeric($int)){
      if($negative === true && (int)$int === 0)
        return (int)$int;
      $int = filter_var($int, FILTER_VALIDATE_INT);
      if ($negative === false && !$int === false && (int)$int >= 1)
          return (int)$int;
      elseif ($negative === true && !$int === false)
        return (int)$int;
      else
          return false;
    }else
      return false;
  }

  /******************************************/
  /***** SanitizeEmail function start from here *********/
  /******************************************/
  static function Email($email){
    if(isset($email) && $email){
      $email = trim($email);
      if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return false;
      else
        return sanitize_email($email);
    }else
      return false;
  }

  /******************************************/
  /***** SanitizeEmail function start from here *********/
  /******************************************/
  static function Username($username, $slength = 1, $elength = 999){
    if(isset($username) && $username && $username != " "){
      $username = stripslashes(trim(sanitize_user( $username, true)));
      if(strlen($username) >= $slength && strlen($username) <= $elength){
        return $username;
      }else
        return false;
    }else
      return false;
  }

  /******************************************/
  /***** SanitizeTextfield function start from here *********/
  /******************************************/
  static function Textfield($text, $slength = 1, $elength = 9999999){
    if(isset($text) && $text && $text != " "){
      $text = stripslashes(trim(sanitize_text_field( $text, true)));
      if(strlen($text) >= $slength && strlen($text) <= $elength){
        return $text;
      }else
        return false;
    }else
      return false;
  }

  /******************************************/
  /***** SanitizeTextarea function start from here *********/
  /******************************************/
  static function Textarea($text, $bballowedtags = false){
    if(isset($text) && $text && $text != " "){
      if($bballowedtags === false){
        global $allowedposttags;
        $text = stripslashes(trim(wp_kses( $text, $allowedposttags)));
      }
			elseif ($bballowedtags === true) {
				$text = stripslashes(trim($text));
			}
      else
        $text = stripslashes(trim(wp_kses( $text, $bballowedtags)));
      if(strlen($text) >= 1){
        return $text;
      }else
        return false;
    }else
      return false;
  }



}// Sanitization class end here
