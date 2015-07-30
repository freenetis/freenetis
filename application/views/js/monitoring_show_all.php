<?php
/**
 * Segment add javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	setInterval(function (){
		$(".monitor-state[title!=Online]").parent().toggleClass('monitor-down');
	}, 800);
	
	setInterval(function (){
		window.location.reload();
	}, 60000);
	
	$(".form button").hide();
	
	$(".form select").live('change', function (){
		$(this).parents('form').submit();
	});
	
	$("#groups-links-title").live('click', function (){
		var src = $("#groups-links-title img").attr('src');
		if ((new_src = str_replace('add', 'minus', src)) == src)
			new_src = str_replace('minus', 'add', src);
		
		$("#groups-links-title img").attr('src', new_src);
		
		$("#groups-links").toggle();
	});
	
	$("#groups-links-title").trigger('click');
	
	$("#groups-links a").live('click', function (){
		var id = str_replace('#','', $(this).attr('href'));
		
		$("#groups-links-title").trigger('click');
		
		$('html,body').animate({scrollTop: $("a[name="+id+"]").offset().top},'slow');
		
		return false;
	});
	
	$(".top-link").live('click', function (){
		var id = str_replace('#','', $(this).attr('href'));
		
		$("#groups-links-title").trigger('click');
		
		$('html,body').animate({scrollTop: $("a[name="+id+"]").offset().top},'slow');
		
		return false;
	});
	
	$(".mark_all").live('change', function(){
		
		var checked = $(this).is(':checked');
		
		
		$(this).parent().find("input[type=checkbox]").attr('checked', checked);
	});
	