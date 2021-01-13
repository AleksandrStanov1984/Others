<?

class WorkPlaceChatComments extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'file' => [self::BELONGS_TO, 'WorkPlaceMyFiles', 'file_id'],
        ];
    }

    protected function beforeSave()
    {
        if (!$this->id) {
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
        return '{{workplace_chat_comment}}';
    }


}