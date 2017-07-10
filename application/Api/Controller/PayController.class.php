<?php
/**
 * APP客户端支付接口
 */
namespace Api\Controller;

use Think\Controller;
use Common\Controller\ApibaseController;

class PayController extends ApibaseController {
	private $pay;							//缺少这一行成员属性定义会报错
	
	public function __construct(){			//构造函数
		parent::__construct();
		Vendor('starPay.starPay');
		$this->pay = new \pay();
	}
	
	/**
	 *  微信支付接口 仅传递必需参数 非必要参数可根据实际应用添加
	 */
    public function weChat() {
		$config = array(
			'appid'=>'',					//您的公众号 appid 或者应用appid
			'appsecret'=>'',				//您的公众号 appsecret
			'type'=>'wechat',				//支付类型 wechat微信支付 alipay支付宝支付 缺省支付宝支付
			'mchid'=>'',					//您的商户id
			'mchkey'=>''					//您的商户平台密钥
		);
		$this->pay->init($config);
		if(IS_POST){
			//验证参数签名
			$weChatPayDataArr = I('post.','','trim,addslashes');
			//对键名进行排序然后拼接密钥并组装成字符串
			$sign = strtolower(trim($weChatPayDataArr['sign']));
			unset($weChatPayDataArr['sign']);
			ksort($weChatPayDataArr);
			$weChatPayDataStr = http_build_query($weChatPayDataArr).'&'.sp_get_option('api_setting')['key'];
			$signType = strtolower(sp_get_option('api_setting')['signType']);
			if($signType == 'sha256'){
				$sign_ = strtolower(hash('sha256',$weChatPayDataStr));
			} else {
				$sign_ = strtolower(md5($weChatPayDataStr));	
			}
			if($sign !== $sign_){
				exit(json_encode(array('status'=>-1,'msg'=>'签名不一致'),JSON_UNESCAPED_UNICODE));
			}
			try {
				//$order = time().mt_rand(10000,20000);
				$body = $weChatPayDataArr['body'];
				$order = $this->pay->createOrder();
				$totalFee = intval($weChatPayDataArr['totalFee']);
				$notify = sp_get_option('wechat_setting')['notify'];
				$ip = get_client_ip();
				//验证参数
				if(empty($body) || empty($order) || empty($totalFee) || empty($notify) || empty($ip)){
					exit(json_encode(array('status'=>0,'msg'=>'参数不能为空'),JSON_UNESCAPED_UNICODE));
				}
			
				$params = array(
					'body'=>$body,					//订单标题
					'out_trade_no'=>$order,			//自主订单号
					'trade_type'=>'APP',			//APP-APP支付  NATIVE-原生扫码支付
					'total_fee'=>$totalFee,			//订单金额 单位:分
					'notify_url'=>$notify,
					'spbill_create_ip'=>$ip
				);
				$order = $this->pay->unifiedOrder($params);
				//print_r($order);
				$appPayParameters = $this->pay->getAppPayParameters($order);
				$appPayParameters['status'] = 1;
				$appPayParameters['msg'] = '请求成功';
				echo json_encode($appPayParameters,JSON_UNESCAPED_UNICODE);
			} catch(Exception $e){
				print $e->getMessage();
			}
		} else {
			echo '非法请求';
		}
    }
    
	//支付宝支付接口
	public function alipay(){
		$config = array(
			'appid'=>'xxx',					//开发者应用ID	  新版接口需要
			'parterid'=>'xxx',				//支付宝合作者身份id 旧版接口需要 新版不需要
			'sellerid'=>'xxx@xxx.com',		//支付宝卖家账号
			'type'=>'alipay'				//支付类型
		);
		$this->pay->init($config);
		if(IS_POST){
			//验证参数签名
			$aliPayDataArr = I('post.','','trim,addslashes');
			//对键名进行排序然后拼接密钥并组装成字符串
			$sign = strtolower(trim($aliPayDataArr['sign']));
			unset($aliPayDataArr['sign']);
			ksort($aliPayDataArr);
			$aliPayDataStr = http_build_query($aliPayDataArr).'&'.sp_get_option('api_setting')['key'];
			$signType = strtolower(sp_get_option('api_setting')['signType']);
			if($signType == 'sha256'){
				$sign_ = strtolower(hash('sha256',$aliPayDataStr));
			} else {
				$sign_ = strtolower(md5($aliPayDataStr));	
			}
			if($sign !== $sign_){
				exit(json_encode(array('status'=>-1,'msg'=>'签名不一致'),JSON_UNESCAPED_UNICODE));
			}
			try {
				$orderId = $this->pay->createOrder();
				$params = array(
					'subject'=>'',											//商品名称					必须
					'out_trade_no'=>$orderId,								//商户订单号					必须
					'total_amount'=>'',										//订单金额					必须
					'notify_url'=>'',										//异步通知地址					必须
					'private_key_path'=>getcwd().'/rsa_private_key.pem',	//用户自主生成私钥存放路径		必须 强烈建议存放在非web目录
					
					'charset'=>'',											//请求使用的编码格式			支付宝必须 但starPay缺省设置 utf-8
					'sign_type'=>'',										//签名算法					支付宝必须 但starPay会缺省设置 RSA
					'body'=>'',												//交易描述					非必须
					'timeout_express'=>'',									//交易超时时间					非必须	例:90m
					'format'=>'',											//格式						非必须	仅支持JSON
					'seller_id'=>'',										//支付宝用户ID(合作者身份id)	非必须
					'goods_type'=>'',										//商品类型					非必须	0虚拟商品	1实物商品
					'passback_params'=>'',									//回传参数 需要urlencode发送	非必须
					'promo_params'=>'',										//优惠参数					非必须
					'extend_params'=>'',									//业务扩展参数					非必须
					'enable_pay_channels'=>'',								//可用渠道					非必须
					'disable_pay_channels'=>'',								//禁用渠道					非必须
					'store_id'=>'',											//商店门店编号					非必须
					'sys_service_provider_id'=>'',							//系统商编号					非必须
					'needBuyerRealnamed'=>'',								//是否发起实名校验 			非必须 	T:发起 F:不发起
					'TRANS_MEMO'=>''										//账务备注					非必须	例:促销
				);
				$alipayStr = $this->pay->aliAppPayParams($params);
				echo json_encode(array('status'=>1,'str'=>$alipayStr,'out_trade_no'=>$params['out_trade_no']),JSON_UNESCAPED_UNICODE);
			} catch (Exception $e){
				print $e->getMessage();	
			}
		}
	}
	
	//微信支付异步回调
	public function weChatNotify(){
		if($this->pay->notify()['checkSign'] == true){
			//file_put_contents('./testNofity.txt',serialize($this->pay->notify()['notifyData']));
			//填写商家自己的业务逻辑
			$this->pay->notifySuccess();
		} else {
			$this->pay->notifyFail();
		}
	}
	
	//支付宝异步回调
	public function alipayNotify(){
		
	}
}

