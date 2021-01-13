<?php

class WorkPlaceFiles extends CActiveRecord
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
        }
        return parent::beforeSave();
    }

    public function tableName(): string
    {
        return '{{workplace_files}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'userTo' => [self::BELONGS_TO, 'User', 'user_id_send'],
            'file' => [self::BELONGS_TO, 'WorkPlaceMyFiles', 'file_id'],
        ];
    }

    public function makeFileInfo($user = false): array
    {
        if ($this->file) {
            $result = [
                'id' => $this->id,
                'comment' => $this->comment,
                'date' => strtotime($this->date),
                'name' => $this->file->name,
                'link' => "/static/uploads/wp/" . $this->file->link,
                'file_id' => $this->file->id,
                'status' => $this->status,
                'search' => $this->file->name . ' ' . date('d.m.Y', strtotime($this->date)),
                'dateD' => null,
                'size' => $this->file->getFileSize(),
            ];


            if ($user) {
                $result['search'] .= ' ' . $user->getKadryFioShort();
            }
        } else {
            $result = [
                'id' => null,
                'comment' => null,
                'date' => null,
                'name' => null,
                'link' => null,
                'file_id' => null,
                'status' => null,
                'search' => null,
                'dateD' => null,
                'size' => null,
            ];
        }

        return $result;
    }

    public static function getFileList(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];

        $user = User::model()->findByPk(Yii::app()->user->id);
        $inUserId = $user->id;

        $data = [];
        // входящие
        $inFiles = WorkPlaceFiles::model()->findAll("user_id_send = $inUserId and status <> 4");
        $inUsers = [];
        foreach ($inFiles as $inFile) {
            $index = $inFile->user_id;
            if ($inFile->status == 1) {
                $inFile->status = 2;
                $inFile->save();
            }
            if (!isset($inUsers[$index])) {
                $inUsers[$index]['count'] = 1;
                $inUsers[$index]['user'] = $inFile->user;
                $inUsers[$index]['userTo'] = $inFile->userTo;
                $inUsers[$index]['userId'] = $inFile->user->id;
                $inUsers[$index]['userFio'] = $inFile->user->getKadryFioShort();
                $inUsers[$index]['files'][] = $inFile;
                if ($inFile->status != 3) {
                    $inUsers[$index]['new'] = 1;
                }
            } else {
                $inUsers[$index]['count']++;
                $inUsers[$index]['files'][] = $inFile;
                if ($inFile->status != 3) {
                    if (isset($inUsers[$index]['new'])) {
                        $inUsers[$index]['new']++;
                    } else {
                        $inUsers[$index]['new'] = 1;
                    }
                }
            }
        }

        usort($inUsers, function ($a, $b) {
            return $a['userFio'] <=> $b['userFio'];
        });

        $userList = [];
        $userFiles = [];
        foreach ($inUsers as $ifDat) {
            $userList[] = User::getUserInfo($ifDat['user']);
            $userFile = new stdClass();
            $userFile->id = $ifDat['userId'];
            $userFile->data = [];
            foreach ($ifDat['files'] as $file) {
                $f = $file->makeFileInfo($file->userTo);
                $f['user'] = User::getUserInfo($file->userTo);

                $userFile->data[] = $f;
            }
            $userFiles[] = $userFile;
        }

        $data['in']['users'] = $userList;
        $data['in']['files'] = $userFiles;

        // исходящие новые
        $outFiles = WorkPlaceFiles::model()->findAll("user_id = $inUserId and status <> 4 AND status < 3");
        $outUsersNew = [];
        foreach ($outFiles as $outFile) {
            $index = $outFile->user_id_send;
            if (!isset($outUsersNew[$index])) {
                $outUsersNew[$index]['count'] = 1;
                $outUsersNew[$index]['user'] = $outFile->user;
                $outUsersNew[$index]['userTo'] = $outFile->userTo;
                $outUsersNew[$index]['userId'] = $outFile->userTo->id;
                $outUsersNew[$index]['userFio'] = $outFile->userTo->getKadryFioShort();
                $outUsersNew[$index]['files'][] = $outFile;
            } else {
                $outUsersNew[$index]['count']++;
                $outUsersNew[$index]['files'][] = $outFile;
            }
        }

        // исходящие старые
        $outFiles = WorkPlaceFiles::model()->findAll("user_id = $inUserId and status <> 4 AND status = 3");
        $outUsersOld = [];
        foreach ($outFiles as $outFile) {
            $index = $outFile->user_id_send;
            if (isset($outUsersNew[$index])) {
                $outUsersNew[$index]['count']++;
                $outUsersNew[$index]['files'][] = $outFile;
            } else {
                if (!isset($outUsersOld[$index])) {
                    $outUsersOld[$index] = [];
                    $outUsersOld[$index]['count'] = 1;
                    $outUsersOld[$index]['user'] = $outFile->user;
                    $outUsersOld[$index]['userTo'] = $outFile->userTo;
                    $outUsersOld[$index]['userId'] = $outFile->userTo->id;
                    $outUsersOld[$index]['userFio'] = $outFile->userTo->getKadryFioShort();
                    $outUsersOld[$index]['files'][] = $outFile;
                } else {
                    $outUsersOld[$index]['count']++;
                    $outUsersOld[$index]['files'][] = $outFile;
                }
            }
        }

        usort($outUsersNew, function ($a, $b) {
            return $a['userFio'] <=> $b['userFio'];
        });
        usort($outUsersOld, function ($a, $b) {
            return $a['userFio'] <=> $b['userFio'];
        });

        $userList = [];
        $userFiles = [];
        foreach ($outUsersNew as $ifDat) {
            $userList[] = User::getUserInfo($ifDat['userTo']);
            $userFile = new stdClass();
            $userFile->id = $ifDat['userId'];
            $userFile->data = [];
            foreach ($ifDat['files'] as $file) {
                if ($file) {
                    $f = $file->makeFileInfo($file->userTo);
                    $f['user'] = User::getUserInfo($file->userTo);
                    $userFile->data[] = $f;
                }
            }
            $userFiles[] = $userFile;
        }
        foreach ($outUsersOld as $ifDat) {
            $userList[] = User::getUserInfo($ifDat['userTo']);
            $userFile = new stdClass();
            $userFile->id = $ifDat['userId'];
            $userFile->data = [];
            foreach ($ifDat['files'] as $file) {
                $f = $file->makeFileInfo($file->userTo);
                $f['user'] = User::getUserInfo($file->userTo);
                $userFile->data[] = $f;
            }
            $userFiles[] = $userFile;
        }

        $data['out']['users'] = $userList;
        $data['out']['files'] = $userFiles;

        $result['data'] = $data;
        $result['success'] = true;

        return $result;
    }

    public static function getCatalogList(): array //getCatalogList
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];

        $status = Yii::app()->request->getParam('status', 0);
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = [];

        try {
            switch ($status) {
                case 0:
                    $criteria = new CDbCriteria();
                    $criteria->addCondition("user_id = $user->id");
                    $criteria->order = 'title ASC';
                    $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
                    $catalogList = [];
                    $catalogList[0] = "Исходящие";
                    foreach ($catalogListDb as $catalog) {
                        $catalogList[$catalog->id] = $catalog->title;
                    }

                    foreach ($catalogList as $key => $catalogName) {
                        $obj = new stdClass();
                        $obj->id = $key;
                        $obj->name = $catalogName;
                        $value = [];
                        $criteria = new CDbCriteria();
                        $criteria->addCondition("catalogId = $key AND user_id = $user->id");
                        $criteria->order = 'name asc';
                        $files = WorkPlaceMyFiles::model()->findAll($criteria);
                        foreach ($files as $file) {
                            $f = new stdClass();
                            $f->id = $file->id;
                            $f->name = $file->name;
                            $f->link = '/static/uploads/wp/' . $file->link;
                            $f->search = $catalogName . '|' . $file->name;
                            $value[] = $f;
                        }
                        $obj->value = $value;
                        $data[] = $obj;
                    }
                    $result['data']['catalog'] = $data;
                    $result['data']['users'] = WorkPlaceChats::getUsersForNewChat();
                    break;

                case 1:
                    $us = WorkPlaceAnaliticsData::getAnalyticalDataList($user);
                    $result['data']['users'] = $us;
                    break;
                default:
                    break;
            }
            $result['success'] = true;
        } catch (Exception $e) {
            $result['success'] = false;
        }
        return $result;
    }

    public static function getMyCatalogFiles(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = [];

        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id");
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        $catalogList = [];
        $catalogList[0]['catalog'] = false;
        $catalogList[0]['type'] = 1;
        $catalogList[0]['name'] = "Исходящие";
        $catalogList[0]['users'] = [];
        $catalogList[0]['catalogUser'] = User::getUserInfo($user);
        $catalogList[0]['my'] = 1;
        $catalogList[0]['id'] = 0;
        foreach ($catalogListDb as $catalog) {
            $catalogList[$catalog->id]['type'] = 1;
            $catalogList[$catalog->id]['name'] = $catalog->title;
            $catalogList[$catalog->id]['catalog'] = $catalog;
            $catalogList[$catalog->id]['catalogUser'] = User::getUserInfo($catalog->user);
            $catalogList[$catalog->id]['my'] = true;
            $catalogList[$catalog->id]['id'] = $catalog->id;
            $userArray = json_decode($catalog->view_user, true);
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
            foreach ($catalog->accessList as $access) {
                $userObj = User::getUserInfo($access->user);
                $userObj['dateAdd'] = $access->created_at;
                $userObj['access'] = $access->access;
                $users[] = $userObj;
            }
            $catalogList[$catalog->id]['users'] = $users;
        }

        // выбираем каталоги, в которых пользователь участник
        $criteria = new CDbCriteria();
        $searchStr = '"id":"' . $user->id . '"';
        $criteria->addSearchCondition('view_user', $searchStr);
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        foreach ($catalogListDb as $catalog) {
            $catalogList[$catalog->id]['type'] = 2;
            $catalogList[$catalog->id]['name'] = $catalog->title;
            $catalogList[$catalog->id]['catalog'] = $catalog;
            $catalogList[$catalog->id]['catalogUser'] = User::getUserInfo($catalog->user);
            $catalogList[$catalog->id]['my'] = false;
            $catalogList[$catalog->id]['id'] = $catalog->id;
            $userArray = json_decode($catalog->view_user, true);
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
            foreach ($catalog->accessList as $access) {
                $userObj = User::getUserInfo($access->user);
                $userObj['dateAdd'] = $access->created_at;
                $userObj['access'] = $access->access;
                $users[] = $userObj;
            }
            $catalogList[$catalog->id]['users'] = $users;
        }

        // выбираем каталоги, в которых пользователь участник из таблицы доступов
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id");
        $catalogListDb = WorkPlaceMyFilesCategoriesAccess::model()->findAll($criteria);
        foreach ($catalogListDb as $catalogAccess) {
            if ($catalogAccess->catalog) {
                $catalog = $catalogAccess->catalog;
                $catalogList[$catalog->id]['type'] = 3;
                $catalogList[$catalog->id]['name'] = $catalog->title;
                $catalogList[$catalog->id]['catalog'] = $catalog;
                $catalogList[$catalog->id]['catalogUser'] = User::getUserInfo($catalog->user);
                $catalogList[$catalog->id]['my'] = false;
                $catalogList[$catalog->id]['id'] = $catalog->id;
                $userArray = json_decode($catalog->view_user, true);
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
                foreach ($catalog->accessList as $access) {
                    $userObj = User::getUserInfo($access->user);
                    $userObj['dateAdd'] = $access->created_at;
                    $userObj['access'] = $access->access;
                    $users[] = $userObj;
                }
                $catalogList[$catalog->id]['users'] = $users;
            }
        }

        foreach ($catalogList as $key => $catalog) {
            $obj = new stdClass();
            $obj->id = $catalog['id'];
            $obj->name = $catalog['name'];
            $obj->my = $catalog['my'];
            $obj->type = $catalog['type'];
            $obj->users = $catalog['users'];
            $obj->catalogUser = $catalog['catalogUser'];
            $value = [];
            /*$criteria = new CDbCriteria();
            $criteria->addCondition("catalogId = $key AND user_id = $user->id");
            $criteria->order = 'name asc';
            $files = WorkPlaceMyFiles::model()->findAll($criteria);*/
            if ($catalog['catalog']) {
                $files = $catalog['catalog']->files;
            } else {
                $criteria = new CDbCriteria();
                $criteria->addCondition("user_id = $user->id AND category_id = 0");
                $criteria->order = "id DESC";
                $files = WorkPlaceMyFiles::model()->findAll($criteria);
            }

            foreach ($files as $file) {
                $value[] = $file->makeFileInfoObject($catalog['name']);
            }
            $obj->value = $value;
            $data[] = $obj;
        }
        $result['data']['user'] = User::getUserInfo($user);
        $result['data']['catalog'] = $data;
        $result['data']['users'] = WorkPlaceChats::getUsersForNewChat();
        $result['data']['token'] = session_id();

        $result['success'] = true;
        return $result;
    }

    public static function getMyCatalogFilesNew(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = [];

        // создаем дефолтный каталог
        $catalog0 = new stdClass();
        $catalog0->catalog = false;
        $catalog0->type = 1;
        $catalog0->name = "Исходящие";
        $catalog0->users = [];
        $catalog0->catalogUser = User::getUserInfo($user);
        $catalog0->my = 1;
        $catalog0->id = 0;
        $catalog0->parent_id = 0;
        $catalogArray = [];

        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id AND category_id = 0");
        $criteria->order = "id DESC";
        $files = WorkPlaceMyFiles::model()->findAll($criteria);
        $value = [];
        foreach ($files as $file) {
            $value[] = $file->makeFileInfoObject("Исходящие");
        }
        $catalog0->value = $value;
        $catalog0->fileArray = $value;
        $data[] = $catalog0;

        // ищем все каталоги пользователя
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id AND parent_id = 0");
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        $catalogList = [];
        foreach ($catalogListDb as $catalog) {
            $data[] = $catalog->makeCatalogData(1);
        }

        // выбираем каталоги, в которых пользователь участник
        $criteria = new CDbCriteria();
        $searchStr = '"id":"' . $user->id . '"';
        $criteria->addSearchCondition('view_user', $searchStr);
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        foreach ($catalogListDb as $catalog) {
            $data[] = $catalog->makeCatalogData(2);
        }

        // выбираем каталоги, в которых пользователь участник из таблицы доступов
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id");
        $catalogListDb = WorkPlaceMyFilesCategoriesAccess::model()->findAll($criteria);
        foreach ($catalogListDb as $catalogAccess) {
            if ($catalogAccess->catalog) {
                $catalog = $catalogAccess->catalog;
                $data[] = $catalog->makeCatalogData(3);
            }
        }
        $result['data']['user'] = User::getUserInfo($user);
        $result['data']['catalog'] = $data;
        $result['data']['users'] = WorkPlaceChats::getUsersForNewChat();
        $result['data']['token'] = session_id();

        $result['success'] = true;
        return $result;
    }

    public static function getMyCatalogFilesOutTree(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $user = User::getUserUser();
        $data = [];
        $dataIds = [];

        // создаем дефолтный каталог
        $catalog0 = new stdClass();
        $catalog0->catalog = false;
        $catalog0->type = 1;
        $catalog0->name = "Исходящие";
        $catalog0->users = [];
        $catalog0->catalogUser = User::getUserInfo($user);
        $catalog0->my = 1;
        $catalog0->id = 0;
        $catalog0->parent_id = 0;
        $catalog0->catalogArray = [];

        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id AND category_id = 0");
        $criteria->order = "id DESC";
        $files = WorkPlaceMyFiles::model()->findAll($criteria);
        $value = [];
        foreach ($files as $file) {
            $value[] = $file->makeFileInfoObject("Исходящие");
        }
        $catalog0->value = $value;
        $catalog0->fileArray = $value;

        $data[] = $catalog0;

        // ищем все каталоги пользователя
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id ");
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        $catalogList = [];
        foreach ($catalogListDb as $catalog) {
            $data[] = $catalog->makeCatalogDataOutTree(1);
        }

        // выбираем каталоги, в которых пользователь участник
        $criteria = new CDbCriteria();
        $searchStr = '"id":"' . $user->id . '"';
        $criteria->addSearchCondition('view_user', $searchStr);
        $criteria->order = 'title asc';
        $catalogListDb = WorkPlaceMyFilesCategories::model()->findAll($criteria);
        foreach ($catalogListDb as $catalog) {
            $data[] = $catalog->makeCatalogDataOutTree(2);
        }

        // выбираем каталоги, в которых пользователь участник из таблицы доступов
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = $user->id");
        $catalogListDb = WorkPlaceMyFilesCategoriesAccess::model()->findAll($criteria);
        foreach ($catalogListDb as $catalogAccess) {
            if ($catalogAccess->catalog) {
                $catalog = $catalogAccess->catalog;
                $data[] = $catalog->makeCatalogDataOutTree(3);
            }
        }
        $result['data']['user'] = User::getUserInfo($user);
        $result['data']['catalog'] = $data;
        $result['data']['users'] = WorkPlaceChats::getUsersForNewChat();
        $result['data']['token'] = session_id();

        $result['success'] = true;
        return $result;
    }

    public static function sendFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $file_list = Yii::app()->request->getParam('file_list');
        $comment = Yii::app()->request->getParam('text');
        $user_id = Yii::app()->request->getParam('user_id');
        $user = User::model()->findByPk(Yii::app()->user->id);
        if (!empty($file_list)) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                foreach ($file_list as $item) {
                    $workPlaceFile = new WorkPlaceFiles();
                    $workPlaceFile->comment = $comment;
                    $workPlaceFile->user_id_send = $user_id;
                    $workPlaceFile->file_id = $item;
                    $workPlaceFile->save();
                    if (!empty($workPlaceFile->user_id_send)) {
                        WorkPlaceNotification::addRecord($workPlaceFile->user_id_send, $workPlaceFile->id, 'WorkPlaceFiles');
                        $userSend = User::model()->findByPk($workPlaceFile->user_id_send);
                        Sms::model()->sendSMSUser($userSend, 'от ' . $user->getKadryFioShort() . ' получен файл.', 'WorkPlaceFiles');
                    }
                }
                $result['success'] = true;
                $result['data']['text'] = count($file_list) . ' ' . Tools::inflector(count($file_list), ['файл успешно отправлен', 'файла успешно отправлены', 'файлов успешно отправлены']);
                $result['data']['id'] = $file_list;
                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = 'Ошибка выполнения операции ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = 'Пустой список файлов';
            $result['status'] = 400;
        }
        return $result;
    }

    // старое

    public function add(array $data)
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
            $result = self::sendSmsForYuer($data['user_id']);
        }
        return $result;
    }

    public function create(array $data)
    {
        $this->comment = $data['comment'];
        $this->user_id_send = $data['user_id'];
        $this->file_id = $data['file'];
    }

    public static function sendSmsForYuer($id): bool
    {
        $user = User::model()->findByPk($id);
        if (Sms::model()->sendSMSUser($user, "Вы получили файл от " . Yii::app()->user->title, 'wp')) return true;
        return false;
    }

    public static function setLinkUploadForFileTransfer(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $file_id = Yii::app()->request->getParam('file_id');
        $catalog_id = Yii::app()->request->getParam('catalog_id');

        $file = WorkPlaceMyFiles::model()->findByPk($file_id);
        try {
            $transaction = Yii::app()->db->beginTransaction();

            if ($file) {
                if ($file->category_id == $catalog_id) {
                    if ($file->upload_user != Yii::app()->user->id) {
                        $file->upload_user = Yii::app()->user->id;

                        $file->save();

                        $transaction->commit();
                        $result['success'] = true;
                        $result['status'] = 200;
                    }
                } else {
                    $result['error'] = 'Вы уже скачивали этот файл';
                    $transaction->rollback();
                    $result['status'] = 500;
                }
            } else {
                $result['error'] = 'Не найден файл ' . $file_id;
                $transaction->rollback();
                $result['status'] = 500;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function setLinkUploadForFileSend(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $file_id = Yii::app()->request->getParam('file_id');
        $id = Yii::app()->request->getParam('file_user_id');
        $user_id = Yii::app()->request->getParam('user_id');
        $position = Yii::app()->request->getParam('position');

        $user = User::model()->findByPk(Yii::app()->user->id);
        $inUserId = $user->id;

        try {
            $transaction = Yii::app()->db->beginTransaction();

            switch ($position) {
                case 'in':
                    $inFiles = WorkPlaceFiles::model()->find("id = $id and user_id_send = $inUserId and status <> 4");
                    if ($inFiles->file_id == $file_id && $inFiles->id == $id) {

                        if ($inFiles->upload_user != Yii::app()->user->id) {
                            $inFiles->upload_user = Yii::app()->user->id;
                            $inFiles->save();

                            $transaction->commit();
                            $result['success'] = true;
                            $result['status'] = 200;
                        }
                    } else {
                        $result['error'] = 'Вы уже скачивали этот файл';
                        $transaction->rollback();
                        $result['status'] = 500;
                    }
                    break;

                case 'out':
                    $outFiles = WorkPlaceFiles::model()->find("id = $id and user_id = $inUserId and status <> 4 AND status < 3");
                    if ($outFiles->file_id == $file_id && $outFiles->id == $id) {

                        if ($outFiles->upload_user != Yii::app()->user->id) {
                            $outFiles->upload_user = Yii::app()->user->id;
                            $outFiles->save();

                            $transaction->commit();
                            $result['success'] = true;
                            $result['status'] = 200;
                        }
                    } else {
                        $result['error'] = 'Вы уже скачивали этот файл';
                        $transaction->rollback();
                        $result['status'] = 500;
                    }
                    break;

                default:
                    break;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }

}