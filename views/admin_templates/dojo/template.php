<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<?php defined('SYSPATH') OR die('No direct access allowed.');
	$config  = Kohana::config('yaat');
    $js_root_path = $config['app.js.dojo'];
    $js_xdomain = $config['app.js.dojo.xdomain'];
    $js_namespaces = $config['app.js.dojo.namespaces'];
    $js_requires = $config['app.js.dojo.requires'];
    $js_theme = $config['app.js.dojo.theme'];
    $js_extra = $config['app.js.extra'];
    $css_extra = $config['app.css.extra'];
?>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php echo $config['app.name']?> | <?php echo html::specialchars($title) ?></title>
    <link media="screen" rel="stylesheet" type="text/css" 
        href="<?php echo $js_root_path ?>/dojo/resources/dojo.css" />
    <link media="screen" rel="stylesheet" type="text/css" 
        href="<?php echo $js_root_path ?>/dijit/themes/dijit.css" />
    <?php if ($js_theme=='claro'){?><link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dijit/themes/claro/claro.css" /><?php }?>        
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dijit/themes/tundra/tundra.css" />
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dijit/themes/soria/soria.css" />
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dijit/themes/nihilo/nihilo.css" />
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dojox/grid/resources/tundraGrid.css" />
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dojox/layout/resources/ExpandoPane.css" />
    <link media="screen" rel="stylesheet" type="text/css"
        href="<?php echo $js_root_path ?>/dojox/layout/resources/ResizeHandle.css" />
    <link type="text/css" rel="stylesheet" href="<?php echo url::base(TRUE).'yaat/css/main_dojo.css'?>" />
    <?php if ($css_extra){?><link type="text/css" rel="stylesheet" href="<?php echo $css_extra ?>" /><?php }?>

    <?php if ($js_extra){?><script type="text/javascript" src="<?php echo $js_extra ?>"></script><?php }?>
    <script type="text/javascript">
        djConfig = {
            parseOnLoad: false,
            locale: '<?php echo Kohana::config('locale.language.0')?>',
            preventBackButtonFix: false,
            <?php if ($js_xdomain){?>baseUrl:'<?php echo url::base(FALSE, 'http') ?>js/',<?php }?>            
            modulePaths: {'dsms': '<?php echo url::base(TRUE).'yaat/js'?>'<?php if ($js_namespaces) echo ', '.$js_namespaces?>},
            dojoIframeHistoryUrl:'<?php echo url::base(FALSE, 'http') ?>iframe_history.html',
            dojoBlankHtmlUrl:'<?php echo url::base(FALSE, 'http') ?>blank.html'
        };
    </script>        
    <script type="text/javascript" src="<?php echo $js_root_path ?>/dojo/dojo<?php if ($js_xdomain) echo '.xd';?>.js"></script>
    <script type="text/javascript" src="<?php echo $js_root_path ?>/dojo/back.js"></script>
    
    <script type="text/javascript">
    dojo.require("dijit.Dialog");
    dojo.require("dijit.ProgressBar");
    dojo.require("dijit.Menu");
    dojo.require("dijit.Tree");
    dojo.require("dijit.Tooltip");
    dojo.require("dijit.TitlePane");
    dojo.require("dijit._tree.dndSource");
    dojo.require("dojo.dnd.Manager");
    dojo.require("dijit.Editor");
    dojo.require("dijit._editor.plugins.LinkDialog");
    dojo.require("dijit._editor.plugins.TextColor");
    dojo.require("dijit._editor.plugins.FontChoice");
    dojo.require("dijit._editor.plugins.AlwaysShowToolbar");
    //dojo.require("dijit._editor.plugins.ViewSource");// only from 1.4
    dojo.require("dijit.layout.AccordionContainer");
    dojo.require("dijit.layout.BorderContainer");
    dojo.require("dijit.layout.SplitContainer");
    dojo.require("dijit.layout.ContentPane");
    dojo.require("dijit.form.Form");
    dojo.require("dijit.form.Button");
    dojo.require("dijit.form.CheckBox");
    dojo.require("dijit.form.FilteringSelect");
    dojo.require("dijit.form.MultiSelect");
    dojo.require("dijit.form.DateTextBox");
    
    dojo.require("dojox.layout.ExpandoPane");

    dojo.require("dsms.base");
    dojo.require("dsms.data.ItemFileReadStore");
    dojo.require("dsms.data.ItemFileWriteStore");
    dojo.require("dsms.tree.RefreshingForestModel");
    dojo.require("dsms.widgets.FilteringSelect");

    dojo.require("dsms.data.CombiningReadStore");
    dojo.require("dsms.data.CombiningQueryReadStore");

    <?php if ($js_requires) {foreach($js_requires as $js_require){?>
    dojo.require("<?php echo $js_require?>");
    <?php }}?>

    window.dsmsMsg = {
        isRequired: '<?php echo Kohana::lang('model.is-required', '***') ?>',
        isNumber: '<?php echo Kohana::lang('model.is-number', '***') ?>',
        isPositiveNumber: '<?php echo Kohana::lang('model.is-positive-number', '***') ?>'
    };
        
    </script>        
</head>
<body class="<?php echo $js_theme?>">
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
    <div id="menu" dojoType="dojox.layout.ExpandoPane" region="left" splitter="true" 
        title="Menu" maxWidth="300" style="width:160px;">
        <div dojoType="dijit.layout.AccordionContainer">
        <?php if(isset($menu)) echo $menu ?>
        </div>
    </div>
    <div id="content" dojoType="dijit.layout.ContentPane" region="center"
            orientation="horizontal" sizerWidth="7" activeSizing="true">
        <div id="overlayLoading">Loading...</div>
        <div id="main_content" dojoType="dijit.layout.ContentPane" sizeMin="50" sizeShare="70">
	<?php echo $content ?>
        </div>
    </div>
    <div id="footer" dojoType="dojox.layout.ExpandoPane" region="bottom" splitter="true" maxHeight="100"
        title="Copyright &copy;2009 <?php echo $config['app.copyright'];?>" startExpanded="false">
            <p class="copyright">
                    <a href="?_lang=el" class="full">GR</a> <a href="?_lang=en_US" class="full">EN</a>                    
                    [<a href="?_tpl=simple" class="full">simple</a>
                    <a href="?_tpl=ext" class="full">ext</a>]
                    [<a href="#" class="full switch-bodyclass">claro</a> |
                     <a href="#" class="full switch-bodyclass">soria</a> |
                     <a href="#" class="full switch-bodyclass">tundra</a> |
                     <a href="#" class="full switch-bodyclass">nihilo</a>]
            </p>
    </div>
</div>
<script type="text/javascript">
    dojo.back.init();
    dojo.addOnLoad( function() {
        dsms.base.onLoad();
    });
</script>
</body>
</html>