<?

class WorkPlaceChatsNotification extends CActiveRecord
{

    protected $transaction;
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public function tableName()
    {
        return '{{workplace_chat_notification}}';
    }

    public static function getCount($userId, $chatId){
        $sql = " user_id = $userId AND chat_id = $chatId ";
        $result = WorkPlaceChatsNotification::model()->count($sql);
        return $result;
    }

    public static function clearCount($userId, $chatId) {
        $sql = " user_id = $userId AND chat_id = $chatId ";
        WorkPlaceChatsNotification::model()->deleteAll($sql);
    }

}