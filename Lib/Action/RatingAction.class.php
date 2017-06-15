<?php

class RatingAction extends BaseAction
{

    public function view()
    {
        $ratingModel = D('Rating');
        $listRows = C("LIST_RECORD_PER_PAGE");
        $result = $ratingModel->order("score desc")->limit($listRows)->select();
        $ratingMap = array();
        foreach ($result as $item) {
            $obj = (object) $item;
            $rating = $obj->rating;
            if (!isset($ratingMap[$rating])) {
                $ratingMap[$rating] = array($item);
            } else {
                array_push($ratingMap[$rating], $item);
            }
        }
        $this->assign('ratingMap', $ratingMap);
        return $this->display();
    }

    public function filter($targetArea, $area, $rating, $page)
    {
        $ratingModel = M('Rating');
        $listRows = C("LIST_RECORD_PER_PAGE");
        if (isset($targetArea)) {
            $ratingModel->where("FIND_IN_SET('" . $area . "', target_areas)");
        }
        if (isset($area)) {
            $ratingModel->where(array('province|city' => array('like', "$area%")));
        }
        if (isset($rating)) {
            $ratingModel->where(array('rating', $rating));
        }
        $offset = 0;
        if (isset($page)) {
            $offset = $page * $listRows;
        }
        $result = $ratingModel->field('account_id','name', 'rating')->order("score desc")->limit($offset, $listRows)->select();
        if ($result) {
            $this->ajaxReturn($result, 'query success', 1);
        } else {
            $this->ajaxReturn(0, 'result empty', 0);
        }

    }

    public function rating()
    {
        $id = user('id');
        $ratingModel = M('Rating');
        $result = $ratingModel->where(array('account_id' => $id))->select();
        $score = $result[0]['score'];
        $this->assign('score', $score);
        $this->assign('rating', $this->calcRating($score));
        return $this->display();
    }
}
