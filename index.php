<?php
	error_reporting(0);
	
	//PHP程序错误跟踪 by HuangFeng(20160218)
	function _showmyerror($errno, $errstr, $errfile, $errline)
	{
		echo '<div style="border:1px solid #990000;padding-left:20px;margin:0 0 10px 0;">';
		echo '<h4>A PHP Error was encountered</h4>';
		echo '<p>Severity: ' . $errno . '</p>';
		echo '<p>Message:  ' . $errstr . '</p>';
		echo '<p>Filename: ' . $errfile . '</p>';
		echo '<p>Line Number: ' . $errline . '</p>';
		echo '</div>';
	}
	
	if(isset($_GET['debug']))
	{
		$debug = $_GET['debug'];
		echo '[' . debug . '] <br/>';
		//set error handler
		set_error_handler("_showmyerror");
	}
	
	//session_start();
	header("Content-type:text/html;charset=utf-8");
    //ob_start();
    include './helper/helper.php';
    include_once './config/init.php';
	include_once './class/Front.php';
	include_once './class/Domain.php';
	include_once './class/User.php';
	include_once './class/UserYumi.php';
	include_once './class/Portfolio.php';
	
	include_once './class/AdSource.php';
	include_once './class/BaiduPage.php';
	include_once './class/SougouPage.php';
	
	//字符串安全性过滤
	foreach ($_GET as $key => $value){
		$_GET[$key] = Helper::fileterQueryString($value);
	}
	
	$front  = new Front();
	$googleid = null;
	$isblock = $isadult = $isneedsreview = $isfaillisted = '';
	
	//兼容caf框架 by devin.li@20130313
	if(!empty($_GET['query'])){
		$searchKeyword = trim($_GET['query']);
		$searchKeyword = Helper::clearSearchKeyword($searchKeyword);
		//是否来源于输入框搜索 from google定义
		if($_GET['search'] == '1'){
			$_POST['k'] = $searchKeyword;
			$_GET['k'] = NULL;
			unset($_GET['k']);
		}else{
			$_GET['k'] = $searchKeyword;
			$_POST['k'] = NULL;
			unset($_POST['k']);
		}
	}
	
	//输入搜索
    //判断是否输入框搜索是参数is
    //by panqing 2012-09-10
	if(!empty($_GET['is']) && strtolower($_GET['is']) == 'true'){
		$_POST['k'] = Helper::clearSearchKeyword($_GET['k']);
		$_GET['k'] = NULL;
		unset($_GET['k']);
	}
	//过滤
	if(!empty($_GET['k'])){
		$_GET['k'] = Helper::clearSearchKeyword($_GET['k']);
	}
	
	if(!empty($_POST['k'])){
		$_POST['k'] = Helper::clearSearchKeyword($_POST['k']);
	}
	
	$isNoscript = 0;
	$urlarr = null;
	if(isset($_REQUEST['q']) ){
		$urlarr = Helper::self_unserialize( Helper::encrypt( trim( $_REQUEST['q'] ) ,'DECODE') );
		if(is_array($urlarr)){
			$domain  = $urlarr['NE'];
			if($urlarr['isFromHtml'] == 1){
				$domain  = Helper::getarr('dm');
				//兼容国外的不带框架的页面 by devin.li@20120313
				if(empty($domain)){
					$domain = $_SERVER['HTTP_HOST'];
				}
				$configarr['DL'] = $urlarr['DL'] = null;
				unset($configarr['DL']);
			}
			$acc     = $urlarr['acc'];
			$pcc     = $urlarr['pcc'];
	
			$referer = $urlarr['RE'];
			$b       = $urlarr['B'];
			$counid  = $urlarr['CID'];
			$adcourceid = $urlarr['SC'];
			$googleid = $urlarr['GID'];
			$isblock = $urlarr['isblock'];
			$isadult = $urlarr['isadult'];
			$isneedsreview = $urlarr['isneedsreview'];
			$isfaillisted = $urlarr['isfaillisted'];
			$session = $urlarr['session'];
			$isNoscript = $urlarr['isNoscript'];
		}
	}
	if(!is_array($urlarr)){
		$domain  = Helper::getarr('dm');
		//兼容国外的不带框架的页面 by devin.li@20120313
		if(empty($domain)){
			$domain = $_SERVER['HTTP_HOST'];
		}
		
		//接收nginx配置过来的ACC
		if(empty($_GET['acc']) && !empty($_SERVER['URL_ACC'])){
			$acc = $_GET['acc'] = trim($_SERVER['URL_ACC']);
		}
		
		$acc     = Helper::getarr('acc');
		$pcc     = Helper::getarr('pcc');
		
		//特殊用户，2个账号用同一IP by devin.li@20140221 from yanjue
		if($acc == '239046ce-2563-49e9-487e-9ceddd33d59b'){
			include "./class/MemberOnlinenic.php";
			$MemberOnlinenic = new MemberOnlinenic();
			if($MemberOnlinenic -> isInSepcialDomainList($domain)){
				$acc = $_GET['acc'] = $_SERVER['URL_ACC'] = 'b6b2bd09-9d45-f86c-10b6-d7a0d2a4e18f';
			}
		}
		
		if(	(Helper::GetWebConfig('HasFrame') == 'Yes' && !empty($_GET['ref']))
		 || $_GET['framerequest'] == 1
		 || $_GET['error_page'] == 1
		 ){
    		$referer = Helper::getarr('ref');
    	}else{
    		$referer = $_SERVER['HTTP_REFERER'];
    	}

		//外接参数完善 by devin.li@20120206
		$session = Helper::getarr('session');
		//session token验证
		if($session == 'undefined'){
			$session = '';
		}
		if((empty($session) || mb_strlen($session,'utf-8') < 40)
			&& Helper::GetWebConfig('HasFrame') != 'No'
		){
			$dir = Helper::GetWebConfig('UserBehaviorlog').'EmptySession/';
			Helper::mkdirs($dir);
			$file = $dir.date('Y-m-d').".txt";
			$content = "Session:$session";
			$content .= " Date:".date('Y-m-d H:i:s');
			$content .= " Url:http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."\r\n";
			//file_put_contents($file,$content,FILE_APPEND | LOCK_EX);
		}
		$session = Helper::getarr('session');
		
		$isblock = Helper::getarr('isblock') == 'true' ? 1 : 0;
		$isadult = Helper::getarr('isadult') == 'true' ? 1 : 0;
		$isneedsreview = Helper::getarr('isneedsreview') == 'true' ? 1 : 0;
		$isfaillisted = Helper::getarr('isfaillisted') == 'true' ? 1 : 0;
		if($_GET['noscript'] == '1'){
			$isNoscript = 1;
		}
	}
	
    if($_SERVER['SERVER_PORT'] == 443){
        header('HTTP/1.1 301 Moved Permanently');//发出301头部
        header('Location: http://651.dopa.com');//跳转到你希望的地址格式   
        exit;
    }
    if(in_array($domain,array('jlbsc.cuidu345.com','yhcht.ypsc345.com'))){
        header('HTTP/1.1 301 Moved Permanently');//发出301头部
        header('Location: http://651.dopa.com');//跳转到你希望的地址格式   
        exit;
    }
    if(Helper::Check($domain)){
        $domain = Helper::GetWebConfig('DefaultDomain');//跳转到默认页
        header('HTTP/1.1 301 Moved Permanently');//发出301头部
        header('Location: http://app.yunji.com');//跳转到你希望的地址格式   
        exit;
    }
    
    $dopaV2SwitchTime = Helper::getDopaV2SwitchTime();
	//上线切换 by devin.li@20130627
	$configini = null;
	
	//if(time() >= $dopaV2SwitchTime){
		$key = 'domain_yumi_config_'.md5($domain.'_'.$acc.'_'.$pcc);
		$configarr = null;
		if($memcache){
			$configarr = $memcache -> get($key);
			$configarr = Helper::self_unserialize($configarr);
		}
		if(!is_array($configarr) || count($configarr) <= 0){
			$configini = new DomainYumi($domain,$acc,$pcc);
			$configarr = $configini->getDomainConfig();
    		$configarr = $configarr['data'];
			$configarr['alldomain'] = $configini->getAllDomainName();
			if($memcache && is_array($configarr) && count($configarr) > 0){
				$arr = Helper::self_serialize($configarr);
				$memcache -> set($key,$arr , 0, 3600);
			}
		}
	//}
	
    //检测作弊域名 此处逻辑无用，取消 by devin.li@20120330
    //Helper::cheatDomain($configini->punyCode);

    if(empty($configarr) && !isset($configarr['NO']) && !isset($configarr['NE'])){//当配置文件为空时从$_GET获取域名
		$configini = new DomainYumi($domain,$acc,$pcc);
    	$configarr['NO'] = $configini->domain;
    	$configarr['NE'] = Helper::GetPunyCode($configini->domain);
		$configarr['alldomain'] = $configini->getAllDomainName();
    }
	
    //完善参数
    $configarr['isblock'] = $isblock;
    $configarr['isadult'] = $isadult;
    $configarr['isneedsreview'] = $isneedsreview;
    $configarr['isfaillisted'] = $isfaillisted;
    $configarr['session'] = $session;
    $configarr['isNoscript'] = $isNoscript;
    //完整路径
    $configarr['url'] = "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
    
    
    if($configarr['isadult'] == 1){
    	//确定是成人域名，需要记录，并同步到DB
    	AdultDomain::addDomain($domain);
    	$configarr['isAdult'] = 1;
    }
    
    $configarr['referer'] = $referer;
    if(isset( $urlarr['TID']) ){
		//接收切换到搜狗的流量 from xie @20140107
		if($_GET['partner'] == 'sogou'){
			$urlarr['partnerLists'] = array(13,27);
			$urlarr['adSourceName'] = 's_sogou1';
			$config['adsourceid'] = 13;
		}
        $configarr['DL']  = $urlarr['DL'];
        $configarr['TID'] = $urlarr['TID'];
        $configarr['b']   = $b;
        $configarr['CountryId'] = $counid;
        $configarr['adsourceid'] = $adcourceid;
        $configarr['adSourceName'] = $urlarr['adSourceName'];
		$configarr['partnerLists'] = $urlarr['partnerLists'];
		$configarr['searchCount'] = $urlarr['searchCount'];
		$configarr['historyKeywords'] = $urlarr['historyKeywords'];
		
		$configarr['referrerSearchKeyword'] = $urlarr['referrerSearchKeyword'];
		$configarr['referrerHost'] = $urlarr['referrerHost'];
		$configarr['referrerKeywordDomain'] = $urlarr['referrerKeywordDomain'];
		$configarr['referrerKeywordHost'] = $urlarr['referrerKeywordHost'];
		$configarr['adsAssemblePushKey'] = $urlarr['adsAssemblePushKey'];
		$configarr['CountryCode'] = $urlarr['CountryCode'];
		$configarr['landerAssemblePushKey'] = $urlarr['LAPK'];
		$configarr['isSmartLanderPush'] = $urlarr['isSmartLanderPush'];
    }
	
    $configarr['acc'] = isset($acc) ? $acc : '';//当域名以用户转发时第二次获取域名配置时使用
	$configarr['pcc'] = isset($pcc) ? $pcc : '';
	$configarr['GID'] = $googleid != '' ? $googleid : $configarr['GID'];
  	
    //获取当前语言   下拉菜单提交
    if(empty($_POST['ddlLanguages'])){
       $ipinfo = Helper::getlanguage($configarr['DL']);//如果配置中为空则根据IP获取
       if(is_array($ipinfo)){
           $configarr['DL']        = $ipinfo['LanguageId'];
           $configarr['CountryId'] = $ipinfo['Id'];
		   $configarr['CountryCode'] = $ipinfo['Postfix'];
       }else{
           if(empty( $configarr['CountryId'] ) && is_array($countryinfo = Helper::setlanguage())){
               $configarr['CountryId'] = $countryinfo['Id'];
           }
       }
       
    }else{
       $configarr['DL'] = $_POST['ddlLanguages'];
    }
	$configarr['CountryId'] = !empty($configarr['CountryId']) ? $configarr['CountryId'] : Helper::GetWebConfig('ChinaCountryId');
	$configarr['CountryCode'] = !empty($configarr['CountryCode']) ? $configarr['CountryCode'] : 'CN';
    $configarr['isInChina'] = $configarr['CountryId'] == Helper::GetWebConfig('ChinaCountryId') ? 1 : 2; //是否在中国
	//域名组语言设置优化
    $configarr = Helper::optimizeConfigForLanguage($configarr);
	
	//国内框架转发的手机流量，跳出框架(为了更好的适配百度广告)
	if(Helper::GetWebConfig('HasFrame') == 'Yes' 
		&& Helper::is_mobile() == true //手机端
		&& $configarr['isInChina'] == 1 //在中国
		&& empty($_REQUEST['q']) //无搜索行为
		&& $_REQUEST['poprequest'] != 1 //还没有跳出框架
		&& $_REQUEST['noscript'] != 1 //非nojs流量
		&& empty($_REQUEST['partner'])&& strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false //没切换广告源
	){
		//适配单独访问sp3.yousee.com的情况 by devin.li@20150505
		//$url = $_SERVER['REQUEST_URI'].'&poprequest=1';
		$url = strpos($_SERVER['REQUEST_URI'],'?') === false ? $_SERVER['REQUEST_URI'].'?poprequest=1' : $_SERVER['REQUEST_URI'].'&poprequest=1';
		
		//$noscript = $_SERVER['REQUEST_URI'].'&noscript=1';
		$noscript = strpos($_SERVER['REQUEST_URI'],'?') === false ? $_SERVER['REQUEST_URI'].'?noscript=1' : $_SERVER['REQUEST_URI'].'&noscript=1';
		$content = '<!DOCTYPE html PUBLIC"-//W3C//DTD XHTML 1.0 Transitional//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"><html><head><meta http-equiv="content-type"content="text/html;charset=utf-8"/><title></title><script type="text/javascript">try{window.top.location.href = "'.$url.'";}catch(Error){}</script><body><noscript><meta http-equiv="refresh" content="0;url='.$noscript.'"></noscript><noframes><meta http-equiv="refresh" content="0;url='.$noscript.'"></noframes></body></html>';
		echo $content;exit;
	}
	
    //屏蔽检测 by pan@2012-07-18
    if(isset($b)){
		if(AdultDomain::isInList($domain)){
			$configarr['isAdult'] = 1;
		}
    }
    
    //设置要渲染的页面  
    if(isset( $_REQUEST['p']) && in_array($_REQUEST['p'],array('template','search'))) $front->page = $_REQUEST['p'];
    
	//处理优化关键字（区别点击搜索、输入搜索和流量及其权重）
	$curlang = $configarr['languageId'] = Helper::getLanguageByClientIp();
	//是否允许关键词优化
	$allowOptimizeKey = $configarr['KOA'];
	if( isset( $configarr['UKEY'][$curlang] ) ){
	    $UserKey = $configarr['UKEY'][$curlang];
	}else if($allowOptimizeKey == 'true'){ //域名设置了关键词自动优化
	    $UserKey = $configarr['KEY'][$curlang];
	}else{
	    $UserKey = '';
	}
	
	//屏蔽关键词校正 by devin.li@20120725
	if($configarr['b']){
		$UserKey = $configarr['KEY'][$curlang];
	}

	//建议搜索关键词  by devin.li@20120511
	if($curlang == 1 || $curlang == 2){
		$language = $curlang;
	}
	
	$opt_keys = null;
	if(is_array($configarr['KEY_NEW'][$curlang])){
		$rand_keys = Helper::pro_rand($configarr['KEY_NEW'][$curlang],15);
		$stroptkeys = implode(',',$rand_keys);
	}else{
		$stroptkeys = $configarr['KEY'][$curlang];
	}
	$front->dispatcher( $stroptkeys , $configarr['BKEY'][$curlang] , $curlang ,$opt_keys,$configarr);
	$temp = $front->afdkw($configarr['UKEY'][$curlang],$configarr['KEY'][$curlang],$configarr['BKEY'][$curlang] , $curlang ,$configarr);
	
	//根据配置数据渲染模板文件
	$content = $front->rander($configarr,$temp,$opt_keys);
	if($configarr['NE'] == 'openv.tv'){
		$content = str_replace('<div class="top_bg"><div class="container  cpmline"><div style="float:left;"class="logo"id="unit_dm"><h1>便民导航</h1></div><div style="float:right;font-size:14px;width:600px;padding-top: 20px;line-height: 20px;text-align: right;"><script type="text/javascript">try{if(req.buy){if(req.cusbuy.length>0){document.write(req.cusbuy)}else if(req.contactinfo.length>0){document.write(req.contactinfo)}}}catch(e){}</script></div></div></div>','',$content);
	}
	echo $content;
	
	$userid = $front->userid.'000';
	if($front -> yumiUserId > 0){
		$userid = $front->yumiUserId.'000';
	}
	echo '<span style="display:none;">' . $userid . ':' . date("Y-m-d H:i:s",$_SERVER["REQUEST_TIME"]) . '</span>';
	
	