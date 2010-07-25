<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<div class="box">
<?php if ($_POST) {?>
<p align="center" class="error-messages"><?php echo Kohana::lang('yaat.error-incorrect-credentials') ?></p>
<?php } ?>
<?php echo form::open(NULL, array('class'=>'loginform')) ?>
<?php if (array_key_exists('next', $_GET)) echo form::hidden('next', $_GET['next']); 
else if (array_key_exists('next', $_POST)) echo form::hidden('next', $_POST['next']);
?>
	<table width="100%" border="0">
	  <tr>
		<td width="40%" class="form-label">
			<?php echo form::label('username', Kohana::lang('yaat.model-username')) ?>:</td>
		<td>
		<?php echo form::input('username', $_POST?$_POST['username']:'', 'class="form-textbox" size="20" required="required"') ?>
		</td>
	  </tr>
	  <tr>
		<td class="form-label">
			<?php echo form::label('password', Kohana::lang('yaat.model-password')) ?>:</td>
		<td>
		<?php echo form::password('password', '', 'class="form-textbox" size="20" required="required"') ?>
		</td>
	  </tr>
	  <tr>
		<td class="form-label">&nbsp;</td>
		<td><?php echo form::submit('submit',Kohana::lang('yaat.action-login')) ?></td>
	  </tr>
	</table>
<?php echo form::close() ?>
<script type="text/javascript">
    document.getElementById('username').focus();
</script>
</div>
