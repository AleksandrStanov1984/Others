<?

class WorkPlaceConflictFiles extends CActiveRecord
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

    public function tableName(): string
    {
        return '{{workplace_conflict_files}}';
    }

    public function relations(): array
    {
        return [
            'file' => [self::BELONGS_TO, 'WorkPlaceMyFiles', 'file_id'],
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
        ];
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
        $this->file_id = ($data['file_id']) ?? null;
        $this->conflict_id = $data['conflict_id'];
    }

    public static function sendSmsForYuer($id)
    {
        $user = User::model()->findByPk($id);
        if (Sms::model()->sendSMSUser($user, "Вы получили файл от " . Yii::app()->user->title, 'wp')) return true;
        return false;
    }

    public function checkFileNew($file, $checkType = false)
    {
        $result = [];
        $low = Tools::checkAccess('wp', 'low_file_loads', 'edit', 0);
        $middle = Tools::checkAccess('wp', 'middle_file_loads', 'edit', 0);
        $high = Tools::checkAccess('wp', 'high_file_loads', 'edit', 0);
//        if ($checkType) {
//            $type = $this->typeChat;
//        } else {
//            $type = $this->type;
//        }
//        if (!in_array($file['type'], $type)) {// проверяем на сам type файла
//            $result[] = "Не допустимый тип файла " . $file['type'];
//        }

        if (($low == true) || ($middle == true) || ($high == true)) { // проверяем на права
            if (($low == true || $middle == true) && $high !== true) { // проверяем на тип только для low и middle
                /* Мегабайт = Байт
                * 1 = 1048576
                * 2 = 2097152
                * 5 = 5242880
                * 10 = 10485760
                * 20 = 20971520
                */
                $size = 0;
                if ($low == true) $size = 5242880;
                if ($middle == true) $size = 100971520;
                if ($file['size'] > $size) {
                    $result[] = "Файл превышает допустимый размер : " . $file['size'] . " > " . $size;
                }
            }
        } else {
            $result[] = "В настройках пользователя отсутствует информация о разрешенном размере загружаемого файла";
        }

        if (count($result) == 0) {
            return true;
        } else {
            return implode("<br>", $result);
        }
    }
}