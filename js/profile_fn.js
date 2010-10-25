

function send_form(source,target)
{
	$(target).myload({url:"/profile/",params:{ajax:1},datasource:source,animate:500,autohide:3000});
}


function delete_user_photo()
{
	$("#photo_form_loading").myload({url:"/profile/",params:{action:"delete_user_photo",ajax:1},animate:500,autohide:3000,callback:function() { 
		$("#profile_photo").css("display","none");
		$("#profile_no_photo").css("display","block");
	}});
}


$(function()
{
	$("#photo_form_button").upload({
		name:"file",
		method:"post",
		action:"/profile/?action=upload_photo&ajax=1",
		onSubmit:function(data) { $("#photo_form_loading").empty().append("<span class='loading'>Загрузка...</span>"); },
		onComplete:function(data) 
		{ 
			var error = "";
			var success = "";
			var file_url = "";
			eval(data);
			if (error.length > 0)
			{
				$("#photo_form_loading").empty().append("<span class='error'>" + error + "</span>");
			}
			else
			{
				$("#photo_form_loading").empty();
				if (file_url.length > 0)
				{
					$("#profile_photo_img").attr("src",file_url);
					$("#profile_photo").css("display","block");
					$("#profile_no_photo").css("display","none");
				}
			}
		}
	});
});


