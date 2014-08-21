<?php

class IndexAction extends Action {
    public function index(){
    	$news = O('news')->limit(20)->select();
    	for($i=0;$i<count($news);$i++){
    		$name_parts = explode('：', $news[$i]['name']);
    		if(isset($name_parts[1])){
    			$news[$i]['org_name'] = $name_parts[0];
    			$news[$i]['name'] = $name_parts[1];
    		}
    	}
    	
    	$ngo_count = O('user')->with('type', 'ngo')->with('is_checked', 1)->active_only()->count();
    	

    	$this->assign('ngo_count', $ngo_count);
    	$this->assign('news', $news);
    	$this->display();
    }
    
    public function map(){
        $this->display();
    }
    
    public function map_result($province=null, $keyword=null, $type=null, $work_field=null, $minlon=null, $maxlon=null, $minlat=null, $maxlat=null, $page=1){
        // unpack arguments
        if(is_array($province)){
            $args = $province;
            $province = $args['province'];
            $keyword = $args['keyword'];
            $type = $args['type'];
            $work_field = $args['work_field'];
        };
        // build model based on type
        $is_user = false;
        if($type == '公益活动'){
            $base_model = O('event')->with('type', 'ngo')->attach('user');
        }
        else if($type == '企业公益活动'){
            $base_model = O('event')->with('type', 'csr')->attach('user');
        }
        else if($type == '对接案例'){
            $base_model = O('event')->with('type', 'case')->attach('user');
        }
        else{
            $base_model = O('user')->with('type', 'ngo');
            $is_user = true;
        }
        
        if(!empty($province)){
            $base_model = $base_model->province($province);
        }
        if(!empty($keyword)){
            $base_model = $base_model->keyword($keyword);
        }
        if(!empty($work_field)){
            $base_model = $base_model->with('work_field', array('like', "%$work_field%"));
        }
        $grand_total = $base_model->count();
        if(!empty($minlon)){
            $base_model = $base_model->with('longitude', 
                                         array(array('gt', floatval($minlon)), array('lt', floatval($maxlon))))
                                     ->with('latitude', 
                                         array(array('gt', floatval($minlat)), array('lt', floatval($maxlat))));
        }
        
        // do the search
        $count = $base_model->count();
        if(!$is_user){
            $base_model = $base_model->join('event_location on event.id=event_location.event_id');
            $count_with_multipal_locations = $base_model->count();
        }
        else{
            $count_with_multipal_locations = $count;
        }
        import("@.Classes.BNBPage");
        $pager = OO('BNBPage')->build($count_with_multipal_locations, C('LIST_RECORD_PER_PAGE'), $page);

        $result = $base_model->order('cover_img desc')->limit($pager->firstRow, $pager->rowsPerPage)->select();
        // process result: merge same items, concate lng and lat
        $result_map = array();
        if(!is_user){
            foreach($result as $res){
                if(isset($result_map[$res['id']])){
                    // if already in map, concat lon, lat
                    $result_map[$res['id']]['longitude'] .= ','.$res['longitude'];
                    $result_map[$res['id']]['latitude'] .= ','.$res['latitude'];
                }
                else{
                    $result_map[$res['id']] = $res;
                }
            }
        }
        else{
            $result_map = $result;
        }
        $this->assign('count', $count_with_multipal_locations);
        $this->assign('page', $page);
        $this->assign('total_page', $pager->totalPages);
        $this->assign('grand_total', $grand_total);
        $this->assign('result', $result_map);
        $this->assign('pager_html', $pager->show());
        $this->display('Index:map_result');
        
    }
}