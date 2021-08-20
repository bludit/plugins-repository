<?php

class ToDo extends Plugin {

    public function adminController()
    {
        global $layout;
        $layout["title"] = "Bludit | ToDo";		
    }



    public function adminView()
    {	

	$html  = '<link rel="stylesheet" type="text/css" href="' . HTML_PATH_PLUGINS . 'bludit-todo/css/style.css">' . PHP_EOL;
	$html .= '<h2><span class="fa fa-check-circle" style="font-size: 0.9em;"></span><span>ToDo List</span></h2>';
	$html .= '<div class="container">';
	$html .= '<div class="form-group">';
	$html .= '<label for="itemInput">Add Item</label>';
	$html .= '<input type="text" class="form-control" name="" id="itemInput" >';
	$html .= '</div>';
	$html .= '<button type="button" id="addButton" class="btn btn-primary mr-1">Add ToDo</button>';
	$html .= '<button type="button" id="clearButton" class="btn btn-danger">Clear ToDo List</button>';
	$html .= '<br/><br/>';
	$html .= '<ul id="todoList"></ul>';
	$html .= '</div>';
	$html .= '<script src="' . HTML_PATH_PLUGINS . 'bludit-todo/js/todo.js"></script>' . PHP_EOL;
	return $html;
					
    }



    public function adminSidebar()
    {
        $pluginName = Text::lowercase(__CLASS__);
        $url = HTML_PATH_ADMIN_ROOT.'plugin/'.$pluginName;
        $html = '<a id="current-version" class="nav-link" href="'.$url.'">ToDo List</a>';
        return $html;
    }
	
}
?>