/*
	// License block \\
	
	   *   @filename:          js.js
	   *   @date:              May 18th, 2009

	   *   Copyright (C) 2007-2008 mustafa
	   *   This program is free software; you can redistribute it and/or modify it under the terms of the 
	   *   GNU General Public License as published by the Free Software Foundation. See gpl-3.0.txt
	   
	\\ License block //
*/
function $(element) {
	return document.getElementById(element);
}
var CPLanguages, popup, fOp, edit, upload; // global objects
CPLanguages = {
	csharp : "C#",
	css : "CSS",
	generic : "Generic",
	html : "HTML",
	java : "Java",
	javascript : "JavaScript",
	perl : "Perl",
	ruby : "Ruby",
	php : "PHP",
	text : "Text",
	sql : "SQL",
	vbscript : "VBScript"
};
function ajax(url, method, data, handler) {
	json2markup([
	"div",
	{
		attributes : {
			"id" : "ajaxOverlay"
		}
	},
	"img",
	{
		attributes : {
			"src" : "pafm/images/ajax.gif",
			"id" : "ajaxImg",
			"title" : "Loading",
			"alt" : "Loading"
		}
	}], document.body);
	$("ajaxOverlay").style.height = document.body.offsetHeight + "px";
	fade($("ajaxOverlay"), 0, 6, 25, "in");
	var xhr = window.ActiveXObject ? new ActiveXObject("MSXML2.XMLHTTP.3.0") : new XMLHttpRequest();
	xhr.open(method, url, true);
	xhr.onreadystatechange = function(){
		if (xhr.readyState != 4)
			return;
		fade($("ajaxOverlay"), 6, 0, 25, "out", function(){
			document.body.removeChild($("ajaxOverlay"));
			document.body.removeChild($("ajaxImg"));
		});
		if (xhr.status == 200 || xhr.statusText == "OK") {
			if (xhr.responseText == "Please refresh the page and login")
				alert("Please refresh the page and login");
			else
				handler(xhr.responseText);
		}
		else
			alert("AJAX request unsuccessful." 
			+ "\nStatus Code: " + xhr.status
			+ "\nStatus Text: " + xhr.statusText
			+ "\nParameters: " + url);
		xhr = null;
	};
	if (method.toLowerCase() == "post")
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded;charset=UTF-8");
	xhr.send(data);
}
function json2markup(json, path) {
	var i = 0, l = json.length, el, attrib, event;
	for ( ; i < l; i++) {
		if (json[i].constructor == Array)
			json2markup(json[i], el);
		else if (json[i].constructor == Object){
			if (json[i].attributes)
				for (attrib in json[i].attributes)
					switch (attrib.toLowerCase()){
						case "class":
							el.className = json[i].attributes[attrib];
							break;
						case "style":
							el.style.cssText = json[i].attributes[attrib];
							break;
						case "for":
							el.htmlFor = json[i].attributes[attrib]
							break;
						default:
							el.setAttribute(attrib, json[i].attributes[attrib]);
					}
			if (json[i].events)
				for (event in json[i].events)
					el.addEventListener(event, json[i].events[event], false);
			if (json[i].preText)
				path.appendChild(document.createTextNode(json[i].preText));
			if (json[i].text)
				el.appendChild(document.createTextNode(json[i].text));
			switch (json[i].insert){
				case "before":
					path.parentNode.insertBefore(el, path);
					break;
				case "after":
					path.parentNode.insertBefore(el, path.nextSibling);
					break;
				case "under":
				default:
					path.appendChild(el);
			}
			if (json[i].postText)
				path.appendChild(document.createTextNode(json[i].postText));
		}
		else
			el = document.createElement(json[i]);
	}
}
function fade(element, fadeFrom, fadeTo, speed, type, callback) {
	var which = element.style.opacity != undefined, condition, interval;
	element.style[which ? "opacity" : "filter"] = which ? fadeFrom / 10 : "alpha(opacity="+ fadeFrom * 10 +")";
	interval = setInterval(function(){
		if (type == "in") {
			fadeFrom++;
			condition = fadeFrom <= fadeTo;
		}
		else if (type == "out"){
			fadeFrom--;
			condition = fadeFrom >= fadeTo;
		}
		if (condition)
			element.style[which ? "opacity" : "filter"] = which ? fadeFrom / 10 : "alpha(opacity="+ fadeFrom * 10 +")";
		else {
			clearInterval(interval);
			if (callback)
				callback();
		}
	}, speed);
}
popup = {
	init : function(title, content) {
		json2markup([
		"div",
		{
			attributes : {
				"id" : "popOverlay"
			},
			events : {
				"click" : popup.close
			}
		}], document.body);
		json2markup([
			"div",
			{
				attributes : {
					"id" : "popup"
				}
			},
			[
				"div",
				{
					attributes : {
						"id" : "head"
					}
				},
				[
					"a",
					{
						attributes : {
							"id" : "x",
							"href" : "#"
						},
						events : {
							click : function(e){
								popup.close();
								e.preventDefault ? e.preventDefault() : e.returnValue = false;
							}
						},
						text : "[x]"
					},
					"span",
					{
						text : title
					}
				],
				"div",
				{
					attributes : {
						"id" : "body"
					}
				}
			]
		], document.body);
		var popupEl = $("popup"), popOverlayEl = $("popOverlay"), xEl = $('x'), mlEl;
		if (window.ActiveXObject){ //I can't even begin to tell you how much I hate IE
			xEl.style.cssFloat = "none";
			xEl.style.position = "absolute";
			xEl.style.right = xEl.parentNode.offsetLeft + 3;
		}
		json2markup(content, $("body"));
		if (mlEl = $('moveListUL')) {
			if (mlEl.offsetHeight > (document.body.offsetHeight - 150))
				mlEl.style.height = document.body.offsetHeight - 150 + "px";
		}
		popupEl.style.marginTop = "-" + parseInt(popupEl.offsetHeight) / 2 + "px";
		popupEl.style.marginLeft = "-" + parseInt(popupEl.offsetWidth) / 2 + "px";
		fade(popOverlayEl, 0, 6, 25, "in");
		document.onkeydown = function(e) {
			if ((e || window.event).keyCode == 27) {
				popup.close();
				return false;
			}
		};
	},
	close : function() {
		if ($("popup")){
			var popOverlayEl = $("popOverlay");
			fade(popOverlayEl, 6, 0, 25, "out", function(){
				document.body.removeChild(popOverlayEl);
			});
			document.body.removeChild($("popup"));
		}
		document.onkeydown = null;
	}
};
fOp = {
	rename : function(subject, path) {
		popup.init("Rename:", [
			"form",
			{
				attributes : {
					"action" : "?do=rename&subject=" + subject + "&path=" + path,
					"method" : "post"
				}
			},
			[
				"input",
				{
					attributes : {
						"title" : "Rename To",
						"type" : "text",
						"name" : "rename",
						"value" : subject
					}
				},
				"input",
				{
					attributes : {
						"title" : "Ok",
						"type" : "submit",
						"value" : "\u2713"
					}
				}
			]
		]);
	},
	create : function(type, path) {
		popup.init("Create " + type + ":", [
			"form",
			{
				attributes : {
					"method" : "post",
					"action" : "?do=create&path=" + path
				}
			},
			[
				"input",
				{
					attributes : {
						"title" : "Filename",
						"type" : "text",
						"name" : type
					}
				},
				"input",
				{
					attributes : {
						"title" : "Ok",
						"type" : "submit",
						"value" : "\u2713"
					}
				}
			]
		]);
	},
	chmod : function(path, subject, chmod){
		popup.init("Chmod " + unescape(subject) + ":", [
			"form",
			{
				attributes : {
					"method" : "post",
					"action" : "?do=chmod&subject=" + subject + "&path=" + path
				}
			},
			[
				"input",
				{
					attributes : {
						"title" : "chmod",
						"type" : "text",
						"name" : "mod",
						"value" : chmod
					}
				},
				"input",
				{
					attributes : {
						"title" : "Ok",
						"type" : "submit",
						"value" : "\u2713"
					}
				}
			]
		]);
	},
	moveList : function(subject, path, to){
		ajax(("?do=moveList&subject=" + subject + "&path=" + path + "&to=" + to), "get", null, function (response) {
			if (!$("popup"))
				popup.init("Move " + unescape(subject) + " to:", Function("return " + response)());
			else {
				var popupEl = $("popup"), xEl = $('x'), mlEl;
				$("body").innerHTML = "";
				json2markup(Function("return " + response)(), $("body"));
				if (window.ActiveXObject)
					xEl.style.right = xEl.parentNode.offsetLeft + 3;
				if ((mlEl = $('moveListUL')).offsetHeight > (document.body.offsetHeight - 150))
					mlEl.style.height = document.body.offsetHeight - 150 + "px";
				popupEl.style.marginTop = "-" + parseInt(popupEl.offsetHeight) / 2 + "px";
				popupEl.style.marginLeft = "-" + parseInt(popupEl.offsetWidth) / 2 + "px";
			}
		});
	}
};
edit = {
	init : function(subject, path, syntax) {
		var tempAr = [], key, ll, obj;
		syntax = syntax || "text";
		switch (syntax) {
			case "js":
				syntax = "javascript";
				break;
			case "htm":
				syntax = "html";
				break;
			case "pl":
				syntax = "perl";
				break;
			case "rb":
				syntax = "ruby";
		}
		json2markup([
			"div",
			{
				attributes : {
					"id" : "editOverlay"
				}
			}
		], document.body)
		$("editOverlay").style.height = document.body.offsetHeight + "px";
		json2markup([
		"div",
		{
			attributes : {
				"id" : "ea"
			}
		},
		[
			"textarea",
			{
				attributes : {
					"id" : "ta",
					"class" : "codepress " + syntax,
					"rows" : "30",
					"cols" : "90"
				}
			},
			"br",
			{},
			"input",
			{
				attributes : {
					"type" : "text",
					"value" : unescape(subject),
					"readonly" : ""
				}
			},
			"input",
			{
				attributes : {
					"type" : "button",
					"value" : "Save",
					"id" : "save"
				},
				events : {
					click : function(){
						edit.save(subject, path);
					}
				}
			},
			"input",
			{
				attributes : {
					"type" : "button",
					"value" : "Exit",
					"id" : "exit"
				},
				events : {
					click : function(){
						edit.exit(subject, path);
					}
				}
			},
			"input",
			{
				attributes : {
					"type" : "button",
					"value" : "Toggle CodePress"
				},
				events : {
					click : function(){
						ta.toggleEditor();
					}
				}
			},
			"select",
			{
				attributes : {
					"id" : "ll",
					"style" : "margin-left: 1px;"
				},
				events : {
					change : function(e){
						var el = e.srcElement || e.target;
						ta.setLanguage(el[el.selectedIndex].value);
					}
				}
			},
			[
				"option",
				{
					text : "Loading"
				}
			],
			"span",
			{
				attributes : {
					"id" : "editMsg"
				}
			}
		]], document.body);
		document.onkeydown = function(e){
			if ((e || window.event).keyCode == 27) {
				edit.exit(subject, path);
				return false;
			}
		};
		for (key in CPLanguages){
			obj = {
				attributes : {
					"value" : key
				},
				text : CPLanguages[key]
			};
			if (syntax == key)
				obj.attributes.selected = "true";
			tempAr.push("option", obj);
		}
		(ll = $("ll")).innerHTML = "";
		json2markup(tempAr, ll);
		ajax("?do=readFile&path=" + path + "&subject=" + subject, "get", null, function(response){
			$("ta").value = response;
			if (!$("cpjs")) {
				json2markup(["script",
				{
					attributes : {
						"src" : "pafm/codepress/codepress.js",
						"type" : "text/javascript",
						"id" : "cpjs"
					},
					events : {
						load : function(){
							if (!/webkit/.test(navigator.userAgent.toLowerCase()))
								CodePress.run();
						}
					}
				}], document.getElementsByTagName("head")[0]);
				$("cpjs").onreadystatechange = function(){ //ie
					if (this.readyState == "complete")
						CodePress.run();
				}
			}
			else
				CodePress.run();
		});
	},
	save : function(subject, path){
		$("editMsg").innerHTML = null;
		var postData = "data=" + encodeURIComponent(window.ta ? ta.getCode() : $("ta").value);
		ajax("?do=saveEdit&subject=" + subject + "&path=" + path, "post", postData, function(response){
			$("editMsg").className = response != "Saved" ? "failed" : "succeeded"
			$("editMsg").innerHTML = response;
		});
		window.__FILESAVED = true;
	},
	exit : function(subject, path){
		if (window.__FILESAVED) {
			ajax("?do=getfs&path=" + path + "&subject=" + subject, "get", null, function(response){
				var listItems = $("dirList").getElementsByTagName("li"), temp = unescape(subject), i = 0, l = listItems.length;
				for ( ; i < l; i++) {
					if (listItems[i].title == temp) {
						listItems[i].getElementsByTagName("span")[0].innerHTML = response;
						break;
					}
				}
			});
		}
		document.body.removeChild($("ea"));
		document.body.removeChild($("editOverlay"));
		window.__FILESAVED = null;
		document.onkeydown = null;
	}
};
upload = {
	init : function(path, fsize) {
		popup.init("Upload:", [
			"form",
			{
				attributes : {
					"id" : "upload",
					"action" : "?do=upload&path=" + path,
					"method" : "post",
					"enctype" : "multipart/form-data",
					"encoding" : "multipart/form-data"
				}
			},
			[
				"input",
				{
					attributes : {
						"type" : "hidden",
						"name" : "MAX_FILE_SIZE",
						"value" :  fsize
					}
				},
				"input",
				{
					attributes : {
						"type" : "file",
						"name" : "file"
					},
					events : {
						change : function(e) {
							$("rc").innerHTML = "";
							upload.chk((e.srcElement || e.target).value, path);
						}
					}
				},
				"span",
				{
					attributes : {
						"id" : "rc"
					}
				}
			]
		]);
	},
	chk : function(subject, path) {
		var name = subject.split(/\\|\//g), markup;
		name = name.push ? name[name.length-1] : name;
		ajax("?do=fileExists&path="+path+"&subject=" + name, "get", null, function(response){
			if (response == "1")
				markup = [
					"br",
					{},
					"b",
					{
						text : "File exists, overwrite?"
					},
					"br",
					{},
					"input",
					{
						attributes : {
							"type" : "submit",
							"value" : "Yes"
						}
					},
					"input",
					{
						attributes : {
							"type" : "button",
							"id" : "tb",
							"value" : "No"
						},
						events : {
							click : popup.close
						}
					}
				];
			else
				markup = [
					"input",
					{
						attributes : {
							"title" : "Ok",
							"type" : "submit",
							"value" : "\u2713",
							"id" : "uploadOk"
						}
					}
				];
			json2markup(markup, $("rc"));
		});
	}
};
