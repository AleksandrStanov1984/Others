<?

class WorkPlaceMyFiles extends CActiveRecord
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

    protected function beforeSave()
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

    public function tableName()
    {
        return '{{workplace_my_files}}';
    }


    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
        ];
    }

    public function getFileSize()
    {
        return round($this->size / 1024 / 1024, 3);
    }

    public function checkFile($file)
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
        $result = [];
        $low = Tools::checkAccess('wp', 'low_file_loads', 'edit', 0);
        $middle = Tools::checkAccess('wp', 'middle_file_loads', 'edit', 0);
        $high = Tools::checkAccess('wp', 'high_file_loads', 'edit', 0);
        if ($checkType) {
            $type = $this->typeChat;
        } else {
            $type = $this->type;
        }
        if (!in_array($file['type'], $type)) {// проверяем на сам type файла
            $result[] = "Не допустимый тип файла " . $file['type'];
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

    public function add(array $data)
    {
//        var_dump($data['file']);
//        exit();
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

    public function upload(array $data)
    {
        $result = false;
        if (!$this->checkFile($data['file']['wp_files'])) return $result; //проверка прав доступа
        if (!isset($data['name'])) $data['name'] = self::makeFileServerName($data['file']['wp_files']['name']);
        $this->create($data);
        if (move_uploaded_file($data['file']['wp_files']['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWp']) . '/' . $data['name'])) {
            if ($this->save()) $result = true;
        }
        return $result;
    }

    public function multiUpLoad(array $data)
    {
        $result = false;
        if (!$this->checkFile($data['file'])) return $result; //проверка прав доступа
        $data['name'] = self::makeFileServerName($data['file']['name']);
        $this->create($data);
        if (move_uploaded_file($data['file']['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWp']) . '/' . $data['name'])) {
            if ($this->save()) $result = true;
        }
        return $result;
    }

    public function create(array $data)
    {
        $this->name = ($data['file']['wp_files']['name']) ?? $data['file']['name'];
        $this->type = ($data['file']['wp_files']['type']) ?? $data['file']['type'];
        $this->size = ($data['file']['wp_files']['size']) ?? $data['file']['size'];
        $this->link = $data['name'];
        $this->category_id = $data['category_id'];
        $this->category_status = ($data['category_status']) ?? 0;
    }

    public static function makeFileServerName(string $name): string
    {
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        return str_replace('.', '', microtime(true)) . '.' . $extension;
    }

    // API

    public function makeFileInfoObject($catalogName = '')
    {
        $user = User::getUserUser(Yii::app()->user->id);
        $f = new stdClass();
        $f->id = $this->id;
        $f->name = $this->name;
        $f->my = $this->user_id == $user->id;
        $f->link = '/static/uploads/wp/' . $this->link;
        $f->user = User::getUserInfo($this->user);
        $f->date = strtotime($this->created_at);
        $f->search = ($catalogName != '' ? $catalogName . '|' : '') . $this->name;
        $f->size = $this->getFileSize();
        $f->upload = $this->upload_user == Yii::app()->user->id;
        return $f;
    }

    public static function uploadMessageFile($moduleId): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link' => '']];
        $catalogId = 0;
        $moduleId = $moduleId;
        $file = $_FILES['file'];

        $transaction = Yii::app()->db->beginTransaction();
        try {
            $f = new WorkPlaceMyFiles();
            $checkFile = $f->checkFileNew($file, true);
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
                if (move_uploaded_file($file['tmp_name'], Yii::getPathOfAlias(Yii::app()->params['uploadDirWpMessage']) . '/' . $fileName)) {
                    $result['out']['id'] = $f->id;
                    $result['out']['name'] = $f->name;
                    $result['out']['url'] = "/static/uploads/wpMessage/" . $f->link;
                    $result['success'] = true;
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

    public static function uploadMyFile($checkType = false): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link' => '']];
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
                    $result['out']['id'] = $f->id;
                    $result['out']['name'] = $f->name;
                    $result['out']['date'] = strtotime($f->created_at);
                    $result['out']['url'] = "/static/uploads/wp/" . $f->link;
                    $result['success'] = true;
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

    public static function downloadFile()
    {
        $id = Yii::app()->request->getParam('id');
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = WorkPlaceFiles::model()->findByPk($id);
        if ($data && $data->user_id == $user->id && $data->status != 4) {
            $dir = __DIR__ . '/../../static/uploads/wp/';
            if (file_exists($dir . $data->file->link)) {
                ToolsLog::toLog('Юзер ID:' . Yii::app()->user->id . ' скачал файл № ' . $data->file->id . ' путь: ' . $dir . $data->file->link . ' - ' . date('d-m-Y (h:i:s)', time()));
                header("Content-type: application/octet-stream; charset=UTF-8");
                header('Content-Disposition: attachment; filename="' . $data->file->name . '"');
                readfile($dir . $data->file->link);
                exit;
            }
        } else {
            if (!empty($data)) {
                ToolsLog::toLog('Юзер ID:' . Yii::app()->user->id . ' Сделал правонарушение, пытаясь скачать чужой файл - ' . date('d-m-Y (h:i:s)', time()));
                echo 'Вот ты друг и попался Ха-Ха-Ха!!!!';
                //----------------------------------
                echo 'Ваш ID:' . Yii::app()->user->id . ' заблокирован, для розблакировки обратитесь к администратору или вышестоящеу руководству =)';
                //---------------------------------
            } else {
                echo 'Нет данных =)';
            }
        }
    }

    public static function valid($nameUrl, $nameFile):string
    {
        $count = sizeof( $nameFile = explode(".", $nameFile));
        $nameUrl .= '.'.$nameFile[$count - 1];
        return $nameUrl;
    }

    public static function renamesFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 400];

        $id = Yii::app()->request->getParam('id');
        $name = Yii::app()->request->getParam('name');

        $file = WorkPlaceMyFiles::model()->findByPk($id);

        $user = Yii::app()->user->id;
        if ($file) {
            //    if ($user == $file->user_id) {
            $transaction = Yii::app()->db->beginTransaction();
            try {

                $nameTmp = WorkPlaceMyFiles::valid($name, $file->name);

                $file->name =  $nameTmp;
                $file->save();

                $result['out']['id'] = $file->id;
                $result['out']['name'] = $file->name;
                $result['status'] = 200;
                $result['success'] = true;

                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = 'Ошибка переименования файла ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = "Вы не являетесь создателем файла";
            $result['success'] = false;
            $result['status'] = 500;
        }
//        } else {
//            $result['error'] = "Не найден файл по Id $id";
//            $result['success'] = false;
//            $result['status'] = 500;
//        }
        return $result;
    }

    public static function deleteFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ''];
        $id = Yii::app()->request->getParam('id');
        $file = WorkPlaceMyFiles::model()->findByPk($id);
        $user = Yii::app()->user->id;
        if ($file) {
            if ($user == $file->user_id) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $file->delete();

                    $result['out'] = true;
                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $result['error'] = 'Ошибка удаления файла ' . $e->getMessage();
                    $transaction->rollback();
                    $result['status'] = 500;
                }
            } else {
                $result['error'] = "Вы не являетесь создателем файла";
                $result['success'] = false;
                $result['status'] = 500;
            }
        } else {
            $result['error'] = "Не найден файл по Id $id";
            $result['success'] = false;
            $result['status'] = 500;
        }
        return $result;
    }

    public static function setLinkUploadForTask($checkType = false){
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link'=>'']];
        $catalogId = Yii::app()->request->getParam('catalog_id', null);
        $moduleId = Yii::app()->request->getParam('module_id', null);
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
    }

}