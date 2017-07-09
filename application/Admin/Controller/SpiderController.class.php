<?php
/**
 * 抓取百度汉语古诗词接口
 */

namespace Admin\Controller;
use Common\Controller\AdminbaseController;

class SpiderController extends AdminbaseController{

	public function _initialize() {
		parent::_initialize();
	}
	
	
	//抓取百度诗词节日篇
	public function index(){
		$shareMdl = M('share');
		header('Content-type:application/json;charset=utf-8');
		//header('Content-type:text/html;charset=utf-8');
		$seasons = array('写雨','写雪');
		foreach($seasons as $id=>$season){
			$url = 'http://hanyu.baidu.com/hanyu/ajax/search_list?category=景色&about='.$season.'&dynasty=%E5%85%A8%E9%83%A8&ptype=poem_tag&wd=&text=&act=a_click_change_filter&pn=';
			$json = requestGet($url);
			$arr = json_decode($json,true);
			//print_r($arr);
			//exit();
			$total_page = $arr['extra']['total-page'];
			$ret_array = $arr['ret_array'];
			if(!empty($ret_array)){
				foreach($ret_array as $v){
					$insertData = [];
					$insertData['content'] = $v['body'][0];
					$insertData['author'] = $v['literature_author'][0];
					$insertData['weather_id'] = $id+1;
					$shareMdl->add($insertData);
				}
			}
			if($total_page >= 2){
				for($i = 2; $i <= $total_page;$i++){
					$json = requestGet($url.$i);
					$ret_array = json_decode($json,true)['ret_array'];
					if(!empty($ret_array)){
						foreach($ret_array as $v){
							$insertData = [];
							$insertData['content'] = $v['body'][0];
							$insertData['author'] = $v['literature_author'][0];
							$insertData['weather_id'] = $id+1;
							$shareMdl->add($insertData);
						}
					}
				}
			}
		}
	}
	
	//
	
}