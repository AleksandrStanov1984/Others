<?

class WorkPlaceCalendarView extends CActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return '{{ws_calendar_notice_view}}';
    }

    public function attributeLabels(): array
    {
        return array();
    }

    public function primaryKey(): array
    {
        return array('user_id');
    }

    public function getDataNotes($state = 0, $year, $month = null, $day = null): array
    {
        $sql = "call ws_calendar_notice_view (" . Yii::app()->user->id . ", '$state', '$year', '$month', '$day')";
        $all_child_ids = Yii::app()->db->createCommand($sql)
            ->queryAll();
        $data = [];
       // $user = User::model()->findByPk(Yii::app()->user->id );
        foreach ($all_child_ids as $row) {
            $user = User::model()->findByPk($row['user_id']);

            $el = new stdClass();
            $el->status = $row['status'];
            $el->user_status = $row['user_status'];
            $el->color = $row['color'];
            $el->user_view = $row['user_view'];
            $el->record_id = $row['record_id'];
            $el->user_id = $row['user_id'];
            $el->text = $row['text'];
            $el->user_fio = $user ? $user->getKadryFioShort() : "ERROR";
            $el->module = $row['module'];
            $el->date = $row['date'];
            $el->mont = $row['mont'];
            $el->yea = $row['yea'];
            $el->da = $row['da'];
            if ($state == 1) {
                $el->amount = $row['amount'];
            } else {
                $el->amount = 0;
            }
            array_push($data, $el);
        }
        return $data;
    }

    public function ApiData(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $full_data = Yii::app()->request->getParam('date');
        if (!empty($full_data)) {
            $nd = strtotime($full_data);

            $data = self::getDataNotes(0, date('Y', $nd), date('m', $nd), date('j', $nd));
            $array = [];
            $user_id = Yii::app()->user->id;
            if (!empty($data)) {
                foreach ($data as $item) {
                    $temp = '';
                    if ($item->module == 'notice') {
                        $temp = 1;
                    } elseif ($item->module == 'wsnotice') {
                        $temp = 2;
                    } elseif ($item->module == 'BDEmploers') {
                        $temp = 3;
                    } elseif ($item->module == 'wsevents') {
                        $temp = 3;
                    }
                    $my = ($item->user_id == $user_id) ? true : false;
                    $status = $item->status;
                    $color = $item->color;

                    if ($item->module == 'wsevents') {
                        $tempStatus = (json_decode($item->user_status, true)) ?? [];
                        if (array_key_exists($user_id, $tempStatus)) {
                            $status = $tempStatus[$user_id];
                        } else {
                            $status = 0;
                        }

                        $tempColor = (json_decode($item->color, true)) ?? [];
                        if (array_key_exists($user_id, $tempColor)) {
                            $color = $tempColor[$user_id];
                        } else {
                            $color = 0;
                        }
                    }
                    $array[$temp][] = [
                        'text' => preg_replace("/<img[^>]+>/", "", $item->text),
                        'date' => $item->date,
                        'module' => $item->module,
                        'status' => $status,
                        'color' => $color,
                        'record_id' => $item->record_id,
                        'user_fio' => $item->user_fio,
                        'my' => $my,
                    ];
                }
            }
            $result['out'] = $array;
            $result['status'] = 200;
            $result['success'] = true;

        } else {
            $result['out'] = [
                'mess' => 'No full date',
            ];
            $result['status'] = 200;
            $result['success'] = true;
        }
        return $result;
    }

    public function ApiMonth(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $year = (Yii::app()->request->getParam('year')) ?? date('Y', time());
        $month = (Yii::app()->request->getParam('month')) ?? null;

        if (empty($month) || $month == '0')
            $month = strval($month + 1);

        $data = self::getDataNotes(1, $year, $month);
        $array = [];

        if (!empty($data)) {
            foreach ($data as $item) {
                $temp = '';
                if ($item->module == 'notice') {
                    $temp = 1;
                } elseif ($item->module == 'wsnotice') {
                    $temp = 2;
                } elseif ($item->module == 'BDEmploers') {
                    $temp = 3;
                } elseif ($item->module == 'wsevents') {
                    $temp = 3;
                } elseif ($item->module == 'wsreminders') {
                    $temp = [];

                    $criteria = new CDbCriteria;
                    $criteria = $criteria->addBetweenCondition("date",
                        date('Y-m-d 00:00:00', strtotime($item->date)),
                        date('Y-m-d 23:59:59', strtotime($item->date)));
                    $criteria->addCondition("user_id = '" . Yii::app()->user->id . "'");
                    $criteria->addCondition("send_form = 'eip'");
                    $tempData = WorkPlaceReminders::model()->findAll($criteria);

                    if (!empty($tempData)) {
                        $tempArray = [];
                        foreach ($tempData as $tData) {
                           // $model = [];
                            if ($tData->project_model == 'wsevents')
                                $model = WorkPlaceEvents::model()->findByPk($tData->project_id);
                            elseif ($tData->project_model == 'wsnotice')
                                $model = WorkPlaceNotes::model()->findByPk($tData->project_id);

                            if ($model !== null && $model->status != 2)
                                array_push($tempArray, $model->text . ' на ' . date('d.m.Y H:i', strtotime($model->date)));
                        }
                        $temp[4] = $tempArray;
                    }
                }

                if (!empty($month) || $month == '0') {
                    if (isset($array[$item->da]) && !empty($array[$item->da])) {
                        if (!in_array($temp, $array[$item->da]))
                            $array[$item->da][] = $temp;
                    } else
                        $array[$item->da][] = $temp;
                } else {
                    if (isset($array[$item->mont - 1][$item->da]) && !empty($array[$item->mont - 1][$item->da])) {
                        if (!in_array($temp, $array[$item->mont - 1][$item->da]))
                            $array[$item->mont - 1][$item->da][] = $temp;
                    } else
                        $array[$item->mont - 1][$item->da][] = $temp;
                }
            }
        }
        $result['out'] = $array;
        $result['status'] = 200;
        $result['success'] = true;

        return $result;
    }

}