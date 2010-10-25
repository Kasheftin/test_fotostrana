
/*
JQuery load plugin v. 0.5
GPL (GPL-LICENSE.txt) license
Author: Alexey Kuznetsov (Ragneta.com)
Date: 2010-03-06
*/

(function($)
{  
	$.fn.myload = function(options)
	{
		var defaults = 
		{
			method: "POST",
			url: "",
			datasource: "",
			params: "",
			callback: "",
			timeout: 10000,
			mode: "",
			debug:0,
			container:0,
			autohide:0,
			animate:0,
			animate_show:{opacity:"show"},
			animate_hide:{opacity:"hide"},
			target:"",
			escape_params:1
		}
		var options = $.extend(defaults,options);

		this.each(function()
		{
			var req_fn = function(obj)
			{                       
				$(obj).each(function()
				{
					if (this.name && this.value)
					{
						if ((this.type == "checkbox" || this.type == "radio") && this.checked == false) { }
						else query += (query?"&":"") + this.name + "=" + (options.escape_params>0?escape(this.value):this.value);
					}

					$(this).children().each(function() { req_fn(this); });
				});
			}

			var print_status = function(newstatus,status_text,target) 
			{ 
				if (options.animate > 0)
				{
					if ($(options.container).css("display") != "block")
					{
						$(options.container).animate(options.animate_show,options.animate,function() { $(options.container).css("display","block"); });
					}
				}
				else
					$(options.container).css("display","block"); 

				if (target == null) target = self;

//				$(target).empty().append("<span class='" + newstatus + "'>" + newstatus.toUpperCase() + "...</span> " + status_text); 
				$(target).empty().append("<span class='" + newstatus + "'>" + status_text + "</span>"); 
				if ((newstatus == "error" || newstatus == "success") && options.autohide > 0)
				{
					setTimeout(function() 
					{ 
						if (options.animate > 0)
							$(options.container).animate(options.animate_hide,options.animate);
						else
							$(options.container).css("display","none"); 
					},options.autohide);
				}
			}

			var createRequestObject = function()
			{
				if (window.XMLHttpRequest) { try { return new XMLHttpRequest(); } catch (e) { } }
				else if (window.ActiveXObject) { try { return new ActiveXObject('Msxml2.XMLHTTP'); } catch (e) 
				{     
					try { return new ActiveXObject('Microsoft.XMLHTTP'); } catch (e) { } }
				}
				return null;
			}

			var self = this;
			var query = "";

			if (options.container == 0)
				options.container = $(self).parent();

			if (($(options.datasource).length > 0) && (options.datasource.length > 0))
				req_fn(options.datasource);
			for (var i in options.params) query += (query?"&":"") + i + "=" + (options.escape_params>0?escape(options.params[i]):options.params[i]);


			var req_abort = function()
			{
				req.abort();
				print_status("error","time out limit");
			}
			if (options.timeout > 0) var timeout = setTimeout(req_abort,options.timeout);


			var req = createRequestObject();
			if (!req) return print_status("error","Can't define XMLHttpRequest");

			req.onreadystatechange = function()
			{
				var tmp = null;
				var error = null;
				var success = null;
				var response = null;

				if (req.readyState == 0) tmp = "Not initialized";
				if (req.readyState == 1) tmp = "Start loading";
				if (req.readyState == 2) tmp = "Loaded";
				if (req.readyState == 3) tmp = "Processed";
				if (req.readyState == 4) tmp = "Completed";

				if (options.mode != "hidden")
					print_status("loading",tmp);

				if (req.readyState == 4)
				{
					if (req.status == 200) eval(req.responseText);
					else error = "can't receive data";

					if (error) error = error.replace(/&bs;n/g,"\n").replace(/&bs;t/g,"\t").replace(/&bs;r/g,"\r");
					if (success) success = success.replace(/&bs;n/g,"\n").replace(/&bs;t/g,"\t").replace(/&bs;r/g,"\r");
					if (response) response = response.replace(/&bs;n/g,"\n").replace(/&bs;t/g,"\t").replace(/&bs;r/g,"\r");

					if (error) print_status("error",error);
					else if (success) print_status("success",success);
					else if (response && options.target.length==0) $(self).empty().append(response);
					else print_status("error","empty data");

					if (options.target.length > 0)
					{
						if (error) print_status("error",error,$(options.target));
						else if (response) $(options.target).empty().append(response);
						else print_status("error","empty data",$(options.target));
					}

					clearTimeout(timeout);

					if ((success || response) && options.callback) options.callback(req.responseText);
				}
			}

			if (options.mode != "hidden") print_status("loading","please wait");

			if (options.debug) alert(query);

			if (options.method == "GET")
			{
				req.open(options.method,options.url + "?" + query,true);
				req.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				req.send(null);
			}
			if (options.method == "POST")
			{
				req.open(options.method,options.url,true);
				req.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
				req.send(query);
			}
		});
	}
})(jQuery);

