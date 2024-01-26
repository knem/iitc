<?session_start();
	require_once("module_connectDB.php");
	require_once("./tools_iitc.php");
/*
	系統核心 - 訊息記錄器

		藍八系統
		成就系統
*/

	//timerNow();
	header("Access-Control-Allow-Origin: *"); // for IITC re-route


	$mode = $_REQUEST['mode'];			// 操作模式, 0:skip, 1:分析, 2:log only, 3:monitor
	$data = $_REQUEST['data'];
	$data = preg_replace("'\\\\\"'", "\"", $data);


	// 紀錄 scanner 的位址以及最後更新時間
	// 3: blue8 client
	// 104, 103: killer client
	if( $mode==3 || $mode==104 || $mode==103 ){
		$ip = getRemoteIP();
		// 取得已註冊的清單
		$query = "select update_time
			from nia_users
			where ip = '$ip'
			and fun = $mode";
		$getUser = getQueryFields($query);
		if( count($getUser)>0 ){

			$query = "update nia_users 
				set update_time = now()
				where ip = '$ip'
				and fun = $mode";
			if( !mysql_query($query) )
				 errorMSG($query);
		}
		else{
			$query = "insert into nia_users ( ip, update_time, fun )
				values ( '$ip', now(), $mode)";
			if( !mysql_query($query) )
				errorMSG($query);
			else logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] nia_users new login IP: $ip");
		}
	}



	// debug mode
	$DEBUG = 0;
	if($_REQUEST['DEBUG']){
		$DEBUG = 1;
	}

	// log data
	$isDataLog = $_REQUEST['isDataLog'];
	if($isDataLog) {
		$fp_log = fopen("./out/logger.txt","a+");
		fprintf($fp_log, "%s\r\n", $data);
		fclose($fp_log);
	}

	// 
	switch( $mode ){
		case 1:	//======= get portal list START (X) =============	
			logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] mode is invalid");
			break; //======= get portal list END =============

		case 3:	//======= logger & bot START ===========
			// in debug mode, load simulate data
			if($DEBUG) {
				if(!$CONFIG['blue8']['test']['dataFile']) {
					logger(ERROR_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['blue8']['test']['dataFile']: ".$CONFIG['blue8']['test']['dataFile']);
					exit(0);
				}
				$data = file_get_contents($CONFIG['blue8']['test']['dataFile']);
			}

			// 資料一次輸入一筆
			$list = explode("\n", $data);
			$n = count($list);
			for($i=0;$i<$n;$i++){

				$OBJ = json_decode( trim($list[$i]), true);
				if( count($OBJ)<2 ) continue;
				if( !isset($OBJ['guid']) ) continue;
				$guid = $OBJ['guid'];

				// 確認警戒等級
				$query = "select bias, type, farm_n
					from nia_portals
					where guid = '$guid'";
				$getData = getQueryFields($query);
				if(count($getData)==0) {
					logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] no such record: $guid");
					continue;
				} 
				$farmN = $getData[0]['farm_n']; // 圈 n
				$typeProp = iitc_getTypeObj($getData[0]['type']); // convert type as obj
				if($typeProp['isNoneMonitor']) {
					logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] no monitor portal: $guid, ".$getData[0]['type']);
					continue;
				} 

				// update portal time
				$query = "update nia_portals
					set time_update = now()
					where guid = '$guid'";
				if( !mysql_query($query) )
					errorMSG($query);

				// adjust input data
				$OBJ['team'] = iitc_codeToTeam($OBJ['team']); // convert code to enum
				$OBJ['mod'] = array();
				$list_mods = array(); // mod list
				if(count($OBJ['mod_org'])>0) foreach($OBJ['mod_org'] as $ii => $vv) {
					$tmp = $vv['rarity']." ".$vv['name'];
					$tmp = iitc_modReName($tmp);
					$modObj = array();
					if($vv['owner']!="") {
						$modObj['owner'] = $vv['owner'];
						$modObj['mod'] = $tmp;
						array_push($list_mods, $tmp);
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$vv['owner'].": $tmp\n");
					}
					$OBJ['mod'][$ii] = $modObj;
				}
				$str_mods = implode(",", $list_mods);
				
				$list_resons = array(); // reson list
				if(count($OBJ['resonators'])>0) foreach($OBJ['resonators'] as $ii => $vv) {
					if($vv['owner']!="") {
						array_push($list_resons, $vv['level'].$vv['owner']);
					}
				}
				$str_resons = implode(",", $list_resons);

				// ***** 修正白塔時, 陣營是 RES, level 是 1 的 issue
				if($OBJ['owner']=="") {
					$OBJ['team'] = 0;
					$OBJ['level'] = 0;
				} 

				// 建立統計資訊
				$OBJ['sum'] = iitc_makeSumUp($OBJ);

				// 是否有點薯條
				$is_fracker = 0;
				if(count($OBJ['ornaments'])>0){
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] beacon: ".trim($list[$i]));
					if($OBJ['ornaments'][0]=="peFRACK"){
						$OBJ['sum']['is_fracker'] = 1;
						$is_fracker = 1;
					}
				}
				// prepare store into DB as portal content
				$OBJ2 = $OBJ;
				unset($OBJ2['mod_org']); // clean useless raw data

				// make hash string for quick compare
				$str_hash = $OBJ['team']."_".$OBJ['level']."_".$str_mods."_".$str_resons;
				if($is_fracker) $str_hash .= "*";
				//echo "$str_hash<br>";

				$portal_status_is_change = 0;		// portal 是否 update
				$is_first_log = 0;					// 是否是第一個 log
				// 取得已註冊的清單
				$query = "select hash, contents
					from nia_portal_log
					where guid = '$guid'
					order by log_id DESC
					limit 0,1";
				$getLog = getQueryFields($query);
				if(count($getLog)==0) { // 第一次得到 log 只記錄，不動作
					// update status
					//$query = "insert into nia_portal_log ( guid, contents, changes, hash, is_debug)
					//	values ( '$guid', '".json_encode($OBJ2)."', '$changes', '$str_hash', '$DEBUG' ) ";
					//if( !mysql_query($query) )
					//	errorMSG($query);
					//else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] new monitor portal log: $guid");
					$is_first_log = 1;
					//continue;
				}
				if( $getLog[0]['hash']==$str_hash ) continue;
					
				// 是否重複出現, 避免刷來刷去 (測試模式無時間)
				$timeAvoidRepeatInterval = $CONFIG['blue8']['time']['avoidRepeatInterval'];
				if($timeAvoidRepeatInterval<=0) {
					logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['blue8']['time']['avoidRepeatInterval']: $timeAvoidRepeatInterval");
					continue;
				}
				$query = "select 1
					from nia_portal_log
					where guid = '$guid'
					and hash = '$str_hash'
					and create_time > now() - INTERVAL $timeAvoidRepeatInterval SECOND
					limit 0,1";
				$getLog2 = getQueryFields($query);
				if( count($getLog2)==1 && $DEBUG!=1) continue; 

				if($is_first_log==1) {
					$OBJ_new['deploy'] = array();
					$changes = array();
				} else {
					$OBJ_pre = json_decode( $getLog[0]['contents'], true );
					$TABLE_changes = iitc_getPortalChange($OBJ, $OBJ_pre); // TODO: destroy
					$OBJ_new['deploy'] = $TABLE_changes; // change message
					$changes = json_encode($OBJ_new);
				}
				
				
				if($is_first_log==1) logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] new monitor portal log: $guid");
				// update status
				$query = "insert into nia_portal_log ( guid, contents, changes, hash, is_debug)
					values ( '$guid', '".json_encode($OBJ2)."', '$changes', '$str_hash', '$DEBUG' ) ";
				if( !mysql_query($query) )
					errorMSG($query);
				//echo "query: $query<br>";

				// write back to id
				// get log id
				$query = "select log_id
					from nia_portal_log
					where guid = '$guid'
					order by log_id DESC
					limit 0,1";
				$getLog3 = getQueryFields($query);
				$logId = $getLog3[0]['log_id'];

				// nia_portals (blue8_portals)
				$query = "update nia_portals
					set log_id = '$logId'
					where guid = '$guid'";
				if( !mysql_query($query) )
					errorMSG($query);
					
				// update portal status 
				$query = "update nia_portals
					set owner = '".$OBJ['owner']."', 
						level = '".$OBJ['level']."', 
						res_count = '".$OBJ['resCount']."',
						n_res8 = '".$OBJ['sum']['n_res8']."',
						team = '".$OBJ['team']."',
						mod0 = '".$OBJ['mod'][0]['mod']."', 
						mod1 = '".$OBJ['mod'][1]['mod']."', 
						mod2 = '".$OBJ['mod'][2]['mod']."', 
						mod3 = '".$OBJ['mod'][3]['mod']."'
					where guid = '$guid'";
				if( !mysql_query($query) )
					errorMSG($query);


				// 確認 portal 有改變, 接下來就是發布通知
				if($typeProp['isLogOnly']) continue;

				// 取得已註冊的清單
				$query = "select lat, lng
					from nia_portals_all
					where guid = '$guid'";
				$getPortalInfo = getQueryFields($query);
				$title = $OBJ['title'];
				$lat = $getPortalInfo[0]['lat'];
				$lng = $getPortalInfo[0]['lng'];

				// 警戒狀態
				$is_send_tg = 0; // 是否推波訊息
				if($typeProp['isChanged']) {
					// first one log not alert
					if($is_first_log==1) continue;

					// 取得新增的人
					$cns = array();
					if(isset($OBJ_new['deploy']['resonators'])) foreach($OBJ_new['deploy']['resonators'] as $ii => $vv){
						if($vv['owner']!="") $cns[$vv['owner']] = 1;
					}
					if(isset($OBJ_new['deploy']['mod'])) foreach($OBJ_new['deploy']['mod'] as $ii => $vv){
						if($vv['owner']!="") $cns[$vv['owner']] = 1;
					}
					$str_cns = "+".implode(" +", array_keys($cns));
					
					// 訊息
					$msg = ($DEBUG) ? "<b>(測試)</b>" : "";
					if($OBJ['owner']=="") {
						$time_start = (time()-$timekillLogInterval) * 1000; // 收集十分鐘內的紀錄
						$farm_killer = "";
						// 取得相關 log, 來標註誰車的
						$query = "select timestamp, cn
							from nia_logs
							where guid = '$guid'
							and type = 4
							and timestamp > $time_start
							order by timestamp DESC
							limit 0,8";
						$getLogs = getQueryFields($query);
						$list_killer = array();
						if(count($getLogs)>0) foreach($getLogs as $ii => $vv) {
							$list_killer[$vv['cn']] = 1;
						}
						$killers = implode(", ", array_keys($list_killer));

						// 取得前一個陣營狀態
						$query = "select log_id, contents
							from nia_portal_log
							where guid = '$guid'
							and log_id != $logId
							order by log_id DESC
							limit 0,1";
						$getPrevLogs = getQueryFields($query);
						if(count($getPrevLogs)>0) {
							$OBJ_prev = json_decode($getPrevLogs[0]['contents'], true);
						}
						$msg .= "<b>[警戒]</b> ";
						$msg .= $title;
						$msg .= ($getData[0]['bias']=="") ? "\n" : " (".$getData[0]['bias'].")\n";
						$msg .= "(從".iitc_toCamp3($OBJ_prev['team'])."被打白) \n";
						$msg .= $killers;
					} 
					else {
						$msg .= "<b>[警戒](".iitc_toCamp2($OBJ['team']).")</b> ";
						$msg .= $title;
						$msg .= ($getData[0]['bias']=="") ? "\n" : " (".$getData[0]['bias'].")\n";
						$msg .= "(擁有者: ".$OBJ['owner'].") \n";
						$msg .= implode(" | ", $list_mods)."\n";
						$msg .= "<a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".$lat.",".$lng."&z=17&pll=".$lat.",".$lng."\">IITC</a> |";
						$msg .= " <a href=\"".$CONFIG['ingress']['url']['googleMap']."?ll=".$lat.",".$lng."&q=".$lat.",".$lng."\">Gmap</a>";
						$msg .= "\n$str_cns";
					}

					// 建立訊息
					$query = "insert into nia_events ( guid, date_time, description, log_id)
						values ( '$guid', now(), '$msg', '$logId');";
					if( !mysql_query($query) )
						errorMSG($query);

					$is_send_tg = 1;
				}
				else {
					$is_farmy = iitc_isFarmyPortal($OBJ);
					$score = iitc_getScore($OBJ); // 取得分數
					$maxScore = iitc_getPortalHighestScore($guid); // 取得最高分資訊 (一小時內), 超過一小時, 顯示前一筆
					$is_upgraded = ($score>$maxScore)*1;
					//$is_destroyed = $score>$maxScore;
	
					// 取得可能存在的玩家
					$cns = array();
					if(isset($OBJ['resonators'])) foreach($OBJ['resonators'] as $ii => $vv){
						if($vv['owner']!="" && $vv['level']==8) $cns[$vv['owner']] = 1;
					}
					if(isset($OBJ['mod'])) foreach($OBJ['mod'] as $ii => $vv){
						if($vv['owner']!="") $cns[$vv['owner']] = 1;
					}
					$str_cns = implode(" @", array_keys($cns));

					$is_prev_farmy = 0;
					$is_destroyed = 0;
					// 前一筆資料是否是 farmy status
					$query = "select log_id, contents
						from nia_portal_log
						where guid = '$guid'
						and log_id != $logId
						order by log_id DESC
						limit 0,1";
					$getPrevLogs = getQueryFields($query);
					if(count($getPrevLogs)>0) {
						$OBJ_prev = json_decode($getPrevLogs[0]['contents'], true);
						$is_prev_farmy = iitc_isFarmyPortal($OBJ_prev);
						$scorePrev = iitc_getScore($OBJ_prev); // 取得分數
						$is_destroyed = ($scorePrev>$score)*1;
					}
					
					// 重大事件避免重複出現的時間
					$timeEventTimeout = $CONFIG['blue8']['time']['eventTimeout'];
					if($timeEventTimeout<=0) {
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['blue8']['time']['eventTimeout']: $timeEventTimeout");
						continue;
					}
					// 之前是否顯示過薯條
					$is_show_fracker = 0;
					// 取得薯條事件
					$query = "select score
						from nia_events
						where guid = '$guid'
						and is_fracker = 1
						and date_time > now() - INTERVAL $timeEventTimeout SECOND
						limit 0, 1";
					$getFrackerEvent = getQueryFields($query);
					if(count($getFrackerEvent)>0) $is_show_fracker = 1;
					
					// 之前是否顯示過已車
					$is_show_destroyed = 0;
					// 取得薯條事件
					$query = "select score
						from nia_events
						where guid = '$guid'
						and is_destroyed = 1
						and date_time > now() - INTERVAL $timeEventTimeout SECOND
						limit 0, 1";
					$getDestroyEvent = getQueryFields($query);
					if(count($getDestroyEvent)>0) $is_show_destroyed = 1;

					//echo "is_show_destroyed: $is_show_destroyed, is_destroyed: $is_destroyed, is_prev_farmy: $is_prev_farmy<br>";
					//echo "is_farmy: $is_farmy, is_upgraded: $is_upgraded, is_show_fracker: $is_show_fracker, is_fracker: $is_fracker<br>";

					// normal status
					// 是否顯示已車資訊
					if($is_show_destroyed==0 && $is_destroyed==1 && $is_prev_farmy==1) {
						echo "in kill<br>";
							
						// 收集十分鐘內的紀錄
						$timekillLogInterval = $CONFIG['blue8']['time']['killLogInterval'];
						if($timekillLogInterval<=0) {
							logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['blue8']['time']['killLogInterval']: $timekillLogInterval");
							continue;
						}
						$time_start = (time()-$timekillLogInterval); // 收集十分鐘內的紀錄
						$farm_killer = "";
						// 取得相關 log, 來標註誰車的
						$query = "select timestamp, cn
							from nia_logs
							where guid = '$guid'
							and type = 4
							and timestamp > $time_start
							order by timestamp DESC
							limit 0,8";
						$getLogs = getQueryFields($query);
						$list_farm_killer = array();
						if(count($getLogs)>0) foreach($getLogs as $ii => $vv) {
							$list_farm_killer[$vv['cn']] = 1;
						}
						$farm_killer = implode(", ", array_keys($list_farm_killer));

						$msg = ($DEBUG) ? "<b>(測試)</b>" : "";
						$msg .= "<b>[".iitc_toCamp3($OBJ['team']) . iitc_num2chinese($OBJ['level'])."]</b> ".$OBJ['title']." (".$getData[0]['bias'].")\n";
						$msg .= "已車 " . $farm_killer;
						$msg .= "\n<a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".$lat.",".$lng."&z=17&pll=".$lat.",".$lng."\">IITC</a> |";
						$msg .= " <a href=\"".$CONFIG['ingress']['url']['googleMap']."?ll=".$lat.",".$lng."&q=".$lat.",".$lng."\">Gmap</a>";
						

						$query = "insert into nia_events ( guid, date_time, description, log_id, score, is_fracker, is_destroyed)
							values ( '$guid', now(), '$msg', '$logId', '$score', '".($is_fracker*1)."', 1);";
						if( !mysql_query($query) )
							 errorMSG($query);

						$is_send_tg = 1;
					} else if($is_farmy==1 && ($is_upgraded==1 || 
						($is_show_fracker==0 && $is_fracker==1))){ // 農莊塔, 升級 or 薯條
						echo "in upgraded<br>";

						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] n_mod: ".$OBJ['sum']['n_mod'].", "."n_farm_mod: ".$OBJ['sum']['n_farm_mod'].", "."n_res8: ".$OBJ['sum']['n_res8']);

						$msg = ($DEBUG) ? "<b>(測試)</b>" : "";
						$msg .= "<b>[".iitc_toCamp3($OBJ['team']) . iitc_num2chinese($OBJ['level'])."]</b> ";
						$msg .= $title;
						$msg .= ($getData[0]['bias']=="") ? "" : " (".$getData[0]['bias'].")";
						$msg .= ($farmN>0) ? " 圈".$farmN."\n" : "\n";
						$msg .= "擁有者: ".$OBJ['owner']." ";
						$msg .= ($OBJ['level']*1<8) ? "缺".(8-$OBJ['sum']['n_res8'])."上八\n" : "\n";
						$msg .= implode(" | ", $list_mods);
						$msg .= ($is_fracker) ? " (薯條)\n" : "\n";
						$msg .= "<a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".$lat.",".$lng."&z=17&pll=".$lat.",".$lng."\">IITC</a> |";
						$msg .= " <a href=\"".$CONFIG['ingress']['url']['googleMap']."?ll=".$lat.",".$lng."&q=".$lat.",".$lng."\">Gmap</a>";
						$msg .= ($str_cns) ? "\n@".$str_cns : "";
						
						
						$query = "insert into nia_events ( guid, date_time, description, log_id, score, is_fracker)
							values ( '$guid', now(), '$msg', '$logId', '$score', '".($is_fracker*1)."');";
						if( !mysql_query($query) )
							 errorMSG($query);

						$is_send_tg = 1;
					}
				}
				
				// 是否推波訊息
				if(false && $is_send_tg==1) {
					if(!$CONFIG['ingress']['url']['intelMap'])
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['ingress']['url']['intelMap']: ".$CONFIG['ingress']['url']['intelMap']);
					if(!$CONFIG['ingress']['url']['googleMap'])
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['ingress']['url']['googleMap']: ".$CONFIG['ingress']['url']['googleMap']);

					// send to Telegram, 2.5s delay
					$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
					//$channel_id = ($DEBUG || 1) ? $CONFIG['alert']['tg']['channel'][1] : $CONFIG['alert']['tg']['channel'][0];
					//***** filter out to avoid
					//if($OBJ['sum']['n_res8']>5 && $OBJ['team']==1) 
					//	$channel_id = $CONFIG['alert']['tg']['channel'][1];
					//else
						$channel_id = ($DEBUG) ? $CONFIG['alert']['tg']['channel'][1] : $CONFIG['alert']['tg']['channel'][0];
					iitc_sendTgMessage($url, $channel_id, $msg);

					//var_dump($result);
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] portal: ".trim($list[$i]));
					logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] detect: $msg");
				}
			}
			break;	//======= logger & bot END ===========

		//case 4:	// (X) user log (test)
			//break;

		case 5:	// user log
			// 20181227 each request cost 15s(10 logs) to 0.03s
			// in debug mode, load simulate data
			if($DEBUG) {
				if(!$CONFIG['log']['test']['dataFile']) {
					logger(ERROR_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['log']['test']['dataFile']: ".$CONFIG['log']['test']['dataFile']);
					exit(0);
				}
				$data = file_get_contents($CONFIG['log']['test']['dataFile']);
			}

			// check config
			if($CONFIG['alert']['link']<=0)
				logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['alert']['link']: ".$CONFIG['alert']['link']);
			if($CONFIG['alert']['cf']<=0)
				logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['alert']['cf']: ".$CONFIG['alert']['cf']);

			$OBJ = json_decode( trim($data), true);
			//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode]".count($OBJ['result']));
			$DEBUG_is_first = 1;
			if(isset($OBJ['result'])) {
				//timerNow();
				$n = count($OBJ['result']);
				foreach($OBJ['result'] as $i => $v){
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode]" . json_encode($v));
					if(count($v)<2) continue;

					$logId = $v['log_id'];
					$time = round($v['time']/1000); // js unit is ms, php unit is s
					$type = $v['type'];
					$cn = trim($v['cn']);
					$team = $v['team'];
					$mu = $v['mu']*1;
					$mu_unit = $v['mu_unit'];
					$msg = confirmInput($v['text']);
					$note = $v['note'];

					if($DEBUG_is_first) {
						$DEBUG_is_first = 0;
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$v['log_id']." log time: ".date("H:i:s", $time).", records: $n");
					}

					if(!is_numeric($type)) {
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] type invalid: ". json_encode($v));
						continue;
					}
					if($cn=="") {
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] cn invalid: ". json_encode($v));
						//continue;
					}
					if(strlen(trim($cn))!=strlen($cn)) {
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] agent invalid: $cn, ". json_encode($v));
						//continue;
					}
					if(strlen($note)>0) {
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] note: $cn, ". json_encode($v));
						//continue;
					}
					
					// if log exists
					// this query cost 0.3s
					//$query = "select 1
					//	from nia_logs
					//	where log_id = '$logId'";
					//$getLog = getQueryFields($query);
					//if(count($getLog)>0) {
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] log exists: $logId");
					//	continue;
					//}
					// **** log
					if($type==12) {
						logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode]" . json_encode($v));
					}
					if($type==7) {
						$type = 4; // destroy CF treated as type 4
					}
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$OBJ['result'][0]['log_id']."($i) t1: ".timerNow());

					// avoid DB injection
					$v['p_title'] = confirmInput($v['p_title']);
					$v['to_p_title'] = confirmInput($v['to_p_title']);
					// find portal
					$guid = iitc_findPortalByInfo($v['p_title'], $v['p_latE6'], $v['p_latE6']);

					$distance = 0;
					$to_guid = "";
					if($type==2 || $type==6) {
						$to_guid = iitc_findPortalByInfo($v['to_p_title'], $v['to_p_latE6'], $v['to_p_latE6']);
						$distance = iitc_getDistanceE6($v['p_latE6'], $v['p_lngE6'], $v['to_p_latE6'], $v['to_p_lngE6']);
						$linkTheta = iitc_getLinkThetaE6($v['p_latE6'], $v['p_lngE6'], $v['to_p_latE6'], $v['to_p_lngE6']);
						$linkDirect = iitc_getLinkDirection($linkTheta);
						$linkDirectEmoji = iitc_getLinkDirectionEmoji($linkTheta);
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] linkTheta: $linkTheta, linkDirect: $linkDirect, ".$v['p_latE6'].", ".$v['p_lngE6'].", ".$v['to_p_latE6'].", ".$v['to_p_lngE6']);
					}

					// if agent not exists
					$query = "select team
						from nia_players
						where cn = '$cn'";
					$getAgent = getQueryFields($query);
					if(count($getAgent)==0) {
					
						// create
						$query = "insert into nia_players ( cn, team, update_time, lv_guardian)
							values ( '$cn', '$team', now(), 1)";
						if( !mysql_query($query) )
								errorMSG($query);
						else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_players add new agent: $cn, team: $team");
					}
					// fix no team issue
					else if($getAgent[0]['team']!=$team) {

						$newTeam = $team;
						// get agent logs, calculate the most one
						$query = "select team
							from nia_logs
							where cn = '$cn'
							order by id DESC
							limit 0, 5";
						$getAgentLogs = getQueryFields($query);
						if(count($getAgentLogs) > 0) {
							$teams = [];
							foreach($getAgentLogs as $iii => $vvv) {
								++$teams[ $vvv['team'] ];
							}
							$newTeam = $teams[1] > $teams[2] ? 1 : 2; 
						}
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] agent.team: $cn, teams[1]: ".$teams[1].", teams[2]: ".$teams[2]);
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] agent.team: $cn, newTeam: $newTeam, oldTeam: ".$getAgent[0]['team']);
						if($getAgent[0]['team'] != $newTeam) {
							// store a record
							if(!file_exists($CONFIG['data']['root'])) mkdir($CONFIG['data']['agentChangeTeam']['path']);
							$str_date = date("Y-m-d H:i:s", time());
							$filepath = $CONFIG['data']['agentTeamChangedFile'];
							$fp = fopen($filepath, "a+");
							fprintf( $fp, "%s\t%s\t%s\t%s\n", $str_date, $getAgent[0]['team'], $newTeam, $cn);
							fclose($fp);
	
							// create
							$query = "update nia_players
								set team = $newTeam
								where cn = '$cn';";
							if( !mysql_query($query) )
									errorMSG($query);
							else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] agent.team changed: $cn, team: $newTeam, from: ".$getAgent[0]['team']);
						}
					}
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$OBJ['result'][0]['log_id']."($i) t2: ".timerNow());

					// if portal not exists
					unset($comments);
					if($guid=="" && $type!=11 && $type!=12) {
						$str = ($v['p_latE6']/1000000).",". ($v['p_lngE6']/1000000)."\t".$v['p_title'];
						//logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] no guid: ".$str);

						// store into new portal list
						iitc_addNewPortalList($str);
							
						$comments['p']['latE6'] = $v['p_latE6'];
						$comments['p']['lngE6'] = $v['p_lngE6'];
						$comments['p']['title'] = $v['p_title'];
					}
					if($type==2 && $to_guid=="") {
						$str = ($v['to_p_latE6']/1000000).",". ($v['to_p_lngE6']/1000000)."\t".$v['to_p_title'];
						//logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] no guid2: ".$str);

						// store into new portal list
						iitc_addNewPortalList($str);

						$comments['to_p']['latE6'] = $v['to_p_latE6'];
						$comments['to_p']['lngE6'] = $v['to_p_lngE6'];
						$comments['to_p']['title'] = $v['to_p_title'];
					}
					if(count($comments)>0) $comments = json_encode($comments);
					else $comments = '';

					// create a record
					$query = "insert ignore nia_logs (log_id, timestamp, cn, team, type, guid, to_guid, mu, distance, msg, note)
						values ('$logId', '$time', '$cn', '$team', '$type', '$guid', '$to_guid', '$mu', '$distance', '$msg', '$comments')";
					if(!$DEBUG) {
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] $query");
						if( !mysql_query($query) ) {
							errorMSG($query);
						    logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] error, query: $query");
                        }
					} else logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] query: $query");
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$OBJ['result'][0]['log_id']."($i) t3: ".timerNow());
					
					// avoid repeat alert
					if($CONFIG['alert']['time']['repeatInterval']<=0) 
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['alert']['time']['repeatInterval']: ".$CONFIG['alert']['time']['repeatInterval']);
					$timestamp_start = $time - $CONFIG['alert']['time']['repeatInterval']; // 30minute 
					if(1) { // alert or not
						//no alert CF
						// send a alert when a long link (15km)
						while(1) {
							if(($type==2 || $type==6) && $distance > $CONFIG['alert']['link']) {
							
								$distance = ceil($distance/1000) . "km";
	
								// check destory log exist
								if($type==6) {
									// make sure alert has sent
										$query = "select 1
										from nia_logs
										where guid = '$guid'
										and type = 6
										and timestamp > $timestamp_start
										and log_id != '$logId'";
									$getCfLog = getQueryFields($query);
									if(count($getCfLog)) {
										logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ingore repeat distroy link log: $guid, ".$v['p_title']);
										break;  // avoid send repeat msg
									}
								}
	
								// 取得 portal 區域名
								$query = "select adm_2, adm_3, adm_4, adm_5, address
									from nia_portals_all
									where guid = '$guid'";
								$getPortalRegion = getQueryFields($query);
								$str_region = "N/A";
								if(count($getPortalRegion)) {
									if($getPortalRegion[0]['adm_2'] == "臺灣省") $str_region = $getPortalRegion[0]['adm_3'].$getPortalRegion[0]['adm_4'];
									else $str_region = $getPortalRegion[0]['adm_2'].$getPortalRegion[0]['adm_3'].$getPortalRegion[0]['adm_4'];
								}

								$str_regionTo = "N/A";
								if($to_guid) {
									// 取得 portal 區域名
									$query = "select adm_2, adm_3, adm_4, adm_5, address
										from nia_portals_all
										where guid = '$to_guid'";
									$getPortalRegion = getQueryFields($query);
									if($getPortalRegion[0]['adm_2'] == "臺灣省") $str_regionTo = $getPortalRegion[0]['adm_3'].$getPortalRegion[0]['adm_4'];
									else $str_regionTo = $getPortalRegion[0]['adm_2'].$getPortalRegion[0]['adm_3'].$getPortalRegion[0]['adm_4'];
								}
	
								if($type==2) {
									$msg = "<b>Long LINK Created</b>: ".$cn." (".iitc_toCamp2($team).")\n";
									$msg .= date("md-H:i", $time).", dis: ".$distance.", ❉: ".$linkTheta." $linkDirectEmoji \n";
									$msg .= "from <a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".($v['p_latE6']/1000000).",".($v['p_lngE6']/1000000)."&z=17\">".$v['p_title']."</a> (".$str_region.")\n";
									$msg .= "to <a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".($v['to_p_latE6']/1000000).",".($v['to_p_lngE6']/1000000)."&z=17\">".$v['to_p_title']."</a> (".$str_regionTo.")\n";
								} else {
									$msg = "<b>Destroyed</b>: " .$cn." (".iitc_toCamp2($team).")\n";
									$msg .= date("md-H:i", $time).", dis: ".$distance." (".$str_region.")\n";
									$msg .= "<a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".($v['p_latE6']/1000000).",".($v['p_lngE6']/1000000)."&z=17\">".$v['p_title']."</a>";
									$msg .= " - <a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".($v['to_p_latE6']/1000000).",".($v['to_p_lngE6']/1000000)."&z=17\">".$v['to_p_title']."</a> (".$str_regionTo.")\n";	
								}
								if($DEBUG) $msg = "(測試) ".$msg;
								logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] alert: $msg");
	
								// send to Telegram, 2.5s delay
								$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
								//$channel_id = ($DEBUG) ? $CONFIG['alert']['tg']['channel'][1] : $CONFIG['alert']['tg']['channel'][0];
								$channel_id = $CONFIG['alert']['tg']['channel'][1];
								iitc_sendTgMessage($url, $channel_id, $msg);
	
								// log to file
								//$fp_linkLog = fopen($CONFIG['alert']['linkFile'], "a+");
								//if($fp_linkLog) {
								//	$str_type = ($type==2) ? ($linkDirect." ".$linkTheta) : "Destroy";
								//	fprintf($fp_linkLog, "%s\t%s\t%s\t%s\t%20s\t%s\t%s\t%s\t%s,%s\t%s,%s\n", date("md H:i:s", $time), $str_type, $distance, iitc_toCamp2($team), $cn, $str_region, 
								//		$v['p_title'], $v['to_p_title'], $v['p_latE6']/1000000, $v['p_lngE6']/1000000, $v['to_p_latE6']/1000000, $v['to_p_lngE6']/1000000);
								//	fclose($fp_linkLog);
								//} else logger(ERROR_MESSAGE_LEVEL, __FILE__, "[$mode] failed to open link log file: ".$CONFIG['alert']['linkFile']);
							}
							break;
						}
						// 10W MU
						if($type==3 && $mu > $CONFIG['alert']['cf']) {

							$msg = "<b>large CF</b>: ".$cn." (".iitc_toCamp2($team).")\n";
							$msg .= "MUs: ".$mu."\n";
							$msg .= "from <a href=\"".$CONFIG['ingress']['url']['intelMap']."?ll=".($v['p_latE6']/1000000).",".($v['p_lngE6']/1000000)."&z=17\">".$v['p_title']."</a>\n";
							if($DEBUG) $msg = "(測試) ".$msg;

							logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] alert: $msg");
							// send to Telegram, 2.5s delay
							$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
							//$channel_id = ($DEBUG) ? $CONFIG['alert']['tg']['channel'][1] : $CONFIG['alert']['tg']['channel'][0];
							$channel_id = $CONFIG['alert']['tg']['channel'][1];
							iitc_sendTgMessage($url, $channel_id, $msg);
						}
						//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$OBJ['result'][0]['log_id']."($i) t4: ".timerNow());
					}
				}
				//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] ".$OBJ['result'][0]['log_id']." costTime: ".timerNow());
			}
			break;	
			//======= logger & bot END ===========

			
		case 11:	//======= set portal into monitor START =============	
			logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] mode is invalid");
			break; //======= set portal into monitor END =============

		case 12:	//======= get portal list START =============	
			// 取得已註冊的清單
			$query = "select guid, type
				from nia_portals
				where type > 0
				order by guid";
			$getData = getQueryFields($query);
			//$num_getData = count($getData);
			//$list = array();
			//for($i=0;$i<$num_getData;$i++) 
			//	$list[] = $getData[$i]['guid'];
			//$data = json_encode( $list );
			$data = json_encode( $getData );
			echo $data;
			break; //======= get portal list END =============


		//======================= 成就塔機器人 =======================================
		case 101:	//======= update portal list START =============		
			// per request most contains 100 data, it costs 0.06s
			// in debug mode, load simulate data
			if($DEBUG) {
				if(!$CONFIG['data']['portals']['dataFile']) {
					logger(ERROR_MESSAGE_LEVEL, __FILE__, "[$mode] config error, \$CONFIG['data']['portals']['dataFile']: ".$CONFIG['data']['portals']['dataFile']);
					exit(0);
				}
				$data = file_get_contents($CONFIG['data']['portals']['dataFile']);
			}
			// **** get dummy data
			//$fp = fopen("./out/dump.txt", "a+");
			//fprintf($fp, "%s", $data);
			//fclose($fp);

			$list = explode("\n", $data);	
			$n = count($list);
			$timeTag = microTime_float();
			//timerNow();
			//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] $timeTag get portals: $n");
			// pre cache guid list
			$list_guids = array();
			for($i=0;$i<$n;$i++){
				$OBJ = json_decode( trim($list[$i]), true);

				// check decode 
				if( count($OBJ)<2 ) continue;
				
				array_push($list_guids, $OBJ['guid']);
			}
			$str_guid = "'".implode("','", $list_guids)."'";

			// 取得已註冊的清單
			$query = "select guid
				from nia_portals_all
				where guid in ($str_guid)";
			$getData = getQueryFields($query);
			$num_getData = count($getData);
			$TABLE_portals = array();
			for($i=0;$i<$num_getData;$i++)
				$TABLE_portals[ $getData[$i]['guid'] ] = 1;

			// load unknown portal list
			$TABLE_unknownPortals = explode("\n", file_get_contents($CONFIG['data']['path']['newPortal']));
			if(count($TABLE_unknownPortals)) {
				$newList = []; // new one
				foreach($TABLE_unknownPortals as $i => $v) {
					$tmp = explode("\t", $v);
					$tmp2 = explode(",", $tmp[0]);
					$hash = round($tmp2[0]*5, 1).",".round($tmp2[1]*5, 1)."\t".$tmp[1];
					$newList[$hash] = $v;
				}
				$TABLE_unknownPortals = $newList;
			}

			//var_dump($TABLE_unknownPortals);echo "\n\n";
			for($i=0;$i<$n;$i++){

				$OBJ = json_decode( trim($list[$i]), true);

				// check decode 
				if( count($OBJ)<2 )
					continue;
					
				// fix data
				$OBJ['title'] = confirmInput($OBJ['title']);
				$OBJ['lat'] = $OBJ['latE6']/1000000;
				$OBJ['lng'] = $OBJ['lngE6']/1000000;

				$guid = $OBJ['guid'];
				if( isset($TABLE_portals[ $OBJ['guid'] ]) ){	// 更新資料
					// 紀錄狀態改變
					// 取得 portal
					$query = "select title, latE6, lngE6, image
						from nia_portals_all
						where guid = '$guid'";
					$getPortal = getQueryFields($query);
					$newTitle = ($getPortal[0]['title']==$OBJ['title']) ? "" : $OBJ['title'];
					$newImage = ($getPortal[0]['image']==$OBJ['image']) ? "" : $OBJ['image'];
					$distance = iitc_getDistanceE6($getPortal[0]['latE6'], $getPortal[0]['lngE6'], $OBJ['latE6'], $OBJ['lngE6']);
					$newLatE6 = 0;
					$newLngE6 = 0;
					if(!$CONFIG['data']['protalGPSChangedRange']) logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config protalGPSChangedRange not exist");
					if($distance>$CONFIG['data']['protalGPSChangedRange']) { // 20m
						$newLatE6 = $OBJ['latE6'];
						$newLngE6 = $OBJ['lngE6'];
					}
					if($newTitle || $newLatE6 || $newLngE6 || $newImage) {
						$listFields = array();
						$listValues = array();
						if($newTitle) {
							$listFields[] = "title";
							$listValues[] = $newTitle;
						} 
						if($newImage) {
							$listFields[] = "image";
							$listValues[] = $newImage;
						} 
						if($newLatE6) {
							$listFields[] = "latE6";
							$listValues[] = $newLatE6;
							$listFields[] = "lngE6";
							$listValues[] = $newLngE6;
						} 
						
						$query = "insert into nia_portal_profile_logs ( guid, ".implode(", ", $listFields).")
							values ( '$guid', '".implode("', '",$listValues)."')";
						if( !mysql_query($query) )
							errorMSG($query);
						else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_portal_profile_logs updated guid: $guid. ".implode(", ", $listFields).", ".implode(", ", $listValues));
					}	
					
					// update
					$query = "update nia_portals_all
						set title = '".$OBJ['title']."', 
							latE6 = '".$OBJ['latE6']."', 
							lngE6 = '".$OBJ['lngE6']."', 
							lat = '".$OBJ['lat']."', 
							lng = '".$OBJ['lng']."', 
							time_update = now()
						where guid = '".$OBJ['guid']."'";
					if( !mysql_query($query) )
						errorMSG($query);

					// 避免 image 被清空
					if(trim($OBJ['image'])!="") {
						// update status
						$query = "update nia_portals_all
							set image = '".$OBJ['image']."'
							where guid = '$guid'";
						if( !mysql_query($query) )
							errorMSG($query);
					}
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] nia_portals_all updated guid: $guid");
				}
				else{	
					// 新增資料
					$query = "insert into nia_portals_all ( guid, title, latE6, lngE6, lat, lng, image)
						values ( '".$OBJ['guid']."', '".$OBJ['title']."', '".$OBJ['latE6']."', '".$OBJ['lngE6']."', '".$OBJ['lat']."', '".$OBJ['lng']."', '".$OBJ['image']."');";
					if( !mysql_query($query) )
						errorMSG($query);
					logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_portals_all add a record: $guid, ".$OBJ['title']);
					//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] record: ".$list[$i]);
				}

				// 新增 log
				// 確認是否有此筆資料
				$query = "select guid
					from kill_portal_log
					where guid = '$guid'";
				$getLog = getQueryFields($query);
				if(count($getLog)==0){

					$days = 20;
					$str_date = date("Y-m-d H:i:s", time() - $days*86400);
					// 新增一筆 kill_portal_log
					$query = "insert into kill_portal_log ( guid, owner, capture_time, t_captured_time, capture_time_pre, update_time, t_updated_time, is_log, days, is_new, is_unknown)
						values ( '$guid', '', '$str_date', UNIX_TIMESTAMP('$str_date'), '$str_date', '$str_date', UNIX_TIMESTAMP('$str_date'), '0', '$days', 1, 1);";
					if( !mysql_query($query) )
						errorMSG($query);
					logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] kill_portal_log add a record: $guid");
				}

				// update
				$hash = round($OBJ['lat']*5, 1).",".round($OBJ['lng']*5, 1)."\t".trim($OBJ['title']); // for clean unknownPortal list
				//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] hash: $hash, ".$TABLE_unknownPortals[$hash]);
				if(isset($TABLE_unknownPortals[$hash])) {
					logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] ".$CONFIG['data']['path']['newPortal']." clean hash: ".$TABLE_unknownPortals[$hash]);
					unset($TABLE_unknownPortals[$hash]);
				}
			}
			//logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] $timeTag costTime: ".timerNow());

			// update unknownPortals.txt
			$fp = fopen($CONFIG['data']['path']['newPortal'], "w+");
			foreach($TABLE_unknownPortals as $i => $v) {
				fprintf($fp, "%s\n", $v);
			}
			fclose($fp);
			break; //======= get portal list END =============

		case 102:  //======= (X) 自動記錄點, 把台灣切成 n map START =============	
		
			//error_reporting(0);
			
			//session_start();
			
			$p_start['lat'] = 21.812985;
			$p_start['lng'] = 120.107610;
			$p_end['lat'] = 25.314797;
			$p_end['lng'] = 122.008304;

			//$d_lat = 0.034992;
			//$d_lng = 0.078728;
			$d_lat = 0.017;
			$d_lng = 0.037;
			// 102 x 38

			//$tmp = split(",", $_REQUEST['ll']);
			$tmp = explode(",", $_REQUEST['ll']);
			$lat = $tmp[0];
			$lng = $tmp[1];
			if( $lat=="" || $lng=="" ){
				//echo $p_start['lat'].",".$p_start['lng'];
				$lat_new = $p_start['lat'];
				$lng_new = $p_start['lng'];
			}
			else{
				$lng_new = $lng + $d_lng;
				$lat_new = $lat;
				if( $lng_new>$p_end['lng'] ){
					$lng_new = $p_start['lng'];
					$lat_new = $lat + $d_lat;
				}
	 			
				if( $lat_new>$p_end['lat'] ){
					$lat_new = 0;
					$lng_new = 0;
				}
			}
			echo $lat_new.",".$lng_new;

			//echo '<input type=button value="next" onclick="javascript:location.href=\'logger.php?mode=102&lat='.$lat_new.'&lng='.$lng_new.'\';" >';
			break;

		case 103:  //======= 取得一串掃描清單 START =============	
			//***** 如果是被掃描時段, 停止輸出清單
			/*if(isAntiBot() && 0){
				$list = array();
				echo json_encode( $list );
				exit(0);
			}*/
		
			// 取得已註冊的清單
			$query = "select guid
				from kill_portal_log
				where is_log = 0
				and is_mark = 0
				order by t_updated_time
				limit 0,100";
			$getData = getQueryFields($query);
			$num_getData = count($getData);
			$list = array();
			for($i=0;$i<$num_getData;$i++)
				$list[] = $getData[$i]['guid'];

			if( $num_getData>0 )
				$str_list = "'".implode("','", $list)."'";
			else
				$str_list = "''";

			
			$query = "select 1
				from kill_portal_log
				where is_mark = 1";
			$getData = getQueryFields($query);
			if( count($getData)>30000 ){
				// update status
				$query = "update kill_portal_log
					set is_mark = 0
					where is_mark = 1";
				if( !mysql_query($query) )
					 errorMSG($query);
				logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] reset marks");
			}

			// update status
			$query = "update kill_portal_log
				set is_mark = 1
				where guid in ($str_list)";
			if( !mysql_query($query) )
				 errorMSG($query);

			$data = json_encode( $list );
			echo $data;
			break;	//===== 取得一串掃描清單 END ==========

		case 104:  //======= 更新掃描結果 START =============	
		
			$list = explode("\n", $data);
			$n = count($list);
			
			// 取得原有資訊
			$list_portals = array();
			for($i=0;$i<$n;$i++){

				$OBJ = json_decode( trim($list[$i]), true);

				// check decode 
				if( count($OBJ)<2 )
					continue;

				// 資料格式不合
				if( !isset($OBJ['guid']) )
					continue;

				$list_portals[] = $OBJ['guid'];
			}
			$str_portals = "''";
			if( count($list_portals)>0 )
				$str_portals = "'".implode("','", $list_portals)."'";
			
			// 取得已註冊的清單
			$query = "select guid, owner, capture_time, days, is_new
				from kill_portal_log
				where is_log = 0
				and guid in ($str_portals)";
			$getData = getQueryFields($query);
			$str_owner = getFieldList( $getData, "owner"); 
			$num_getData = count($getData);
			$TABLE_portals = array();			// 之前的狀態
			for($i=0;$i<$num_getData;$i++)
				$TABLE_portals[ $getData[$i]['guid'] ] = $getData[$i];

			$time_end = time();
			// 更新訊息
			for($i=0;$i<$n;$i++){

				$OBJ = json_decode( trim($list[$i]), true);

				// check decode 
				if( count($OBJ)<2 )
					continue;

				// 資料格式不合
				if( !isset($OBJ['guid']) )
					continue;
				if( !isset($OBJ['title']) )
					continue;

				// fix data
				$OBJ['title'] = confirmInput($OBJ['title']);
				$guid = confirmInput($OBJ['guid']);
				$cn = confirmInput($OBJ['owner']);
				$team = confirmInput($OBJ['team']);

				// 如果原來無此塔,  do something******
				if( !isset($TABLE_portals[$guid]) ){
					logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] guid log not avaiable: $guid");
				}

				if( $team=="E" ) 
					$team = 2;
				else if( $team=="R" ) 
					$team = 1;
				else 
					$team = 0;

				if($cn!="") {
					// 取得玩家
					$query = "select player_id, team, lv_guardian
						from nia_players
						where cn = '$cn'";
					$getUser = getQueryFields($query);
				}
				// patch team 0 condition
				if($team==0 && $cn!=""){ // **** this may be removed
					logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] get a no team record: $guid, $cn");
					
					// 取得 agent profile
					$team = $getUser[0]['team']*1;
					if($team==0)
						logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] team is null in nia_players");
				}

				//if($cn=="TaiwanBearV") logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] team status: ".$team.", get: ".$OBJ['team']);

				$is_sn_type = 0;	// 是否是官方序號狀態, 修正改名重製佔塔時間的問題
				if( preg_match("'agent_'", $v['owner_pre']) )
					$is_sn_type = 1;

				$time_start = strtotime( $TABLE_portals[$guid]['capture_time'] );
				$time_diff = $time_end - $time_start;
				$days = floor($time_diff/86400) + 2;	// 由於系統延遲, +2~4

				$is_update = 0;
				if( $TABLE_portals[$guid]['is_new']==1 ){	// 換人 但是是新紀錄 （保持天數  更換佔領者
				    $is_update = 1;
					// update status
					$query = "update kill_portal_log
						set owner = '$cn', 
							team = '$team', 
							update_time = now(), 
							t_updated_time = UNIX_TIMESTAMP(now()), 
							capture_time = '".$TABLE_portals[$guid]['capture_time']."', 
							t_captured_time = UNIX_TIMESTAMP('".$TABLE_portals[$guid]['capture_time']."'), 
							days = $days,
							is_new = 0,
							owner_pre = '".$TABLE_portals[$guid]['owner']."', 
							capture_time_pre = '".$TABLE_portals[$guid]['capture_time']."',
							days_pre = '0',
							is_mark = 0
						where guid = '$guid'
						and is_log = 0";
					if( !mysql_query($query) )
						 errorMSG($query);
				}// 確認有無換人
				else if( $TABLE_portals[$guid]['owner']==$cn ){	// update only

					// update status
					$query = "update kill_portal_log
						set update_time = now(), 
							t_updated_time = UNIX_TIMESTAMP(now()), 
							days = $days,
							is_mark = 0
						where guid = '$guid'
						and is_log = 0";
					if( !mysql_query($query) )
						 errorMSG($query);
				}
				else{	// new one
					$is_update = 1;
					
					/// xxxxxx
					// 取得玩家
					$query = "select team
						from nia_players
						where cn = '".$TABLE_portals[ $guid ]['owner']."'";
					$getPreUser = getQueryFields($query);
					
					if($is_sn_type && $team!=0 && $team==$getPreUser[0]['team']) {
						logger(DEBUG_MESSAGE_LEVEL, __FILE__, "[$mode] detect agent_xxxx: $cn, pre cn: ".$TABLE_portals[ $guid ]['owner']);

						// update status
						$query = "update kill_portal_log
							set owner = '$cn', 
								team = '$team', 
								update_time = now(), 
								t_updated_time = UNIX_TIMESTAMP(now()), 
								capture_time = capture_time_pre,
								t_captured_time = UNIX_TIMESTAMP(capture_time_pre), 
								days = days_pre,
								is_unknown = 0,
								owner_pre = '".$TABLE_portals[ $OBJ['guid'] ]['owner']."', 
								capture_time_pre = '".$TABLE_portals[ $OBJ['guid'] ]['capture_time']."', 
								days_pre = '".$TABLE_portals[ $OBJ['guid'] ]['days']."',
								is_mark = 0
							where guid = '$guid'
							and is_log = 0";
						if( !mysql_query($query) )
							errorMSG($query);
					} else {
						// update status
						$query = "update kill_portal_log
							set owner = '$cn', 
								team = '$team', 
								update_time = now(), 
								t_updated_time = UNIX_TIMESTAMP(now()), 
								capture_time = now(),
								t_captured_time = UNIX_TIMESTAMP(now()), 
								days = 0,
								is_unknown = 0,
								owner_pre = '".$TABLE_portals[ $OBJ['guid'] ]['owner']."', 
								capture_time_pre = '".$TABLE_portals[ $OBJ['guid'] ]['capture_time']."', 
								days_pre = '".$TABLE_portals[ $OBJ['guid'] ]['days']."',
								is_mark = 0
							where guid = '$guid'
							and is_log = 0";
						if( !mysql_query($query) )
							errorMSG($query);
					}
				

					$days = 0;	// 被佔領走了
                }
                // NOTE: backup capture log in file
				//if( $is_update==1 ){	// static log
				//	$str_date = date("Y-m-d H:i:s", time());
				//	if(!file_exists($CONFIG['killer']['captureLogs']['root'])) mkdir($CONFIG['killer']['captureLogs']['root']);
				//    $filepath = $CONFIG['killer']['captureLogs']['root'].date("Ymd", time()).".txt";
				//	$fp = fopen($filepath, "a+");
				//	fprintf( $fp, "%s\t%s\t%s\n", $str_date, $guid, $cn);
				//	fclose($fp);
				//}
				



				// log user
				if( $cn!="" ){
					// 取得此塔是否有記錄
					// 資料量太大
					/*$query = "select player_id
						from kill_players
						where cn =  '".$OBJ['owner']."'
						and guid = '".$OBJ['guid']."'";
					$getUser = getQueryFields($query);
					if( count($getUser)>0 ){	
						// update
						$query = "update kill_players
							set update_time = now()
							where player_id = '".$getUser[0]['player_id']."'";
						if( !mysql_query($query) )
							 errorMSG($query);
					}
					else{	
						// create
						$query = "insert into kill_players ( cn, guid, update_time)
							values ( '".$OBJ['owner']."', '".$OBJ['guid']."', now())";
						if( !mysql_query($query) )
							 errorMSG($query);
					}*/
					
					if( count($getUser)>0 ){	
						// 只針對不曉得陣營的時候才紀錄
						if($getUser[0]['team']!=$team && $getUser[0]['team']==0){
							// store a record
							if(!file_exists($CONFIG['data']['root'])) mkdir($CONFIG['data']['agentChangeTeam']['path']);
							$str_date = date("Y-m-d H:i:s", time());
							$filepath = $CONFIG['data']['agentTeamChangedFile'];
							$fp = fopen($filepath, "a+");
							fprintf( $fp, "%s\t%s\t%s\t%s\n", $str_date, $getUser[0]['team'], $team, $cn);
							fclose($fp);

							// update
							$query = "update nia_players
								set update_time = now(),
									team = '$team'
								where player_id = '".$getUser[0]['player_id']."'";
							if( !mysql_query($query) )
								errorMSG($query);
							else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] agent.team changed, owner: $cn, team: $team, from: ".$getUser[0]['team']);
						}

						// 2016/11/14 更新日期
						$guardian = iitc_days2num($days);
						if( $guardian>$getUser[0]['lv_guardian'] ){

							// update
							$query = "update nia_players
								set update_time = now(),
									lv_guardian = $guardian
								where player_id = '".$getUser[0]['player_id']."'";
							if( !mysql_query($query) )
								 errorMSG($query);

							if($guardian>3) logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] guardian changed, owner: $cn, guardian: ".$guardian.", guid: $guid");
						}
					}
					else{	// 新玩家
						$guardian = iitc_days2num($days);

						// create
						$query = "insert into nia_players ( cn, team, update_time, lv_guardian)
							values ( '$cn', '$team', now(), 1)";
						if( !mysql_query($query) )
							 errorMSG($query);
						else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_players add new agent 2: $cn");
					}
				}



				$OBJ['latE6'] = $OBJ['latE6'];
				$OBJ['lngE6'] = $OBJ['lngE6'];
				$OBJ['lat'] = $OBJ['latE6']/1000000;
				$OBJ['lng'] = $OBJ['lngE6']/1000000;
				// 紀錄狀態改變
				// 取得 portal
				$query = "select title, latE6, lngE6, image
					from nia_portals_all
					where guid = '$guid'";
				$getPortal = getQueryFields($query);
				$newTitle = ($getPortal[0]['title']==$OBJ['title']) ? "" : $OBJ['title'];
				$newImage = ($getPortal[0]['image']==$OBJ['image']) ? "" : $OBJ['image'];
				$distance = iitc_getDistanceE6($getPortal[0]['latE6'], $getPortal[0]['lngE6'], $OBJ['latE6'], $OBJ['lngE6']);
				$newLatE6 = 0;
				$newLngE6 = 0;
				if(!$CONFIG['data']['protalGPSChangedRange']) logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] config protalGPSChangedRange not exist");
				if($distance>$CONFIG['data']['protalGPSChangedRange']) { // 20m
					$newLatE6 = $OBJ['latE6'];
					$newLngE6 = $OBJ['lngE6'];
				}
				if($newTitle || $newLatE6 || $newLngE6 || $newImage) {
					$listFields = array();
					$listValues = array();
					if($newTitle) {
						$listFields[] = "title";
						$listValues[] = $newTitle;
					} 
					if($newImage) {
						$listFields[] = "image";
						$listValues[] = $newImage;
					} 
					if($newLatE6) {
						$listFields[] = "latE6";
						$listValues[] = $newLatE6;
						$listFields[] = "lngE6";
						$listValues[] = $newLngE6;
					} 
					
					$query = "insert into nia_portal_profile_logs ( guid, ".implode(", ", $listFields).")
						values ( '$guid', '".implode("', '",$listValues)."')";
					if( !mysql_query($query) )
						errorMSG($query);
					else logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_portal_profile_logs updated. ".implode(", ", $listFields).", ".implode(", ", $listValues));
				}

				// 2017/4/12 更新名稱
				//**** 是否比對之後再做更新?
				// update status
				$query = "update nia_portals_all
					set title = '".$OBJ['title']."', 
						latE6 = '".$OBJ['latE6']."', 
						lngE6 = '".$OBJ['lngE6']."', 
						lat = '".$OBJ['lat']."', 
						lng = '".$OBJ['lng']."',
						time_update = now()
					where guid = '$guid'";
				if( !mysql_query($query) )
					 errorMSG($query);

				// 避免 image 被清空
				if(trim($OBJ['image'])!="") {
					// update status
					$query = "update nia_portals_all
						set image = '".$OBJ['image']."'
						where guid = '$guid'";
					if( !mysql_query($query) )
						errorMSG($query);
				}
			}
			break;	//===== 更新掃描結果 END ==========


		case 105:  //======= 修正 有 nia_portals_all 但是 沒 kill_portal_log 的情況 START =============	
		
			// 取得已註冊的清單
			$query = "select guid
				from kill_portal_log";
			$getData = getQueryFields($query);
			$num_getData = count($getData);
			$list = array();
			for($i=0;$i<$num_getData;$i++)
				$list[] = $getData[$i]['guid'];
			if( $num_getData>0 )
				$str_list = "'".implode("','", $list)."'";
			else
				$str_list = "''";
			
			// 取得已註冊的清單
			$query = "select guid, time_update
				from nia_portals_all
				where guid not in ($str_list)
				order by time_update DESC";
			$getData = getQueryFields($query);
			$num_getData = count($getData);
			for($i=0;$i<$num_getData;$i++){
				// import
				$query = "insert into kill_portal_log ( guid, update_time, t_updated_time, capture_time, t_captured_time, days )
					values ( '".$getData[$i]['guid']."', now(), UNIX_TIMESTAMP(now()), now()-86400*2, UNIX_TIMESTAMP(now()-86400*2), 2 )";
				if( !mysql_query($query) )
					 errorMSG($query);
				logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] kill_portal_log add record, guid: ".$getData[$i]['guid']);
			}
			break;	//=====  import nia_portals_all to kill_portal_log END ==========

		case 107:  //======= 2017/4/12 自動記錄點, 把台灣切成 n map START =============
		
			if(!$CONFIG['window']['size']['lat'] || !$CONFIG['window']['size']['lng']){
				errorMsg("CONFIG['window']['size']['lat'] not exist");
				exit(0);
			}
	
			$d_lat = $CONFIG['window']['size']['lat'];	// 
			$d_lng = $CONFIG['window']['size']['lng'];	// 

			preg_match("'([^,]+?),([^,]+)'", $_REQUEST['ll'], $match);
			$lat = $match[1];
			$lng = $match[2];
			$cell_id = confirmInput($_REQUEST['cell_id']);
			if(!$cell_id && $lat && $lng){

				// 取得掃瞄點
				$query = "select cell_id
					from `nia_map_cell`
					where is_hit = 1
					and gps_y < $lat
					and gps_y > ".($lat-$d_lat)."
					and gps_x < $lng
					and gps_y > ".($lng-$d_lng)."
					limit 0,1";
				$getCell = getQueryFields($query);
				$cell_id = $getCell[0]['cell_id'];
			}

			if($cell_id){
				// update DB
				$query = "update `nia_map_cell`
					set is_search = 1
					where cell_id = $cell_id";
				if( !mysql_query($query) )
					 errorMSG($query);
				//echo "query: $query<br>";
				logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] nia_map_cell is searched, cell_id: $cell_id");
			}

			// 取得下一個點
			$query = "select gps_x, gps_y, cell_id
				from `nia_map_cell`
				where is_hit = 1
				and is_search = 0
				order by gps_y, gps_x
				limit 0,1";
			$getNext = getQueryFields($query);
			$newLat = $getNext[0]['gps_y'] + $d_lat/2;
			$newLng = $getNext[0]['gps_x'] + $d_lng/2;

			// 掃描結束時, 提示現在的總數
			if(count($getNext)==0) {
				// 顯示現在的總數量
				// 取得 portal list
				$query = "select count(*) as n
					from nia_portals_all";
				$getData = getQueryFields($query);
				// 取得 portal list
				$query = "select count(*) as n
					from kill_portal_log";
				$getData2 = getQueryFields($query);
				logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] the END. current portals: ".$getData[0]['n'].", logs: ".$getData2[0]['n']);
			}
			else echo $newLat.",".$newLng."&cell_id=".$getNext[0]['cell_id'];
			break;

		case 108:  //======= 2019/2/12 自動掃描 unknown list START =============
		
			// load unknown portal list
			$unknownPortals = explode("\n", file_get_contents($CONFIG['data']['path']['newPortal']));
			if(count($unknownPortals)) {
				$list = []; // new list
				$gps = "";
				$focus_record = "";
				// 需要避免... portal 改名導致查詢不到的問題
				foreach($unknownPortals as $i => $v) {
					//echo "$i: $v<br>";
					$v = trim($v);
					if(strlen($v)<5) continue;
					$tmp = explode("\t", $v);
					
					if($gps=="" && $tmp[0]!=$_REQUEST['ll']) {
						$focus_record = $v;
						$gps = $tmp[0];
					} else $list[] = $v; // push to new list
				}
				// let this record move to end
				if($focus_record) {
					$list[] = $focus_record;
					$list[] = "";
					$fp = fopen($CONFIG['data']['path']['newPortal'], "w+");
					fprintf($fp, "%s", implode("\n", $list));
					fclose($fp);
					
					logger(INFO_MESSAGE_LEVEL, __FILE__, "[$mode] search: $focus_record");
				}
				echo $gps;
			}
			break;

		case 111:  // 時間管控，取得時間設定資料
			// relative time
			$query = "select *
				from data_time_config
				order by priority";
			$getSettings = getQueryFields($query);
			$num_getSettings = count($getSettings);

			$str = json_encode($getSettings, true);

			echo $str;
			break;

		// ===== 其他功能 =====
		case 999:  // 紀錄 iitc console 訊息
			$level = confirmInput(trim($_REQUEST['level'])); // ref config.php  MESSAGE_LEVEL
			if($level==""){
				logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] level is invalid");
				exit(0);
			}
			$msg = confirmInput(trim($_REQUEST['msg']));
			if($msg==""){
				logger(WARN_MESSAGE_LEVEL, __FILE__, "[$mode] msg is invalid");
				exit(0);
			}

			if(preg_match("'out of retryCount: ([\s\S]+)$'", $msg, $match)) {
				$guid = confirmInput($match[1]);
				// 取得 portal name
				$query = "select title
					from nia_portals_all
					where guid = '$guid'";
				$getData = getQueryFields($query);
				$msg .= " ".$getData[0]['title'];
			}
			logger($level, __FILE__, "[$mode] msg: $msg");
			break;

		default:
			echo "undefine mode: $mode";
			break;

	}


//echo timerNow();


//GET https://api.telegram.org/bot12345:AAJqs_w4/sendMessage?chat_id=-1001033293696&text=Hello
function curlRequest( $url, $post)
{
	global $_SERVER;
	global $_COOKIE;

	$ch = curl_init();
	if( count($post)==0 ){
		$options = array(
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER=>0,
			CURLOPT_VERBOSE=>0
		);
	}
	else{
		$options = array(
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_HEADER=>0,
			CURLOPT_VERBOSE=>0,
			CURLOPT_RETURNTRANSFER=>true,
			CURLOPT_POST=>true,
			CURLOPT_POSTFIELDS=>http_build_query($post)
		);
	}

	$options[CURLOPT_URL] = $url;
	curl_setopt_array($ch, $options);
	// CURLOPT_RETURNTRANSFER=true 會傳回網頁回應,
	// false 時只回傳成功與否
	$result = curl_exec($ch); 
	curl_close($ch);
	
	return $result;
}
?>
