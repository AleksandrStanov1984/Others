<?

class WorkPlaceTaskComments extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if (!$this->id) {
            $this->date = date('Y-m-d H:i:s');
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = $this->created_at;
            $this->user_id = Yii::app()->user->id;
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public function tableName()
    {
        return '{{workplace_tasks_comment}}';
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],// Пользователь который ведёт план продаж
            'file' => [self::BELONGS_TO, 'WorkPlaceMessageFiles', 'file_id'],
        ];
    }

}