<h2><?php echo __('Import invoice') ?></h2><br />
<p id="phone_invoice_import_hint">
    <?php echo __('Open Vodafone invoice in Adobe Reader'); ?>.<br />
    <?php echo __('Using CTRL+A copy text to input'); ?>.
</p>
<script type="text/javascript"><!--

    $(document).ready(function () {
        $("#phone_invoices_sumit").click(function () {
            $("#phone_invoices_form").hide();
            $("#phone_invoices_loader").show();
        });
    });

//--></script>
<?php if (isset($error)): ?>
<p class="error"><?php echo $error; ?></p>
<?php endif; ?>
<br />
<?php echo $form; ?>
<?php echo html::image(array('src'=>'media/images/icons/animations/ajax-loader.gif', 'id'=>'phone_invoices_loader','class'=>'ajax-loader', 'style'=>'display:none;')); ?>