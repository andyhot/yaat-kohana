<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Base controller for yaat.
 * TODO: move override options to config
 */
class Base_Controller extends Template_Controller {
    
    var $name;
    var $authlite;
    var $record_limit = 15;

	// Set the name of the template to use
	public $template = 'admin_templates/dojo/template';
    protected $contentTemplate = 'admin_templates/display_content';
	protected $admin_template_path = 'admin_templates/';
	protected $login_partial_template = 'admin_templates/login_partial';

        protected $admin_path = 'admin/';
        protected $login_page = 'admin/login';
        protected $after_login_page = 'admin/dashboard';

        private $NEXT_VAR = 'next';
        private $USER_VAR = 'user';

        protected $auth_name = 'cosmo_emps';
        protected $allow_edit = true;
        protected $theme = 'dojo';
        protected $bulk_actions = array('delete');

	public function __construct()
	{
            parent::__construct();            
        }

        protected function hasBulkActions() {
            return count($this->bulk_actions) > 0;
        }

        protected function myName() {
            if ($this->name=="") {             
                $this->name = URI::controller(FALSE);
            }
            return $this->name;
        }

        /**
         * Finds the model related to a controller.
         * @param string $controller
         */
        protected function toModel($controller) {
            return ucfirst(inflector::singular($controller))."_Model";
        }

        protected function toController($model) {
            return strtolower(inflector::plural(str_replace("_Model", "", $model) ));
        }

        protected function setupAuth() {
            $this->authlite = Authlite::instance($this->auth_name);
        }

        protected function logged_in() {
            return Authlite::session_logged_in($this->auth_name);
        }

        private function jsConfirm() {
            return "return confirm('".Kohana::lang('model.action-deleteask')."')";
        }
        
        protected function overrideRedirect($path) {
        	$_REQUEST['_overrideredirect'] = $path;
        }

        protected function create_view($view_name, $append_theme=FALSE) {
            if ($append_theme) {
                if ($this->$theme)
                    $view = new View($this->$theme.'/'.$view_name);
                else
                    $view = new View($view_name);
            } else if (is_array($view_name)) {
                if ($this->theme && array_key_exists($this->theme, $view_name)) {
                    $view = new View($view_name[$this->theme]);
                } else {
                    $view = new View(current($view_name));
                }
            } else {
                $view = new View($view_name);
            }
            $view->read_rights = $_SESSION[$this->USER_VAR]['read_rights'];
            return $view;
        }

        protected function create_filter_button($contr = NULL) {
        	$class = '';
        	if ($contr!=NULL) {
				$model = $this->toModel($contr);
				$modelInstance = new $model();
				$prefixes = $modelInstance->get_search_prefixes();
				if (count($prefixes)>0) {
					$class = ' class="filter-additional';
					foreach ($prefixes as $prefix) {
						$class .= ' filter-by-'.$prefix;
					}
					$class .= '"';
				}
        	}
        	if ($this->theme=='dojo') {			
            	return ($class=='' ? '' : '<input'.$class.' type="button" value="+" title="'.Kohana::lang('model.tooltip-extrafilter').'"/> ')
                	.'<button type="submit" dojoType="dijit.form.Button">'.Kohana::lang('model.action-filter').'</button>';
        	} else {
            	return ($class=='' ? '' : '<input'.$class.' type="button" value="+" title="'.Kohana::lang('model.tooltip-extrafilter').'"/> ')
                	.'<input type="submit" value="'.Kohana::lang('model.action-filter').'" />';        	
        	}        	
        }

        protected function viewAllowed($controller=NULL) {
            if (!$controller) $controller = $this->myName();
            $rights = $_SESSION[$this->USER_VAR]['read_rights'];
            if (!$rights) return false;
            if ($rights=='*') return true;
            return strpos($rights, $controller)!==false;
        }

        protected function editAllowed($controller=NULL) {
            if (!$controller) $controller = $this->myName();
            $rights = $_SESSION[$this->USER_VAR]['write_rights'];
            if (!$rights) return false;
            if ($rights=='*') return true;
            return strpos($rights, $controller)!==false;
        }

        protected function hierarchicalAllowed($model=NULL) {
            if (!$model) {
                $model = $this->toModel($this->myName());
            }
            $instance = new $model();
            return $instance->isHierarchical();
        }

        protected function doCheck() {
            return TRUE;
        }

        protected function check() {
            // Authlite instance
            $this->setupAuth();

            // login check
            if ( $this->doCheck() && !$this->authlite->logged_in() && Router::$method != 'login')
            {
                $this->redirect($this->login_page, TRUE);
            }

            $template_var = '_tpl';
            $lang_var = '_lang';
            $firephp_var = '_firephp';

            foreach(array($template_var, $lang_var, $firephp_var) as $var) {
                if (array_key_exists($var, $_GET)) {
                    $_SESSION[$var] = $_GET[$var];
                }
            }

            if (array_key_exists($template_var, $_SESSION)) {
                $this->theme = $_SESSION[$template_var];
                $this->template = new View($this->admin_template_path.$this->theme.'/template');
            } else {
                $this->theme = 'dojo';
            }
            if (array_key_exists($lang_var, $_SESSION)) {
                Kohana::config_set('locale.language.0', $_SESSION[$lang_var]);
            } else {
            	$yaat_config = Kohana::config('yaat');
            	if (isset($yaat_config['app.lang']))
            		Kohana::config_set('locale.language.0', $yaat_config['app.lang']);
            }
            
            if (array_key_exists($firephp_var, $_SESSION)) {
                Kohana::config_set('debug_toolbar.firephp_enabled', $_SESSION[$firephp_var]);
            }

            if ($this->allow_edit) {
                $this->allow_edit = $this->editAllowed();
            }
        }

        function login() {
            if (!empty($_POST)) {
                $this->setupAuth();
                $user = $this->authlite->login($_POST['username'], $_POST['password']);
                if ($user) {
                    $_SESSION[$this->USER_VAR] = $user->as_array();
                    if (array_key_exists($this->NEXT_VAR, $_POST)) {
                        $this->redirect($this->admin_path.$_POST[$this->NEXT_VAR]);
                    } else {
                        $this->redirect($this->after_login_page);
                    }
                }
            }
            if (Router::$controller!='admin') {
                $this->redirect($this->login_page);
            }
            if ($this->isLean()) {
                $this->only_output(new View($this->login_partial_template));
                return;
            }
            $this->initContentTemplate($this->login_partial_template, NULL);
            $this->template->content->output = "";
        }

        function logout() {
            $this->setupAuth();
            $this->authlite->logout(true);
            url::redirect("/");
        }

	function index($args=NULL) {
            $this->list_all($args);
	}

        protected function output($obj) {
            echo "<pre>".print_r($obj, TRUE)."</pre>";
        }
		
		/**
		 * Set the main content for this page.
		*/
		protected function setContent($content) {
			if (!isset($this->template->content))
				$this->initContentTemplate();
			$this->template->content->output = $content;
		}

        protected function initContentTemplate($content = NULL, $menu = '_') {
            if ($content==NULL) {
                $content = $this->contentTemplate;
            }

            if ($menu=='_') {
                $this->template->menu = $this->create_view(
                    array('' => 'admin_templates/simple/admin_menu', 
					'dojo' => 'admin_templates/dojo/admin_menu'));
            } else if ($menu!=NULL) {
                $this->template->menu = new View($menu);
            }

            $this->template->content = new View($content);            
            $this->template->title = Kohana::lang('model.title-'.$this->myName());
        }

        public function list_all($page=NULL) {
            $this->check();

            $this->initContentTemplate();
            $contr = $this->myName();
            $prefix = $this->admin_path;

            if (!$this->viewAllowed()) {
                if ($this->isJson()) {
                    $this->only_output(json_encode($this->jsonInit(false,
                            Kohana::lang('model.no-view-rights'))));
                } else {
                    $err = '<div class="boxy"><p>'.Kohana::lang('model.no-view-rights')." - "
                        .morehtml::anchor_l10n($prefix.URI::controller(FALSE).'/login', 'model.relogin')."</p></div>";
                    if ($this->isLean())
                        $this->only_output($err);
                    else
                        $this->template->content->output = $err;
                }
                return;
            }
            
            $model = $this->toModel($contr);
            
            $modelInstance = new $model();
            $modelInstance->orderby_search();
                        
            $is_search = array_key_exists('q', $_GET);
            $is_paging = array_key_exists('start', $_GET) || array_key_exists('count', $_GET);
            
            $limit = array_key_exists('count', $_GET) ? $_GET['count'] : $this->record_limit;
            if ($limit<1) $limit=1;
            
            if ($is_paging && array_key_exists('start', $_GET)) {
            	$page = 1 + ($_GET['start']/$limit);            	
            }
            if ($page<1) $page=1;
            
            if ($is_search) {
                if ($this->isJson() && !$is_paging)
                    $items = $modelInstance->filter_all($_GET['q']);
                else
                    $items = $modelInstance->filter_all($_GET['q'], $limit, ($page-1)*$limit);
            } else {
                $items = $modelInstance->find_all($limit, ($page-1)*$limit);
            }
            $total = $modelInstance->count_last_query();

            if ($this->isJson()) {
                $this->only_output($this->jsonViewList($items, $total, $page));
                return;
            }

            $pagination = NULL;
            if ($page!=1 || $items->count()==$limit) {
                $pagination = new Pagination(array(
                    'uri_segment'=>'index',
                    'total_items'=>$total,
                    'items_per_page'=>$limit
                ));
            }

            $output = $this->renderViewList($items, $contr, $this->allow_edit, $pagination, $total);
            if ($this->isLean()) {
                $this->only_output($output);
                return;
            }
            $this->template->content->output = $output;
        }

        public function delete($id = FALSE, $model = NULL, $redirect = NULL) {
            $this->check();

            $this->initContentTemplate();
            $contr = $this->myName();
            $prefix = $this->admin_path;

            if (!$this->editAllowed()) {
                if ($this->isJson()) {
                    $this->only_output(json_encode($this->jsonInit(false,
                            Kohana::lang('model.no-write-rights'))));
                } else {
                    $err = '<div class="boxy"><p>'.Kohana::lang('model.no-write-rights')." - "
                        .morehtml::anchor_l10n($prefix.URI::controller(FALSE).'/login', 'model.relogin')."</p></div>";
                    if ($this->isLean())
                        $this->only_output($err);
                    else
                        $this->template->content->output = $err;
                }
                return;
            }

            if (!empty($_POST) && $id) {
                if (!$model) {
                    $model = $this->toModel($contr);
                }
                $entity = new $model();
                $result = $entity->delete($id);

                $session = Session::instance();
                $session->set_flash('info', Kohana::lang('model.delete-success'));

                if ($redirect) {
                    $this->redirect($prefix.$contr."/edit/".$redirect);
                } else {
                    $this->redirect($prefix.$contr);
                }
                return;
            }

            $output = "Use 'POST'";
            $output .= "<p>".html::anchor($prefix.$contr, 'Back')."</p>";
            
            $this->template->content->output = $output;
        }

        /**
         * Processes the form submit (create/edit) of a model.
         * Override display by:
         * - process_edit_<entity>($id)
         * 
         * @return string the html
         */           
        public function edit($id = FALSE) {
            $this->check();

            $this->initContentTemplate();
            $contr = $this->myName();
            $prefix = $this->admin_path;

            if (!$this->editAllowed()) {
                if ($this->isJson()) {
                    $this->only_output(json_encode($this->jsonInit(false,
                            Kohana::lang('model.no-write-rights'))));
                } else {
                    $err = '<div class="boxy"><p>'.Kohana::lang('model.no-write-rights')." - "
                        .morehtml::anchor_l10n($prefix.URI::controller(FALSE).'/login', 'model.relogin')."</p></div>";
                    if ($this->isLean())
                        $this->only_output($err);
                    else
                        $this->template->content->output = $err;
                }
                return;
            }

            $model = $this->toModel($contr);
            
            if (!empty($_POST)) {
                $redirect = NULL;
                if (!array_key_exists('_cancel', $_POST)) {
                    if (array_key_exists('_model', $_POST)) {
                        $redirect = $id;
                        $model = $_POST['_model'];
                        $id = $_POST['id'];                        
                    }
                    if (array_key_exists('_delete', $_POST)) {
                        $this->delete($id, $model, $redirect);
                    } else if (array_key_exists('_tree', $_POST) 
                        && $this->hierarchicalAllowed($model)) {

                        $count = $_POST['_tree'];
                        for ($i = 0; $i < $count; $i++) {
                            $entity = new $model();                            
                            $entity->populateWithParent($_POST, $i);
                            $entity->save();
                        }

                        if ($this->isLean() || $this->isJson()) {
                            $this->only_output("");
                            return;
                        }
                    } else if (method_exists($this, 'process_edit_'.$model)) {
                        $fn = 'process_edit_'.$model;
                        $custom_output = $this->$fn($id);

                        $session = Session::instance();
                        $session->set_flash('info', $custom_output);
                    } else {
                        $entity = new $model($id);
                        $entity->populate($_POST, '', $_FILES);
                        $entity->save();

                        $session = Session::instance();
                        $session->set_flash('info',
                            Kohana::lang($id ? 'model.update-success':'model.insert-success'));
                    }
                }
                if (isset($_REQUEST['_skipredirect']) && $this->isLean()) {
                	$this->only_output('');
                	return;
                }
                
                if (isset($_REQUEST['_overrideredirect'])) {
                	$this->redirect($_REQUEST['_overrideredirect']);
                } else if ($redirect) {
                    $this->redirect($prefix.$contr."/edit/".$redirect);
                } else {
                    $this->redirect($prefix.$contr);
                }
                
                return;
            }

            $entity = new $model($id);//$this->output($entity->getProps());
            $output = $this->renderEdit($entity);

            if ($this->isLean()) {
                $this->only_output($output);
                return;
            }
            $this->template->content->output = $output;
        }

        public function view($id = FALSE) {
            $this->check();

            $this->initContentTemplate();
            $contr = $this->myName();
            $prefix = $this->admin_path;

            if (!$this->viewAllowed()) {
                if ($this->isJson()) {
                    $this->only_output(json_encode($this->jsonInit(false,
                            Kohana::lang('model.no-view-rights'))));
                } else {
                    $err = "<p>".Kohana::lang('model.no-view-rights')." - "
                        .morehtml::anchor_l10n($prefix.URI::controller(FALSE).'/login', 'model.relogin')."</p>";
                    $this->template->content->output = $err;
                }
                return;
            }

            $model = $this->toModel($contr);

            if (!$id) {
                $output = "Supply id";

                if ($this->isJson()) {
                    $this->only_output(
                        json_encode($this->jsonInit(false, $output)));
                    return;
                }
            } else {
                $item = new $model($id);

                if ($this->isJson()) {
                    $this->only_output($this->jsonView($item));
                    return;
                }
                $output = $this->renderView($item);
            }
            $output .= "<p>";
            if ($this->allow_edit) {
                    $output .= morehtml::anchor_l10n($prefix.$contr.'/edit/'.$id, 'model.action-edit', array('class'=>'edit-item'))
                    ." | ";
            }
            $output .= morehtml::anchor_l10n($prefix.$contr, 'model.action-back', array('class'=>'back go-back'))
                    ."</p>";

            if ($this->isLean()) {
                $this->only_output($output);
                return;
            }            
            $this->template->content->output = $output;
        }

        public function bulk() {
            $this->check();

            $this->initContentTemplate();
            $contr = $this->myName();
            $prefix = $this->admin_path;

            if (!$this->editAllowed()) {
                if ($this->isJson()) {
                    $this->only_output(json_encode($this->jsonInit(false,
                            Kohana::lang('model.no-write-rights'))));
                } else {
                    $err = '<div class="boxy"><p>'.Kohana::lang('model.no-write-rights')." - "
                        .morehtml::anchor_l10n($prefix.URI::controller(FALSE).'/login', 'model.relogin')."</p></div>";
                    if ($this->isLean())
                        $this->only_output($err);
                    else
                        $this->template->content->output = $err;
                }
                return;
            }

            $found_action = null;
            foreach ($this->bulk_actions as $action) {
                if (array_key_exists('bulk_'.$action, $_POST)) {
                    $found_action = $action;
                    break;
                }
            }
            
            $session = Session::instance();            
            
            if (!$found_action) {                
                $session->set_flash('info', Kohana::lang('model.bulk-noaction'));                
            } else {
                $selection = $this->cookieSelectionValue($contr);
                if ($selection) {
                    $fn = 'bulk_'.$found_action;
                    $this->$fn($selection);
                    return;                    
                } else {
                    $session->set_flash('info', 
                        Kohana::lang('model.bulk-noselection', Kohana::lang('model.title-'.$contr)));
                }
            }

            $output = '';
            if ($this->isLean()) {                
                $this->only_output($output);
            } else {
                $this->template->content->output = $output;
            }
        }

        protected function bulk_delete($selection) {
            $contr = $this->myName();
            $model = $this->toModel($contr);

            $message = '';
            $about = Kohana::lang('model.title2-'.$contr);
            if (array_key_exists('all', $selection)) {
                $filter = $selection['all'];
                if (strlen($filter)==0) {
                    $entity = new $model();
                    $entity->delete_all();
                    $message = Kohana::lang('model.bulk-delete-all', $about);
                } else {
                    $entity = new $model();
                    $entity->build_filter($filter)->delete_all();
                    $message = Kohana::lang('model.bulk-delete-filter', $about, $filter);
                }
            } else {
                $items = $selection['items'];
                $entity = new $model();
                $entity->delete_all($items);
                $message = Kohana::lang('model.bulk-delete-some', count($items), $about);
            }

            $this->resetCookieSelectionValue($contr);

            $session = Session::instance();
            $session->set_flash('info', $message);

            $prefix = $this->admin_path;
            $this->redirect($prefix.$contr);
        }        

	    protected function bulk_enable($selection) {
	        $contr = $this->myName();
	        $l10n_key = inflector::singular($contr);
	        
	        $message = '';                
	
	        $data = array('disabled' => false);
	        $model = $this->toModel($contr);
	
	        if (array_key_exists('all', $selection)) {
	            $filter = $selection['all'];
	            if (strlen($filter)==0) {
	                $entity = new $model();
	                $entity->update_all($data);
	                $message = Kohana::lang('model.bulk-enable-all-'.$l10n_key);
	            } else {
	                $entity = new $model();
	                $entity->build_filter($filter)->update_all($data);
	                $message = Kohana::lang('model.bulk-enable-filter-'.$l10n_key, $filter);
	            }
	        } else {
	            $items = $selection['items'];
	            $entity = new $model();
	            $entity->update_all($data, $items);
	            $message = Kohana::lang('model.bulk-enable-some-'.$l10n_key, count($items));
	        }
	
	        $session = Session::instance();
	        $session->set_flash('info', $message.' ');
	        
	        // TODO: ideally redirect to originating page
        	$this->redirect($this->admin_path.'/'.$contr);
    	}
		
		protected function bulk_disable($selection) {
	        $contr = $this->myName();
	        $l10n_key = inflector::singular($contr);
	        
	        $message = '';                
	
	        $data = array('disabled' => true);
	        $model = $this->toModel($contr);
	
	        if (array_key_exists('all', $selection)) {
	            $filter = $selection['all'];
	            if (strlen($filter)==0) {
	                $entity = new $model();
	                $entity->update_all($data);
	                $message = Kohana::lang('model.bulk-disable-all-'.$l10n_key);
	            } else {
	                $entity = new $model();
	                $entity->build_filter($filter)->update_all($data);
	                $message = Kohana::lang('model.bulk-disable-filter-'.$l10n_key, $filter);
	            }
	        } else {
	            $items = $selection['items'];
	            $entity = new $model();
	            $entity->update_all($data, $items);
	            $message = Kohana::lang('model.bulk-disable-some-'.$l10n_key, count($items));
	        }
	
	        $session = Session::instance();
	        $session->set_flash('info', $message.' ');
	        
	        // TODO: ideally redirect to originating page
        	$this->redirect($this->admin_path.'/'.$contr);
    	}

        /**
         * Return a hash of the selection for this controller,
         * or NULL if nothing exists. Hash should contain:
         * - a flag with filter if exists (key: all), and
         * - array of individual selections (key: items).
         * @param <type> $contr
         * @return <type>
         */
        private function cookieSelectionValue($contr) {
            if (!array_key_exists('sel', $_COOKIE))
                return NULL;

            $sel_all_json = $_COOKIE['sel'];
            $sel_all = json_decode($sel_all_json, TRUE);
            
            if (!array_key_exists($contr, $sel_all))
                return NULL;

            $raw = $sel_all[$contr];

            $items = explode('|', $raw);
            $all = FALSE;
            $final_items = array();
            foreach ($items as $item) {
                $pos = strpos($item, 'all:');
                if ($pos!==FALSE) {
                    $all = substr($item, 4);
                    if ($all===FALSE) $all='';
                } else if (strlen($item)>0) {
                    $final_items[] = $item;
                }
            }
            
            $val = array('items' => $final_items);
            if ($all!==FALSE) $val['all'] = $all;

            return $val;
        }

        /**
         * Reset selection (from cookies) for given controller.
         * @param <String> $contr
         */
        private function resetCookieSelectionValue($contr) {
            if (!array_key_exists('sel', $_COOKIE))
                return;

            $sel_all_json = $_COOKIE['sel'];
            $sel_all = json_decode($sel_all_json, TRUE);

            if (!array_key_exists($contr, $sel_all))
                return;

            unset($sel_all[$contr]);
            $sel_all_json = json_encode($sel_all);
            if ($sel_all_json=='[]') $sel_all_json = '{}';
            setcookie('sel', $sel_all_json, null, '/' );
        }

        private function jsonView($item) {
            $json = $this->jsonInit(true);
            $json['data'] = $item->getValues();
            return json_encode($json);
        }

        /**
         * Renders the view of a model.
         * Override display by:
         * - render_main_view_start_<entityname>($object)
         * - render_main_view_end_<entityname>($object)
	 	 * - render_view_<relation>($object, $relation)
         * 
         * @param string $item the model to render
         * @return string the html
         */
        protected function renderView($item, $fk=NULL, $view_props=NULL) {
            $output = '<div class="dataview">';
            $prefix = $this->admin_path;
            if (!$fk)
                $output .= "<h2>{$item->toDisplay()}</h2>";
            if (method_exists($this, 'render_main_view_start_'.$item->object_name)) {
                $fn = 'render_main_view_start_'.$item->object_name;
                $output .= $this->$fn($item);
            }
            $oddeven = true;
            if (!$view_props) $view_props = $item->getProps(FALSE);
            foreach ($view_props as $key => $value) {
                if ($key==$fk) continue;
                $display = $item->$key;
                $type = $value['type'];
                if ($type=='boolean') {
                    $display = Kohana::lang('model.bool-'.($display==TRUE?'t':'f'));
                } else if ($type=='foreign' && $display) {
                    $foreign = $value['foreign'];
                    $rel = ORM::factory($foreign, $display);
                    if ($rel->id) {
                        $ctrl = inflector::plural($foreign);
                        $display = html::anchor($prefix.$ctrl."/view/".$display, $rel->toDisplay());
                    } else {
                        $display = " - ";
                    }
                } else if ($type=='image') {
                    if (trim($display)=="") $display = "-";
                    else {
                        $img_path = url::base(FALSE).'files/'.$display;
                        $display = '<a href="'.$img_path.'" target="_blank" class="img-preview"><img height="75" src="'.$img_path.'" class="noasync" /></a> '.$display;
                    }
                } else if ($type=='lov') {
                	$lov_prefix = array_key_exists('prefix', $value) ? $value['prefix'] : '';
                	$display = Kohana::lang('model.lov-'.$lov_prefix.$display);
                } else if ($type=='password') {
                	$display = '<span title="'.html::specialchars($display).'">***</span>';                	
                } else if ($type=='html') {
                	// keep as is
                } else {
                	// all other text needs escaping
                	$display = html::specialchars($display);
                }
                if (trim($display)=="") $display = "&nbsp;";
                $output .= "<div class=\"".($oddeven?"odd":"even")."\"><label>".Kohana::lang('model.label-'.$key)."</label> ".$display."</div>";
                $oddeven = !$oddeven;
            }
            if (method_exists($this, 'render_main_view_end_'.$item->object_name)) {
                $fn = 'render_main_view_end_'.$item->object_name;
                $output .= $this->$fn($item);
            }            
            $output .= "</div>";

            $relations = $item->getManyRelations();

            foreach ($relations as $relation) {
                $title = Kohana::lang('model.title-'.$relation);
                $output .= "<div>";
                $output .= "<h3>$title</h3>";
                // check for override in rendering view relations
                if (method_exists($this, 'render_view_'.$relation)) {
                    $fn = 'render_view_'.$relation;
                    $output .= $this->$fn($item, $relation);
                } else {
                    $values = $item->$relation;
                    if ($values->count()==0) {
                         $output .= "<p>".Kohana::lang('model.notfound', $title)."</p>";
                    } else {
                        $fk = $item->object_name.'_id';
                        foreach ($values as $value) {
                            $output .= $this->renderView($value, $fk);
                            $output .= "<br />";
                        }
                    }
                }

                $output .= "</div>";
            }
            
            return $output;
        }

        /**
         * Renders the edit form of a model.
         * Override display by:
         * - render_edit_<relation>($object, $relation)
         * 
         * @return string the html
         */        
        protected function renderEdit($entity, $model=NULL, $fk=NULL) {
            $id = $entity->id;
            $suffix = $model ? '_'.$id : NULL;
            $action_label = ($id == FALSE) ?
                Kohana::lang('model.action-create') : Kohana::lang('model.action-update');
            $all_props = $entity->getProps();
            $output = "";            

            if ($this->theme=='dojo' && !$fk) {
                $contr = $model ? $this->toController($model) : $this->myName();
                $title_label = $action_label.' '.Kohana::lang('model.title2-'.$contr)
                    .(($id == FALSE) ? '' : ": '".$entity->toDisplay()."'");

                $output .= '<div dojoType="dijit.TitlePane" title="'.$title_label.'" open="true">';
            }
            $output .= '<div class="dataedit">';

            $has_file = FALSE;
            foreach ($all_props as $key => $value) {
                if ($value['type']=='image') {
                    $has_file = TRUE;
                    break;
                }
            }
            if ($has_file) {
                $action = Router::$complete_uri;
                $action = str_replace('lean=true', '', $action);
                $output .= form::open_multipart($action, array('class'=>'beanform noasync'));
            } else {
                $output .= form::open(NULL, array('class'=>'beanform'));
            }

            $hiddens = array('id' => $id);
            if ($model) {
                $hiddens['_model'] = $model;
                $hiddens[$fk] = $entity->$fk;
            }
            $output .= moreform::propHiddens($hiddens);
            $oddeven = true;
            foreach ($all_props as $key => $value) {
                if ($key==$fk) continue;
                $value['theme'] = $this->theme;
                $output .= moreform::propEdit($key, $entity, Kohana::lang('model.label-'.$key), 
                    $value, $suffix, $oddeven?"odd":"even");
                $oddeven = !$oddeven;
            }

            $output .= '<div class="formbuttons">';
            $output .= $this->renderEdit_formbuttons($model, $id, $suffix, $action_label);            
            $output .= '<div class="clr"> </div>';
            $output .= "</div>";

            $output .= form::close();
            $output .= "</div>";
            if ($this->theme=='dojo' && !$fk) {
                $output .= '</div>';
            }

            if ($id) {
                $relations = $entity->getManyRelations();
                $fk = $entity->object_name.'_id';

                foreach ($relations as $relation) {
                    $output .= '<div class="clr"> </div>';
                    $title = Kohana::lang('model.title-'.$relation).' '.($entity->toDisplay());
                    if ($this->theme=='dojo') {
                        $output .= '<div dojoType="dijit.TitlePane" title="'.$title.'">';
                    } else {
                        $output .= "<div>";
                        $output .= "<h3>$title</h3>";
                    }
                    //$output .= "<h4>render_edit_$relation</h4>";
                    // check for override in rendering edit relations
                    if (method_exists($this, 'render_edit_'.$relation)) {
                        $fn = 'render_edit_'.$relation;
                        $output .= $this->$fn($entity, $relation);
                    } else {
                        $relatedModel = $this->toModel($relation);
                        // for editing existing
                        $values = $entity->$relation;
                        if ($values->count()>0) {
                            foreach ($values as $value) {
                                $output .= $this->renderEdit($value, $relatedModel, $fk);
                                $output .= '<div class="clr"> </div>';
                            }
                        }
                        // for adding new
                        $relatedEntity = new $relatedModel();
                        if (array_key_exists($fk, $relatedEntity->table_columns)) {
                            $relatedEntity->$fk = $id;
                            $output .= $this->renderEdit($relatedEntity, $relatedModel, $fk);
                        }
                    }

                    $output .= "</div>";
                }
            }

            return $output;
        }
        
        protected function renderEdit_formbuttons($model, $id, $suffix, $action_label) {
            $output = moreform::propSubmit('_submit', $action_label, $suffix, 'class="save-item"');
            if ($model && $id) {
                $output .= moreform::propSubmit('_delete',
                    Kohana::lang('model.action-delete'),
                    $suffix,
                    'onclick="'.$this->jsConfirm().'" class="delete-item"');
            }
            if (!$model) {
                $output .= moreform::propSubmit('_cancel', Kohana::lang('model.action-cancel'),
                    $suffix, 'class="back cancel-item"');
            }
            return $output;        
        }

        private function jsonViewList($items, $total, $page) {
            $json = $this->jsonInit(true);
            $vals = array();
            foreach ($items as $thisItem) {
                $vals[] = $thisItem->getJsonValues();
            }
            // TODO: add better way to 'know' label
            $json['label']='title';
            $json['total']=$total;
            $json['page']=$page;
            $json['count']=count($items);
            $json['items']=$vals;
            return json_encode($json);
        }

        private function renderViewList($items, $contr, $linkToNew = TRUE, $pagination = NULL, $total = NULL) {
            $prefix = $this->admin_path;
            $search = array_key_exists('q', $_GET) ? $_GET['q'] : '';
            $bulk = $this->hasBulkActions() && $this->allow_edit;

            $output = '<div class="filterpane">';
            
            if ($bulk) {
                $output .= '<div class="selectpane">'.Kohana::lang('model.select-label').': '
                    .'<a href="#" class="noasync select-all">'.Kohana::lang('model.select-all').'</a>, '
                    .'<a href="#" class="noasync select-none">'.Kohana::lang('model.select-none').'</a>';
                if ($total && $total > $items->count()) {
                    $titleAttr = $search ? ' title="'.Kohana::lang('model.select-filter', $search).'"' : '';
                    $output .= ' <a href="#" rel="'.$search.'" class="noasync select-everything"'.$titleAttr.' style="display:none;">';
                    $item_name_ref = Kohana::lang('model.title2-'.$contr);
                    $output .= Kohana::lang('model.select-everything', $total, $item_name_ref);
                    $output .= '</a>';
                    $output .= '<span'.$titleAttr.' style="font-weight:bold;display:none;" class="select-everything-true">';
                    $output .= Kohana::lang('model.title-selected-'.$contr, $total);
                    $output .= '</span>';                    
                }
                $output .= form::open($prefix.$contr.'/bulk', array('method'=>'post'));
                $output .= "<input type=\"hidden\" name=\"_submit\" value=\"dummy\" />";
                foreach ($this->bulk_actions as $action) {
                    $action_label = Kohana::lang('model.bulk-'.$action);
                    $output .= " <input type=\"submit\" name=\"bulk_$action\" class=\"bulk_$action\" value=\"$action_label\" />";
                }
                $output .= form::close();

                $output .= '</div>';
            }

            $output .= '<div class="filter">';
            $output .= form::open($prefix.$contr, array('method'=>'get'));
            $output .= form::input(array('name'=>'q', 'id'=>'search_'.$contr), $search, 'class="filter-items"');
            $output .= $this->create_filter_button($contr);
            $output .= form::close();
            $output .= '</div>';
            $output .= '<div class="clr"></div>';
            $output .= '</div>';
            
            $output .= $items->count()==0 ? "<div class=\"viewreport odd\">".Kohana::lang('model.notfound',
                Kohana::lang('model.title-'.$contr))."</div>" : '<div><table class="datatable" cellspacing="0" cellpadding="0">';
            $oddeven = true;            
            foreach ($items as $item) {
                $id = $item->id;
                $output .= "<tr class=\"".($oddeven?"odd":"even")."\">";
                if ($bulk) $output .= "<td class=\"select_cell\"><input class=\"selector\" type=\"checkbox\" name=\"".$contr."_".$id."\"/></td>";
                $output .= "<td class=\"info_cell\">"
                    .html::anchor($prefix.$contr.'/view/'.$id, $item->toDisplay())
                    ."<span class=\"summary_line\">".($item->toSummaryDisplay())."</span>"
                    ."</td>";
                
                if ($this->allow_edit) {
                    $output .= "<td class=\"action_cell\">";
                    $output .= morehtml::anchor_l10n($prefix.$contr.'/edit/'.$id, 'model.action-edit', array('class'=>'edit-item'))
                        //." | ".morehtml::anchor_l10n($prefix.$contr.'/delete/'.$id, 'model.action-delete')
                        ." | ".moreform::formWithSubmit($prefix.$contr.'/delete/'.$id,
                        array('name'=>'submit', 'id'=>'submit'.$id, 'class'=>'deleteBtn delete-item'),
                        Kohana::lang('model.action-delete'),
                        array("onclick"=>$this->jsConfirm()) );
                    $output .= "</td>";
                }
                
                $output .= "</tr>";
                $oddeven = !$oddeven;
            }
            if ($items->count()!=0) {
                $output .= "</table>";
            }
            if ($pagination) {
                $output.=$pagination->render('digg');
            }
            if ($linkToNew) {
                $output .= "<hr /><div>"
                    .morehtml::anchor_l10n($prefix.$contr.'/edit', 'model.action-new', array('class'=>'create-new'))."</div>";
            }
            $output .= "</div>";
            return $output;
        }

	/**
	 * Displays a list of available actions in the controller
	 */
        protected function showActions($content = NULL, $menu = NULL)
	{
            if (get_class($this)==get_class()) {
                return;
            }
            $this->initContentTemplate('admin_templates/display_actions');


            // Get the methods that are only in this class and not the parent class.
            $options = array_diff
            (
                    get_class_methods(get_class($this)),
                    get_class_methods(get_parent_class($this))
            );

            sort($options);

            $contrName = $this->myName();
            $actions = array();
            foreach ($options as $method)
            {
                if ($method == __FUNCTION__)
                        continue;

                $actions[$method] = $contrName.'/'.$method;
            }

            $this->template->content->actions = $actions;
	}

        protected function jsonInit($ok, $problem=NULL) {
            $result = array('ok'=>$ok);
            if ($problem) {
                $result['error'] = $problem;
            }
            return $result;
        }

        protected function isJson() {
            return array_key_exists('json', $_GET) || array_key_exists('json', $_POST);
        }

        protected function isLean() {
            return array_key_exists('lean', $_GET) || array_key_exists('lean', $_POST);
        }

        /**
         * Makes the given text, the only thing sent to the client. However, if the request
         * is lean, adds the current/new page title and any flash messages.
         *
         * @param <String> $output If output is empty, clientside javascipt will not
         * clear the page... useful for just sending status messages.
         */
        protected function only_output($output) {
            $this->auto_render = FALSE;
            echo $output;
            
            if ($this->isLean()) {                
                if ($this->template->is_set('title')) {
                    echo '<span id="newtitle" style="display:none" class="'.$this->myName().'">'
                        .html::specialchars($this->template->title).'</span>';
                }
                $session = Session::instance();
                $msg = $session->get_once('info');
                if ($msg)
                    echo '<span id="newflash" style="display:none">'.$msg.'</span>';
            }
        }

        /**
         * Redirects to a page. Ensures that if current request is lean, the next one
         * is also lean.
         * 
         * @param <String> $to where to go
         * @param <boolean> $addFrom whether to add originating control... useful to
         * support redirect back
         */
        protected function redirect($to, $addFrom=FALSE) {
            if ($this->isLean()) {
                $to = moreform::addQueryParameter($to, 'lean', 'true');
            }
            if ($addFrom) {
                $to = moreform::addQueryParameter($to, $this->NEXT_VAR, $this->myName());
            }
            url::redirect($to);
        }
}
