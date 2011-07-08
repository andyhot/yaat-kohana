<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Base class for models. Adds utilities to get a displayable version,
 * the editable properties, the many relations and to populate itself
 * from an array of data.
 */
abstract class Base_Model extends ORM {
    
    /**
     * The properties of this model - either editable or viewable (pass FALSE).
     * Looks for arrays: csv_valued, boolean_valued, foreign_valued, readonly_valued.
     *
     * TODO: Results can be cached (per class) since they are invariable. Cache
     * them in production only!
     */
    public function getProps($editable=TRUE) {
        $props = $this->table_columns;
        unset($props['id']);

        foreach ($props as $key => $val) {
            if (array_key_exists('binary', $val)) {
                $props[$key]['type'] = 'boolean';
            }
        }

        if (property_exists($this, 'csv_valued')) {
            foreach ($this->csv_valued as $csv) {
                $vals = $csv."_vals";
                $props[$csv]['type'] = 'csv';
                $props[$csv]['ext'] = $this->$vals;
            }
        }
        
        if (property_exists($this, 'lov_valued')) {
            foreach ($this->lov_valued as $lov) {
                $vals = $lov.'_vals';
                $props[$lov]['type'] = 'lov';
                $props[$lov]['ext'] = $this->$vals;
                
                $vals_prefix = $vals.'_prefix';
                if (property_exists($this, $vals_prefix)) {
                	$props[$lov]['prefix'] = $this->$vals_prefix;
                } else if (is_array($props[$lov]['ext']) 
                	&& in_array($props[$lov]['ext'][0], array('0', '1')) ) {
                	$props[$lov]['prefix'] = $lov.'_';
                }
            }
        }        

        if (property_exists($this, 'boolean_valued')) {
            foreach ($this->boolean_valued as $val) {
                $props[$val]['type'] = 'boolean';
            }
        }
        
        if (property_exists($this, 'date_valued')) {
            foreach ($this->date_valued as $val) {
                $props[$val]['type'] = 'date';
            }
        }        

        if (property_exists($this, 'foreign_valued')) {
            foreach ($this->foreign_valued as $key => $val) {
                $props[$key]['type'] = 'foreign';
                $props[$key]['foreign'] = $val;
            }
        }

        if (property_exists($this, 'html_valued')) {
            foreach ($this->html_valued as $val) {
                $props[$val]['type'] = 'html';
            }
        }

        if (property_exists($this, 'image_valued')) {
            foreach ($this->image_valued as $val) {
                $props[$val]['type'] = 'image';
            }
        }
        
        if (property_exists($this, 'password_valued')) {
            foreach ($this->password_valued as $val) {
                $props[$val]['type'] = 'password';
            }
        }        
        
        if (property_exists($this, 'edit_override')) {
            foreach ($this->edit_override as $key => $val) {
                $props[$key]['edit_override'] = $val;
            }
        }
        
        if ($editable && property_exists($this, 'readonly_valued')) {
            foreach ($this->readonly_valued as $readonly_key) {
                unset($props[$readonly_key]);
            }
        }        

        return $props;
    }

    public function get_search_column() {
    	$keys = array_keys($this->getProps());
    	return $keys[0]; //'description';
    }
    
    /**
     * Defines the prefixes that will be handled by the filtering process.
     * Stuff like is, has, cat so that we can understand a filter such as
     * is:on or has:attachment
     */
    public function get_search_prefixes() {
        return array();
    }

    protected function parse_search_terms($filter, $prefixes) {        
        $curr = 0;
        $curr_main = 0;
        $main = $filter;
        $term = '';
        $found = array();
        
        while (($pos = strpos($filter, ':', $curr))) {

            $pos_space = strrpos(substr($filter, 0, $pos), ' ');

            if ($pos_space===FALSE) $pos_space = -1;
            $pos_space++;

            $prefix = substr($filter, $pos_space, $pos - $pos_space);

            if (in_array($prefix, $prefixes)) {
                $prev_value = substr($filter, $curr_main, $pos_space - $curr_main);
                if ($term=='') {
                    $main = $prev_value;
                } else {
                    $found[$term] = trim($prev_value);
                }


                $term = $prefix;
                $curr_main = $pos + 1;
            }

            $curr = $pos + 1;
        }

        if ($term!='') {
            $found[$term] = trim(substr($filter, $curr_main));
        }

        $found['main'] = trim($main);
        return $found;
        
    }

    public function toDisplay() {
        $output = "";//"{$this->id}. ";
        $keys = array_keys($this->getProps());
        /*foreach($keys as $item) {
            $output .= $this->$item." ";
        }*/
        $output .= $this->$keys[0];
        return $output;
    }

    public function toSummaryDisplay() {
        $output = "";//"{$this->id}. ";
        $keys = array_keys($this->getProps());
        /*foreach($keys as $item) {
            $output .= $this->$item." ";
        }*/
        $output .= $this->$keys[1];
        $output = substr($output , 0, 100);
        return $output;
    }

    /**
     * Populates model properties from an array.
     * @param array $values Array with data.
     * @param string $prefix Prefix for array keys (defaults to empty).
	 * @param object $ext_values Files submitted.
     */
    public function populate($values, $prefix = '', $ext_values = NULL) {
        foreach ($this->getProps() as $key => $val) {
            $index = $prefix.$key;
            $exists = array_key_exists($index, $values);
            if ($val['type'] == 'csv') {
                if ($exists) {
                    if ($values[$index][0]=="_all") {
                        $this->$key = "*";
                    } else {
                        $this->$key = implode(",", $values[$index]);
                    }
                } else {
                    $this->$key = NULL;
                }
            } else if ($val['type'] == 'boolean') {
                $this->$key = $exists ? 1 : 0;
            } else if ($val['type'] == 'date' && $exists) {
            	$dval = $values[$index];
                $this->$key = $dval && strlen($dval)>0 ? $dval : null;
            } else if ($exists) {
                $this->$key = $values[$index];
            } else {
                if ($val['type'] == 'image' && $ext_values && array_key_exists($index, $ext_values)) {
                    $file_data = $ext_values[$index];

                    if(preg_match('/image\//i', $file_data['type'])) {					

                        $random_dir = ''.rand(0, 7).'/';
                        $uploaddir = getcwd().'/files/'.$random_dir;
                        $dir = opendir($uploaddir);
                        $files = array();

                        while($file = readdir($dir)) { array_push($files,"$file"); } closedir($dir);
                        $fullpath = ceil(count($files)+'1').''.strtolower(strrchr($file_data['name'], '.'));
                        $uploadfile = $uploaddir . basename($fullpath);

                        move_uploaded_file($file_data['tmp_name'], $uploadfile);

                        $this->$key = $random_dir.$fullpath;
                    }
                    
                }
            }
        }
        if (property_exists($this, 'auto_populate')) {
            foreach($this->auto_populate as $key => $val) {
                if ($this->$key===NULL) {
                    $this->$key = $this->auto_populate[$key];
                }
            }
        }
    }

    public function populateWithParent($values, $suffix) {
        if ($this->isHierarchical()) {
            $this->find($values["id".$suffix]);
            $key = $this->hierarchical;
            $index = "pId".$suffix;
            if (array_key_exists($index, $values)) {
                $val = $values[$index];
                $this->$key = $val;
            }
        }
    }

    /**
     * An array of models with which this entity has a many relatation.
     * @return array
     */
    public function getManyRelations() {
        $all = array_merge($this->has_many, $this->has_and_belongs_to_many);
        /*if (property_exists($this, 'belongs_to_many'))
            $all = array_merge($all, $this->belongs_to_many);*/
        return $all;
    }

    public function isHierarchical() {
        return property_exists($this, 'hierarchical');
    }

    /**
     * Json encodes this entity's values.
     * @return string
     */
    public function to_json() {
        return json_encode( $this->getJsonValues() );
    }

    /**
     * Returns an array of properties from this entity's values.
     * @param array $skip The properties to skip or include.
     * @param boolean $inverse Whether to skip or include - default skips.
     * @return array
     */
    public function getValues($skip=array(), $inverse = FALSE){
        $returnValue = array();
        foreach($this->object as $key=>$value) {
        	$inarray = in_array($key,$skip);
            if ((!$inarray && !$inverse) || ($inarray && $inverse)) {
                $returnValue[$key]=$value;
            }
        }
        return $returnValue;
    }
    
    public function getJsonValues(){
    	return $this->getValues();    	
    }

    /**
     * Builds the where part of a query that applies the given filter.
     * By default it is a LIKE query on the 'search_column' of the entity.
     * @param <String> $filter
     */
    public function build_filter($filter) {
        return $this->like($this->get_search_column(), $filter);
    }

    /**
     * Finds entries matching filter.
     * To customize the behavior, subclass the build_filter method instead.
     * @param <String> $filter
     */
    public function filter_all($filter, $limit = NULL, $offset = NULL) {
        $query = $this->build_filter($filter);
        return $query->find_all($limit, $offset);
    }

    public function load_all_results() {
        return $this->load_result(TRUE);
    }

    public function find_like($field, $match, $auto = TRUE)
    {
        $this->create_like($field, $match, $auto);
        return $this->load_result(TRUE);
    }

    public function orderby_search() {
        $this->orderby($this->get_search_column());
    }

    /**
     * Creates an sql like. Can handle csv_valued properties.
     * @param <String> $field
     * @param <String> $match
     * @param <boolean> $auto
     * @return <type>
     */
    public function create_like($field, $match, $auto = TRUE)
    {
        $this->db->like($field, $match, $auto);
        if (property_exists($this, 'csv_valued') && in_array($field, $this->csv_valued))
        {
            $this->db->orwhere($field, '*');
        }
        return $this->db;
    }

    public function update_all($data, $ids = NULL)
    {
            if (is_array($ids))
            {
                    // Delete only given ids
                    $this->db->in($this->primary_key, $ids);
            }
            elseif (is_null($ids))
            {
                    // Delete all records
                    $this->db->where(TRUE);
            }
            else
            {
                    // Do nothing - safeguard
                    return $this;
            }

            // Delete all objects
            $this->db->update($this->table_name, $data);

            return $this->clear();
    }
    
    public function count_query($sql) {
		$result = $this->db->query('SELECT COUNT(*) AS '.
			$this->db->escape_column('total_rows').' '.$sql);

		// Return the total number of rows from the query
		return (int) $result->current()->total_rows;    	
    }

}