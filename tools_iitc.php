<?
function iitc_colorCn( $team, $cn )
{
	switch( $team ){
		case 0:
			return '<span style="color:#999999;">'.$cn.'</span>';
		case 1:
			return '<span style="color:#0000FF;">'.$cn.'</span>';
		case 2:
			return '<span style="color:#00FF00;">'.$cn.'</span>';
	}
}

// convert code to team type
function iitc_codeToTeam($team)
{
	switch( $team ){
		case 1:
		case "R":
		case "RES":
			return 1; // RES
		case 2:
		case 'E':
		case "ENL":
			return 2; // ENL
        case 3:
        case 'N':
        case "NEU":
            return 3; // NEU
        case "":
			return 0; // system
	}
	return 0;
}

function iitc_toCamp( $team )
{
	switch( $team ){
		case 0:
			return '';
		case 1:
			return 'RES';
		case 2:
			return 'ENL';
        case 3:
            return 'NEU';
	}
}

function iitc_toCampColor( $team )
{
	switch( $team ){
		case "RES":
		case 1:
			return '<span style="color:#0000FF;">RES</span>';
		case "ENL":
		case 2:
			return '<span style="color:#00CC00;">ENL</span>';
		default:
			return '';
	}
}

function iitc_cnToCampColor( $cn, $team )
{
	switch( $team ){
		case 0:
			return '';
			break;
		case 1:
			return '<span style="color:#0000FF;">'.$cn.'</span>';
			break;
		case 2:
			return '<span style="color:#00CC00;">'.$cn.'</span>';
			break;
	}
}

function iitc_toCamp2( $team )
{
	switch( $team ){
		case 0:
		case "N":
			return '';
		case 1:
		case "R":
		case "RES":
			return 'RES';
		case 2:
		case "E":
		case "ENL":
			return 'ENL';
	}
}

function iitc_toCamp3( $team )
{
	switch( $team ){
		case 0:
		case "N":
			return '白';
		case 1:
		case "R":
		case "RES":
			return '藍';
		case 2:
		case "E":
		case "ENL":
			return '綠';
	}
}

function iitc_modReName( $name )
{
	$name = trim(strtolower($name));
	switch( $name ){
		case "common heat sink":
		case "common heat-sink":
		case "chs":
			return 'CHS';
		case "rare heat sink":
		case "rare heat-sink":
		case "rhs":
			return 'RHS';
		case "very rare heat sink":
		case "very rare heat-sink":
		case "very_rare heat sink":
		case "very_rare heat-sink":
		case "vrhs":
			return 'VRHS';
		case "common multi hack":
		case "common multi-hack":
		case "cmh":
			return 'CMH';
		case "rare multi hack":
		case "rare multi-hack":
		case "rmh":
			return 'RMH';
		case "very rare multi hack":
		case "very rare multi-hack":
		case "very_rare multi hack":
		case "very_rare multi-hack":
		case "vrmh":
			return 'VRMH';

		case "common portal shield":
		case "c-s":
			return 'C-S';
		case "rare portal shield":
		case "r-s":
			return 'R-S';
		case "very rare portal shield":
		case "very_rare portal shield":
		case "very-rare portal shield":
		case "vr-s":
			return 'VR-S';
		case "very rare axa shield":
		case "very-rare axa shield":
		case "very_rare axa shield":
		case "very_rare aegis shield":
		case "axa":
			return 'AXA';
			
		case "rare force amp":
		case "fa":
			return 'FA';
		case "rare turret":
		case "turret":
			return 'turret';

		case "rare link amp":
		case "la":
			return 'LA';
		case "very_rare softbank ultra link":
		case "sb-la":
			return 'SB-LA';
			
		case "very_rare ito en transmuter (+)":
		case "VERY_RARE Ito En Transmuter (+)":
		case "ito+":
			return 'Ito+';
		case "very_rare ito en transmuter (-)":
		case "VERY_RARE Ito En Transmuter (-)":
		case "ito-":
			return 'Ito-';
		
		case "":
			return '';

		default:
			logger(WARN_MESSAGE_LEVEL, __FILE__, "[iitc_modReName] undefine: $name");
			return $name;
	}
}

// short name mod is farm mod or not
function iitc_isFarmMod( $name )
{
	switch($name){
		case "RMH":
		case "RHS":
		case "VRMH":
		case "VRHS":
		case "Ito-":
		case "Ito+":
			return 1;
			break;
	}
	return 0;
}

function iitc_getMonitor( $is )
{
	switch($is){
		case 1:
			return "<span style=\"color:#FF0000;\">紀錄監控</span>";
		case 2:
			return "<span style=\"color:#FF0000;\">藍八監控</span>";
		case 3:
			return "<span style=\"color:#FF0000;\">及時監控</span>";
		default:
			return "";
	}
}

	  
function getRemoteIP()
{
	if( !empty($_SERVER['HTTP_CLIENT_IP']) )
		return $_SERVER['HTTP_CLIENT_IP'];
	if( !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
	else
		return $_SERVER['REMOTE_ADDR'];
}

// 成就牌狀態, 數字換成為字串
function iitc_guard2str( $lv )
{
	switch( $lv ){
		case 1:
			return "銅";
			break;
		case 2:
			return "銀";
			break;
		case 3:
			return "金";
			break;
		case 4:
			return "白金";
			break;
		case 5:
			return "黑";
			break;
		case 6:		// unknown
			return "N/A";
			break;
		default:
			return "<span style='color:#cccccc;'>N/A</span>";
			break;
	}
}
// 成就牌狀態, 數字換成為字串
function iitc_guard2str2( $lv )
{
	switch( $lv ){
		case 1:
			return "銅";
			break;
		case 2:
			return "銀";
			break;
		case 3:
			return "金";
			break;
		case 4:
			return "白金";
			break;
		case 5:
			return "黑";
			break;
		case 6:		// unknown
			return "N/A";
			break;
		default:
			return "unknown";
			break;
	}
}

// 成就牌狀態, 字串換成為數字
function iitc_guard2num( $str )
{
	$TABLE = array("銅"=>1, "銀"=>2, "金"=>3, "白金"=>4, "黑"=>5, "N/A"=>6);
	if( isset($TABLE[$str]) )
		return $TABLE[$str];
	else 
		return 0;
}

// 成就牌狀態, 字串換成為數字
function iitc_days2num( $n )
{
	if( $n>=999 )
		return 6;
	else if( $n>=150 )
		return 5;
	else if( $n>=90 )
		return 4;
	else if( $n>=20 )
		return 3;
	else if( $n>=10 )
		return 2;
	else if( $n>=5 )
		return 1;
	else
		return 0;
}


function iitc_num2chinese( $n )
{
	switch( $n ){
		case 0:
		case 1:
			return '一';
		case 2:
			return '二';
		case 3:
			return '三';
		case 4:
			return '四';
		case 5:
			return '五';
		case 6:
			return '六';
		case 7:
			return '七';
		case 8:
			return '八';
		default:
			break;
	}
}

// portal 是否為農莊狀態
// 一般情況 vs activity time 一個人可以插兩隻八腳, 偵測條件不同
function iitc_isFarmyPortal($OBJ)
{
	global $CONFIG;	
	// empty mod (farm mod)
	$n = 4 - ($OBJ['sum']['n_mod'] - $OBJ['sum']['n_farm_mod']);
	
	if($CONFIG['alert']['is_activity']){ // activity time
		if($OBJ['level']==8) { 
			if($OBJ['sum']['n_farm_mod']>=1) return 1; // 八塔有一格農莊
		} else if ($OBJ['level']==7) {
			if($OBJ['sum']['n_farm_mod']>=2) return 1; // 七塔有兩格農莊
		}
	} else {
		// 五隻八腳以上，只要有兩格可以上農莊就通報
		if($OBJ['sum']['n_res8']>=5) { 
			if($n>=2) return 1; // 
		} else if ($OBJ['level']==7) {
			if($OBJ['sum']['n_farm_mod']>=2) return 1; // 七塔有兩格農莊
		}
	}
	return 0;
}

// 計算得分
function iitc_getScore($OBJ)
{	
	if(iitc_isFarmyPortal($OBJ)) $score = 20 + $OBJ['sum']['n_res8']*10 + $OBJ['sum']['n_farm_mod']*2; // 50 分以上
	else $score = $OBJ['sum']['n_res8']*5 + $OBJ['sum']['n_farm_mod']; // 50 分以下
	return $score;
}

// 取得之前的最高分紀錄, (前一筆資料 or 一小時內的最高紀錄)
function iitc_getPortalHighestScore($guid)
{	
	// 取得已註冊的清單
	$query = "select score
		from nia_events
		where guid = '$guid'
		and date_time > now() - INTERVAL 3600 SECOND
		order by score DESC
		limit 0,1";
	$getMaxScore = getQueryFields($query);
	return $getMaxScore[0]['score']*1;
}

// sum up portal resonator & mod
function iitc_makeSumUp($OBJ)
{	
	$OBJ_new['n_res8'] = 0;
	foreach($OBJ['resonators'] as $v) {
		if($v['level']==8) ++$OBJ_new['n_res8'];
	}
	$OBJ_new['n_farm_mod'] = 0;
	$OBJ_new['n_mod'] = 0;
	foreach($OBJ['mod'] as $i => $v) {
		if($v['owner']=="") continue;
		++$OBJ_new['n_mod'];
		if(iitc_isFarmMod($v['mod'])) ++$OBJ_new['n_farm_mod'];
	}
	return $OBJ_new;
}

// 輸入兩個狀態，計算出不同處 (只有 deploy, 沒有計算 destroy)
function iitc_getPortalChange($OBJ, $OBJ_pre)
{
	// match, destroy (X), deploy
	// deploy
	$TABLE_changes = array();
	// make a hash str
	$hashs = array();
	if(isset($OBJ_pre['resonators'])) foreach($OBJ_pre['resonators'] as $iii => $vvv){
		$hash = $vvv['level']." ".$vvv['owner'];
		$hashs[] = $hash;
	}
	if(isset($OBJ_pre['mod'])) foreach($OBJ_pre['mod'] as $iii => $vvv){
		$tmp = $vvv['owner'];
		$tmp2 = $vvv['mod'];
		$hash = $tmp2." ".$tmp;
		$hashs[] = $hash;
	}
	//echo "\n\n\nhash: ";
	//var_dump($hashs);echo "\n";

	// compare diff
	if(isset($OBJ['resonators'])) foreach($OBJ['resonators'] as $iii => $vvv){
		$hash = $vvv['level']." ".$vvv['owner'];
		$index = array_search($hash, $hashs);
		//echo "hash: $hash ".$index."\n";
		if(!is_numeric($index))
			$TABLE_changes['resonators'][$iii] = $vvv;
		else 
			unset($hashs[$index]);
	}
	if(isset($OBJ['mod'])) foreach($OBJ['mod'] as $iii => $vvv){
		$tmp = $vvv['owner'];
		$tmp2 = $vvv['mod'];
		$hash = $tmp2." ".$tmp;
		$index = array_search($hash, $hashs);
		//echo "hash: $hash ".$index."\n";
		if(!is_numeric($index)) 
			$TABLE_changes['mod'][$iii] = $vvv;
		else 
			unset($hashs[$index]);
	}

	return $TABLE_changes;
}

// 計算取得 portal guid
function iitc_findPortalByInfo($title, $latE6, $lngE6)
{
	$lat_up = $latE6 + 100;
	$lat_down = $latE6 - 100;
	$lng_up = $lngE6 + 100;
	$lng_down = $lngE6 - 100;

	// 查詢
	$query = "select guid
		from nia_portals_all 
		where title = '$title'
		and latE6 > $lat_down
		and latE6 < $lat_up
		and lngE6 > $lng_down
		and lngE6 > $lng_up
		limit 0, 1";
	$getPortal = getQueryFields($query);

	return $getPortal[0]['guid'];
}

// 計算距離 (E6), unit: m
function iitc_getDistanceE6($src_latE6, $src_lngE6, $to_latE6, $to_lngE6)
{
	return iitc_getDistance($src_latE6 / 1000000, $src_lngE6 / 1000000, 
		$to_latE6 / 1000000, $to_lngE6 / 1000000);
}

// 計算距離, unit: m
function iitc_getDistance($lat1, $lng1, $lat2, $lng2) 
{
	$earthRadius = 6367000; //approximate radius of earth in meters 

	/* 
	Convert these degrees to radians 
	to work with the formula 
	*/ 

	$lat1 = ($lat1 * pi() ) / 180; 
	$lng1 = ($lng1 * pi() ) / 180; 

	$lat2 = ($lat2 * pi() ) / 180; 
	$lng2 = ($lng2 * pi() ) / 180; 

	/* 
	Using the Haversine formula calculate the distance 
	http://en.wikipedia.org/wiki/Haversine_formula 
	*/ 

	$calcLongitude = $lng2 - $lng1; 
	$calcLatitude = $lat2 - $lat1; 
	$stepOne = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2); 
	$stepTwo = 2 * asin(min(1, sqrt($stepOne))); 
	$calculatedDistance = $earthRadius * $stepTwo; 

	return round($calculatedDistance);
}

// 計算射擊角度, src - dst, 正北為0度, 順時針方向
function iitc_getLinkThetaE6($lat1, $lng1, $lat2, $lng2) 
{
	return iitc_getLinkTheta($lat1/1000000, $lng1/1000000, $lat2/1000000, $lng2/1000000);
}

// 計算射擊角度, src - dst, 正北為0度, 順時針方向
function iitc_getLinkTheta($lat1, $lng1, $lat2, $lng2) 
{
	$dLat = $lat2 - $lat1;
	$dLng = $lng2 - $lng1;
	if(abs($dLng)<0.001) {
		if($dLat > 0) return 0;
		else return 180;
	}

	$thetaR = atan($dLat / $dLng);
	$theta = round($thetaR * 180 / pi());	// -90 ~ +90, 逆時針方向
	$theta = 90 - $theta;			// 順時針方向, 正北為 0 
	if($dLng < 0) $theta += 180;	// 二, 三 象限
	return $theta;
}

// 射擊角度轉換成圖文
function iitc_getLinkDirection($theta) 
{
	// ↑ ↗ → ↘ ↓ ↙ ← ↖  
	if($theta < 23 ) return "↑";		// 23
	else if($theta < 68 ) return "↗"; 	// 45 + 23
	else if($theta < 113 ) return "→"; 	// 90 + 23
	else if($theta < 158 ) return "↘"; 	// 135 + 23
	else if($theta < 203 ) return "↓"; 	// 180 + 23
	else if($theta < 248 ) return "↙"; 	// 225 + 23
	else if($theta < 293 ) return "←"; 	// 270 + 23
	else if($theta < 338 ) return "↖"; 	// 315 + 23
	else return "↑"; 	
}

// 射擊角度轉換成圖文
function iitc_getLinkDirectionEmoji($theta) 
{
	// ⬆ ↗ ➡ ↘ ⬇ ↙ ⬅ ↖
	if($theta < 23 ) return "⬆";		// 23
	else if($theta < 68 ) return "↗"; 	// 45 + 23
	else if($theta < 113 ) return "➡"; 	// 90 + 23
	else if($theta < 158 ) return "↘"; 	// 135 + 23
	else if($theta < 203 ) return "⬇"; 	// 180 + 23
	else if($theta < 248 ) return "↙"; 	// 225 + 23
	else if($theta < 293 ) return "⬅"; 	// 270 + 23
	else if($theta < 338 ) return "↖"; 	// 315 + 23
	else return "↑"; 	
}

// log time to timestamp
// 2018-10-18 17:37:35.748
function iitc_getTimestamp($t) 
{
	//return date("Y-m-d H:i:s", $t/1000) . "." . $t%1000;
	//return date("Y-m-d H:i:s", $t/1000);
	return date("Y-m-d H:i:s", $t);
}

// 17:37:35
function iitc_getTimeHIS($t) 
{
	//return date("d H:i:s", $t/1000);
	return date("d H:i:s", $t);
}

function iitc_getTimestampColor($t) 
{
	//return date("Y-m-d H:i:s", $t/1000) . "<span style='color:#CCCCCC;'>." . ($t%1000)."</span>";
	//return date("Y-m-d H:i:s", $t/1000);
	return date("Y-m-d H:i:s", $t);
}

// convert log type to string
function iitc_getLogTypeStr($t) 
{
	switch($t) {
		case 1:
			return 'deploy';
		case 2:
			return 'link';
		case 3:
			return 'CF';
		case 4: // destroy resonater
			return 'destroy';
		case 5:
			return 'capture';
		case 6: // destroy link
			return 'DLink';
		case 7: // destroy CF
			return 'DCF';
		case 11:
			return 'chat';
		case 12:
			return '<span style="color:#FF0000;">Recursed</span>';
		case 13:
			return 'Fracker';
		case 14:
			return 'Beacon';
		case 15:	// **** not ready to use
			return 'System';
		case 16:
			return 'Deploy RBB';
		case 17:
			return 'RBB End';
		case 18:
			return 'Deploy VRBB';
		case 19:
			return 'VRBB End';
		case 20:
			return 'RBB Detect!';
		default:
			return "N/A";
	}
}

// 取得 type 的延伸屬性意義
function iitc_getTypeObj($type)
{	
	$OBJ = array();

	$OBJ['isNoneMonitor'] = 1; // 是否不警戒
	$OBJ['isLogOnly'] = 0; // 是否只能做紀錄
	$OBJ['isFarm'] = 0; // 是否農場偵測
	$OBJ['isChanged'] = 0; // 是否變動偵測
	$OBJ['monitorFrequency'] = 0; // 是否只能做紀錄
	$OBJ['monitorFrequency'] = 0; // 偵測頻率
	if($type==ALERT_LEVEL_NONE) return $OBJ;
	$OBJ['isNoneMonitor'] = 0;
	
	$OBJ['monitorFrequency'] = MONITOR_FREQUENCY_HIGH;
	if($type==ALERT_LEVEL_LOG_L || 
		$type==ALERT_LEVEL_FARM_L || 
		$type==ALERT_LEVEL_CHANGED_L) $OBJ['monitorFrequency'] = MONITOR_FREQUENCY_LOW;
	
	if($type==ALERT_LEVEL_LOG_L || $type==ALERT_LEVEL_LOG_H) $OBJ['isLogOnly'] = 1;
	if($type==ALERT_LEVEL_FARM_L || $type==ALERT_LEVEL_FARM_H) $OBJ['isFarm'] = 1;
	if($type==ALERT_LEVEL_CHANGED_L || $type==ALERT_LEVEL_CHANGED_H) $OBJ['isChanged'] = 1;
	return $OBJ;
}

// 新增一筆新 portal 紀錄
function iitc_addNewPortalList($str)
{
	global $CONFIG;
	if(strlen($str)<10) return; // fix 0,0 issue
	if($CONFIG['data']['path']['newPortal']=="") {
		logger(WARN_MESSAGE_LEVEL, __FILE__, "[iitc_addNewPortalList] no config, CONFIG['data']['path']['newPortal']: ".$CONFIG['data']['path']['newPortal']);
		return;
	}
	$data = file_get_contents($CONFIG['data']['path']['newPortal']);
	$data = explode("\n", $data);

	// make as table
	$list = array_flip($data);
	if(!isset($list[$str])) {
		$fp = fopen($CONFIG['data']['path']['newPortal'], "a+");
		if(!$fp) {
			logger(WARN_MESSAGE_LEVEL, __FILE__, "[iitc_addNewPortalList] failed to create file: ".$CONFIG['data']['path']['newPortal']);
			return;
		}
		logger(INFO_MESSAGE_LEVEL, __FILE__, "[iitc_addNewPortalList] ".$CONFIG['data']['path']['newPortal']." add record: ".$str);
		fprintf($fp, "%s\n", $str);
		fflush($fp);
		fclose($fp);
	}
}

// send to Telegram, 2.5s delay
// if msg use illegal char, message will be blocked
function iitc_sendTgMessage($url, $channel_id, $msg) 
{	
	// private channel
	$post = array(
		"chat_id" => $channel_id,
		"text" => $msg,
		"parse_mode" => "HTML",
		"disable_web_page_preview" => "True"
	);
	$result = curlRequest( $url, $post );
}

// 計算指定 portal 是圈 n
function iitc_getFarmN($guid) 
{
	$CONFIG_CYCLE_DISTANCE = 40;	// 40m, the cycle radius range
	
	// 取得所有待評估塔
	$query = "select guid, lat, lng
		from nia_portals_all
		where guid = '$guid'";
	$getPortalInfo = getQueryFields($query);
	if(count($getPortalInfo)==0) {
		logger(ERROR_MESSAGE_LEVEL, __FILE__, "[iitc_getFarmN] guid not found: $guid");
		return 0;
	}

	// 在指定 portal 的 80m (40m*2) 範圍，能夠圈到的點
	$portals = iitc_getRangePortals($getPortalInfo[0], $CONFIG_CYCLE_DISTANCE*2); // radius in meter
	$num_portals = count($portals);

	$lat = $getPortalInfo[0]['lat'];
	$lng = $getPortalInfo[0]['lng'];

	//echo "<br><br>";
	$n = 0; // 圈 n
	// 每兩個點的中點作為中心點的方式來評估圈 n
	for($a=0;$a<$num_portals;$a++){
		for($b=$a+1;$b<$num_portals;$b++){
			
			// 取得兩個點的中心點
			$lat_new = ($portals[$a]['lat'] + $portals[$b]['lat'])/2;
			$lng_new = ($portals[$a]['lng'] + $portals[$b]['lng'])/2;
			
			// 必須圈到指定點
			$distance = iitc_getDistance($lat, $lng, $lat_new, $lng_new);
			//echo "center: $distance<br>";
			if($distance>$CONFIG_CYCLE_DISTANCE) continue;

			// 計算中心點有圈 n
			$n_new = 0;
			for($c=0;$c<$num_portals;$c++){
				$distance = iitc_getDistance($portals[$c]['lat'], $portals[$c]['lng'], $lat_new, $lng_new);
				//echo $portals[$c]['guid'].": ".$portals[$c]['title'].": $distance<br>";
				if($distance<$CONFIG_CYCLE_DISTANCE) ++$n_new;
			}
			if($n_new>$n) $n = $n_new; // 新的圈n數據
			//echo "$n, $n_new<br>";
		}
	}
	return $n;
}

// 計算指定 portal 是圈 n (detail)
// return
//		n: 圈n
//		center.lat center.lng: 參考中心點
//		neighbors: 鄰居的 guids
function iitc_getFarmNDetail($guid) 
{
	$rtnObj = array();

	$CONFIG_CYCLE_DISTANCE = 40;	// 40m, the cycle radius range
	
	// 取得所有待評估塔
	$query = "select guid, lat, lng
		from nia_portals_all
		where guid = '$guid'";
	$getPortalInfo = getQueryFields($query);
	if(count($getPortalInfo)==0) {
		logger(ERROR_MESSAGE_LEVEL, __FILE__, "[iitc_getFarmN] guid not found: $guid");
		return $rtnObj;
	}

	// 在指定 portal 的 80m (40m*2) 範圍，能夠圈到的點
	$portals = iitc_getRangePortals($getPortalInfo[0], $CONFIG_CYCLE_DISTANCE*2); // radius in meter
	$num_portals = count($portals);

	$lat = $getPortalInfo[0]['lat'];
	$lng = $getPortalInfo[0]['lng'];

	//echo "<br><br>";
	$n = 0; // 圈 n
	$point_neighbors = array();
	$point_center = array();
	// 每兩個點的中點作為中心點的方式來評估圈 n
	for($a=0;$a<$num_portals;$a++){
		for($b=$a+1;$b<$num_portals;$b++){
			
			// 取得兩個點的中心點
			$lat_new = ($portals[$a]['lat'] + $portals[$b]['lat'])/2;
			$lng_new = ($portals[$a]['lng'] + $portals[$b]['lng'])/2;
			
			// 必須圈到指定點
			$distance = iitc_getDistance($lat, $lng, $lat_new, $lng_new);
			//echo "center: $distance<br>";
			if($distance>$CONFIG_CYCLE_DISTANCE) continue;

			// 計算中心點有圈 n
			$n_new = 0;
			$neighbors = array();
			for($c=0;$c<$num_portals;$c++){
				$distance = iitc_getDistance($portals[$c]['lat'], $portals[$c]['lng'], $lat_new, $lng_new);
				//echo "$distance<br>";
				if($distance<$CONFIG_CYCLE_DISTANCE) {
					++$n_new;
					$neighbors[] = $portals[$c]['guid'];
				}
			}
			if($n_new>$n) {
				$n = $n_new; // 新的圈n數據
				$point_center['lat'] = $lat_new;
				$point_center['lng'] = $lng_new;
				$point_neighbors = $neighbors;
			}
			//echo "$n, $n_new<br>";
		}
	}

	$rtnObj['neighbors'] = $point_neighbors;
	$rtnObj['center'] = $point_center;
	$rtnObj['n'] = $n;
	return $rtnObj;
}


// 在指定 portal 的 80m (40m*2) 範圍，能夠圈到的點
function iitc_getRangePortals($portal, $range)
{
	$lat = $portal['lat'];
	$lng = $portal['lng'];
	
	$CONFIG_DX = 0.00098;	// 100m
	$CONFIG_DY = 0.0009;	// 100m

	$dy = $CONFIG_DY / 100 * $range;
	$dx = $CONFIG_DX / 100 * $range;

	$lat_down = $lat - $dy;
	$lat_up = $lat + $dy;
	$lng_down = $lng - $dx;
	$lng_up = $lng + $dx;

	// 取得範圍內的候選塔
	$query = "select lat, lng, guid, title
		from nia_portals_all
		where is_delete = 0
		and lat > $lat_down
		and lat < $lat_up
		and lng > $lng_down
		and lng < $lng_up";
	$getPortals = getQueryFields($query);
	return $getPortals;
}



// 取得 agent 的活動時段 (允許多個時段)
// time: 現在時間, unit: s
// return  objs[]
//      time_start 開始時間, unit: ms
//      time_end 結束時間, unit: ms
//      diff 活動總時間, unit: ms
function iitc_calLogActivitySum($cn, $time)
{
	$CONFIG['log']['active']['timeInterval'] = 30 * 60 * 1000; // 斷開的時間間隔, unit: ms

	//$time_start = (floor($time / 86400) * 86400 - 3600*5) * 1000;
	//$time_end = (floor($time / 86400) * 86400 + 3600*19) * 1000;
	$time_start = (floor($time / 86400) * 86400 - 3600*5);
	$time_end = (floor($time / 86400) * 86400 + 3600*19);

	$results = array();

	$day = date("ymd", $time);
	// 確認之前是否有計算過
	$query = "select results
		from nia_log_players
		where cn = '$cn'
		and day = '$day'";
	$getLogs = getQueryFields($query);
	if(count($getLogs)) return json_decode($getLogs[0]['results'], true);

	// 取得 logs
	$query = "select timestamp
		from nia_logs
		where cn = '$cn'
		and timestamp > $time_start
		and timestamp < $time_end
		order by timestamp";
	$getLogs = getQueryFields($query);
	unset($obj);
	if(count($getLogs)) foreach($getLogs as $i => $v) {
		if(!isset($obj)) {
			$obj = array();
			$obj['time_start'] = $v['timestamp'];
			$time_pre = $v['timestamp'];
		}

		$time_diff = $v['timestamp'] - $time_pre;
		//echo "$i, $time_diff<br>";
		if($time_diff > $CONFIG['log']['active']['timeInterval']) {
			$obj['time_end'] = $time_pre;
			$obj['diff'] = $v['timestamp'] - $obj['time_start'];
			$results[] = $obj;
			unset($obj);
		}
		$time_pre = $v['timestamp'];
	}
	if($obj) {
		$obj['time_end'] = $time_pre;
		$obj['diff'] = $v['timestamp'] - $obj['time_start'];
		$results[] = $obj;
	}

	// log it
	if(count($results)) {
		$str_results = json_encode($results);
		$query = "insert into nia_log_players ( cn, day, results)
			values ( '$cn', '$day', '$str_results');";
		if( !mysql_query($query) )
			 errorMSG($query);
	}

	return $results;
}

// convert ms time to date time
function iitc_msTimeToString($msTime) 
{
	//return date("H:i", $msTime / 1000);
	return date("H:i", $msTime);
}

function iitc_scanTileMappingXY($value, $d)
{
	$tmp = $value + 0.00001; // atom shift
	return floor($tmp / $d);
}

// return region info
function iitc_getPortalRegionInfo($portalData)
{
	if($portalData['adm_2'] == "臺灣省") return $portalData['adm_3'].$portalData['adm_4'];
	else return $portalData['adm_2'].$portalData['adm_3'].$portalData['adm_4'];
}

// detect log type
function iitc_detectLogType($obj)
{
    $msg = $obj['text'];
    // 1: deploy, 2: link, 3:CF, 4:destroy, 5:capture, 6:destroy link, 11:chat, 12:recursed, 13:fracker, 14:beacon, 15: others, 16: deploy RBB, 17: RBB end, 18: deploy VRBB, 19: VRBB end
    // 21: system
    //echo sprintf("%s<br>", $obj['markup'][2][0]);
    //var_dump($obj); echo "<br>";
    // skip self message
    if(preg_match("'^Your '", $msg, $match)) return 99;
    else if($obj['markup'][0][0] == "SECURE") {
        if(preg_match("' recursed '", $msg, $match)) return 12;
        if(!preg_match("':'", $obj['text'], $match)) return 21; // system message
        if($obj['markup'][2][0] == "TEXT" || $obj['markup'][2][0] == "PLAYER") {
            return 11;
        }
    } else if($obj['markup'][0][0] == "SENDER") {
        if(preg_match("' recursed '", $msg, $match)) return 12;
        return 11;
    } else if(preg_match("'^[a-zA-Z0-9_]+ Recursed'", $msg, $match)) {
        return 12;
    } else {
        if(preg_match("'[\w\d_-]+ captured '", $msg, $match)) return 1;
        if(preg_match("' deployed (a|the) Resonator '", $msg, $match)) return 1;
        if(preg_match("' linked '", $msg, $match)) return 2;
        if(preg_match("' created (a|the) Control Field '", $msg, $match)) return 3;
        if(preg_match("' destroyed (a|the) Resonator '", $msg, $match)) return 4;
        //if(preg_match("' captured '", $msg, $match)) return 5;
        if(preg_match("'^[\w\d]+ captured '", $msg, $match)) return 5;

        if(preg_match("' destroyed the [^ ]+ Link '", $msg, $match)) return 6;
        if(preg_match("' destroyed (a|the) Link '", $msg, $match)) return 6;
        if(preg_match("' destroyed the [^ ]+ Control Field '", $msg, $match)) return 7; // new
        if(preg_match("' destroyed (a|the) Control Field '", $msg, $match)) return 7; // new
        
        if(preg_match("' deployed a Portal Fracker '", $msg, $match)) return 13;
        if(preg_match("' deployed a Beacon '", $msg, $match)) return 14;
        if(preg_match("' deployed Fireworks '", $msg, $match)) return 15; // others
        if(preg_match("' deployed a Rare Battle Beacon '", $msg, $match)) return 16;
        if(preg_match("' won a CAT-\w+ Rare Battle Beacon '", $msg, $match)) return 17;
        if(preg_match("' deployed a Very Rare Battle Beacon '", $msg, $match)) return 18;
        if(preg_match("' won a CAT-\w+ Very Rare Battle Beacon '", $msg, $match)) return 19;
        if(preg_match("'^Rare Battle Beacon will be deployed at the end '", $msg, $match)) return 20;  // new
        if(preg_match("'^Drone returned to '", $msg, $match)) return 21;  // system message
    }
    
    if(preg_match("' has decayed'", $msg, $match)) return 99;

    echo sprintf("dump: %s<br>\n", json_encode($obj['markup']));
    echo "$msg<br>";
    return 0; // un-defined
}

// compatible with getPlext & getPortalDetails
function iitc_parsePortal($str)
{
    $obj = [];
    $fields = json_decode(trim($str), true);
    $fieldsLen = count($fields);
    //var_dump($fields);
    $ext_tile = ""; // extra tile key info
    if($fieldsLen == 19) { // field + 1, getPortalDetails
        $obj["guid"] = $fields[18];
        $obj["getPortalDetails"] = 1;
    } else { // getPlext
        $obj["guid"] = trim($fields[0]);
        $ext_tile = $fields[3]; // OEM field to keep tile key
        $fields = $fields[2];
    }
    $fieldsLen = count($fields);
    //echo sprintf("%d, %s<br>\n", $fieldsLen, $fields[0]);
    $obj["type"] = $fields[0]; // p: portal
    if( $fieldsLen < 3) return [];
    if ($fieldsLen == 3) return $obj; // r 8
    if ($fieldsLen == 8) return $obj; // e 8
    if ($obj["type"] != "p") return $obj;

    $obj["team"] = $fields[1]; // team, E: ENL, R: RES, N: normalize
    $obj["latE6"] = $fields[2];
    $obj["lngE6"] = $fields[3];
    $obj["level"] = $fields[4]; // portal level, 1~8
    $obj["health"] = $fields[5]; // in percentage
    $obj["resCount"] = $fields[6]; // res count
    $obj["image"] = trim($fields[7]);
    $obj["name"] = trim($fields[8]); // encoding
    $obj["na1"] = $fields[9];
    $obj["na2"] = $fields[10];
    $obj["na3"] = $fields[11];
    $obj["na4"] = $fields[12];
    $obj["na5"] = $fields[13];

    // getPortalDetail
    if ($fieldsLen > 14){
        $obj["mods"] = $fields[14];
        $obj["ress"] = $fields[15];
        $obj["owner"] = $fields[16];
        $obj["na6"] = $fields[17];
    }
    // getPlext ext
    if ($ext_tile){
        if(preg_match("'^15_(\d+)_(\d+)'", $ext_tile, $match)) {
            $obj["tile_x"] = $match[1];
            $obj["tile_y"] = $match[2];
        }
    }
    return $obj;
}

// 0: no, 1: is fake, 2: unknown
function iitc_isNiaFakePortal($title, $image)
{
    if($title == "Niantic") {
        return ($image == "http://lh3.googleusercontent.com/j0tbQax4PikYzcLsnEbUV9NUQo0e7FVIq59qz0IAkGxsr6eugpBB9uFTakyGDbGUS-yEQnN0sy8DOw0qIpV9p96XjoYZU-MWOOJSHQ") ? 1 : 2;
    }
    return 0;
}


function deepCopy($obj)
{
    return unserialize(serialize($obj));
}
?>