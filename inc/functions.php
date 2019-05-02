<?php
// exit if file is called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/******************************************/
/***** Debug functions start from here **********/
/******************************************/
if(!function_exists("alert")){

  function alert($alertText){
  	echo '<script type="text/javascript">';
  	echo "alert(\"$alertText\");";
  	echo "</script>";
  } // function alert

}// if end


if(!function_exists("db")){

  function db($array1){
  	echo "<pre>";
  	var_dump($array1);
  	echo "</pre>";
	}// function db

}// if


/******************************************/
/***** ArraytoSelectList **********/
/******************************************/
if(!function_exists("ArraytoSelectList")){
  function ArraytoSelectList($array, $sValue = ""){
    $output = '';
    foreach($array as $key=>$value){
      if($key == $sValue)
        $output .= '<option value="'.esc_attr($key).'" selected="selected">'.esc_html($value).'</option>';
      else
        $output .= '<option value="'.esc_attr($key).'">'.esc_html($value).'</option>';
    }
    return $output;
	}
}

/******************************************/
/***** ArraytoSelectList **********/
/******************************************/
if(!function_exists("ArraytoRadioList")){
  function ArraytoRadioList($array, $name = "", $sValue = ""){
    $output = '';
		
    foreach($array as $key=>$value){
    	$checked = '';
			if($sValue && $value['value'] == $sValue)
				$checked = 'checked="checked"';
		
			$output .= '<input type="radio" value="'.$value['value'].'" id="'.$value['id'].'" name="'.$name.'" '.$checked.' />';
			$output .= '<label for="'.$value['id'].'">'.$value['label'].' </label>';
			$output .= ' &nbsp;&nbsp;';
			
    }
    return $output;
	}
}


/******************************************/
/***** ArraytoSelectList **********/
/******************************************/
if(!function_exists("ArraytoCheckBoxList")){
  function ArraytoCheckBoxList($array, $name = "", $sValue = array()){
    $output = '';
		$i = 1;
    foreach($array as $key=>$value){
    	$checked = '';
			if($sValue && is_array($sValue) && in_array($value['value'], $sValue))
				$checked = 'checked="checked"';
		
			$output .= '<input type="checkbox" value="'.$value['value'].'" id="'.$value['id'].$i.'" name="'.$name.'[]" '.$checked.' />';
			$output .= '<label for="'.$value['id'].$i.'">'.$value['label'].' </label>';
			$output .= ' &nbsp;&nbsp;';
			$i++;
			
    }
    return $output;
	}
}


/******************************************/
/***** arrayToSerializeString **********/
/******************************************/
if(!function_exists("ArrayToSerializeString")){
  function ArrayToSerializeString($array){
    if(isset($array) && is_array($array) && count($array) >= 1)
      return serialize($array);
    else
      return serialize(array());
  }
}


/******************************************/
/***** SerializeStringToArray **********/
/******************************************/
if(!function_exists("SerializeStringToArray")){
  function SerializeStringToArray($string){
    if(isset($string) && is_array($string) && count($string) >= 1)
      return $string;
    elseif(isset($string) && $string && @unserialize($string)){
      return unserialize($string);
    }else
      return array();
  }
}

/******************************************/
/*****Setting update notice function **********/
/******************************************/
if(!function_exists("BBWPUpdateErrorMessage")){
  function BBWPUpdateErrorMessage(){
    if(get_option('bbwp_update_message'))
      echo '<div class="updated"><p><strong>'.get_option('bbwp_update_message').'</strong></p></div>';
    elseif(get_option('bbwp_error_message'))
      echo '<div class="error"><p><strong>'.get_option('bbwp_error_message').'</strong></p></div>';
    update_option('bbwp_update_message', '');
    update_option('bbwp_error_message', '');
  }
}


/******************************************/
/*****Setting update notice function **********/
/******************************************/
if(!function_exists("formatBytes")){
  function formatBytes($bytes) { 
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 0) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
  } 
}

/******************************************/
/***** generate random integre value **********/
/******************************************/
if(!function_exists("generateRandomInt")){
  function generateRandomInt($number_values)
  {
    $number_values = $number_values-2;
    $lastid = rand(0,9);
    for($i=0; $i <= $number_values; $i++)
    {
      $lastid .= rand(0,9);
    }
    return $lastid;
  }
}