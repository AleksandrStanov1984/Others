<?

class WorkPlaceTaskFiles extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave(): bool
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->user_id = Yii::app()->user->id;
        }
        return parent::beforeSave();
    }

    public function relations(): array
    {
        return [
            'file' => [self::BELONGS_TO, 'WorkPlaceMyFiles', 'file_id'],
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
        ];
    }

    public function tableName(): string
    {
        return '{{workplace_tasks_files}}';
    }

    public function add($data)
    {
        $result = false;
        try {
            if ($this->upload($data)) {
                $result = true;

            } else {
                $result = $this->getErrors();
            }

        } catch (Exception $exception) {
            $result = [$exception->getMessage()];
        }
        return $result;
    }

    public function upload(array $data): bool
    {
        $result = false;
        $this->create($data);
        if ($this->save()) {
            $result = true;
        }
        return $result;
    }

    public function create(array $data)
    {
        $this->task_id = $data['task_id'];
        $this->file_id = $data['file_id'];
    }

    public static function sendSmsForYuer($id): bool
    {
        $user = User::model()->findByPk($id);
        if (Sms::model()->sendSMSUser($user, "Вы получили файл от " . Yii::app()->user->title, 'wp')) return true;
        return false;
    }

}