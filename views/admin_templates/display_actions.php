<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<div class="box">
	<p>Actions</p>
</div>

<ul>
<?php foreach ($actions as $action => $link): ?>
	<li><?php echo html::anchor($link, $action)?></li>
<?php endforeach ?>
</ul>