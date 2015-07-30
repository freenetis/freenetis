<?php
/**
 * Devices show javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif;
	$etm = new Enum_type_Model();
?>
	var device_id = $('#device_id').val();
	var depth = 2;
	
	function initTrees()
	{
		$('#tree').jstree({
			'json_data' : {
				'ajax' : {
					'url' : '<?php echo url_lang::base() ?>devices/get_map',
					'data' : function (n) {
	                    // the result is fed to the AJAX request `data` option
	                    return {
	                        'root' : n.attr ? 'false' : 'true',
							'from' : n.attr ? n.attr('id') : device_id,
	                        'depth' : 2
	                    };
	                }
				}
			},
			'themes' : {
				'theme' : 'classic',
				'url' : '<?php echo url::base() ?>media/css/jquery.jstree.css'
			},
			'types' : {
	            'valid_children' : [ 'root' ],
	            'types' : {
	                '<?php echo $etm->get_type_id('pc', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/pc.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('client', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/client.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('router', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/router.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('switch', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/switch.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('notebook', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/notebook.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('home ap', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/homeap.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('voip', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/voip.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('ap', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/ap.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('tablet', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/tablet.png'
	                    }
	                },
					'<?php echo $etm->get_type_id('server', Enum_type_Model::DEVICE_TYPE_ID) ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/server.png'
	                    }
	                }
	            }
	        },
			'plugins' : ['themes', 'json_data', 'ui', 'types']
		});
	}
	
	$('#device_id').change(function()
	{
		$('#tree').empty();
		device_id = $('#device_id').val();
		initTrees();
	})
	
        $('#tree a').live('click', function()
        {   
            var link = '<?php echo url_lang::base() ?>devices/show/' + $(this).parent().attr('id');
            
            if (!$(this).hasClass('popup_link'))
            {
                $(this).addClass('popup_link').attr('href', link).trigger('click');
            }
        })
        
	$(document).ready(function()
	{
		initTrees();
	})