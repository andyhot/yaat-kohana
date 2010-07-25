Edit views/admin_templates/simple/admin_menu.php
to customize the menu<br /><br />
<?php 
$root = 'controllers/admin';
$ctrls = Kohana::list_files($root); 

foreach ($ctrls as $ctrl) {
$pos = strpos($ctrl, $root);
$ctrl = substr($ctrl, $pos + strlen($root) + 1);
echo $ctrl;
echo "<br/>";
} 

?>