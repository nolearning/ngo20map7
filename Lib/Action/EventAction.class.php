<?php
define('PAGE_BASIC_INFO',1);
define('PAGE_PHOTOS',2);
define('PAGE_CONTACT_INFO',3);

class EventAction extends BaseAction{

    function add(){
        $this->display();
    }

    function view($id){
    	$event = O('event')->find($id);
    	$user = O('user')->find($event['user_id']);
    	$media = O('media')->with('event_id', $event['id'])->select();
        $related_users = O('user')->recommend($user);
        $locations = O('event_location')->with('event_id', $event['id'])->select();
        $location_pairs = array();
        foreach($locations as $loc){
            $location_pairs[] = $loc['longitude'] . ',' . $loc['latitude'];
        }
        $baidu_static_img_markers = implode('|', $location_pairs);

        $this->assign('user', $user);
        $this->assign('event', $event);
        $this->assign('baidu_static_img_markers', $baidu_static_img_markers);
        $this->assign('related_users', $related_users);
        $this->display();
    }

    function edit($id=0, $p=PAGE_BASIC_INFO){

        if($id == 0){   // just edit the recent item
            if(isset($_SESSION['recent_event_id'])){
                $p=PAGE_CONTACT_INFO;
                $event = O('event')->find($_SESSION['recent_event_id']);
                if(!$event){
                    echo '对不起，系统出错，请联系info@ngo20.org';
                    return;
                }
            }
            else{
                echo '对不起，系统出错，请联系info@ngo20.org';
                return;
            }
        }
        else{   // if id is specified
            $event = O('event')->find($id);
        }

        $this->userMayEditEvent($event['id']);

        if(!$event['account_id']){
            // event from a new user
            $event['account_id'] = user('account_id');
            O('event')->save($event);
        }

        //get all locations
        if($p == PAGE_BASIC_INFO){
            $locations = O('event_location')->where(array('event_id'=>$event['id']))->select();
        }

        // get all photos
        if($p == PAGE_PHOTOS){
            $images = O('media')->where(array('event_id'=>$event['id'], 'type'=>'image'))->select();
            $this->assign('images', $images);
        }

        if(!event){
            die('无法正常打开项目/活动，请联系info@ngo20.org');
        }

        $this->assign('event', $event);
        $this->assign('locations', $locations);
        $this->assign('p', $p);
        $this->display();
    }

    // @ajaxaction
    function insert(){
        $event = O('event');
        $event->create();
        $event->create_time = date('Y-m-d H:i:s');
        if(!user()){
            $_SESSION['next_mission'] = 'Event/edit';
            $event->account_id = 0;
        }
        if(user('user_id')){
            $event->user_id = user('user_id');
        }
        if(!empty($_POST['images'])){   // pick the first image as cover image
            $event->cover_img = $_POST['images'][0];
        }
        $new_id = $event->add();

        if($new_id){
            //add locations
            $_SESSION['recent_event_id'] = $new_id;
            $location_model = O('event_location');
            if(!empty($_POST['place']) && !empty($_POST['longitude'])){
                $_POST['provinces'][] = $_POST['province'];
                $_POST['cities'][] = $_POST['city'];
                $_POST['places'][] = $_POST['place'];
                $_POST['lngs'][] = $_POST['longitude'];
                $_POST['lats'][] = $_POST['latitude'];
            }
            for($i=0;$i<count($_POST['lngs']);$i++){
                $location_model->add(array(
                        'province' => $_POST['provinces'][$i],
                        'city' => $_POST['cities'][$i],
                        'place' => $_POST['places'][$i],
                        'longitude' => $_POST['lngs'][$i],
                        'latitude' => $_POST['lats'][$i],
                        'event_id' => $new_id,
                ));
            }

            //add images
            $media_model = O('media');
            foreach($_POST['images'] as $image){
                $media_model->add(array(
                    'url' => $image,
                    'event_id' => $new_id,
                    'type' => 'image'
                ));
            }

            echo 'ok';
        }   
        else{
            echo '对不起，系统出错，请联系info@ngo20.org';
        }
    }

    function save(){
        $this->userMayEditEvent($_POST['id']);
        $event = O('event');
        $event->create();
        $event->save();

        if(!empty($_POST['place']) && !empty($_POST['longitude'])){
            $_POST['provinces'][] = $_POST['province'];
            $_POST['cities'][] = $_POST['city'];
            $_POST['places'][] = $_POST['place'];
            $_POST['lngs'][] = $_POST['longitude'];
            $_POST['lats'][] = $_POST['latitude'];
        }
        if(isset($_POST['lngs'])){
            $location_model = O('event_location');
            $location_model->with('event_id', $_POST['id'])->delete();
            for($i=0;$i<count($_POST['lngs']);$i++){
                $location_model->add(array(
                        'province' => $_POST['provinces'][$i],
                        'city' => $_POST['cities'][$i],
                        'place' => $_POST['places'][$i],
                        'longitude' => $_POST['lngs'][$i],
                        'latitude' => $_POST['lats'][$i],
                        'event_id' => $_POST['id'],
                ));
            }
        }

        $this->back();
    }

    function addEventPhoto(){
        $this->userMayEditEvent($_POST['event_id']);
        O('media')->add(array(
            'url' => $_POST['url'],
            'event_id' => $_POST['event_id'],
            'type' => 'image'
        ));
        echo 'ok';
    }

    function deleteEventPhoto(){
        $this->userMayEditEvent($_POST['event_id']);
        $media = O('Media')->with('event_id', $_POST['event_id'])
                           ->with('url', $_POST['url'])->find();
        
        O('Media')->with('id', $media['id'])->delete();
        echo 'ok';
    }


}