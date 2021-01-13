<?

class WorkPlaceMyFilesCategories extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return '{{workplace_my_files_categories}}';
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

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'files' => [self::HAS_MANY, 'WorkPlaceMyFiles', ['category_id' => 'id'], 'order' => 'id DESC'],
            'accessList' => [self::HAS_MANY, 'WorkPlaceMyFilesCategoriesAccess', 'category_id'],
            'childs' => [self::HAS_MANY, 'WorkPlaceMyFilesCategories', 'parent_id', 'order' => 'title ASC'],
            'parent' => [self::BELONGS_TO, 'WorkPlaceMyFilesCategories', 'parent_id'],
        ];
    }

    public function makeCatalogData($type): stdClass
    {
        $result = new stdClass();
        $result->id = $this->id;
        $result->parent_id = $this->parent_id;
        $result->name = $this->title;
        $result->catalog = $this;
        $result->catalogUser = User::getUserInfo($this->user);
        $result->type = $type;
        $result->my = $this->user_id == Yii::app()->user->id;


        $userArray = json_decode($this->view_user, true);
        $users = [];
        if ($userArray && is_array($userArray)) foreach ($userArray as $k => $u) {
            $user1 = User::model()->findByPk($k);
            if ($user1) {
                $userObj = User::getUserInfo($user1);
                $userObj['dateAdd'] = $u['date'];
                $userObj['access'] = 0;
                $users[] = $userObj;
            }
        }
        foreach ($this->accessList as $access) {
            $userObj = User::getUserInfo($access->user);
            $userObj['dateAdd'] = $access->created_at;
            $userObj['access'] = $access->access;
            $users[] = $userObj;
        }
        $result->users = $users;

        $value = [];
        foreach ($this->files as $file) {
            $value[] = $file->makeFileInfoObject($this->title);
        }
        $result->fileArray = $value;
        $result->value = $value;

        $catalogArray = [];
        $catalog = $this;
        if ($catalog->childs) foreach ($catalog->childs as $child) {
            $catalogArray[] = $child->makeCatalogData($type);
        }
        $result->catalogArray = $catalogArray;

        return $result;
    }

    public function makeCatalogDataOutTree($type): stdClass
    {
        $result = new stdClass();
        $result->id = $this->id;
        $result->parent_id = $this->parent_id;
        $result->name = $this->title;
        $result->catalog = $this;
        $result->catalogUser = User::getUserInfo($this->user);
        $result->type = $type;
        $result->my = $this->user_id == Yii::app()->user->id;


        $userArray = json_decode($this->view_user, true);
        $users = [];
        if ($userArray && is_array($userArray)) foreach ($userArray as $k => $u) {
            $user1 = User::model()->findByPk($k);
            if ($user1) {
                $userObj = User::getUserInfo($user1);
                $userObj['dateAdd'] = $u['date'];
                $userObj['access'] = 0;
                $users[] = $userObj;
            }
        }
        foreach ($this->accessList as $access) {
            $userObj = User::getUserInfo($access->user);
            $userObj['dateAdd'] = $access->created_at;
            $userObj['access'] = $access->access;
            $users[] = $userObj;
        }
        $result->users = $users;

        $value = [];
        foreach ($this->files as $file) {
            $value[] = $file->makeFileInfoObject($this->title);
        }
        $result->fileArray = $value;
        $result->value = $value;

        $catalogArray = [];
        $catalog = $this;
        if ($type == 1 && $catalog->childs) foreach ($catalog->childs as $child) {
            $catalogArray[] = $child->id;
        }
        $result->catalogArray = $catalogArray;

        return $result;
    }

    public static function addFilesToCatalog(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => []];
        $catalog_id = Yii::app()->request->getParam('catalog_id');
        $file_array = Yii::app()->request->getParam('file_array');
        $transaction = Yii::app()->db->beginTransaction();
        try {
            // переносим файлы в каталог
            $files = WorkPlaceMyFiles::model()->findAllByPk($file_array);
            $resiltFiles = [];
            foreach ($files as $file) {
                $resiltFiles[] = $file->makeFileInfoObject();
                $file->category_id = $catalog_id;
                $file->save();
            }
            $transaction->commit();
            $result['out'] = $resiltFiles;
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function addUserToCatalog(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => []];
        $catalog_id = Yii::app()->request->getParam('catalog_id');
        $user_id = Yii::app()->request->getParam('user_id');
        $user_access = Yii::app()->request->getParam('user_access', 0);
        try {
            WorkPlaceMyFilesCategoriesAccess::addUserToCatalog($catalog_id, $user_id, $user_access);
            $result['out'] = User::getUserInfo(User::model()->findByPk($user_id));
            $result['out']['dataAdd'] = strtotime(date('Y-m-d H:i:s'));
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function deleteUserFromCatalog(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link' => '']];
        $catalog_id = Yii::app()->request->getParam('catalog_id');
        $user_id = Yii::app()->request->getParam('user_id');
        $transaction = Yii::app()->db->beginTransaction();
        try {
            WorkPlaceMyFilesCategoriesAccess::deleteUserFromCatalog($catalog_id, $user_id);
            $transaction->commit();
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function renameCatalog(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link' => '']];
        $catalog_id = Yii::app()->request->getParam('catalog_id');
        $catalog_title = Yii::app()->request->getParam('catalog_title');
        $catalog = WorkPlaceMyFilesCategories::model()->findByPk($catalog_id);
        if ($catalog) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $catalog->title = $catalog_title;
                $catalog->save();
                $transaction->commit();
                $result['success'] = true;
            } catch (Exception $e) {
                $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = "Не найден каталог по Id $catalog_id";
        }
        return $result;
    }

    public static function addMyFileCatalog(): array
    {
        // catalog_name, user_array, file_array
        $user = User::model()->findByPk(Yii::app()->user->id);
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => []];
        $catalog_name = Yii::app()->request->getParam('catalog_name');
        $user_array = Yii::app()->request->getParam('user_array');
        $catalog_id = Yii::app()->request->getParam('catalog_id', 0);
        $file_array = Yii::app()->request->getParam('file_array');
        // проверяем полученные данные
        $errors = [];
        if (!$catalog_name) {
            $errors[] = "Не задано имя каталога";
        }

        if (count($errors) == 0) {
            // проверяем наличие каталога с таким именем у пользователя
            $criteria = new CDbCriteria();
            $criteria->addCondition("user_id = $user->id");
            $criteria->addCondition("parent_id = '$catalog_id'");
            $criteria->addCondition("title = '$catalog_name'");
            $tmpCatalog = WorkPlaceMyFilesCategories::model()->find($criteria);
            if (!$tmpCatalog) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    // создаем каталог
                    $catalog = new WorkPlaceMyFilesCategories();
                    $catalog->title = $catalog_name;
                    $catalog->parent_id = $catalog_id;
                    $catalog->save();
                    // добавляем доступ пользователям
                    WorkPlaceMyFilesCategoriesAccess::addUserToCatalog($catalog->id, $user_array, 0, false);
                    // переносим файлы в каталог
                    $files = WorkPlaceMyFiles::model()->findAllByPk($file_array);
                    foreach ($files as $file) {
                        $file->category_id = $catalog->id;
                        $file->save();
                    }
                    $result['out'] = $catalog->makeCatalogObject();
                    $transaction->commit();
                    $result['success'] = true;
                } catch (Exception $e) {
                    $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
                    $transaction->rollback();
                    $result['status'] = 500;
                }
            } else {
                $result['error'] = "Каталог с именем [$catalog_name] уже существует";
                $result['status'] = 400;
            }
        } else {
            $result['error'] = implode('<br>', $errors);
            $result['status'] = 400;
        }
        return $result;
    }

    public function makeCatalogObject(): stdClass
    {

        $obj = new stdClass();
        $obj->id = $this->id;
        $obj->parent_id = $this->parent_id;
        $obj->name = $this->title;
        $obj->my = $this->user_id == Yii::app()->user->id;
        $obj->type = 4;
        $obj->users = [];
        foreach ($this->accessList as $access) {
            $userObj = User::getUserInfo($access->user);
            $userObj['dateAdd'] = $access->created_at;
            $userObj['access'] = $access->access;
            $obj->users[] = $userObj;
        }
        $obj->catalogUser = User::getUserInfo($this->user);
        $value = [];

        foreach ($this->files as $file) {
            $value[] = $file->makeFileInfoObject();
        }
        $obj->value = $value;
        $obj->filesArray = $value;
        $obj->catalogArray = [];

        return $obj;
    }

    public static function renameFolderWithFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $c_id = Yii::app()->request->getParam('id');
        $c_name = Yii::app()->request->getParam('name');
        $errors = [];
        if (!$c_id || $c_id == 0 || $c_id == '') {
            $errors[] = "Не указан каталог";
        }
        if (count($errors) == 0) {
            $result = WorkPlaceMyFilesCategoriesAccess::renameCatalog($c_id, $c_name);
        } else {
            $result['error'] = implode("<br>", $errors);
            $result['status'] = 400;
        }
        return $result;
    }

    public static function deleteCatalogFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ''];

        $catalog_id = Yii::app()->request->getParam('id');
        $catalogId = WorkPlaceMyFilesCategories::model()->findByPk($catalog_id);
        $user = Yii::app()->user->id;

        if ($user == $catalogId->user_id) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                WorkPlaceMyFilesCategoriesAccess::deleteCatalog($catalog_id);
                $result['success'] = true;
                $transaction->commit();
            }
            catch (Exception $e) {
                $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = "У Вас не достаточно прав";
            $result['success'] = false;
            $result['status'] = 500;
        }
        return $result;
    }

    public static function deleteFolderWithFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $catalog_id = Yii::app()->request->getParam('id');

        $transaction = Yii::app()->db->beginTransaction();
        try {
            WorkPlaceMyFilesCategoriesAccess::deleteCatalog($catalog_id);
            $transaction->commit();
            $result['success'] = true;
        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
            $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

}