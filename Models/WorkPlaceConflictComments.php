<?

class WorkPlaceConflictComments extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave(): bool
    {
        if (!$this->id) {
            $this->date = date('Y-m-d H:i:s');
            $this->user_id = Yii::app()->user->id;
        } else {
            $this->date = date('Y-m-d H:i:s');
        }

        return parent::beforeSave();
    }

    public function tableName(): string
    {
        return '{{workplace_conflicts_comment}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],// Пользователь который ведёт план продаж
            'file' => [self::BELONGS_TO, 'WorkPlaceMessageFiles', 'file_id'],
        ];
    }

    public function addCommentToChatConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => ['id' => 0, 'date' => '']];

        $conflictId = Yii::app()->request->getParam('id');
        $file_id = Yii::app()->request->getParam('file_id');

        $user = User::model()->findByPk(Yii::app()->user->id);
        $w_conflict = WorkPlaceConflict::model()->findByPk($conflictId);

        if ($w_conflict) {
            $transaction = Yii::app()->db->beginTransaction();

                if ($w_conflict->user_id == $user->id) { // проверка, редактировать сообщение может только создатель
                    $data1 = new WorkPlaceConflictComments();
                    $data1->conflict_id = $w_conflict->id;

                    $data1->user_id = $user->id;
                    $data1->file_id = $file_id;
                    $data1->created_at = date('Y-m-d H:i:s');
                    $data1->change = 0;
                    $data1->save();
                    $transaction->commit();

                    $result['out']['id'] = $data1->id;
                    $result['out']['date'] = strtotime($data1->date);
                    $result['out']['quote'] = $data1->quote;
                    $result['out']['system'] = $data1->system;
                    $result['out']['text'] = $data1->comment;
                    $result['out']['updated'] = $data1->change; //////

                    $result['out']['user_id'] = $data1->user_id;
                    $result['out']['my'] = $w_conflict->user_id == $user->id;

                    $result['success'] = true;

                if (!empty($w_conflict->view_user)) {
                    foreach (json_decode($w_conflict->view_user) as $user_id) {
                        if ($user_id == $user->id) continue;
                        WorkPlaceNotification::addRecord($user_id, $conflictId, 'WorkPlaceConflict');
                    }
                }
                if ($user->id != $w_conflict->user_id) {
                    WorkPlaceNotification::addRecord($w_conflict->user_id, $conflictId, 'WorkPlaceConflict');
                }
            }
        } else {
            $result['error'] = 'Не найден конфликт по id ' . $conflictId;
            $result['status'] = 204;
        }
        return $result;
    }
}