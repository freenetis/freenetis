<?php
/**
 * Devices show javascript view.
 * 
 * @author Michal Kliment, Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type='text/javascript'><?php endif

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
	                '<?php echo Device_Model::TYPE_PC ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/pc.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_CLIENT ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/client.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_ROUTER ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/router.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_SWITCH ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/switch.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_NOTEBOOK ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/notebook.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_HOMEAP ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/homeap.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_VOIP ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/voip.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_AP ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/ap.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_MOBILE ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/mobile.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_TV ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/tv.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_CAMERA ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/camera.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_TABLET ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/tablet.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_PRINTER ?>' : {
	                    'icon' : {
	                        'image' : '<?php echo url::base() ?>media/images/icons/devices/printer.png'
	                    }
	                },
					'<?php echo Device_Model::TYPE_SERVER ?>' : {
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