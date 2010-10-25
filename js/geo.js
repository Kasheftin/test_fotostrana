

$(function()
{
	searchform_initialize();
});


function searchform_initialize()
{
	$(".selectCountry").each(function()
	{
		var country_id = parseInt($(this).attr("default"));
		var target = $(this).attr("onchangetarget");
		fillSelect(this,countries);
		fillSelect("#" + target,cities[country_id]);
	}).change(function()
	{
		var target = $(this).attr("onchangetarget"); 
		fillSelect("#" + target,cities[this.value]); 
	});

}


function setUserGeo()
{
	country_id = $("#selectCountry").val();
	city_id = $("#selectCity").val();
	$.ajax({type:"POST",url:"/profile/",data:{ajax:1,action:"set_geo",country_id:country_id,city_id:city_id},success:function(data) { 
		var success = null;
		var error = null;
		var city_name = null;
		eval(data);
		$("#geoDivCityName").html(city_name);
		switchDiv("selectGeoDiv");
	}});
}


