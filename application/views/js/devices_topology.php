<?php
/**
 * Devices show all javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>
	var ids = [<?php echo $device_id ?>];
	
	states = {<?php echo $device_id ?>: 1}
	
	$(".topology-link").live('click', function (){
		
		$this = $(this);
		
		var id = $this.attr('id').replace('topology-link-', '');

		if (!in_array(id, ids))
		{
			$.ajax({
				async: false,
				type: 'POST',
				url: $(this).attr('href'),
				success: function (data){

					data = $(data).find(".topology");
					
					if (data[0] !== undefined)
						$this.parent().parent().append(data[0].outerHTML);
				}
			});
			
			$this.parent().parent().find('li .topology-link').each (function (){
				var id = $(this).attr('id').replace('topology-link-', '');

				if (in_array(id, ids))
				{
					$(this).parent().replaceWith($(this).text());
				}
			});
			
			ids.push(id);
			
			states[id] = 0;
			
			$this.parent().parent().find('.topology').hide();
		}
		
		states[id] = (states[id] == 0) ? 1 : 0;
		
		$this.parent().parent().find('.topology').toggle('slow');
		
		return false;
	});