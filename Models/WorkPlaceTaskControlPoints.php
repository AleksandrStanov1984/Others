<?

class WorkPlaceTaskControlPoints extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        /*if (!$this->id) {
            $this->date = date('Y-m-d H:i:s');
            $this->user_id = Yii::app()->user->id;
        }*/
        return parent::beforeSave();
    }

    public function tableName()
    {
        return '{{workplace_task_control_points}}';
    }

    public function relations()
    {
        return [
            'creator' => [self::BELONGS_TO, 'User', 'created_user_id'],
            'reporter' => [self::BELONGS_TO, 'User', 'reporter_user_id'],
            'editor' => [self::BELONGS_TO, 'User', 'editor_user_id'],
        ];
    }

    public function makeCpInfo(){
        $result = new stdClass();
        $result->pointId = $this->id;
        $result->pointDate = $this->point_date ? strtotime($this->point_date) : null;
        $result->pointCreator = $this->creator ? User::getUserInfo($this->creator) : null;
        $result->pointCreateDateTime = $this->created_at ? strtotime($this->created_at) : null;
        $result->left = $this->left;
        $result->reporterName = $this->reporter ? User::getUserInfo($this->reporter) : null;
        $result->reportText = $this->report_text;
        $result->reportDateTime = $this->report_date ? strtotime($this->report_date) : null;
        $result->edited = $this->edited ? true : false;
        if ($this->edited) {
            $result->editedDateTime = $this->edit_date ? strtotime($this->edit_date) : null;
            $result->editorName = $this->editor ? User::getUserInfo($this->editor) : null;
            $result->editText = $this->edit_text;
        } else {
            $result->editedDateTime = null;
            $result->editorName = null;
            $result->editText = null;
        }

        return $result;
    }

    public static function addTaskControlPoint(){
        $result = ['success'=>false, 'error'=>'', 'status'=>200, 'out'=>[]];
        $error = [];
        if (!Yii::app()->request->getParam('task_id', null)) {
            $error[] = 'task_id';
        }
        if (!Yii::app()->request->getParam('left', null)) {
            $error[] = 'left';
        }
        if (!Yii::app()->request->getParam('point_date_time', null)) {
            $error[] = 'point_date_time';
        }
        if (count($error) == 0) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $cp = new WorkPlaceTaskControlPoints();
                $cp->task_id = Yii::app()->request->getParam('task_id', null);
                $cp->created_user_id = Yii::app()->user->id;
                $cp->created_at = date('Y-m-d H:i:s');
                $cp->point_date = date('Y-m-d H:i:s', Yii::app()->request->getParam('point_date_time', null));
                $cp->left = Yii::app()->request->getParam('left', null);
                $history = [
                    'date'=>date('d.m.Y H:i:s'),
                    'userId' => Yii::app()->user->id,
                    'userFio' => Yii::app()->user->getKadryFio(),
                    'text' => "Создана контрольная точка на дату ".date('d.m.Y H:i:s', Yii::app()->request->getParam('point_date_time', null)).". [$cp->left %]",
                ];
                $cp->history = json_encode($history);
                $cp->save();

                $transaction->commit();
                $result['id']      = $cp->id;
                $result['success'] = true;
                $result['out']     = $cp->makeCpInfo();
            } catch (Exception $e) {
                $transaction->rollback();
                $result['error']  = $e->getMessage();
                $result['status'] = 500;
            }
        } else {
            $result['out']  = $error;
            $result['error']  = "Онибка заполнения полей";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function addTaskControlPointReport(){
        $result = ['success'=>false, 'error'=>'', 'status'=>200, 'out'=>[]];
        $error = [];
        if (!$pointId = Yii::app()->request->getParam('point_id', null)) {
            $error[] = 'point_id';
        }
        if (!$reportText = Yii::app()->request->getParam('report_text', null)) {
            $error[] = 'report_text';
        }
        $cp = WorkPlaceTaskControlPoints::model()->findByPk($pointId);
        if ($cp) {
            if (count($error) == 0) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    if (!$cp->report_text) { // первый отчет (не редактирование)
                        $cp->reporter_user_id = Yii::app()->user->id;
                        $cp->report_date      = date('Y-m-d H:i:s');
                        $cp->report_text      = $reportText;
                        $history = json_decode($cp->history, true);
                        $history[]            = [
                            'date' => date('d.m.Y H:i:s'),
                            'userId' => Yii::app()->user->id,
                            'userFio' => Yii::app()->user->getKadryFio(),
                            'text' => "Сохранен отчет по точке контроля. [$reportText]",];
                        $cp->history         = json_encode($history);
                    } else {
                        $cp->editor_user_id = Yii::app()->user->id;
                        $cp->edit_date      = date('Y-m-d H:i:s');
                        $cp->edited = 1;
                        $cp->edit_text      = $reportText;
                        $history = json_decode($cp->history, true);
                        $history[]            = [
                            'date' => date('d.m.Y H:i:s'),
                            'userId' => Yii::app()->user->id,
                            'userFio' => Yii::app()->user->getKadryFio(),
                            'text' => "Изменен отчет по точке контроля. [$reportText]",];
                        $cp->history         = json_encode($history);
                    }

                    $cp->save();

                    $transaction->commit();
                    $result['id']      = $cp->id;
                    $result['success'] = true;
                    $result['out']     = $cp->makeCpInfo();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $result['error']  = $e->getMessage();
                    $result['status'] = 500;
                }
            } else {
                $result['out']    = $error;
                $result['error']  = "Ошибка заполнения полей";
                $result['status'] = 400;
            }
        } else {
            $result['out']    = ["Не найдена точка контроля по id $pointId "];
            $result['error']  = "Не найдена точка контроля по id $pointId ";
            $result['status'] = 400;
        }
        return $result;
    }

}