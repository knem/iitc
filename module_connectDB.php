<?php
require_once("config.php");
require_once("tools.php");				// functions

if(!isset($CONFIG)) {
	echo "[module_connectDB] failed to load config data";
	exit(0);
}

$config = $CONFIG['database'];

// 2018/1/29 hack mysql_query
// MYSQL 遠端連線限制 https://www.jianshu.com/p/9c175e9293e2
// 密碼強度 https://www.brilliantcode.net/404/mysql-5-7-error-1819-hy000-validate-password-policy/
if( !function_exists("mysql_query") ){
	
	// connection to the database
	$db_handler = mysqli_connect($config['host'], $config['user'], $config['password']) or die("Couldn't connect to SQL Server on ".$config['host']);

	function mysql_query($query){
		global $db_handler;
		return mysqli_query( $db_handler, $query);
	}
	function mysql_select_db($dbName){
		global $db_handler;
		return mysqli_select_db( $db_handler, $dbName);
	}

	function mysql_fetch_array($query){
		return mysqli_fetch_array($query);
	}

	function mysql_fetch_row($query){
		return mysqli_fetch_row($query);
    }
    
	function mysql_fetch_object($query){
		return mysqli_fetch_object($query);
	}
    
	function mysql_num_rows($query){
		if(!$query) return 0;
		return mysqli_num_rows($query);
    }
    
    function mysql_real_escape_string($str) {
		global $db_handler;
        return mysqli_real_escape_string($db_handler, $str);
    }
}
else{
	// connection to the database
	@mysql_connect($config['host'], $config['user'], $config['password']) or die("Couldn't connect to SQL Server on ".$config['host']);
}




// select a database to work with
mysql_select_db($config['database']) or die("Couldn't open database ".$config['database']);
//echo "You are connected to the " . $myDB . " database on the " . $myServer . ".";

mysql_query("SET NAMES 'utf8mb4'"); 
mysql_query("SET CHARACTER_SET_CLIENT=utf8mb4"); 
mysql_query("SET CHARACTER_SET_RESULTS=utf8mb4"); 


function getQueryData($query)
{
	$data = array();
	//traceMSG($query."<br>");
	$result = mysql_query($query);
	$rows = mysql_num_rows($result);
	for($i=0;$i<$rows;$i++)
		$data[$i] = mysql_fetch_row($result);

	return $data;
}

function getQueryFields($query)
{
	$data = array();
	$result = mysql_query($query);
	if(!$result){
		errorMSG($query."<br>");
		return $data;
	}

	$rows = mysql_num_rows($result);
	for($i=0;$i<$rows;$i++){
		$data[$i] = (array)mysql_fetch_object($result); // use this to avoid generate array item
	}

	return $data;
}

function getQueryCount($query)
{
	$result = mysql_query($query);
	if(!$result){
		errorMSG($query."<br>");
		return $data;
	}
	return mysql_num_rows($result);
}


// get data as list with specific field from DB query result
// 20181102 optimal
function getFieldList( $data, $fields )
{
	$n = count($data);
	$list = array();
	for($i=0;$i<$n;$i++)
		$list[$data[$i][$fields]] = 1; 

	if( $n>0 ) {
		$list = array_keys($list);
		return "'".implode("','", $list)."'";
	}
	else return "''";
}

// 取得的資料, 建成資料表 以方便接下來的 DB 處理
// data, index field, data field
function getFieldAsTable( $data, $f1, $f2 )
{
	$n = count($data);
	$list = array();
	for($i=0;$i<$n;$i++){
		if(!$f2)
			$list[ $data[$i][$f1] ] = $data[$i];
		else
			$list[ $data[$i][$f1] ] = $data[$i][$f2];
	}

	return $list;
}

// for INSERT statement, get keys
function db_getKeyValue($record)
{
	$out = array();
	$out['key'] = array();
	$out['value'] = array();
	if(count($record)) foreach($record as $i => $v) {
		$out['key'][] = $i;
		$out['value'][] = $v;
	}

	$out['key'] = implode(", ", $out['key']);
	$out['value'] = "'" . implode("','", $out['value']) . "'";
	return $out;
}
?>