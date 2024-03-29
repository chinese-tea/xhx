<?php


class TaskClass{
	
	protected $app;
	protected $ch;
	protected $configArr;
	protected $disclickArr = array();
	
	function __construct($app, $configArr){
		$this->app = $app;
		$this->ch = $app->ch;
		$this->configArr = $configArr;
		
	}
	
	function run(){
		while(1){
	
			echo date('Y-m-d H:i:s')."\n";
			

			$domObj = $this->getDomObj(); 
			
			$tr = $domObj->find('tr');		
		
			$len = count($tr);
			//倒序接单，优先接花呗单			
			for($i = $len; $i>0 ; $i--){
				$v = $tr[$i-1];
				$td = $v->find('td', 7);
				$a = $td->find('a',0);
				$shopTel = $this->getTel($v);
				if($this->canClick($shopTel) && $this->orderLimit($a->sum_price, 0, 300)){
					$res = $this->checkOrder($a->offer_id);				
					if($this->disClickStatus($res->code)){
						array_push($this->disclickArr, $shopTel);
						continue;
					}
					if($res->code == 0){
						if($this->grabTask($a->offer_id)){
							echo '抢单成功';break;
						}
					}
					sleep(3);
					echo $res->code.':'.$res->msg.'   '.$shopTel."\n"; 
				}	
			}
			print_r($this->disclickArr);
			exit;
			
			
			
		
/* 			$taskList = array_slice($res->list, 0, 3);	
			$task = $this->selectOrder($taskList);	
			if($task != null){ //如果接到了任务，打印任务信息，不再继续循环
				$r = $this->grabTask($task->id, $task->not_match);
				if($r->code == '000'){
					$this->prompt('淘宝浏览单',$task);
					break;
				}		
			} */
			if($res == '操作太频繁啦!! 请休息一下再试.'){
				sleep(mt_rand(3,6)*60);
			}else {
				sleep(4);
			}
			
	
			
		}
	}
	
	
	function getDomObj(){
		$url = 'http://tao.huhuifu.com/lists/apply?p=1&t=2&a=418673&c=101';
		$res = $this->get($url);
		return str_get_html($res);
	}
	
	function orderLimit($money, $min=0, $max=300){
		return $min <= $money && $money <= $max;
	}
	
	function canClick($tel){
		return !in_array($tel, $this->disclickArr);
	}
	
	function getTel($v){
		return $v->find('td', 0)->innertext;
	}
		
	function checkOrder($offer_id){
		$url = 'http://tao.huhuifu.com/order/order_check';
		$fields = array(
			'offer_id' => $offer_id,
			'plat_id' => 1,
			'captcha' => '',
			'account_id' => 418673
		);
		$res = $this->post($url, $fields);
		return $this->decode($res);
	}
		
	function disClickStatus($code){
		//16同一买号同一商家任务30天内不能重复接手，33、38、39接单条件不符合！，17任务已接完
		$a = array(16,17,32,33,38,39);
		return in_array($code, $a);
	}

	function grabTask($offer_id){
		$url = 'http://tao.huhuifu.com/order/step/'.$offer_id.'/418673';echo $url;exit;
		$res_code = $this->get($url);echo '状态吗：'.$res_code;exit;
		return $res_code == 200;
	}

	function prompt($name,$task){
		echo $this->configArr['user_name'].'接到任务'.$name.' 佣金：'.$task->money.'   任务id：'.$task->id.'  店铺名：'.$task->name;
		exec($this->app->env['prompt']['type'][$this->app->env['prompt']['type_id']]);
	}

	function post($url, $fields=array()){

		curl_setopt($this->ch,CURLOPT_URL, $url);

		$header = array('User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36');

		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($this->ch,CURLOPT_POST,true);
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $fields);
		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($this->ch,CURLOPT_HTTPHEADER,$header);
		curl_setopt($this->ch,CURLOPT_COOKIE,$this->configArr['cookie']); 
		
		return curl_exec($this->ch);
	}
	
	function get($url,$getcode=false){		
		curl_setopt($this->ch,CURLOPT_URL, $url);

		$header = array('User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36');

		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYHOST, FALSE);

		curl_setopt($this->ch,CURLOPT_RETURNTRANSFER,true);

		curl_setopt($this->ch,CURLOPT_HTTPHEADER,$header);
		curl_setopt($this->ch,CURLOPT_COOKIE,$this->configArr['cookie']);
	curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1); 
		if($getcode){
			curl_exec($this->ch);
			$httpCode = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
			return $httpCode;
		} else {
			$res = curl_exec($this->ch);$location = curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);echo $location;
			return $res;
		}
	}
	 
	function decode($content){
		return json_decode($content);
	}
	
	
}
