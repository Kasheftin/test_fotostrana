

function switchDiv(dName)
{
	if ($("#"+dName).css("display") == "block") 
		$("#"+dName).animate({height:"hide"},300);
	else 
		$("#"+dName).animate({height:"show"},300,function() { $(this).css("display","block"); });
	return false;
}


function switchDivByCheckbox(obj,dName)
{
	var i = $(obj).attr("checked")?1:0;
	if (i > 0)
		$("#"+dName).css("display","block");
	else
		$("#"+dName).css("display","none");
}


function fillSelect(target,ar)
{
	$(target).each(function()
	{
		var self = this;

		var s = $(self).attr("default");
		              
		$(self).empty();

		for (var i in ar)
		{
			$(self).append("<option value='" + (i==0?"undefined":i) + "'" + (i==s?" selected='selected'":"") + ">" + ar[i] + "</option>");
		}
	});
}


function GetCookie(sName)
{
	var out = "";
	var aCookie = document.cookie.split("; ");
	for (var i=0; i < aCookie.length; i++)
	{
		var aCrumb = aCookie[i].split("=");
		if (sName == aCrumb[0]) 
		{
			out = unescape(aCrumb[1]);
		}
	}
	return out;
}


function SetCookie(sName, sValue)
{
	document.cookie = sName + "=" + escape(sValue) + "; path=/";
}
                 

