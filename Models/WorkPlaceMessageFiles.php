<?

class WorkPlaceMessageFiles extends CActiveRecord
{

    private $type = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.oasis.opendocument.text',
        'application/msword',
        'application/pdf',
        'image/png',
        'image/jpeg',
    ];

    private $typeChat = [
        'image/png',
        'image/jpeg',
    ];

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave(): bool
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->user_id = Yii::app()->user->id;
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public function tableName(): string
    {
        return '{{workplace_message_files}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
        ];
    }

    public function checkFile($file): bool
    {
        $low = Tools::checkAccess('wp', 'low_file_loads', 'edit', 0);
        $middle = Tools::checkAccess('wp', 'middle_file_loads', 'edit', 0);
        $high = Tools::checkAccess('wp', 'high_file_loads', 'edit', 0);

        if (($low == true) || ($middle == true) || ($high == true)) { // проверяем на права
            if (($low == true || $middle == true) && $high !== true) { // проверяем на тип только для low и middle
                if (in_array($file['type'], $this->type)) {// проверяем на сам type файла
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
                    if ($file['size'] <= $size) {
                        return true;
                    }
                }
                return false;
            }
            return true;
        }
        return false;
    }

    public function checkFileNew($file, $checkType = false)
    {
        $result= [];
        $low = Tools::checkAccess('wp', 'low_file_loads', 'edit', 0);
        $middle = Tools::checkAccess('wp', 'middle_file_loads', 'edit', 0);
        $high = Tools::checkAccess('wp', 'high_file_loads', 'edit', 0);
        if ($checkType) {
            $type = $this->typeChat;
        } else {
            $type = $this->type;
        }
        if (!in_array($file['type'], $type)) {// проверяем на сам type файла
            $result[] =  "Не допустимый тип файла " . $file['type'];
        }

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
                    $result[] =  "Файл превышает допустимый размер : " . $file['size'] . " > " . $size;
                }
            }
        } else {
            $result[] =  "В настройках пользователя отсутствует информация о разрешенном размере загружаемого файла";
        }

        if (count($result) == 0) {
            return true;
        } else {
            return implode("<br>", $result);
        }
    }


    public static function makeFileServerName($moduleId, $name): string
    {
        switch ($moduleId) {
            case 2:
                $moduleName = 'chat';
                break;
            case 1:
                $moduleName = 'task';
                break;
            default:
                $moduleName = 'undefined';
        }
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        return $moduleName . "_" . Yii::app()->user->id . "_" . str_replace('.', '', microtime(true)) . '.' . $extension;
    }

    // API

    public static function uploadFileEmployee($moduleId){
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link'=>'']];
        $catalogId = 0;
        $moduleId = $moduleId;
        $file = $_FILES['file'];
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = new WorkPlaceMyFiles();
            $fileName = self::makeFileServerName($moduleId, $file['name']);
            $f->type = $file['employee_id'];
            $f->name = $file['name'];
            $f->type = $file['link'];
            $f->size = $file['size'];
            $f->link = $fileName;
            $f->moduleId = $moduleId;
            $f->save();
        }catch (Exception $e){
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }


    }

    public static function uploadMessageFile($moduleId): array
    {
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link'=>'']];
        $catalogId = 0;
       // $moduleId = $moduleId;
        $file = $_FILES['file'];

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = new WorkPlaceMyFiles();
            $checkFile = $f->checkFileNew($file, true);
            if ($checkFile !== true) {
                $result['error'] = 'Файл не прошел проверку по параметрам : <br>' . $checkFile;
                $result['status'] = 403;
            } else {
                $fileName = self::makeFileServerName($moduleId, $file['name']);
                $f->name = $file['name'];
                $f->type = $file['type'];
                $f->size = $file['size'];
                $f->link = $fileName;
                $f->moduleId = $moduleId;
                $f->save();
                if (move_uploaded_file($file['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWp']) . '/' . $fileName)) {
               // if (move_uploaded_file($file['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWpMessage']) . '/' . $fileName)) {
                    $result['out']['id']  = $f->id;
                    $result['out']['name'] = $f->name;
                    $result['out']['date'] = strtotime($f->created_at);
                    $result['out']['size'] = number_format( $f->size / 1048576, 2 );
                    $result['out']['url'] = "/static/uploads/wp/" . $f->link;
                   // $result['out']['url'] = "/static/uploads/wpMessage/" . $f->link;
                    $result['success']    = true;
                    $transaction->commit();

                    /*if ($categoryStatus == 1) { // отправляем сообщение в чат об добавлении файла

                    } elseif ($categoryStatus == 3)  { // файл из задачника

                    }*/
                } else {
                    $result['error'] = 'Ошибка загрузки файла';
                    $transaction->rollback();
                    $result['status'] = 500;
                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }

        return $result;
    }


    public static function uploadMessageFileConflict($moduleId): array
    {
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link'=>'']];
        $catalogId = 0;
        $file = $_FILES['file'];

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = new WorkPlaceMessageFiles();
                $fileName = self::makeFileServerName($moduleId, $file['name']);
                $f->name = $file['name'];
                $f->type = $file['type'];
                $f->size = $file['size'];
                $f->link = $fileName;
                $f->moduleId = $moduleId;
                $f->save();
                 if (move_uploaded_file($file['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWp']) . '/' . $fileName)) {
                    $result['out']['id']  = $f->id;
                    $result['out']['name']  = $f->name;
                    $result['out']['url'] = "/static/uploads/wp/" . $f->link;
                    $result['success']    = true;
                    $transaction->commit();
            }
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function uploadMyFile($checkType = false): array
    {
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link'=>'']];
        $catalogId = Yii::app()->request->getParam('catalog_id');
        $moduleId = Yii::app()->request->getParam('module_id');
        $file = $_FILES['file'];

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = new WorkPlaceMyFiles();
            $checkFile = $f->checkFileNew($file, $checkType);
            if ($checkFile !== true) {
                $result['error'] = 'Файл не прошел проверку по параметрам : <br>' . $checkFile;
                $result['status'] = 403;
            } else {
                $fileName = self::makeFileServerName($file['name']);
                $f->name = $file['name'];
                $f->type = $file['type'];
                $f->size = $file['size'];
                $f->link = $fileName;
                $f->category_id = $catalogId;
                $f->catalogId = $catalogId;
                $f->category_status = $moduleId;
                $f->moduleId = $moduleId;
                $f->save();
                if (move_uploaded_file($file['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWp']) . '/' . $fileName)) {
                    $result['out']['id']  = $f->id;
                    $result['out']['name']  = $f->name;
                    $result['out']['url'] = "/static/uploads/wp/" . $f->link;
                    $result['success']    = true;
                    $transaction->commit();

                    /*if ($categoryStatus == 1) { // отправляем сообщение в чат об добавлении файла

                    } elseif ($categoryStatus == 3)  { // файл из задачника

                    }*/
                } else {
                    $result['error'] = 'Ошибка загрузки файла';
                    $transaction->rollback();
                    $result['status'] = 500;
                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }

        return $result;
    }
}