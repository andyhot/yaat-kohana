<?php defined('SYSPATH') OR die('No direct access allowed.'); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Cosmodata | <?php echo html::specialchars($title) ?></title>
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo url::base(FALSE)?>js/extjs/resources/css/ext-all.css" />
    <link type="text/css" rel="stylesheet" href="<?php echo url::base(FALSE) ?>css/main_dojo.css" />
    <style type="text/css">
        #overlayLoading {
            background:#f00; padding:2px;
            position:absolute; top:0; right:0;z-index:99999;
            color:white; font-weight:bold;
        }
    </style>

    <script type="text/javascript"
        src="<?php echo url::base(FALSE)?>js/extjs/adapter/ext/ext-base.js"></script>
    <script type="text/javascript" 
        src="<?php echo url::base(FALSE)?>js/extjs/ext-all.js"></script>

    <script type="text/javascript">

    window.dsmsMsg = {
        isRequired: '<?php echo Kohana::lang('model.is-required', '***') ?>'
    };
    </script>
</head>
<body>
<div id="overlay"></div>
<div id="container" dojoType="dijit.layout.BorderContainer">
    <div id="header" dojoType="dijit.layout.ContentPane" region="top">
	<h1><?php echo html::specialchars($title) ?></h1>
        <p>
        User: <?php if( array_key_exists('user', $_SESSION)) echo $_SESSION['user']['username']?> |
        <?php echo html::anchor('admin/'.Router::$controller.'/logout', Kohana::lang('model.action-logout')) ?>
        </p>
        <?php if( array_key_exists('info', $_SESSION)) {?>
            <p class="info"><?php echo $_SESSION['info']?></p>
        <?php }?>
    </div>
    <div id="content" dojoType="dijit.layout.SplitContainer" region="center"
            orientation="horizontal" sizerWidth="7" activeSizing="true">
        <div id="overlayLoading">Loading...</div>
        <div id="menu" dojoType="dijit.layout.ContentPane" sizeMin="20" sizeShare="9">
            <?php if(isset($menu)) echo $menu ?>
        </div>
        <div id="main_content" dojoType="dijit.layout.ContentPane" sizeMin="50" sizeShare="70">
	<?php echo $content ?>
        </div>
    </div>
    <div id="footer" dojoType="dijit.layout.ContentPane" region="bottom">
            <p class="copyright">
                    Rendered in {execution_time} seconds, using {memory_usage} of memory<br />
                    <a href="?_lang=el" class="full">GR</a> <a href="?_lang=en_US" class="full">EN</a>
                    [<a href="?_tpl=simple" class="full" ext:qtip="Change to a simple theme">simple</a>
                    <a href="?_tpl=dojo" class="full" ext:qtip="Change to a DOJO based theme">dojo</a>]
                    Copyright &copy;2009 hellassites
            </p>
    </div>
</div>
<script type="text/javascript">
    Ext.onReady(function() {
        Ext.state.Manager.setProvider(new Ext.state.CookieProvider());
        Ext.QuickTips.init();

        var header = new Ext.Panel({
            region:'north',
            margins:'5 0 5 5',
            height: 80,
            contentEl:'header'
        });

        var footer = new Ext.Panel({
            region:'south',
            margins:'5 0 5 5',
            height: 80,
            contentEl:'footer'
        });

        var menu = new Ext.Panel({
            region:'west',
            title:'Menu',
            split:true,
            collapsible: true,
            width: 200,
            minSize: 110,
            maxSize: 300,
            margins:'5 0 5 5',
            cmargins:'5 5 5 5',
            layout:'accordion',
            layoutConfig:{
                animate:true
            },
            items:[
                {
                margins:'5 5 5 0',
                cls:'empty',
                contentEl:'menu',
                title:'Main',
                autoScroll:true,
                border:false
            }]
            //contentEl:'menu'
        });

        var viewport = new Ext.Viewport({
            layout:'border',
            items:[
                header,menu,footer, {
                region:'center',
                margins:'5 5 5 0',
                cls:'empty',
                contentEl:'main_content'
            }]
        });

        Ext.get('overlayLoading').hide();
    });
</script>
</body>
</html>