<?php 

    class pluginPopularPosts extends Plugin {
        public function init()
        {
            $this->dbFields = array(
                    'label'         =>  'Popular Posts',
                    'popular-posts' =>  '3',
                    'expire'        =>  '1'
            );

            if(!file_exists($this->workspace()."/pop.json")) {
                $pop = array();
                $file = json_encode($pop, JSON_PRETTY_PRINT);
                file_put_contents($this->workspace()."/pop.json", $file);
            }

            if(!file_exists($this->workspace()."/popip.json")) {
                $popip = array();
                $file = json_encode($popip, JSON_PRETTY_PRINT);
                file_put_contents($this->workspace()."/popip.json", $file);
            }

            if(!file_exists($this->workspace()."/filetime.txt")) {
                $myfile = fopen($this->workspace()."/filetime.txt", "w");
                fwrite($myfile, date("Y-m-d"));
                fcolse($myfile);
            }

            $dir_name = $this->workspace()."/oldpop";
            if (!is_dir($dir_name)) {
                mkdir($dir_name, 0755);
            }
        }

        public function form() {
            global $L;

            $html  = '<div class="alert alert-primary" role="alert">';
            $html .= $this->description();
            $html .= '</div>';

            $html .= '<div>';
            $html .= '<label>'.$L->get('Label').'</label>';
            $html .= '<input name="label" type="text" placeholder="Popular Posts" value="'.$this->getValue('label').'">';
            $html .= '<span class="tip">'.$L->get('This title is almost always used in the sidebar of the site').'</span>';
            $html .= '</div>';

            $html .= '<div>';
            $html .= '<label>'.$L->get('Show the top posts in the sidebar by:').'</label>';
            $html .= '<select name="expire">';
            $html .= '<option value="0"  '.($this->getValue('expire') === "0" ?'selected':'').'>'.$L->get('disable').'</option>';
            $html .= '<option value="1"  '.($this->getValue('expire') === "1" ?'selected':'').'>'.$L->get('day').'</option>';
            $html .= '<option value="7"  '.($this->getValue('expire') === "7" ?'selected':'').'>'.$L->get('week').'</option>';
            $html .= '<option value="30" '.($this->getValue('expire') === "30" ?'selected':'').'>'.$L->get('month').'</option>';
            $html .= '</select>';
            $html .= '</div>';

            $html .= '<div>';
            $html .= '<label>'.$L->get('How many popular posts has to be listed').'</label>';
            $html .= '<input type="text" name="popular-posts" placeholder="4" value="'.$this->getValue('popular-posts').'" style="width:10%">';
            $html .= '</div>';

            return $html;
        }

        public function pageBegin() {
            global $page;
            global $site;
            
            $uuid       = $page->getValue('uuid');
            $pageTitle  = $page->title();
            $pageLink   = $page->permalink();
            $pageDate   = $page->date('Y-m-d');
            $ip         = $this->getUserIpAddr();
            $expire     = $this->getValue('expire');

            $file = file_get_contents($this->workspace()."/pop.json"); 
            $pop = json_decode($file, true);

            $ipfile = file_get_contents($this->workspace()."/popip.json"); 
            $popip = json_decode($ipfile, true);

            session_start();

            if($expire == 30) {
                $expire = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));
            }

            $files = glob($this->workspace().'/oldpop/*');
            foreach ($files as $file) {
                $basename = basename($file);
                $filedate = strtotime(substr($basename, 0, 10));
                $filedate = date("Y-m-d", $filedate);
                $expdate  = strtotime($filedate . " + " . $expire . " days");
                $expdate  = date("Y-m-d", $expdate);
                $deldate  = strtotime($filedate . " + 60 days");
                $deldate  = date("Y-m-d", $deldate);

                if (is_file($file) && (date("Y-m-d") > $expdate)) {
                    unlink($file);
                }
                else if ($expire === 0 && (date("Y-m-d") > $deldate)) {
                    unlink($file);
                }
            }

            $myfile = fopen($this->workspace()."/filetime.txt", "w");
            fwrite($myfile, date("Y-m-d", strtotime(date("Y-m-d") . " -1 days" )));

            if(!file_exists($this->workspace()."/"."oldpop"."/".date('Y-m-d')."-pop.json")) { 
                rename($this->workspace()."/pop.json", $this->workspace()."/"."oldpop"."/".date('Y-m-d')."-pop.json");
                rename($this->workspace()."/popip.json", $this->workspace()."/"."oldpop"."/".date('Y-m-d')."-popip.json");
            }
        

            if (!isset( $_SESSION[$pageTitle]) && !$page->isStatic() && $GLOBALS['WHERE_AM_I'] != 'home' && $pageDate != false ) {

                $_SESSION[$pageTitle] = "yes";

                $checkip = array('uuid' => $uuid, 'title' => $pageTitle, 'ip' => $ip);
            
                if (!in_array($checkip, $popip)) {
                    $iparr = array('uuid' => $uuid, 'title' => $pageTitle, 'ip' => $ip);
                    $popip[] = $iparr;
                    $popip = array_unique($popip, SORT_REGULAR);

                    $ipfile = json_encode($popip, JSON_PRETTY_PRINT);
                    file_put_contents($this->workspace()."/popip.json", $ipfile);

                    if (array_search($pageTitle, array_column($pop, 'title')) > -1) {
                        $key = array_search($pageTitle, array_column($pop, 'title'));
                        $counterVal = $pop[$key]['visited'] + 1;
                        $pop[$key]['visited'] = $counterVal;
                    } else {
                        $poparr = array('title' => $pageTitle, 'link' => $pageLink, 'date' => date("Y-m-d"), 'visited' => 1);
                        $pop[] = $poparr;
                    }

                    $file = json_encode($pop, JSON_PRETTY_PRINT);
                    file_put_contents($this->workspace()."/pop.json", $file);
                }
            }
        }

        public function siteSidebar() {
            global $pages;
            global $page;
            global $site;

            $items = $this->getValue('popular-posts');

			$html  = '<div class="plugin plugin-top-posts">';
            $html .= '<h2 class="plugin-label">'.$this->getValue('label').'</h2>';  
            $html .= '<ul>';

            $file = file_get_contents($this->workspace()."/pop.json"); 
			$pop = json_decode($file, true);                

            if(!empty($pop)) {
                usort($pop, function ($a, $b) {
                    return $a['visited'] <= $b['visited'];
                });

                $latestpages = $pages->getList(1, $items * 2, true);
                $duplicate = array();

                foreach ($latestpages as $key) {
                    $page = buildPage($key);
                    $duplicate[] = array('title' => $page->title(), 'link' => $page->permalink());
                }

                for ($i = 0; $i < count($pop); $i++) {
                    for ($y = 0; $y < count($duplicate); $y++) {
                        if ($pop[$i]['title']  ===  $duplicate[$y]['title'] ) {
                            unset($duplicate[$y]);
                        }    
                    }
                }

                $pop = array_merge($pop, $duplicate);

                for ($x = 0; $x < $items; $x++) {
                    $html .= '<li><a href=' . $pop[$x]['link'] . '>' . $pop[$x]['title'] . '</a></li>';
                }

            } else {                
                $latestpages = $pages->getList(1, $items, true);

                foreach ($latestpages as $key) {
                    $page = buildPage($key);
                    $html .= '<li><a href=' . $page->permalink() . '>' . $page->title() . '</a></li>';
                }
            }
            $html .= '</ul>';
            $html .= '</div>';

            return $html;
        }
    
        function getUserIpAddr() {
            if(!empty($_SERVER['HTTP_CLIENT_IP'])){
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            }elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])){
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }else{
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            return $ip;
        }
    }
?>