<?php
/*
相對目錄的相容方法，使用絕對路徑 $_SERVER['DOCUMENT_ROOT']
*/

// ================== const START ==================
// system
// debug log level
define("ERROR_MESSAGE_LEVEL", 1);
define("WARN_MESSAGE_LEVEL", 2);
define("INFO_MESSAGE_LEVEL", 3);
define("DEBUG_MESSAGE_LEVEL", 4);

// blue8
// alert types
define("ALERT_LEVEL_NONE", 0);
define("ALERT_LEVEL_LOG_L", 1);
define("ALERT_LEVEL_LOG_H", 2);
define("ALERT_LEVEL_FARM_L", 11);
define("ALERT_LEVEL_FARM_H", 12);
define("ALERT_LEVEL_CHANGED_L", 21);
define("ALERT_LEVEL_CHANGED_H", 22);

define("MONITOR_FREQUENCY_HIGH", 1);
define("MONITOR_FREQUENCY_LOW", 3);

// chat log

// ================== const END ====================





// ================== var START ==================
// fix CLI mode no header data (DOCUMENT_ROOT is empty)
if(!$_SERVER['DOCUMENT_ROOT']){
    $pwd = getenv('PWD');
    preg_match("'^([\s\S]+?)/iitc/[\s\S]+?$'", $pwd, $match);
    //$CONFIG['filepath']['rootDirectory'] = $match[1];
    $CONFIG['filepath']['rootDirectory'] = "/var/www/html/";
} else {
    $CONFIG['filepath']['rootDirectory'] = $_SERVER['DOCUMENT_ROOT'];
}

$CONFIG['filepath']['project'] = 'iitc';

$CONFIG['root'] = $CONFIG['filepath']['rootDirectory'] . '/' . $CONFIG['filepath']['project'] . '/';

// database config
$CONFIG['database']['host'] = 'localhost';
$CONFIG['database']['database'] = 'ingress';
$CONFIG['database']['user'] = 'user';
$CONFIG['database']['password'] = 'password';

$CONFIG['export'] = $CONFIG['root'] . 'out/';

// tg 

$CONFIG['host']['url']['root'] = ""; // 
// ingress config
$CONFIG['ingress']['team']['UNKNOWN'] = 0; // (X) 陣營, 未知
$CONFIG['ingress']['team']['RES'] = 1; // (X) 陣營, RES
$CONFIG['ingress']['team']['ENL'] = 2; // (X) 陣營, ENL
$CONFIG['ingress']['url']['intelMap'] = "https://intel.ingress.com/intel"; // intel map URL
$CONFIG['ingress']['url']['googleMap'] = "https://www.google.com/maps"; // google map URL


// application 
// system
$CONFIG['debug']['level'] = DEBUG_MESSAGE_LEVEL;

// killer
$CONFIG['data']['root'] = $CONFIG['root'] . 'data/';
$CONFIG['data']['guardianFile'] = $CONFIG['data']['root'] . 'guardiansRoy.txt';
$CONFIG['data']['portalDayFile'] = $CONFIG['data']['root'] . 'portalDaysRoy.txt';

$CONFIG['data']['guardianFile'] = $CONFIG['data']['root'] . 'guardiansRoy.txt';
$CONFIG['data']['agentTeamChangedFile'] = $CONFIG['data']['root'] . "agentTeamChanged.txt";

$CONFIG['data']['protalGPSChangedRange'] = 20; // portal 會記錄的移動距離, unit: m

$CONFIG['killer']['root'] = $CONFIG['root'] . 'killer/';
$CONFIG['killer']['export']['root'] = $CONFIG['export'];
$CONFIG['killer']['export']['tmp'] = $CONFIG['export'] . 'tmp.txt';
$CONFIG['killer']['export']['map'] = $CONFIG['export'] . 'out.kml';
$CONFIG['killer']['export']['guardians'] = $CONFIG['export'] . 'guardiansRoy.txt';
$CONFIG['killer']['captureLogs']['root'] = $CONFIG['root'] . 'logs/';
$CONFIG['killer']['oldLogs'] = $CONFIG['export'] . 'oldLogs.txt';
$CONFIG['killer']['removedPortal']['time'] = 120; // unit: hours, 限制能夠移除的最新天數 n = now() - portalUpdatedTime, n <= 0: no limited

$CONFIG['rename']['highLevel'] = 2; // 改名匹配等級, 0:無, 1:可能, 2:非常可能
$CONFIG['rename']['logFile'] = $CONFIG['export'].'renameTime.txt'; // 上次測試改名時間 (一天一次)
$CONFIG['rename']['logInterval'] = 86400; // 測試改名時間 unit: 1s (一天一次)



// blue8
$CONFIG['blue8']['time']['avoidRepeatInterval'] = 300; // 允許在某個時間範圍內停發重複的訊息, unit: s
$CONFIG['blue8']['time']['eventTimeout'] = 3600; // 允許在某個時間範圍內停發重複的事件 (Ex: 薯條、已車), unit: s
$CONFIG['blue8']['time']['killLogInterval'] = 600; // 出現已車事件，發布時間範圍內的 log, unit: s
$CONFIG['blue8']['test']['dataFile'] = "./data/blue8Data.txt"; // 測試資料
$CONFIG['blue8']['path']['export'] = "../out/monitorPortals.txt"; // 匯出監控資料
$CONFIG['blue8']['path']['map'] = "../out/blue8Monitor.kml"; // 匯出監控地圖

// data
$CONFIG['data']['path']['newPortal'] = "./out/unknownPortals.txt"; // 輸出發現的新塔清單, by logger.php
$CONFIG['data']['portals']['dataFile'] = "./data/portalList.txt"; // 掃描新塔的測試資料

// log
$CONFIG['log']['test']['dataFile'] = "./data/chatLogData.txt"; // 測試資料
$CONFIG['log']['path']['export'] = "../out/user.kml"; // 匯出監控資料, 變成地圖

// import
$CONFIG['import']['root'] = $CONFIG['root'] . 'data/';
$CONFIG['import']['images'] = $CONFIG['import']['root'] . 'images/';
$CONFIG['import']['royPortals'] = $CONFIG['export'] . 'royPortals.txt';
$CONFIG['killer']['royPortals']['lack'] = $CONFIG['export'] . 'sysLackPortals.txt';
$CONFIG['killer']['royPortals']['royLack'] = $CONFIG['export'] . 'royLackPortals.txt';

// scan window 大小, 修改設定值的話, 執行 scan 功能需要重新校正 nia_map_cells
$CONFIG['window']['size']['lat'] = 0.03212;	// 單位 cell 高度, 0.03212 (z=15), 0.015858 (z=16)
$CONFIG['window']['size']['lng'] = 0.077486;	// 單位 cell 寬度, 0.077486, 0.0347
	
// 掃描範圍
$CONFIG['scanRange']['start']['lat'] = 20.247041;	// 左下角座標
$CONFIG['scanRange']['start']['lng'] = 115.608506;
$CONFIG['scanRange']['end']['lat'] = 27.772813;	// 右上角座標
$CONFIG['scanRange']['end']['lng'] = 125.480924;

// notification
$CONFIG['alert']['tg']['bot'][0] = ""; // main bot, 推送通知訊息 for blue8
$CONFIG['alert']['tg']['bot'][1] = ""; // query bot, 查詢資訊用的介面 bot
$CONFIG['alert']['tg']['channel'][0] = ""; // for blue8 notification
$CONFIG['alert']['tg']['channel'][1] = ""; // for test notification
$CONFIG['alert']['link'] = 15000; // 15km
$CONFIG['alert']['cf'] = 100000; // 10WMU
$CONFIG['alert']['time']['repeatInterval'] = 3600; // 避免重複跳訊息, unit: s
$CONFIG['alert']['linkFile'] = $CONFIG['export'] . 'linkLogs.txt';    // 連線紀錄

// 是否是特別活動時間。特別活動期間 偵測規則會比較鬆
$CONFIG['alert']['is_activity'] = 0; 


// test
// ================== var END ====================



// load custom config
if(file_exists("./myConfig.php")) require_once("./myConfig.php");
else if(file_exists("../myConfig.php")) require_once("../myConfig.php");

//var_dump($CONFIG);
?>