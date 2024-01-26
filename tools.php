<?
	if(file_exists("./config.php")) require_once("./config.php");
	else if(file_exists("../config.php")) require_once("../config.php");

/*紀錄檔案 */
$fp_logger;
function logger($level, $prefix, $message){
    global $fp_logger;
    global $CONFIG;
    if(!$fp_logger){
        if(!file_exists($CONFIG['export'])) mkdir($CONFIG['export']);
        $fp_logger = fopen($CONFIG['export']."log.txt", "a+");
    }

	if($level>$CONFIG['debug']['level']) return;

	$str_level = 'N/A';
	switch($level){
		case ERROR_MESSAGE_LEVEL:
			$str_level = 'ERROR';
			break;
		case WARN_MESSAGE_LEVEL:
			$str_level = 'WARN';
			break;
		case INFO_MESSAGE_LEVEL:
			$str_level = 'INF';
			break;
		case DEBUG_MESSAGE_LEVEL:
			$str_level = 'D';
			break;
	}

	$str_prefix = $prefix;
	if(preg_match("'iitc[/\\\\]([\s\S]+)$'", $prefix, $match)) $str_prefix = $match[1]; 

	$str = date("Y-m-d H:i:s", time())."\t".$str_level."\t".$str_prefix.'| '.$message."\r\n";
	fprintf($fp_logger, "%s", $str);
	fflush($fp_logger);
	return $str."<br>";
}
/* */


// 顯示錯誤訊息
function errorMSG($msg)
{
	$str = "<div style=\"font:normal 12px verdana;color:#800080\">Error:<br>".$msg."</div>\n";
	logger(ERROR_MESSAGE_LEVEL, __FILE__, "msg: $msg");
	logger(DEBUG_MESSAGE_LEVEL, __FILE__, json_encode(debug_backtrace()));
	
	echo $str;
	return;
}

/* 2009/12/4 過濾攻擊文字 (轉換), 也適用於數字,  */
// $type 0:文字, 1:數字
// 2009/12/8 加入過濾陣列
function confirmInput( $str, $type=0)
{
	if( is_array($str) ){
		foreach($str as $index => $value){
			$output[$index] = confirmInput($value);
		}
	}
	else{
		if( $type==0 ){			// 文字
			$output = str_replace( "'", "`",$str);
			$output = str_replace( "\\\"", "\"",$output);
			$output = str_replace( "\\\\", "\\",$output);
		}
		else if( $type==1 ){	// 數字
			$output = is_numeric($str)*$str;
		}
		else{
			echo "[failure] (fn) confirmInput type error";
			exit(0);
		}
	}
	return $output;
}

/* 2017/11/3 過濾攻擊文字 (轉換), 也適用於數字,  */
function confirmExport( $str)
{
	$output = str_replace( "'", "`",$str);
	$output = str_replace( ">", "&#60;",$output);
	$output = str_replace( "<", "&#60;",$output);
	return $output;
}

//*** 測量時間
$sTIME_start = "";
$sTIME_pre = "";

/* 得到毫秒的時間 */
function microTime_float(){
	list($usec, $sec) = explode(" ", microtime());
	return ($usec + $sec);
}

/*得到執行時間 */
function timerNow($title = "")
{
	global $sTIME_start;
	global $sTIME_pre;
    $t = microTime_float();
	if($sTIME_start==""){
		$sTIME_start = $t;
		$sTIME_pre = $sTIME_start;
	}
	else{
        $t_stamp = $t - $sTIME_start;
        $t_diff = $t - $sTIME_pre;
        echo sprintf("<font color=#800080>Time: %.5f, diff: %.5f s %s</font><br>\n", $t_stamp, $t_diff, $title);
		$sTIME_pre = $t;
	}
	return ($t-$sTIME_start);
}
/*** ***/




function rscandir($base='') 
{ 
  $data = array();
  $array = array_diff(scandir($base), array('.', '..', 'Thumbs.db')); /* remove ' and .. from the array */
  
  foreach($array as $value){
    echo "value: $value<br>";
 
	if (is_dir($base.$value)){
	  $data[] = $base.$value.'/'; /* add it to the $data array */
	  $data = rscandir($base.$value.'/', $data); /* then make a recursive call with the
	  current $value as the $base supplying the $data array to carry into the recursion */
	 
	}else if (is_file($base.$value)){
	  $data[] = $base.$value; /* just add the current $value to the $data array */
    }
  }
 
  return $data; // return the $data array
}

/*** ***/
?>