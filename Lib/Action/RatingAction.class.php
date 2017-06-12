<?php
class RatingAction extends BaseAction
{

    public function view($area = null)
    {
        $ratingModel = M('Rating');
        if (isset($area)) {
            $data = "FIND_IN_SET('" . $area . "', target_areas)";
        }
        $count = $ratingModel->where($data)->count();
        $listRows = C("LIST_RECORD_PER_PAGE");
        import("@.Classes.TBPage");
        $pager = new TBPage($count, $listRows);
        $result = $ratingModel->where($data)->order("score desc")->limit($pager->firstRow, $listRows)->select();
        $ratingMap = array();
//        $rating = "";
        foreach ($result as $item) {
            $rating = $this->calcRating($item['score']);
            if (!isset($ratingMap[$rating])) {
                $ratingMap[$rating] = array($item);
            } else {
                array_push($ratingMap[$rating], $item);
            }
        }
        $this->assign('curArea', $area);
        $this->assign('ratingMap', $ratingMap);
        $this->assign('pager_html', $pager->show());
        return $this->display();
    }

    public function rating() {
        $id = user('account_id');
        $ratingModel = M('Rating');
        $result = $ratingModel->where(array('account_id'=>$id))->select();
        $score = $result[0]['score'];
        $this->assign('score', $score);
        $this->assign('rating', $this->calcRating($score));
        return $this->display();
    }

    private function calcRating($score)
    {
        if ($score >= 85) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 75) return 'A-';
        if ($score >= 65) return 'B+';
        if ($score >= 60) return 'B';
        return 'B-';
    }
}