<?php
/////////////////////////////////////////////////////////////////////////////
//
// SpeedPHP - 快速的中文PHP框架
//
// Copyright (c) 2008 - 2009 SpeedPHP.com All rights reserved.
//
// 许可协议请查看 http://www.speedphp.com/
//
/////////////////////////////////////////////////////////////////////////////

/**
 * 将pwinput注册到smarty模板中使用
 * 请注意spAcl文件并非自动加载，所以在smarty中使用pwinput前，需要在控制器中用import("spAcl.php");来载入文件
 *
 * 在smarty中可以用：<{pwinput id=mypw add="class=pwform name=mypwname"}>来生成该输入框
 */
spAddViewFunction('pwinput', array("spAcl", "smarty_pwinput"));


/**
 * 基于组的用户权限判断机制
 * 要使用该权限控制程序，需要在应用程序配置中做以下配置：
 * 有限控制的情况，在配置中使用	'launch' => array( 'router_prefilter' => array( array('spAcl','mincheck'), ), )
 * 强制控制的情况，在配置中使用	'launch' => array( 'router_prefilter' => array( array('spAcl','maxcheck'), ), )
 */
class spAcl
{
	/**
	 * 默认权限检查的处理程序设置，可以是函数名或是数组（array(类名,方法)的形式）
	 */
	public $checker = array('spAclModel','check');
	
	/**
	 * 默认提示无权限提示，可以是函数名或是数组（array(类名,方法)的形式）
	 */
	public $prompt = array('spAcl','def_prompt');
	/**
	 * 构造函数，设置权限检查程序与提示程序
	 */
	public function __construct()
	{	
		$params = spExt("spAcl");
		if( !empty($params["prompt"]) )$this->prompt = $params["prompt"];
		if( !empty($params["checker"]) )$this->checker = $params["checker"];
	}

	/**
	 * 获取当前会话的用户标识
	 */
	public function get()
	{
		return $_SESSION["SpAclSession"];
	}

	/**
	 * 强制控制的检查程序，适用于后台。无权限控制的页面均不能进入
	 */
	public function maxcheck()
	{
		$acl_handle = $this->check();
		if( 1 !== $acl_handle ){
			$this->prompt();
			return FALSE;
		}
		return TRUE;
	}

	/**
	 * 有限的权限控制，适用于前台。仅在权限表声明禁止的页面起作用，其他无声明页面均可进入
	 */
	public function mincheck()
	{
		$acl_handle = $this->check();
		if( 0 === $acl_handle ){
			$this->prompt();
			return FALSE;
		}
		return TRUE;
	}
	
	/**
	 * 使用程序调度器进行检查等处理
	 */
	private function check()
	{
		GLOBAL $__controller, $__action;
		$checker = $this->checker; $name = $this->get();

		if( is_array($checker) ){
			return spClass($checker[0])->{$checker[1]}($name, $__controller, $__action);
		}else{
			return call_user_func_array($checker, array($name, $__controller, $__action));
		}
	}
	/**
	 * 无权限提示跳转
	 */
	public function prompt()
	{
		$prompt = $this->prompt;
		if( is_array($prompt) ){
			return spClass($prompt[0])->{$prompt[1]}();
		}else{
			return call_user_func_array($prompt,array());
		}
	}
	
	/**
	 * 默认的无权限提示跳转
	 */
	public function def_prompt()
	{
		$url = spUrl(); // 跳转到首页，在强制权限的情况下，请将该页面设置成可以进入。
		echo "<html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\"><script>function sptips(){alert(\"Access Failed!\");location.href=\"{$url}\";}</script></head><body onload=\"sptips()\"></body></html>";
		exit;
	}

	/**
	 * 设置当前用户，内部使用SESSION记录
	 * 
	 * @param acl_name    用户标识：可以是组名或用户名
	 */
	public function set($acl_name)
	{
		$_SESSION["SpAclSession"] = $acl_name;
	}
	
	/**
	 * 获取安全加密的密码输入框，开发者将需要在HTML中form标签上加入<code>onsubmit="aclcode();"</code>来触发加密
	 *
	 * @param id    在input框的id值。
	 * @param add    在input框内的其他内容，除id外，name，class等均可。
	 */
	public function pwinput($id, $add = null)
	{
		$raphash = substr(md5(mt_rand(10000,99999)),2,12);
		$html = "<script type='text/javascript'>".spAcl::getmd5()."</script>";
		$html .= "<script type='text/javascript'>function aclcode(){aclpwinput=document.getElementById('{$id}');document.getElementById('{$raphash}').value = hex_md5(aclpwinput.value);aclpwinput.value = '0000000000000000';}</script>";
		$html .= "<input type='password' id='{$id}' {$add}>";
		$html .= "<input type='hidden' id='{$raphash}' name='{$raphash}'>";
		$_SESSION["SpAclInputHash"] = $raphash;
		return $html;		
	}
	/**
	 * 辅助pwinput的函数，让pwinput可在模板中使用。
	 * @param params 传入的参数
	 */
	public function smarty_pwinput($params){
		return spAcl::pwinput($params["id"],$params["add"]);
	}
	/**
	 * 获取加密后的密码，该密码为MD5加密后的字符串
	 *
	 * 请注意返回值：
	 *
	 * -1 是无hash值，可以判断为远程提交等方式的攻击或是访问超时。需要重新访问登录页面。
	 * false 是没有输入密码，或是远程提交导致无法获取到正确的hash码。同样要求重新访问登录页面以再次输入密码提交。
	 * MD5编码后的密码
	 */
	public function pwvalue()
	{
		if(empty($_SESSION["SpAclInputHash"]))return -1;
		$md5pw = spClass("spArgs")->get($_SESSION["SpAclInputHash"],false);
		unset($_SESSION["SpAclInputHash"]);
		return $md5pw;
	}
	/**
	 * MD5的JS提供
	 */
	private function getmd5(){
		return <<<EOF
var hexcase=0;function hex_md5(a){return rstr2hex(rstr_md5(str2rstr_utf8(a)))}function hex_hmac_md5(a,b){return rstr2hex(rstr_hmac_md5(str2rstr_utf8(a),str2rstr_utf8(b)))}function md5_vm_test(){return hex_md5("abc").toLowerCase()=="900150983cd24fb0d6963f7d28e17f72"}function rstr_md5(a){return binl2rstr(binl_md5(rstr2binl(a),a.length*8))}function rstr_hmac_md5(c,f){var e=rstr2binl(c);if(e.length>16){e=binl_md5(e,c.length*8)}var a=Array(16),d=Array(16);for(var b=0;b<16;b++){a[b]=e[b]^909522486;d[b]=e[b]^1549556828}var g=binl_md5(a.concat(rstr2binl(f)),512+f.length*8);return binl2rstr(binl_md5(d.concat(g),512+128))}function rstr2hex(c){try{hexcase}catch(g){hexcase=0}var f=hexcase?"0123456789ABCDEF":"0123456789abcdef";var b="";var a;for(var d=0;d<c.length;d++){a=c.charCodeAt(d);b+=f.charAt((a>>>4)&15)+f.charAt(a&15)}return b}function str2rstr_utf8(c){var b="";var d=-1;var a,e;while(++d<c.length){a=c.charCodeAt(d);e=d+1<c.length?c.charCodeAt(d+1):0;if(55296<=a&&a<=56319&&56320<=e&&e<=57343){a=65536+((a&1023)<<10)+(e&1023);d++}if(a<=127){b+=String.fromCharCode(a)}else{if(a<=2047){b+=String.fromCharCode(192|((a>>>6)&31),128|(a&63))}else{if(a<=65535){b+=String.fromCharCode(224|((a>>>12)&15),128|((a>>>6)&63),128|(a&63))}else{if(a<=2097151){b+=String.fromCharCode(240|((a>>>18)&7),128|((a>>>12)&63),128|((a>>>6)&63),128|(a&63))}}}}}return b}function rstr2binl(b){var a=Array(b.length>>2);for(var c=0;c<a.length;c++){a[c]=0}for(var c=0;c<b.length*8;c+=8){a[c>>5]|=(b.charCodeAt(c/8)&255)<<(c%32)}return a}function binl2rstr(b){var a="";for(var c=0;c<b.length*32;c+=8){a+=String.fromCharCode((b[c>>5]>>>(c%32))&255)}return a}function binl_md5(p,k){p[k>>5]|=128<<((k)%32);p[(((k+64)>>>9)<<4)+14]=k;var o=1732584193;var n=-271733879;var m=-1732584194;var l=271733878;for(var g=0;g<p.length;g+=16){var j=o;var h=n;var f=m;var e=l;o=md5_ff(o,n,m,l,p[g+0],7,-680876936);l=md5_ff(l,o,n,m,p[g+1],12,-389564586);m=md5_ff(m,l,o,n,p[g+2],17,606105819);n=md5_ff(n,m,l,o,p[g+3],22,-1044525330);o=md5_ff(o,n,m,l,p[g+4],7,-176418897);l=md5_ff(l,o,n,m,p[g+5],12,1200080426);m=md5_ff(m,l,o,n,p[g+6],17,-1473231341);n=md5_ff(n,m,l,o,p[g+7],22,-45705983);o=md5_ff(o,n,m,l,p[g+8],7,1770035416);l=md5_ff(l,o,n,m,p[g+9],12,-1958414417);m=md5_ff(m,l,o,n,p[g+10],17,-42063);n=md5_ff(n,m,l,o,p[g+11],22,-1990404162);o=md5_ff(o,n,m,l,p[g+12],7,1804603682);l=md5_ff(l,o,n,m,p[g+13],12,-40341101);m=md5_ff(m,l,o,n,p[g+14],17,-1502002290);n=md5_ff(n,m,l,o,p[g+15],22,1236535329);o=md5_gg(o,n,m,l,p[g+1],5,-165796510);l=md5_gg(l,o,n,m,p[g+6],9,-1069501632);m=md5_gg(m,l,o,n,p[g+11],14,643717713);n=md5_gg(n,m,l,o,p[g+0],20,-373897302);o=md5_gg(o,n,m,l,p[g+5],5,-701558691);l=md5_gg(l,o,n,m,p[g+10],9,38016083);m=md5_gg(m,l,o,n,p[g+15],14,-660478335);n=md5_gg(n,m,l,o,p[g+4],20,-405537848);o=md5_gg(o,n,m,l,p[g+9],5,568446438);l=md5_gg(l,o,n,m,p[g+14],9,-1019803690);m=md5_gg(m,l,o,n,p[g+3],14,-187363961);n=md5_gg(n,m,l,o,p[g+8],20,1163531501);o=md5_gg(o,n,m,l,p[g+13],5,-1444681467);l=md5_gg(l,o,n,m,p[g+2],9,-51403784);m=md5_gg(m,l,o,n,p[g+7],14,1735328473);n=md5_gg(n,m,l,o,p[g+12],20,-1926607734);o=md5_hh(o,n,m,l,p[g+5],4,-378558);l=md5_hh(l,o,n,m,p[g+8],11,-2022574463);m=md5_hh(m,l,o,n,p[g+11],16,1839030562);n=md5_hh(n,m,l,o,p[g+14],23,-35309556);o=md5_hh(o,n,m,l,p[g+1],4,-1530992060);l=md5_hh(l,o,n,m,p[g+4],11,1272893353);m=md5_hh(m,l,o,n,p[g+7],16,-155497632);n=md5_hh(n,m,l,o,p[g+10],23,-1094730640);o=md5_hh(o,n,m,l,p[g+13],4,681279174);l=md5_hh(l,o,n,m,p[g+0],11,-358537222);m=md5_hh(m,l,o,n,p[g+3],16,-722521979);n=md5_hh(n,m,l,o,p[g+6],23,76029189);o=md5_hh(o,n,m,l,p[g+9],4,-640364487);l=md5_hh(l,o,n,m,p[g+12],11,-421815835);m=md5_hh(m,l,o,n,p[g+15],16,530742520);n=md5_hh(n,m,l,o,p[g+2],23,-995338651);o=md5_ii(o,n,m,l,p[g+0],6,-198630844);l=md5_ii(l,o,n,m,p[g+7],10,1126891415);m=md5_ii(m,l,o,n,p[g+14],15,-1416354905);n=md5_ii(n,m,l,o,p[g+5],21,-57434055);o=md5_ii(o,n,m,l,p[g+12],6,1700485571);l=md5_ii(l,o,n,m,p[g+3],10,-1894986606);m=md5_ii(m,l,o,n,p[g+10],15,-1051523);n=md5_ii(n,m,l,o,p[g+1],21,-2054922799);o=md5_ii(o,n,m,l,p[g+8],6,1873313359);l=md5_ii(l,o,n,m,p[g+15],10,-30611744);m=md5_ii(m,l,o,n,p[g+6],15,-1560198380);n=md5_ii(n,m,l,o,p[g+13],21,1309151649);o=md5_ii(o,n,m,l,p[g+4],6,-145523070);l=md5_ii(l,o,n,m,p[g+11],10,-1120210379);m=md5_ii(m,l,o,n,p[g+2],15,718787259);n=md5_ii(n,m,l,o,p[g+9],21,-343485551);o=safe_add(o,j);n=safe_add(n,h);m=safe_add(m,f);l=safe_add(l,e)}return Array(o,n,m,l)}function md5_cmn(h,e,d,c,g,f){return safe_add(bit_rol(safe_add(safe_add(e,h),safe_add(c,f)),g),d)}function md5_ff(g,f,k,j,e,i,h){return md5_cmn((f&k)|((~f)&j),g,f,e,i,h)}function md5_gg(g,f,k,j,e,i,h){return md5_cmn((f&j)|(k&(~j)),g,f,e,i,h)}function md5_hh(g,f,k,j,e,i,h){return md5_cmn(f^k^j,g,f,e,i,h)}function md5_ii(g,f,k,j,e,i,h){return md5_cmn(k^(f|(~j)),g,f,e,i,h)}function safe_add(a,d){var c=(a&65535)+(d&65535);var b=(a>>16)+(d>>16)+(c>>16);return(b<<16)|(c&65535)}function bit_rol(a,b){return(a<<b)|(a>>>(32-b))};
EOF;
	}
}

 define("SPANONYMOUS","SPANONYMOUS"); // 无权限设置的角色名称
 /**
 * ACL操作类，通过数据表确定用户权限
 * 表结构：
 * CREATE TABLE acl
 * (
 * 	aclid int NOT NULL AUTO_INCREMENT,
 * 	name VARCHAR(200) NOT NULL,
 * 	controller VARCHAR(50) NOT NULL,
 * 	action VARCHAR(50) NOT NULL,
 * 	acl_name VARCHAR(50) NOT NULL,
 * 	PRIMARY KEY (aclid)
 * ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci
 */
class spAclModel extends spModel
{

	public $pk = 'aclid';
	/**
	 * 表名
	 */
	public $table = 'acl';

	/**
	 * 检查对应的权限
	 *
	 * 返回1是通过检查，0是不能通过检查（控制器及动作存在但用户标识没有记录）
	 * 返回-1是无该权限控制（即该控制器及动作不存在于权限表中）
	 * 
	 * @param acl_name    用户标识：可以是组名或是用户名
	 * @param controller    控制器名称
	 * @param action    动作名称
	 */
	public function check($acl_name = SPANONYMOUS, $controller, $action)
	{
		$rows = array('controller' => $controller, 'action' => $action );
		if( $acl = $this->findAll($rows) ){
			foreach($acl as $v){
				if($v["acl_name"] == SPANONYMOUS || $v["acl_name"] == $acl_name)return 1;
			}
			return 0;
		}else{
			return -1;
		}
	}
}