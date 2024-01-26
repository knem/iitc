<?
	require_once("module_connectDB.php");


	set_time_limit(0);
	/*
		相互檢查, 如果 偵測 Routine 沒有啟動，那就自行發布訊息



		routine.php
			發送 TG 訊息
			偵測 log 狀態，發布 bot 離線通知


	*/




	$VAR_is_detect_idle = 0;
	$file_log = "/var/www/html/iitc/tg.txt";

	// 確認檔案是否存在
	if( !file_exists($file_log) ){
		$fp = fopen( $file_log, "w+");
		fclose($fp);
		@chmod( $file_log, 0777);
	}

	// idle function
	while( 1 ){

		// if there are tg messages need to be send?
		// format: @@##level,message##@@ 
		$data = @file_get_contents( $file_log );
		if( preg_match_all("'@@##(\d+),([\s\S]+?)##@@'", $data, $match) ){
			$n = count($match[1]);
			for($i=0;$i<$n;$i++){
				
				$level = $match[1][$i];
				$msg = $match[2][$i];
				// 5 級以上是高級警戒訊息
				if( $level>=5 && 0 ){
					// send to Telegram, 2.5s delay
					$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
					// private channel
					$post = array(
						"chat_id" => $CONFIG['alert']['tg']['channel'][0],
						"text" => $msg
					);
					$result = curlRequest( $url, $post );
				}

				//if( 1 ){
				if( 0 ){
					// send to Telegram, 2.5s delay
					$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
					// private channel
					$post = array(
						"chat_id" => $CONFIG['alert']['tg']['channel'][0],
						"text" => $msg
					);
					$result = curlRequest( $url, $post );


//                    $url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
                    // private channel
//                    $post = array(
//                        "chat_id" => $CONFIG['alert']['tg']['channel'][1],
//                        "text" => $msg
//                    );
//                    $result = curlRequest( $url, $post );

				}
			}
				

			echo "message amounnt... $n\n";
		}
		if( $data!="" ){
			echo $data."\n";

			$fp = fopen( $file_log, "w+");
			fclose($fp);
			@chmod( $file_log, 0777);
			//@unlink( $file_log );
		}



		// check rebot idle
		// 取得最新一筆 log
		$query = "select time_update
			from nia_portals
			where type > 0
			order by time_update DESC
			limit 0, 1";
		$getData = getQueryFields($query);
		if( count($getData)>0 &&  0){

			$time_end = strtotime( $getData[0]['time_update'] );
			$time_diff = time() - $time_end;
			if( $time_diff>300 ){
				//echo "$time_diff<br>";

				if( $VAR_is_detect_idle==0 ){
					$VAR_is_detect_idle = 1;

					$msg = "bot idle...";
					// send to Telegram, 2.5s delay
					$url = "https://api.telegram.org/".$CONFIG['alert']['tg']['bot'][0]."/sendMessage";
					// private channel
					$post = array(
						"chat_id" => $CONFIG['alert']['tg']['channel'][1],
						"text" => $msg
					);
					$result = curlRequest( $url, $post );
				}
			}
			else
				$VAR_is_detect_idle = 0;
		}






		// 
		sleep(5);
	}









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
