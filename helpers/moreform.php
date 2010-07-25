<?php defined('SYSPATH') or die('No direct script access.');
/**
 * form Class
 */
class moreform {

    /**
     * Return html with a label and an input (or textarea) box.
     *
     * @param   string $name  property to edit
     * @param   string $entity   entity that's being edited
     * @param   string $label   the label to display for the property
     * @param   array $prop    additional properties (like type, length, e.t.c.) for the property
     * @param   string $idSuffix Adds a suffix to the generated ids.
     * @param   string $extra additional class to add to the containing div.
     * @return string
     */
    public static function propEdit($name, &$entity, $label, $prop = array(), $idSuffix = NULL, $extra = "") {
    	$value = $entity->$name; 
    	
        $id = $idSuffix ? $name.$idSuffix : $name;

        $dojo = $prop['theme']=='dojo';
        $ext = $prop['theme']=='ext';
        
        $type = $prop['type'];
        $constraints = '';
        if (!array_key_exists('null', $prop) || !$prop['null']) {
            $constraints .= ' required';
        }
        $cssClass = $type ? 'class="'.$type.'"' : '';
        $value = $value ? $value : '';
        $output = "<div class=\"$type$constraints $extra\">".form::label($id, $label);
        if(array_key_exists('edit_override', $prop) && $dojo) {
        	$fn = $prop['edit_override'];
        	$output .= $entity->$fn($name, $id);        	        	
    	} else if ($type=='csv' && array_key_exists('ext', $prop)) {            
            $name = $name."[]";
            $ext = $prop['ext'];
            $output .= '<div class="csvvalues">';
            $output.=form::checkbox(array('name'=>$name, 'id'=>$id."__all", 'class'=>'chkbox'), "_all", $value=="*" );
            $output.=form::label($id."__all", Kohana::lang('model.csv-all'));
            foreach($ext as $item) {
                $this_id = $id."_".$item;
                $output.=form::checkbox(array('name'=>$name, 'id'=>$this_id, 'class'=>'chkbox'), $item, strpos($value, $item)!==false );
                $output.=form::label($this_id, Kohana::lang('model.csv-'.$item));
            }
            $output .= "</div>";
        } else if ($type=='lov' && array_key_exists('ext', $prop)) {
            $ext = $prop['ext'];
        	$output .= '<div class="lovvalues">';
        	$first = true;
            foreach($ext as $item) {
                $this_id = $id."_".$item;
                $output.=form::radio(array('name'=>$name, 'id'=>$this_id, 'class'=>'radio'), $item, 
                	($value == $item) || ($first && $value=='') );
                $output.=form::label($this_id, Kohana::lang('model.lov-'.$item));
                //$output.='['.$item.']';
                $first = false;
            }        	
        	$output .= "</div>";
        } else if ($type=='html') {
            if ($dojo)
                $output.= "<div ".html::attributes(array('id'=>$id,
                        'dojoType'=>'dijit.Editor', 'height'=>'', 
                        'extraPlugins'=>"['formatBlock', 'foreColor', '|', 'createLink', 'insertImage', 'dijit._editor.plugins.AlwaysShowToolbar']")) // viewsource
                    .' '.$cssClass.">$value</div>";
            else
                $output.= form::textarea(array('name'=>$name, 'id'=>$id), $value, $cssClass);
        } else if ($type=='date') {
            if ($dojo) {
            	if ($value) {
            		$dval = date_parse($value);
            		$value = $dval['year'].'-'.str_pad($dval['month'], 2, '0', STR_PAD_LEFT).'-'.str_pad($dval['day'], 2, '0', STR_PAD_LEFT);
            	} 
                $output.= form::input(array('name'=>$name, 'id'=>$id, 'dojoType'=>'dijit.form.DateTextBox'), 
                	$value, $cssClass);
            }
            else
                $output.= form::input(array('name'=>$name, 'id'=>$id), $value, $cssClass);
        } else if ($type=='string'
            && (!array_key_exists('length', $prop) || $prop['length']>299)) {
            $output.= form::textarea(array('name'=>$name, 'id'=>$id, 'rows'=>8), $value, $cssClass);
        } else if ($type=='boolean') {
            $output.= form::checkbox(array('name'=>$name, 'id'=>$id, 'class'=>'chkbox'), "1", $value==TRUE);
        } else if ($type=='foreign') {
            $foreign = $prop['foreign'];
            $rel = ORM::factory($foreign);
            $rel->orderby_search();
            $data = $rel->select_list(NULL, $rel->get_search_column());
            $data['']='-';
            $output.= form::dropdown(array('name'=>$name, 'id'=>$id, 'dojoType'=>'dijit.form.FilteringSelect', 
                'required'=>'false', 'autocomplete'=>'false', 'class'=>'mediumSelect'), $data, $value);
        }  else if ($type=='image') {
            $output.= form::upload(array('name'=>$name, 'id'=>$id), $value, $cssClass);
            if ($value) $output.= '<img height="75" src="'.url::base(FALSE).'files/'.$value.'" />';
        }  else {
            $output.= form::input(array('name'=>$name, 'id'=>$id), $value, $cssClass);
        }
        $output.= '<div class="clr"> </div></div>';
        return $output;
    }

    public static function propHiddens($data, $value = '') {
        return "<div style='display:none'>".form::hidden($data, $value)."</div>";
    }

    public static function propSubmit($name = '', $value = '', $idSuffix = NULL, $extra = '') {
        $id = $idSuffix ? $name.$idSuffix : $name;
        return form::submit(array('name'=>$name, 'id'=>$id), $value, $extra);
    }    
    
    public static function prop_multilist($entity, $relation, $values = NULL, $id = NULL, $url = NULL) {
        if (!$values &&  !is_array($values))
        	$values = $entity->$relation;
        if (!$id)
        	$id = $entity->id;
        	
        if (is_array($relation)) {
        	$data = $relation['data'];
        	$relation = $relation['name'];        	
        }
        		
        $output = '<div class="multi">';
        $output .= form::open($url, array('class'=>'customform'));
        $hiddens = array('id' => $id, '_model' => $relation);
        $output .= moreform::propHiddens($hiddens);

        if (!isset($data)) {
	        $rel = ORM::factory(inflector::singular($relation));
	        $rel->orderby_search();
	        $data = $rel->select_list(NULL, $rel->get_search_column());
        }
        
        $output.= '<div class="available">';
        $output.= '<label>'.Kohana::lang('model.multi-available').'</label><br>';
        $output.= form::dropdown(array('id'=>"avail_$id", 'dojoType'=>'dijit.form.MultiSelect',
            'required'=>'false', 'autocomplete'=>'false', 'multiple'=>'true'), $data);
        $output .= "</div>";

        $output .= '<div class="leftRightButtons">';
        $output .= '<button id="switchleft_'.$id.'" class="switch" title="'.Kohana::lang('model.multi-remove').'">&lt;</button>';
        $output .= '<button id="switchright_'.$id.'" class="switch" title="'.Kohana::lang('model.multi-add').'">&gt;</button>';
        $output .= '<br><br><button id="moveup_'.$id.'" class="mover" title="'.Kohana::lang('model.multi-moveup').'">'.Kohana::lang('model.multi-up').'</button><br>';
        $output .= '<button id="movedown_'.$id.'" class="mover" title="'.Kohana::lang('model.multi-movedown').'">'.Kohana::lang('model.multi-down').'</button>';
        $output .= '</div>';

        $selected = array();
        foreach ($values as $value) {
            $selected[$value->id] = $value->toDisplay();
        }
        $output.= '<div class="selected">';
        $output.= '<label>'.Kohana::lang('model.multi-selected').'</label><br>';
        $output.= form::dropdown(array('name'=>'selected[]', 'id'=>"sel_$id", 'dojoType'=>'dijit.form.MultiSelect',
            'required'=>'false', 'autocomplete'=>'false', 'multiple'=>'true', 'class'=>'select-all-on-submit'), $selected);
        $output .= "</div>";

        $output .= '<div class="clr"> <br /> </div>';
        
        $output .= '<div class="formbuttons">';
        $output .= moreform::propSubmit('_submit', Kohana::lang('model.action-update'), "", 'class="save-item"');
        $output .= "</div>";

        $output .= form::close();
        
        $output .= "</div>";
        
        return $output;
    }    
    
    public static function formWithSubmit($url, $buttonAttr, $label = 'Submit', $formAttr = array() ) {
        return form::open($url, $formAttr).form::submit($buttonAttr, $label).form::close();
    }

    public static function addQueryParameter($url, $name, $value, $replace=FALSE) {
    	if ($replace) $url = moreform::removeQueryParameter($url, $name);
        return (strpos($url, "?")) ? $url."&".$name."=".$value : $url."?".$name."=".$value;
    }
    
    public static function removeQueryParameter($url, $name) {
    	$pos = strpos($url, $name."=");
    	if ($pos!==FALSE) {
    		$end = strpos($url, "&", $pos+1); 
    		if ($end!==FALSE) {
    			if ($url[$pos-1]=='&') $pos--;
    			$url = substr($url, 0, $pos).substr($url, $end);
    		} else {
    			$url = substr($url, 0, $pos-1);
    		}
    	}
        return $url;
    }    
}