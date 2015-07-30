<script type="text/javascript"><!--

    /** @var boolean */
    var can_add_user_contact = <?php echo $can_add_user_contact ? 'true' : 'false' ?>;
    /** @var string */
    var del = '<?php echo __('Delete') ?>';
    /** @var array */
    var users_names = [
	<?php foreach ($users_names as $user_name):
	    echo '["' . $user_name->id . '", "' . $user_name->username . ' (' . $user_name->id . ')"],';
	endforeach; ?>
    ];
    /** @var string */
    var users_names_options = '';
    /** @var string */
    var default_phone_prefix = '<?php echo $default_phone_prefix ?>';

    // options of selectboxes
    for (var i = 0; i < users_names.length; i++)
    {
		users_names_options += '<option value="' + users_names[i][0] + '">' + users_names[i][1] + '</option>';
    }

    $(document).ready(function ()
    {
		// submit form
		$('#import_form').submit(function()
		{
			// valid data?
			if ($(this).validate().form())
			{
				// submit the form
				$.ajax({
					success:    im_ajax_response,
					error:		im_ajax_response_error,
					async:		false,
					type:		'post',
					dataType:   'json',
					data:		$(this).serialize(),
					url:		'<?php echo url_lang::base() ?>private_phone_contacts/import_save_ajax/<?php echo $user->id ?>/'
				});
			}
			// return false to prevent normal browser submit and page navigation
			return false;
		});
		// parse button click
		$('#import_button').click(function ()
		{
			var input = $('#import_field');
			var error = $('#import_error');
			var json;

			if (input.val().length <= 0)
			{
				error.html('<?php echo __('Fill in field'); ?>');
				input.focus();
				return false;
			}
			error.html('');

			// parse JSON in input field
			try
			{
				json = $.parseJSON(input.val());
			}
			catch (e)
			{
				error.html('<?php echo __('Wrong date format'); ?>');
				input.focus();
				return false;
			}

			// lock form
			$(this).hide();
			input.attr('readonly', 'readonly');

			// data
			var contact;
			var phone;
			var counter = 0;
			var contact_counter = 0;
			// work with data
			if (json.contacts && isArray(json.contacts))
			{
				// foreach contact
				for (var i = 0; i < json.contacts.length; i++)
				{
					contact = json.contacts[i];
					// contacts
					if (contact.phone && isArray(contact.phone))
					{
						contact_counter++;
						// for each phone
						for (var u = 0; u < contact.phone.length; u++)
						{
							phone = contact.phone[u].v;
							counter++;
							// add default prefix, if there is no prefix
							if (phone[0] != '+')
							{
								phone = '+' + default_phone_prefix + phone;
							}
							// create form
							add_to_form(contact, phone, counter, contact_counter);
						}
					}
				}
				// show form
				$('#import_output').show();
			}
			else
			{
				error.html('<?php echo __('Wrong date format'); ?>');
				input.focus();
			}


			return false;
		})
    });

    /**
     * Post-submit success callback
     */
    function im_ajax_response(responseJSON)
    {
		// is error?
		if (responseJSON.success == undefined)
		{
			alert(responseJSON.error);
		}
		else
		{
			// change form to read
			$('#submit_button').hide();
			$('#th_delete').text('<?php echo __('State') ?>');
			$('#import_form_table').find(':input').attr('disabled', 'disabled');
			// display result
			for (var i = 0; i < responseJSON.success.length; i++)
			{
				var id = responseJSON.success[i].id;
				var status = responseJSON.success[i].status;

				// zero is empty string
				if (id == 0)
				{
					id = '0';
				}

				if (status % 2 == 0)
				{ // saved
					$('#td_delete_'+id).html('<?php echo html::image(array(
						'src'	=> 'media/images/states/good_16x16.png',
						'title' => __('Added')
					)) ?>');
				}
				else if (status == 5)
				{ // already in
					$('#td_delete_'+id).html('<?php echo html::image(array(
						'src'	=> 'media/images/states/warning_16x16.png',
						'title' => __('Contact is owned by another user')
					)) ?>');
				}
				else
				{ // already in
					$('#td_delete_'+id).html('<?php echo html::image(array(
						'src'	=> 'media/images/states/readonly_16x16.png',
						'title' => __('Contact is already in database')
					)) ?>');
				}
			}
			// saved
			$('#import_message').text('<?php echo __('Import has been successfully finished') ?>');
			$('#import_message').show();
		}
    }

    /**
     * Post-submit error callback
     */
    function im_ajax_response_error(jqXHR, textStatus, errorThrown)
    {
		alert('<?php echo __('Cannot save form') ?>:\n' +
			  textStatus + '\n' + errorThrown);
    }

    /**
     * Create form with given data
     */
    function add_to_form(contact, phone, count, count_contact)
    {
		var table = $('#import_form_table');
		// 
		table.append('<tr' + ((count_contact%2) ? ' class="even"' : '') + '>\
				<td><input name="im_name['+count+']" type="text" value="' +
					contact.firstname + ' ' + (contact.middlename ? contact.middlename + ' ' : '') +
					contact.lastname + '" class="required" /></td>\
					<input name="im_namef['+count+']" type="hidden" value="' +
					contact.firstname + '" />\
					<input name="im_namel['+count+']" type="hidden" value="' +
					contact.lastname + '" />\
				<td>' + phone +
				'<input name="im_phone['+count+']" type="hidden" value="' +
					phone.substr(1, phone.length) + '" /></td>\
				<?php if ($can_add_user_contact): ?>\
				<td>\
					<input name="im_private['+count+']" type="checkbox" value="1" \
					class="checkbox" checked="checked" onchange="im_change(this)" />\
					<select name="im_contact['+count+']" disabled="disabled" style="width: 200px; display: none;"></select>\
				</td>\
				<?php else: ?>\
					<input name="im_private['+count+']" type="hidden" value="1" />\
				<?php endif; ?>\
				<td id="td_delete_'+count+'"><a href="#" onclick="return im_delete_row(this);">' + del + '</a></td>\
				  </tr>');
    }

    /**
     * Check if obj is array
     * @param unknown obj
     * @return boolean
     */
    function isArray(obj)
    {
		return obj.constructor == Array;
    }

    /**
     * Deletes row where a belongs
     */
    function im_delete_row(a)
    {
		$(a).parent().parent().remove();
		return false;
    }

    /**
     * Onchange function for checkbox b
     */
    function im_change(b)
    {
		var checkbox = $(b);
		var selectbox = checkbox.parent().find('select');
		var username = checkbox.parent().parent().find('input[type="text"]');

		if (selectbox)
		{
			if (checkbox.attr('checked'))
			{
				selectbox.attr('disabled', 'disabled');
				selectbox.hide();
				username.removeAttr('readonly');
			}
			else
			{
				// search for username in users array
				if (username)
				{
					username.attr('readonly', 'readonly');
					// foreach username
					for (var i = 0; i < users_names.length; i++)
					{
						// is selectbox empty?
						if (selectbox.children().length <= 0)
						{ // add options
							selectbox.html(users_names_options);
						}
						// starts with?
						if (users_names[i][1].match('^' + username.val()))
						{
							selectbox.find('option[value="' + users_names[i][0] + '"]')
								 .attr('selected', 'selected');
							break;
						}
					}
				}
				// enable
				selectbox.removeAttr('disabled');
				selectbox.show();
			}
		}
    }

//--></script>


<h2><?php echo __('Import private contact') ?></h2><br />
<h3><?php echo __('Import contact from server Funanbol') ?></h3>
<p><?php echo __('Insert data from webpage')?>: <br /><b>http://my.funambol.com/capi/contact?command=getdata&amp;login=<i>LOGIN</i>&amp;password=<i>PASSWORD</i></b></p>
<br/>
<table cellspacing="0" class="form" id="form_table">
    <tr>
	<td>
	    <textarea cols="" rows=""  id="import_field"></textarea>
	</td>
    </tr>
    <tr>
	<td class="error" id="import_error"></td>
    </tr>
    <tr>
	<td>
	    <button type="button" id="import_button" class="submit"><?php echo __('Parse') ?></button>
	</td>
    </tr>
</table>

<br /><br />

<div style="display: none;" id="import_output">
    <h3><?php echo __('Imported contacts') ?></h3><br />
    <div id="import_message" class="message" style="display: none;"></div>
    <form action="" id="import_form" method="post">
	<table id="import_form_table" class="main grid_table">
	    <tr>
			<th><?php echo __('Name') ?></th>
			<th><?php echo __('Telephone number') ?></th>
			<?php if ($can_add_user_contact): ?>
			<th><?php echo __('Private contact') ?>?</th>
			<?php endif; ?>
			<th id="th_delete"><?php echo __('Delete') ?></th>
	    </tr>
	</table>
	<br />
	<button type="submit" class="submit" name="submit" id="submit_button"><?php echo __('Submit') ?></button>
    </form>
</div>