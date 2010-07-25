<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cosmodata | <?php echo html::specialchars($title) ?></title>
    <link type="text/css" rel="stylesheet" href="<?php echo url::base(FALSE) ?>css/main_simple.css">
</head>
<body>
    <div id="container">
        <div id="header">
	<h1><?php echo html::specialchars($title) ?></h1>
            <p>
            User: <?php echo $_SESSION['user']['username']?> |
            <?php echo html::anchor('admin/'.Router::$controller.'/logout', Kohana::lang('model.action-logout')) ?>
            </p>
            <?php if( array_key_exists('info', $_SESSION)) {?>
                <p class="info"><?php echo $_SESSION['info']?></p>
            <?php }?>
        </div>
        <div id="content">
        <?php if(isset($menu)) echo '<div id="menu">'.$menu.'</div>' ?>
        <div id="main_content">
	<?php echo $content ?>
        </div>
        <div class="clr"> </div>
        </div>        
        <div id="footer">
            <p class="copyright">
                Rendered in {execution_time} seconds, using {memory_usage} of memory<br />
                <a href="?_lang=el" class="full">GR</a> <a href="?_lang=en_US" class="full">EN</a>
                [<a href="?_tpl=dojo" class="full">dojo</a>
                <a href="?_tpl=ext" class="full">ext</a>]
                Copyright &copy;2009 hellassites
            </p>
        </div>
    </div>
</body>
</html>