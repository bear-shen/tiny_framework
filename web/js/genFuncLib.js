// ==UserScript==
// @name         genFunc debug
// @namespace    http://tampermonkey.net/
// @version      0.1
// @description  try to take over the world!
// @author       You
// @include      /^http?://.*/
// @include      /^https?://.*/
// @grant        none
// ==/UserScript==

console.info('genfunc debugger');
/**
 * 2018-11-26 主版本
 *
 * */
window.genFunc = function () {
};
/**
 * @deprecated
 * */
genFunc.zzz = function () {
};
/**
 * @inherit
 * @param parentNode {HTMLElement|String|Node}
 * @return HTMLElement
 * */
genFunc.getParentNode = function (parentNode) {
  var targetNode = document.body;
  if (parentNode && typeof parentNode == "string" && parentNode.length > 0) {
	var targetNodeTmp = document.getElementById(parentNode);
	if (targetNodeTmp) {
	  targetNode = targetNodeTmp;
	}
  }
  //输入为节点对象
  else if (parentNode && typeof parentNode == "object") {
	targetNode = parentNode;
  }
  //输入无效，写入body
  else {
	console.warn("genFunc.getParentNode -- no parentNode,add 2 body");
  }
  return targetNode;
};
/**
 * 2.0版本的添加节点
 * 和createNodeList一起重构，到时候将child方法移到list里，否则顺序很麻烦
 *
 * @param tag {"string"}
 * 输入标签类型
 *
 * @param attributes {Array}
 * 输入tag的参数，格式为
 * {
 *    "Name":"Value"
 * }
 *
 * @param parentNode {Node|Element|String}
 * parentnode可以为id或者是dom类
 * 没有充分测试一些奇葩情况……
 *
 * @param eventListener {Array}
 * eventlistener追加事件节点，格式为
 * {
 *    "event":function(){}
 *    "event":[ function(){} , function(){} ]
 * }
 * */
genFunc.createNode = function (tag, attributes, parentNode, eventListener) {
  if (!tag) {
	console.error("没有标签名");
	return;
  }
  var newNode = document.createElement(tag);
  //设置attribute
  //期望这样写可以提升点效率……
  // console.warn(attributes);
  if (attributes && (typeof attributes) == "object") {
	for (var key in attributes) {
	  if (attributes.hasOwnProperty(key)) {
		var att   = document.createAttribute(key);
		att.value = attributes[key];
		newNode.setAttributeNode(att);
	  }
	}
  }
  //添加事件
  if (eventListener && (typeof eventListener) == "object") {
	for (var eventName in eventListener) {
	  if (eventListener.hasOwnProperty(eventName)) {
		//一个事件上绑定多个函数
		if (eventListener[eventName].__proto__ === Array.prototype) {
		  for (var ia = 0; ia < eventListener[eventName].length; ia++) {
			newNode.addEventListener(
			 eventName, eventListener[eventName][ia]
			);
		  }
		}
		//单个函数
		else {
		  newNode.addEventListener(
		   eventName, eventListener[eventName]
		  );
		}
	  }
	}
  }
  // console.warn(newNode);
  //写入节点
  //输入为字符串
  genFunc.getParentNode(parentNode).appendChild(newNode);
  //返回节点
  return newNode;
};
/**
 * 2.0版本的添加节点
 *
 * nodeArray中可以写入字符串类型的数据，支持html写入
 * 但是html文本需要闭合，疑似是浏览器的锅……
 * 这么写法主要是觉得方便……
 * @param parentNode    {string|HTMLElement}
 * @param nodeArray     array
 * [
 *    "",
 *    {
 *       "tag"             :"",
 *       "attributes"      :{},
 *       "eventListener"   :{},
 *       "child"           :[],//可以用string替代，会自动判断
 *    }
 * ]
 * */
genFunc.createNodeList = function (parentNode, nodeArray) {
  //全部加上验证一遍
  if (!nodeArray) {
	console.error("没有要添加的节点");
	console.error(parentNode);
	console.error(nodeArray);
	return;
  }
  if (!Array.isArray(nodeArray)) {
	nodeArray = [nodeArray];
  }
  for (var ia = 0; ia < nodeArray.length; ia++) {
	var currentEle = nodeArray[ia];
	//console.warn(currentEle);
	if (currentEle && typeof currentEle == "object") {
	  //console.warn("tag");
	  //遍历
	  //console.warn(currentEle);
	  var target = genFunc.createNode(
	   currentEle["tag"],
	   currentEle["attributes"],
	   parentNode,
	   currentEle["eventListener"]
	  );
	  //console.warn(target);
	  if (currentEle["child"]) {
		genFunc.createNodeList(target, currentEle["child"]);
	  }
	}
	else if (currentEle && typeof currentEle == "string") {
	  //console.warn("string");
	  //console.warn(genFunc.getParentNode(parentNode));
	  parentNode = genFunc.getParentNode(parentNode);
	  parentNode.insertAdjacentHTML("beforeEnd", currentEle);
	  //parentNode.innerHTML=parentNode.innerHTML+currentEle;
	}
	else {
	}
  }
};
/**
 * 2.1 收集数据并发送ajax
 * 带了 withCredentials 跨域可发送cookie
 * @param path      {string}                    ajax文件路径
 * @param getReq    {String|Array|Object}       get请求字段
 * @param data      {String|Array|Object}       请求字段 本来想整合，但是感觉自由度太低所以放弃了
 *                                              输入""可以强制post空数据，即输入string时强制post
 *                                              输入method也可以强制post
 * @param header    {Array}                     设定header，用{k:v}
 * @param async     {Boolean|function(text,status)}             默认为false，同步加载，异步加载时，可作为callback
 * @param method    {string}                    method，不写入时自动判断为get或者post
 *
 *    */
genFunc.sendAjaxV2 = function (path, getReq, data, header, async, method) {
  console.log("========= sending ajax =========");
  ///////////////////////////////////////////////////////////////////////
  // 过滤，追加默认值
  {
	if (!path) {
	  return false;
	}
	getReq = !getReq ? {} : getReq;
	data   = !data ? {} : data;
	//
	if (!header || header.__proto__ !== Object.prototype) {
	  header = {
		// "Content-Type": "application/x-www-form-urlencoded",
		"Accept": "application/json, text/javascript, */*; q=0.01"
	  }
	}
	if (!async) {
	  async = false;
	}
	// async = !!async;
  }
  ///////////////////////////////////////////////////////////////////////
  //处理post和get,path
  {
	if (!method) {
	  var isPost      = false;
	  var requestData = data;
	  if (requestData.__proto__ === String.prototype || requestData.__proto__ === FormData.prototype) {
		//console.warn("====////====1");
		// if (requestData.__proto__ === FormData.prototype) {
		//// header["Content-Type"] = "multipart/form-data";
		// }
		isPost = true;
	  }
	  else {
		//console.warn("====////====2");
		var formData = new FormData;
		for (var k in requestData) {
		  //console.warn(ia);
		  //console.warn(data);
		  if (requestData.hasOwnProperty(k)) {
			isPost = true;
			//console.warn(ia);
			//console.warn(data[ia]);
			//console.warn(typeof data[ia]);
			formData.append(k, requestData[k]);
		  }
		}
		//console.warn(formData);
		requestData = formData;
		// header["Content-Type"] = "multipart/form-data";
		// console.warn(formData.keys());
	  }
	  method = isPost ? "post" : "get";
	}
	//get
	var getRequest = "";
	if (!getReq) {

	} else if (getReq.__proto__ === String.prototype) {
	  getRequest = getReq;
	}
	else if (getReq.__proto__ === Array.prototype) {
	  getRequest = genFunc.implode(getReq, "&");
	}
	else {
	  var getArray = [];
	  for (var ia in getReq) {
		if (getReq.hasOwnProperty(ia)) {
		  getArray.push(ia + "=" + getReq[ia]);
		}
	  }
	  getRequest = genFunc.implode(getArray, "&");
	}
	//path
	var hasQus = path.indexOf("?");
	var hasAnd = path.indexOf("&", hasQus + 1);
	if (!getRequest) {
	  if (hasQus == -1) {
		path = path + "?";
	  } else if (hasAnd != -1) {
		path = path + "&";
	  }
	  path = path + getRequest;
	}
  }
  //XMLHttp

  ///////////////////////////////////////////////////////////////////////
  {
	var xmlhttp;
	var result                 = "";
	xmlhttp                    = new XMLHttpRequest();
	xmlhttp.withCredentials    = true;
	xmlhttp.onreadystatechange = function () {
	  if (xmlhttp.readyState == 4 /*&& xmlhttp.status < 400*/) {
		//console.warn(xmlhttp.responseText);
		if (async && async.__proto__ === Function.prototype) {
		  return async(xmlhttp.responseText, xmlhttp.status);
		}
	  }
	};
	xmlhttp.open(method, path, async);
	for (var k in header) {
	  if (header.hasOwnProperty(k)) {
		xmlhttp.setRequestHeader(k, header[k]);
	  }
	}
	console.warn(requestData);
	if (method != "get") {
	  xmlhttp.send(requestData);
	}
	else {
	  xmlhttp.send();
	}
  }
  if (!async) {
	return xmlhttp.responseText;
	// return result;
  }
  else {
	return xmlhttp;
  }
};
/**
 * @param input {Object}
 * {
 * path				:'',
 * get				:{},
 * post				:{},  [{k:123,v:123}] {123:123} 'asdasdasdasd'
 * method			:'',
 * header			:{},
 * async			:true,
 * withCredentials	:true,
 * success			:()=>{},
 * error			:()=>{},
 * }
 * @return {XMLHttpRequest|String|Boolean}
 * */
genFunc.sendAjaxV3 = function (input) {
  var localConfig         = {
	'path'           : '',
	/**
	 * [{k:k1,v:v1},{k:k2,v:v2},]
	 * {k1:v1,k2:v2,} * default
	 * */
	'get'            : false,
	/**
	 * [{k:k1,v:v1},{k:k2,v:v2},]
	 * {k1:v1,k2:v2,}
	 * FormData * default
	 * */
	'post'           : false,
	/**
	 * {get|post}
	 * * */
	'method'         : 'get',
	/**
	 * ['k1:v1','k2:v2',]
	 * {k1:v1,k2:v2,} * default
	 * */
	'header'         : false,
	'async'          : true,
	'withCredentials': true,
	'success'        : null,
	'error'          : null,
  };
  localConfig             = Object.assign(localConfig, input);
  let xmlHttp             = new XMLHttpRequest();
  xmlHttp.withCredentials = localConfig.withCredentials;
  //get
  {
	//arr to default
	if (localConfig.get instanceof Array) {
	  let newData = {};
	  for (let i1 = 0; i1 < localConfig.get.length; i1++) {
		newData[localConfig.get[i1].k] = localConfig.get[i1].v;
	  }
	  localConfig.get = newData;
	}
	//clear
	let pathArr = localConfig.path.split(/#/ig);
	pathArr     = pathArr[0].split(/\?/ig);
	if (pathArr.length == 1 || pathArr[pathArr.length - 1].length == 0) {
	  localConfig.path += '?';
	}
	//merge
	let i = 0;
	for (let k in localConfig.get) {
	  if (!localConfig.get.hasOwnProperty(k)) continue;
	  if (i++ != 0) localConfig.path += '&';
	  localConfig.path += `${k}=${localConfig.get[k]}`;
	}
  }
  //post
    {
        if (!localConfig.post) {
        	// console.info('empty');
        } else if (typeof localConfig.post === 'string') {
            // console.info('string');
            localConfig.post = newData;
        } else {
            //arr to object
            if (localConfig.post instanceof Array) {
                // console.info('array');
                let newData = {};
                for (let i1 = 0; i1 < localConfig.post.length; i1++) {
                    newData[localConfig.post[i1].k] = localConfig.post[i1].v;
                }
                localConfig.post = newData;
            }
            //object to formdata
            if (!(localConfig.post instanceof FormData)) {
                // console.info('FormData');
                let newData   = new FormData();
                let processed = false;
                for (let k in localConfig.post) {
                    // console.info(k);
                    // console.info(localConfig.post[k]);
                    processed = true;
                    if (!localConfig.post.hasOwnProperty(k)) continue;
                    newData.append(k, localConfig.post[k]);
                }
                // console.info(newData);
                if (processed) localConfig.post = newData;
            }
        }
        //
    }
  //method
  {
	if (!localConfig.method) localConfig.method = 'get';
	if (localConfig.post) localConfig.method = 'post';
	localConfig.method=localConfig.method.toLocaleUpperCase();
  }
  //header
  {
	if (localConfig.header instanceof Array) {
	  let newData = {};
	  for (let i1 = 0; i1 < localConfig.header.length; i1++) {
		//setRequestHeader 冒号要拆
		let headerEqual = localConfig.header[i1].indexOf(':');
		if (headerEqual == -1) continue;
		newData[
		 localConfig.header[i1].slice(0, headerEqual).trim()
		 ] = localConfig.header[i1].slice(headerEqual + 1).trim();
	  }
	  localConfig.header = newData;
	}
  }
  xmlHttp.onreadystatechange = function () {
	if (xmlHttp.readyState == 4 /*&& xmlhttp.status < 400*/) {
	  // console.warn(xmlHttp.responseText);
	  if (localConfig.async) {
		if (xmlHttp.status < 400)
		  return localConfig.success ? localConfig.success(xmlHttp.responseText, xmlHttp.status) : true;
		else
		  return localConfig.error ? localConfig.error(xmlHttp.responseText, xmlHttp.status) : false;
	  }
	}
  };
  xmlHttp.open(localConfig.method, localConfig.path, localConfig.async);
  for (var k in localConfig.header) {
	if (!header.hasOwnProperty(k)) continue;
	xmlHttp.setRequestHeader(k, header[k]);
  }
  // console.info(localConfig.post);
  if (localConfig.method == 'GET') xmlHttp.send();
  else xmlHttp.send(localConfig.post);
  if (!localConfig.async) return xmlHttp.responseText;
  else return xmlHttp;
};
//alter
genFunc.send=function (input) {
  genFunc.sendAjaxV3(input);
};
genFunc.sendAjax=function (input) {
  genFunc.sendAjaxV3(input);
};

//array////////////////////////////////////////////////////////////////////////////////////////
/**
 * 判断当前对象是否为数组
 * 可以使用自带的 Array.isArray(array);
 * */
genFunc.isArray = function (input) {
  return Object.prototype.toString.call(input) === '[object Array]';
};
/**
 * 1.1 合并任意数量的对象数组
 * 后入的会取代先入的
 * 如果是对象会自动判定
 *
 * @deprecated
 * @see Object.assign(a,b)
 * */
genFunc.mergeArray = function () {
  var newArray = {};
  for (var ia = 0; ia < arguments.length; ia++) {
	for (var key in arguments[ia]) {
	  if (!arguments[ia].hasOwnProperty(key)) continue;
	  newArray[key] = arguments[ia][key];
	}
  }
  //console.error(newArray);
  return newArray;
};
/**
 * 同php方法
 * split本来就可以直接取代了……
 * */
genFunc.explode = function (string, splitter, limit) {
  return string.split(splitter, limit);
};
/**
 * 同php方法
 * */
genFunc.implode = function (array, splitter) {
  if (!splitter) {
	splitter = ",";
  }
  var targetString = "";
  for (var ia = 0; ia < array.length; ia++) {
	targetString += array[ia];
	if (ia != array.length - 1) {
	  targetString += splitter;
	}
  }
  return targetString;
};
/**
 * 删除数组中的一组数据
 * 感觉可能会有bug，但是总之目前看起来可以用
 * */
genFunc.removeVal = function (sourceArr, val) {
  if (typeof val == "string") {
	val = [val];
  }
  else if (typeof val != "object" || !val.length) {
	return [];
  }
  var arr = sourceArr.slice();
  for (var i1 = 0; i1 < val.length; i1++) {
	for (var i2 = 0; i2 < arr.length; i2++) {
	  if (arr[i2] == val[i1]) {
		arr.splice(i2, 1);
		--i2;
		//break;
	  }
	}
  }
  return arr;
};
/**
 * 输入表，排序对象，是否是数值型排序(T/F)，方向( T 小→大 / F 大→小 )
 * 适用
 * [
 *    {
 *       "a":"a",
 *       "b":"c",
 *    },
 *    {
 *       "a":"a",
 *       "b":"c",
 *    },
 * ]
 * @return array
 * */
genFunc.sort2DTable = function (inputTable, sortCol, isNum, direction) {
  return inputTable.sort(
   function (a, b) {
	 //数字
	 if (isNum) {
	   if (direction) {
		 return a[sortCol] - b[sortCol];
	   }
	   else {
		 return b[sortCol] - a[sortCol];
	   }
	 }
	 else {
	   //字符串比较
	   if (direction) {
		 return a[sortCol].localeCompare(b[sortCol]);
	   }
	   else {
		 return b[sortCol].localeCompare(a[sortCol]);
	   }
	 }
   }
  );
};
//cookie////////////////////////////////////////////////////////////////////////////////////////
/**
 * 设定cookie中的指定值
 * */
genFunc.setCookie = function (c_name, value, expiredays, path) {
  console.warn("setCookie");
  var exdate = new Date();
  exdate.setDate(exdate.getDate() + expiredays);
  document.cookie = c_name + "=" + escape(value) + ";" +
   ((expiredays == null) ? "" : "expires=" + exdate.toGMTString()) + ";" +
   ((path == null) ? "" : ";path=" + path) + ";"
};
/**
 * 获取cookie中的字段
 * */
genFunc.getCookie = function (c_name) {
  console.info(document.cookie);
  if (document.cookie.length > 0) {
	c_start = document.cookie.indexOf(c_name + "=");
	if (c_start != -1) {
	  c_start = c_start + c_name.length + 1;
	  c_end   = document.cookie.indexOf(";", c_start);
	  if (c_end == -1) c_end = document.cookie.length;
	  return unescape(document.cookie.substring(c_start, c_end))
	}
  }
  return ""
};
//string////////////////////////////////////////////////////////////////////////////////////////
/**
 * 获取连接中的get请求
 * 空字段之类的没有处理
 * 返回k:v
 * */
genFunc.getRequests = function () {
  var url        = location.search; //获取url中"?"符后的字串
  var theRequest = [];
  if (url.indexOf("?") !== -1) {
	var str = url.substr(1);
	strs    = str.split("&");
	for (var i = 0; i < strs.length; i++) {
	  var pair = strs[i].split("=");
	  if (pair.length === 2) {
		theRequest[pair[0]] = unescape(pair[1]);
	  }
	}
  }
  return theRequest;
};
genFunc.mergeRequests = function (array) {
  var result = [];
  for (var k in array) {
	// console.warn("========================================");
	// console.warn(k);
	// console.warn(array[k]);
	// console.warn(array[k].__proto__);
	// console.warn(Object.prototype);
	if (array[k].__proto__ === String.prototype) {
	  result.push(k + "=" + array[k]);
	}
  }
  return genFunc.implode(result, "&");
};
/**
 * trim方法，使用正则，默认为\s
 * */
genFunc.trim = function (string, trimChar) {
  if (!trimChar) trimChar = "\\\s";
  var reg = "^[" + trimChar + "]*(.*?)[" + trimChar + "]*$";
  // console.warn(reg);
  return string.replace(new RegExp(reg, "igm"), "$1");
};
genFunc.prefix = function (num, length, txt) {
  return (Array(length).join(txt) + num).slice(-length);
};
/**
 * @deprecated
 * GBK的gbkurlencode
 * 仅限于GBK页面
 * */
genFunc.encodeGBKurl = function (s) {
  var img = document.createElement("img");

// escapeDBC 对多字节字符编码的函数
  function escapeDBC(s) {
	if (!s) return "";
	if (window.ActiveXObject) {
// 如果是 ie, 使用 vbscript
	  execScript('SetLocale "zh-cn"', 'vbscript');
	  return s.replace(
	   /[\d\D]/g, function ($0) {
		 window.vbsval = "";
		 execScript('window.vbsval=Hex(Asc("' + $0 + '"))', "vbscript");
		 return "%" + window.vbsval.slice(0, 2) + "%" + window.vbsval.slice(-2);
	   }
	  );
	}
// 其它浏览器利用浏览器对请求地址自动编码的特性
	img.src = "nothing.action?separator=" + s;
	return img.src.split("?separator=").pop();
  }

// 把 多字节字符 与 单字节字符 分开，分别使用 escapeDBC 和 encodeURIComponent 进行编码
  return s.replace(
   /([^\x00-\xff]+)|([\x00-\xff]+)/g, function ($0, $1, $2) {
	 return escapeDBC($1) + encodeURIComponent($2 || '');
   }
  );
};
genFunc.getUrlParam = function (name) {
  var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
  var r   = window.location.search.substr(1).match(reg); //匹配目标参数
  if (r != null) return unescape(r[2]);
  return null; //返回参数值
};
//other////////////////////////////////////////////////////////////////////////////////////////
/**
 * 判断函数是否为空
 * */
genFunc.isEmpty = function (object) {
  for (var ia in object) {
	if (object.hasOwnProperty(ia)) {
	  return false;
	}
  }
  return true;
}
genFunc.toggleClass = function (element, className) {
  var classes = element.className;
  //没参数
  if (!classes || !classes.trim().length) {
	element.className = className;
	return;
  }
  var classArr = classes.split(' ');
  var exist    = false;  //用变量标识传入的类是否已经存在
  for (var i = 0; i < classArr.length; i++) {
	if (classArr[i] === className) {
	  classArr.splice(i, 1); //类存在，删除之
	  exist = true;
	}
  }
  if (!exist) classArr.push(className);
  element.className = Array.prototype.join.call(classArr, " ");  //把数组转成字符串并赋值
};
/**
 * 1.5 收集数据并发送ajax
 * @param path      {string}              ajax文件路径
 * @param getReq    {String|Array|Object} get字段，支持{k:v},["a=b"],和普通字符串
 * @param postReq   {String|Array|Object} 存在post则为post，否则为get，支持同上
 *                                        支持发送文件
 * @param header    {string}              设定header，用{k:v}
 * @param async     {Boolean}             默认为false，同步加载
 * @param parseType {String}              格式化字符串方式
 *
 * 输出encodeURIComponent
 *    encode
 *    encodeURI
 *    encodeuri
 *    encodeURIComponent
 *    encodeuricomponent
 * 输出escape
 *    escape
 * 正常输出
 *    string
 *
 * */
genFunc.zzz.sendAjax = function (path, getReq, postReq, header, async, parseType) {
  console.log("========= sending ajax =========");

  /**
   * @param sourceReq {String|Array|Object} 支持{k:v},["a=b"],和普通字符串
   *                                        当输入为{k:v}时追加对值类型的判断，
   *                                        如输入值为File或Blob则使用formData方式返回数据
   * @param parseType {String}              格式化字符串方式
   *                                        支持格式见主函数
   * */
  function processQuery(sourceReq, parseType) {
	function parseString(sourceString, parseType) {
	  switch (parseType) {
		case "encode":
		case "encodeURI":
		case "encodeuri":
		case "encodeURIComponent":
		case "encodeuricomponent":
		  return encodeURIComponent(sourceString);
		case "escape":
		  return escape(sourceString);
		case "string":
		default :
		  return sourceString;
	  }
	}

	var targetString = "";
	//对formData的判断在{key:value}型中
	//当value中存在文件时则自动返回一个formData格式
	var parseFormData  = false;
	var targetFormData = null;
	if ((typeof FormData) != "undefined") {
	  targetFormData        = new FormData();
	  targetFormData.length = 0;
	}
	var counter = 0;
	//{key:value}型
	if (sourceReq.__proto__ === Object.prototype) {
	  for (var key in sourceReq) {
		if ((typeof sourceReq[key]) == "undefined") {
		  continue;
		}
		//判断是否是文件，存在文件，则接下来使用formData
		//console.warn(sourceReq);
		//console.warn(sourceReq[key]);
		if (
		 sourceReq[key].__proto__ === File.prototype
		 || sourceReq[key].__proto__ === Blob.prototype
		) {
		  parseFormData = true;
		  break;
		}
		if (counter != 0) {
		  targetString += "&";
		}
		targetString += parseString(key, parseType) + "=" + parseString(sourceReq[key], parseType);
		counter++;
	  }
	}
	//["key=value"]型
	else if (sourceReq.__proto__ === Array.prototype) {
	  for (var ia = 0; ia < sourceReq.length; ia++) {
		if (ia != 0) {
		  targetString += "&";
		}
		targetString += parseString(sourceReq[ia], parseType);
	  }
	}
	//存在formdata的时候输出formdata
	if (targetFormData != null
	 &&
	 parseFormData == true) {
	  for (var key in sourceReq) {
		if ((typeof sourceReq[key]) == "undefined") {
		  continue;
		}
		//判断是否是文件
		if (
		 sourceReq[key].__proto__ === File.prototype
		 || sourceReq[key].__proto__ === Blob.prototype
		) {
		  targetFormData.append(parseString(key, parseType), sourceReq[key]);
		}
		else {
		  targetFormData.append(parseString(key, parseType), parseString(sourceReq[key], parseType));
		}
		targetFormData.length++;
	  }
	  return targetFormData;
	}
	return targetString;
  }

  //parseType////////////////////////////////////////////////////////
  var currentParseType = "";
  if (typeof parseType == "string") {
	currentParseType = parseType;
  }
  //get////////////////////////////////////////////////////////
  var getString = "";
  if (typeof getReq == "string") {
	getString = getReq;
  }
  else if (typeof getReq == "object") {
	getString = processQuery(getReq, currentParseType);
  }
  //post////////////////////////////////////////////////////////
  var postValue = "";
  if (typeof postReq == "string") {
	postValue = postReq;
  }
  else if (typeof postReq == "object") {
	postValue = processQuery(postReq, currentParseType);
  }
  //method////////////////////////////////////////////////////////
  var method = postValue.length > 0 ? "post" : "get";
  //path+get////////////////////////////////////////////////////////
  if (getString.length > 0) {
	path += "?" + getString;
  }
  //async////////////////////////////////////////////////////////
  if (
   typeof async == "undefined"
   || async == null
   || async.__proto__ !== Object.prototype
  ) {
	async = false;
  }
  ///////////////////////////////////////////////////////////////
  //console.log("path       :"+path);
  //console.log("post       :"+postReq);
  //console.log("accept     :"+accept);
  //console.log("contentType:"+contentType);
  //console.log("method     :"+method);
  //console.log("async      :"+async);
  //result/////////////////////////////////////////////////////////////
  var result                 = "";
  var xmlhttp;
  // var targetUrl             =path;
  xmlhttp                    = new XMLHttpRequest();
  xmlhttp.onreadystatechange = function () {
	if (xmlhttp.readyState === 4 && xmlhttp.status < 400) {
	  //console.warn(xmlhttp.responseText);
	  result = xmlhttp.responseText;
	}
  };
  //false设置async////////////////////////////////////////////////
  xmlhttp.open(method, path, async);
  //设置header////////////////////////////////////////////////////
  if (typeof header == "object") {
	console.warn("==header=========================");
	for (var key in header) {
	  //console.warn(typeof header[key]);
	  if ((typeof header[key]) == "undefined") {
		continue;
	  }
	  xmlhttp.setRequestHeader(key, header[key]);
	}
  }
  else {
	xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xmlhttp.setRequestHeader("Accept", "application/json, text/javascript, */*; q=0.01");
  }
  console.warn("===========================");
  //console.warn(postReq.length);
  //send//////////////////////////////////////////////////////////
  if (method == "post") {
	//if(typeof  postReq=="string") {
	xmlhttp.send(postValue);
	//}else{
	//	xmlhttp.send(postReq);
	//}
  }
  else {
	xmlhttp.send();
  }
  //console.error(result);
  console.log("========= finish ajax =========");
  return result;
};
/**
 * @deprecated
 * 1.2 创建一个node并通过传入的对象数组设置属性
 *
 * @param tag {"string"}
 * 输入标签类型
 *
 * @param attributes {Array}
 * 输入tag的参数，格式为
 * {
 *    "Name":"Value"
 * }
 *
 * @param parentNode {Node|Element|String}
 * parentnode可以为id或者是dom类
 * 没有充分测试一些奇葩情况……
 *
 * @param innerTxt {String}
 * innertxt追加文本节点
 *
 * @param eventListener {Array}
 * eventlistener追加事件节点，格式为
 * {
 *    "event":function(){}
 *    "event":[ function(){} , function(){} ]
 * }
 *
 * 当然没做ie兼容……
 * */
genFunc.zzz.createNode = function (tag, attributes, parentNode, innerTxt, eventListener) {
  var newNode = document.createElement(tag);
  //设置attribute
  for (var key in attributes) {
	var att   = document.createAttribute(key);
	att.value = attributes[key];
	newNode.setAttributeNode(att);
  }
  //console.warn(innerTxt);
  //console.warn(typeof innerTxt);
  //console.warn(typeof new Array(0));
  //处理文字
  if ((typeof innerTxt) == "object") {
	var txt = "";
	for (var i0 = 0; i0 < innerTxt.length; i0++) {
	  txt += innerTxt[i0] + "\r\n";
	}
	innerTxt = txt;
  }
  //console.warn(innerTxt);
  //添加文字
  if (innerTxt != null && innerTxt.length > 0) {
	newNode.appendChild(document.createTextNode(innerTxt));
  }
  //添加事件
  if ((typeof eventListener) == "object") {
	//console.warn(eventListener);
	for (var eventName in eventListener) {
	  if (eventListener[eventName].__proto__ === Array.prototype) {
		for (var ia = 0; ia < eventListener[eventName].length; ia++) {
		  newNode.addEventListener(
		   eventName, eventListener[eventName][ia]
		  );
		}
	  }
	  else {
		newNode.addEventListener(
		 eventName, eventListener[eventName]
		);
	  }
	}
  }
  //写入节点
  //console.warn(typeof parentNode);
  //console.warn(parentNode);
  //console.warn(parentNode.prototype);
  //没有父节点的情况
  //console.info(parentNode);
  if (
   parentNode == null
   || typeof parentNode == "undefined"
   || (typeof parentNode == "string" && parentNode.length == 0)
   || (typeof parentNode == "string" && document.getElementById(parentNode) == null)
  ) {
	console.error("genFunc.createNode -- no parentNode,add 2 body");
	document.body.appendChild(newNode);
  }
  //输入的父节点为字符串的情况
  else if (typeof parentNode == "string") {
	parentNode = document.getElementById(parentNode);
	parentNode.appendChild(newNode);
  }
  //输入的父节点为dom类的情况，没有深度验证
  else if (typeof parentNode == "object") {
	parentNode.appendChild(newNode);
  }
  //返回节点
  return newNode;
};
/**
 * @deprecated
 * 添加多个节点
 * @param parentNode    {string|HTMLElement}
 * @param nodeArray     array
 * [
 *    {
 *       "tag"             :"",
 *       "attributes"      :{},
 *       "innerTxt"        :"",
 *       "eventListener"   :{},
 *       "child"           :[],
 *    }
 * ]
 * */
genFunc.zzz.createNodeList = function (parentNode, nodeArray) {
  //console.warn("==== createNodeList ====");
  //全部加上验证一遍
  for (var ia = 0; ia < nodeArray.length; ia++) {
	var currentEle = nodeArray[ia];
	if (typeof currentEle["tag"] == "undefined") {
	  currentEle["tag"] = "";
	}
	if (typeof currentEle["attributes"] == "undefined") {
	  currentEle["attributes"] = {};
	}
	if (typeof currentEle["innerTxt"] == "undefined") {
	  currentEle["innerTxt"] = "";
	}
	if (typeof currentEle["eventListener"] == "undefined") {
	  currentEle["eventListener"] = {};
	}
	if (typeof currentEle["child"] == "undefined") {
	  currentEle["child"] = [];
	}
	//遍历
	var target = genFunc.createNode(
	 currentEle["tag"],
	 currentEle["attributes"],
	 parentNode,
	 currentEle["innerTxt"],
	 currentEle["eventListener"]
	);
	//console.warn(target);
	genFunc.createNodeList(target, currentEle["child"]);
  }
};
