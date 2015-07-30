<?php
/**
 * Work report form javascript view.
 * 
 * @author Ondřej Fibich
 */

// IDE complementation
if (FALSE): ?><script type="text/javascript"><?php endif

?>

	currency = ' <?php echo __(Settings::get('currency')) ?>';

	/**
	 * Gets value from string reprezentation of price
	 * @param price		Price to get value of
	 * @example 100 000,78 => 100000.78
	 */
	function get_value_of_price_str(price)
	{
		if (!price || !price.length)
		{
			return 0;
		}
		
		var price_replaced = price.replace(/ /g, '');
		return parseFloat(price_replaced.replace(/,/g, '.'));
	}

	/**
	 * Adds actions for work form
	 */
	function add_actions()
	{

		// resizing of textarea with class one_row_textarea

		$('.one_row_textarea').unbind('focus');
		$('.one_row_textarea').unbind('blur');

		// set bigger on focus
		$('.one_row_textarea').focus(function ()
		{
			var height = parseInt($(this).css('height'));
			
			var lines = $(this).val().split('\n').length;
			
			if (lines < 3)
			{
				lines = 3;
			}
			
			$(this).css('height', (height * lines).toString() + 'px');
		});

		// st smaller on blur
		$('.one_row_textarea').blur(function ()
		{
			$(this).css('height', '16px');
		});

		// recalculate price after changing any of hour fields

		$(':text[name^="work_hours"], #price_per_hour').unbind('keyup');

		$(':text[name^="work_hours"], #price_per_hour').keyup(function ()
		{
			recalculate_hours();
		});

		// recalculate after changing any of km fields

		$(':text[name^="work_km"], #price_per_km').unbind('keyup');

		$(':text[name^="work_km"], #price_per_km').keyup(function ()
		{
			recalculate_km();
		});

		// add capability to add fields

		$('#add_work').unbind('click');

		$('#add_work').click(function ()
		{
			var $last_tr = $('#work_table tbody tr:last');
			var index = 0;

			if ($last_tr.length)
			{
				var name = $last_tr.find('input[name^="work_km"]').attr('name');
				index = parseInt(name.split('[')[1].split(']')[0]);
			}

			add_work_row(index + 1);
			add_actions();

			var works_count = parseInt($('#works_count').val());
			$('#works_count').val(works_count + 1);

			return false;
		});

		// add capability to remove work

		$('.remove_row').unbind('click');

		$('.remove_row').click(function ()
		{
			var works_count = parseInt($('#works_count').val());

			if (works_count < 2)
			{
				alert('<?php echo __('Work report has to have at least one work.') ?>');
				return false;
			}

			var $tr = $(this).parent().parent();

			if ($tr.find('textarea[name^="work_description"]').val() ||
				$tr.find('input[name^="work_km"]').val() ||
				$tr.find('input[name^="work_hours"]').val())
			{
				if (!window.confirm('<?php echo __('Do you really want to delete this record') ?>?'))
				{
					return false;
				}
			}

			$tr.remove();
			$('#works_count').val(works_count - 1);

			recalculate_hours();
			recalculate_km();

			return false;
		});

		// add capability to clear work row

		$('.clear_row').unbind('click');

		$('.clear_row').click(function ()
		{				
			var $tr = $(this).parent().parent();
			
			if ($tr.find('input[name^="work_id"]').length &&
				!window.confirm('<?php echo __('Do you really want to delete this record') ?>?'))
			{
				return false;
			}

			$tr.find('textarea[name^="work_description"]').val('');
			$tr.find('input[name^="work_km"]').val('');
			$tr.find('input[name^="work_hours"]').val('');
			$tr.find('input[name^="work_id"]').remove();
			$(this).hide();

			recalculate_hours();
			recalculate_km();

			return false;
		});

	}

	/**
	 * Adds new row to work form
	 * 
	 * @param int index		Index for inputs
	 * @param string date	Date in format YYYY-mm-dd [optional]
	 */
	function add_work_row(index, date)
	{
		var $tbody = $('#work_table tbody');
		var is_weekend = false;

		if (!date)
		{
			date = '';
		}
		else
		{
			var da = date.split('-');
			is_weekend = new Date(da[0], da[1] - 1, da[2]).getDay() % 6 == 0;
		}

		// create HTML (buffer is faster than concatenation)

		var b = [];

		b.push('<tr');

		if (is_weekend)
			b.push(' style="background: #f1f1f1"');

		b.push('><td><input type="text" name="work_date[');
		b.push(index);
		b.push(']" value="');
		b.push(date);
		b.push('" style="width: 80px"');

		if (date)
			b.push(' readonly="readonly"');
		else
			b.push(' class="date"');

		b.push(' /></td><td><textarea name="work_description[');
		b.push(index);
		b.push(']" class="one_row_textarea" style="width: 450px"></textarea></td>');
		b.push('<td><input type="text" name="work_hours[');
		b.push(index);
		b.push(']" value="" maxlength="5" style="width: 30px" /></td>');
		b.push('<td><input type="text" name="work_km[');
		b.push(index);
		b.push(']" value="" maxlength="6" style="width: 30px" /></td>');
		b.push('<td>');

		if (!date)
		{
			b.push('<a href="#" class="remove_row" class="action_field_icon" title="<?php echo __('Remove this work') ?>"><?php
					echo html::image(array('src' => 'media/images/icons/grid_action/delete.png', 'width' => 14, 'height' => 14))
			?></a>');
		}

		b.push('</td></tr>');

		$tbody.append(b.join(''));
	}
	
	/**
	 * Recalculate hours count and price of report
	 */
	function recalculate_hours()
	{
		var hours_count = 0.0;

		$(':text[name^="work_hours"]').each(function ()
		{
			var val = $(this).val();
			
			if (!val.length)
			{
				$(this).removeClass('error');
			}
			else if (!is_numeric(val) || val >= 24 || val <= 0)
			{
				$(this).addClass('error');
			}
			else
			{
				$(this).removeClass('error');
				hours_count = hours_count + get_value_of_price_str(val);
			}
		});
		
		$('#total_hours_count').text(round(hours_count, 2) + ' h');

		var pph = $('#price_per_hour').val();

		if (!pph.length)
		{
			pph = 0;
			$('#price_per_hour').removeClass('error');
		}
		else if (!is_numeric(pph))
		{
			pph = 0;
			$('#price_per_hour').addClass('error');
		}
		else
		{
			$('#price_per_hour').removeClass('error');
		}

		var price = pph * hours_count;

		$('#total_hours_price').text(number_format(price, 2, ',', ' ') + currency);

		price = price + get_value_of_price_str($('#total_km_price').text());

		$('#total_price').text(number_format(price, 2, ',', ' ') + currency);
	}

	/**
	 * Recalculate km count and price of report
	 */
	function recalculate_km()
	{
		var km_count = 0;

		$(':text[name^="work_km"]').each(function ()
		{
			if (!$(this).val().length)
			{
				$(this).removeClass('error');
			}
			else if (isNaN($(this).val()))
			{
				$(this).addClass('error');
			}
			else
			{
				$(this).removeClass('error');
				km_count = km_count + get_value_of_price_str($(this).val());
			}
		});

		$('#total_km_count').text(number_format(km_count, 0) + ' km');

		var ppkm = $('#price_per_km').val();

		if (!ppkm.length)
		{
			ppkm = 0;
			$('#price_per_hour').removeClass('error');
		}
		else if (!is_numeric(ppkm))
		{
			ppkm = 0;
			$('#price_per_km').addClass('error');
		}
		else
		{
			$('#price_per_km').removeClass('error');
		}
		
		var price = ppkm * km_count;

		$('#total_km_price').text(number_format(price, 2, ',', ' ') + currency);

		price = price + get_value_of_price_str($('#total_hours_price').text());
		price = Math.round(price * 100) / 100;

		$('#total_price').text(number_format(price, 2, ',', ' ') + currency);
	}
	
	/**
	 * Check second form (work form) for validity
	 */
	function check_second_form()
	{
		var valid = true;
		var km_filled = false;

		$(':text[name^="work_hours"]').removeClass('error');
		$(':text[name^="work_km"]').removeClass('error');
		$('textarea[name^="work_description"]').removeClass('error');
		$(':text[name^="work_date"]').removeClass('error');
		$('#price_per_hour').removeClass('error');
		$('#price_per_km').removeClass('error');

		$(':text[name^="work_km"]').each(function ()
		{
			if ($(this).val().length)
			{
				if (isNaN($(this).val()))
				{
					$(this).addClass('error');
					valid = false;
				}
				else
				{
					km_filled = true;

					if (parseInt($(this).val()) < 0)
					{
						$(this).addClass('error');
						valid = false;
					}
					else
					{
						var index = parseInt($(this).attr('name').split('[')[1].split(']')[0]);

						if (!$('textarea[name="work_description[' + index + ']"]').val().length)
						{
							$('textarea[name="work_description[' + index + ']"]').addClass('error');
							valid = false;
						}

						if (!$(':text[name="work_hours[' + index + ']"]').val().length)
						{
							$(':text[name="work_hours[' + index + ']"]').addClass('error');
							valid = false;
						}

						var date = $(':text[name="work_date[' + index + ']"]').val();

						if (!date.length || !(/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/.test(date)))
						{
							$(':text[name="work_date[' + index + ']"]').addClass('error');
							valid = false;
						}
					}
				}
			}
		});

		$(':text[name^="work_hours"]').each(function ()
		{
			if ($(this).val().length)
			{
				if (!is_numeric($(this).val()))

				{
					$(this).addClass('error');
					valid = false;
				}
				else
				{
					var value = get_value_of_price_str($(this).val());

					if (value <= 0.0 || value > 24.0)
					{
						$(this).addClass('error');
						valid = false;
					}
					else
					{
						var index = parseInt($(this).attr('name').split('[')[1].split(']')[0]);

						if (!$('textarea[name="work_description[' + index + ']"]').val().length)
						{
							$('textarea[name="work_description[' + index + ']"]').addClass('error');
							valid = false;
						}

						var date = $(':text[name="work_date[' + index + ']"]').val();

						if (!date.length || !(/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/.test(date)))
						{
							$(':text[name="work_date[' + index + ']"]').addClass('error');
							valid = false;
						}
					}
				}
			}
		});

		$('textarea[name^="work_description"]').each(function ()
		{
			if ($(this).val().length)
			{
				var index = parseInt($(this).attr('name').split('[')[1].split(']')[0]);

				if (!$(':text[name="work_hours[' + index + ']"]').val().length)
				{
					$(':text[name="work_hours[' + index + ']"]').addClass('error');
					valid = false;
				}

				var date = $(':text[name="work_date[' + index + ']"]').val();

				if (!date.length || !(/^[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}$/.test(date)))
				{
					$(':text[name="work_date[' + index + ']"]').addClass('error');
					valid = false;
				}
			}
		});

		var pph = $('#price_per_hour').val();

		if (!pph.length || !is_numeric(pph))
		{
			$('#price_per_hour').addClass('error');
			valid = false;
		}

		if (km_filled)
		{
			var ppkm = $('#price_per_km').val();

			if (!ppkm.length || !is_numeric(ppkm))
			{
				$('#price_per_km').addClass('error');
				valid = false;
			}
		}

		if (!valid)
		{
			$('.error:first').trigger('click');
			$('.error:first').focus();
		}

		return valid;
	}
