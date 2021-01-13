<?

class WorkPlaceConflict extends CActiveRecord
{
    protected $fields = [
        'title' => 'Тема конфликта - ',
        'text' => 'Суть конфликта - ',
        'user_conflict' => 'Участник конфликта - ',
        'head_conflict' => 'Начальник участник конфликта - ',
        'file' => 'Прикреплён файл - ',
        'view_user' => 'Кто еще видит - ',
    ];

    const MODULE_NAME = 'conflict';

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

    public function tableName(): string
    {
        return '{{workplace_conflict}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'],
            'complain' => [self::BELONGS_TO, 'User', 'user_conflict'],
            'head' => [self::BELONGS_TO, 'User', 'head_conflict'],
            'comments' => [self::HAS_MANY, 'WorkPlaceConflictComments', 'conflict_id'],// Комменты
            'userMakeConflict' => [self::BELONGS_TO, 'User', 'user_id'],
            'files' => [self::HAS_MANY, 'WorkPlaceConflictFiles', 'conflict_id'],
        ];
    }

    // API

    public $memberRoleList = [
        'boss' => 'Руководитель',
        'creator' => 'Создатель',
        'complain' => 'На кого пожаловались',
        'obozrevatel' => 'Обозреватели',
    ];

    const STATE_FIELD_NAME = [
        1 => [
            'column1' => 'text',
            'column2' => 'conflict_text',
            'column3' => 'head_text',
        ],
        '2' => [
            'column1' => 'result_text',
            'column2' => 'result_conflict_text',
            'column3' => 'result_head_text',
        ]
    ];

    public static function getFieldName($state, $column)
    {
        $result = false;
        if (isset(self::STATE_FIELD_NAME[$state]) && isset(self::STATE_FIELD_NAME[$state][$column])) {
            $result = self::STATE_FIELD_NAME[$state][$column];
        }
        return $result;
    }

    public function makeAccess($user = false): stdClass
    {
        if (!$user) {
            $user = User::model()->findByPk(Yii::app()->user->id);
        }
        // определяем пренадлежность пользователя к данному конфликту
        $isCreator = $user->id == $this->user_id;
        $isBoss = $user->id == $this->head_conflict;
        $isconflicted = $user->id == $this->user_conflict;
        $isObozrevatel = false;
        foreach (User::getUsersListFromJson($this->view_user) as $u) {
            if ($user->id == $u['id']) {
                $isObozrevatel = true;
                break;
            }
        }
        $result = new stdClass();
        $result->isBoss = $isBoss;
        $result->isCreator = $isCreator;
        $result->isObozrevatel = $isObozrevatel;
        $result->isconflicted = $isconflicted;
        return $result;
    }

    public function makeTools(): stdClass
    {
        $result = new stdClass();
        $result->my = $this->user_id == Yii::app()->user->id;
        $state = 1;
        if (!empty($this->text) && !empty($this->head_text) && ($this->conflict_view == 1))
            $state = 2;
        if (!empty($this->text) && !empty($this->head_text) && ($this->conflict_view == 0))
       //     $state = 1; // и жёдм conflict_text
            $state = 2; // и жёдм conflict_text
        if (!empty($this->result_text))
            $state = 2; // conflict_view  уже не может быть 1

        $result->state = $state;
        $result->addedConflicted = $this->conflict_view ? true : false;

        $access = $this->makeAccess();
        /*
                $result->isBoss = $isBoss;
                $result->isCreator = $isCreator;
                $result->isObozrevatel = $isObozrevatel;
                $result->isconflicted = $isconflicted;
        */
        $result->column1 = new stdClass();
        $result->column1->name = 'column1';
        $result->column2 = new stdClass();
        $result->column2->name = 'column2';
        $result->column3 = new stdClass();
        $result->column3->name = 'column3';

        $result->column1->use = $access->isCreator && $state == 2 && !$this->result_text;

        if ($state == 1) {
            $column2 = ($access->isCreator && !$this->conflict_text && !$this->conflict_view) || ($this->conflict_view && $access->isconflicted && !$this->conflict_text);
            $column3 = $access->isBoss && !$this->head_text;
        } else {
            $column2 = $this->conflict_view && $access->isconflicted && !$this->result_conflict_text;
            $column3 = $access->isBoss && !$this->result_head_text;
        }
        $result->column2->use = $column2;
        $result->column3->use = $column3;
        return $result;
    }

    public function makeTextResolve(): stdClass
    {
        $result = new stdClass();
        $result->textMakeConflict = $this->text ?? '';
        $result->textConflicted = $this->conflict_text ?? '';
        $result->textAnalyze = $this->head_text ?? '';
        return $result;
    }

    public function makeTextSolution(): stdClass
    {
        $result = new stdClass();
        $result->textMakeConflict = $this->result_text ?? '';
        $result->textConflicted = $this->result_conflict_text ?? '';
        $result->textAnalyze = $this->result_head_text ?? '';
        return $result;
    }

    public function makeConflictInfoFull(): stdClass
    {
        $user = User::model()->findByPk(Yii::app()->user->id);
        //$user   = Yii::app()->user->id;
        $comments = [];
        foreach ($this->comments as $comment) {
            $comments[] = [
                'id' => $comment->id,
                'text' => $comment->comment,
                'url' => $comment->file ? "/static/uploads/wp/" . $comment->file->link : null,
                'date' => strtotime($comment->date),
                'user' => User::getUserInfo($comment->user),
                "my" => ($comment->user_id == $user->id) ? true : false, // мое сообщение или нет
                'system' => ($comment->system || $comment->file) ? 1 : 0, // системное или файл - удаление, добавление пользователя, нельзя редактировать
                'quote' => ($comment->quote > 0) ? $comment->quote : false, // является сообщение ответом на другое сообщение или нет
                'updated' => (strtotime($comment->created_at) < strtotime($comment->updated_at)) ? 1 : 0,
            ];
        }
        $files = [];
        foreach ($this->files as $file) {
            $files[] = [
                'id' => $file->id,
                'user' => User::getUserInfo($file->user),
                'file_id' => $file->file->id,
                'name' => $file->file->name,
                'link' => "/static/uploads/wp/" . $file->file->link,
                'date' => strtotime($file->created_at),
                'start' => (/*($file->user_id == $this->created_user) &&*/
                ($file->created_at == $this->created_at)) ? 1 : 0, // файл создан при  создании задачи или нет
                'upload' => $file->upload_user == Yii::app()->user->id,
            ];
        }


        $result = new stdClass();

        $result->conflictData = new stdClass();
        $result->conflictData->id = $this->id;

        $result->conflictData->title = $this->title;
        $result->conflictData->emoji_id = $this->chagrin;
        $result->conflictData->in_archive = $this->status ? true : false;
        $result->conflictData->textResolve = $this->makeTextResolve();
        $result->conflictData->textSolution = $this->makeTextSolution();
        $result->conflictData->userMakeConflict = User::getUserInfo($this->userMakeConflict);
        $result->conflictData->userConflicted = User::getUserInfo($this->complain);
        $result->conflictData->userAnalyze = User::getUserInfo($this->head);
        $result->conflictData->my = $user->id == $this->user_id ? true : false;
        $result->observersArray = User::getUsersListFromJson($this->view_user);
        $result->attachedFiles = $files;
        $result->comments = $comments;
        $result->tools = $this->makeTools();

        return $result;
    }

    public function getMember($typ): array
    {
        $result = [];
        switch ($typ) {
            case 'boss' : // прямой руководитель
                $result[] = OkSprPdr::findBoss($this->pdr_id, 'user');
                break;
            case 'creator' : // создатель
                $result[] = $this->user;
                break;
            case 'complain' : // исполнитель
                $result[] = $this->complain;
                break;
            case 'obozrevatel': // обозреватели
                foreach (User::getUsersListFromJson($this->view_user) as $u) {
                    $result[] = $u;
                }
                break;
        }
        return $result;
    }

    public function makeSearchStr(): string
    {
        return $this->userMakeConflict->getKadryFioShort() . "|" . $this->title . "|" . date('d.m.Y', strtotime($this->created_at)) . "|" . $this->complain->getKadryFioShort();
    }

    public function makeConflictInfo()
    {
        $result = new stdClass();
        $content = new stdClass();
        $content->color_id = $this->color_id;
        $content->count = WorkPlaceNotification::getCount(Yii::app()->user->id, $this->id, 'WorkPlaceConflict');
        $content->data = strtotime($this->created_at);
        $content->id = $this->id;
        $content->my = $this->userMakeConflict->id = Yii::app()->user->id ? true : false;
        $content->searchStr = $this->makeSearchStr();
        $content->status = $this->status;
        $content->title = $this->title;
        $content->updated_at = strtotime($this->updated_at);
        $content->userMakeConflict = User::getUserInfo($this->userMakeConflict);
        $content->userMakeConflict['id'] = $this->user_id;
        $content->userMakeConflict['value'] = $this->user_id;
        $content->userConflicted = User::getUserInfo($this->complain);
        $content->userAnalyze = User::getUserInfo($this->head);
        $content->observersArray = User::getUsersListFromJson($this->view_user);

        $result->id = 'conflict' . $this->id;
        $result->content = $content;

        return $result;
    }

    public static function getConflictData()
    {

    }

    public static function getConflictList($archive = 0): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $data = new stdClass();
        $user = User::getUserUser();
        $criteria = new CDbCriteria();
        $sql = " user_id = $user->id "; // создал конфликт
        $sql .= " OR head_conflict = $user->id "; //руководитель конфликта
        $sql .= " OR (user_conflict = $user->id AND conflict_view = 1 ) "; //виновник конфликта и ему разрешено видкть конфликт
        $sql .= " OR view_user LIKE '%\"$user->id\"%' "; // Является наблюдателем

        $criteria->addCondition($sql);
        $criteria->addCondition("status = $archive");

        $conflicts = WorkPlaceConflict::model()->findAll($criteria);
        if ($archive == 0) {
            // проверяем привязку конфликтов к каталогам
            WorkPlaceColumns::checkDefaultColumns(self::MODULE_NAME, $user); // проверяем наличие дефолтных категорий
            $noDeleteIds = [];
            foreach ($conflicts as $conflict) {
                $noDeleteIds[] = $conflict->id;
                if (!WorkPlaceColumnLinks::checkLink(self::MODULE_NAME, $user, $conflict->id)) {
                    WorkPlaceColumnLinks::addDefaultLinkModuleName(self::MODULE_NAME, $conflict->id); // если конфликт не привязан ни к одной котагории, привязываем к дефолтной
                }
            }
            WorkPlaceColumnLinks::deleteOldLinks(self::MODULE_NAME, $user, $noDeleteIds);
            $columns = WorkPlaceColumns::makeColumnsList(self::MODULE_NAME, $user);
            $data->columnOrder = $columns['columnOrder'];
            $data->columnsOn = $columns['columnsOn'];
        } else {
            $data->columnOrder = new stdClass();
            $data->columnsOn = new stdClass();
        }


        $conflictOn = new stdClass();
        foreach ($conflicts as $conflict) {
            $conflictName = self::MODULE_NAME . $conflict->id;
            $conflictOn->$conflictName = $conflict->makeConflictInfo();
        }
        $data->conflictOn = $conflictOn;

        $result['success'] = true;
        $result['data'] = $data;
        return $result;
    }

    public static function addNewConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $title = Yii::app()->request->getParam('title'); // тема
        $text = Yii::app()->request->getParam('text');   // суть
        $emoji_id = Yii::app()->request->getParam('emoji_id'); // Эмоция
        $user_conflict = Yii::app()->request->getParam('user_id'); // С кем конфликт
        $director_id = Yii::app()->request->getParam('director_id'); // Выбранный руководитель
        $users_array = Yii::app()->request->getParam('users_array', []); // Обозреватели
        $files = Yii::app()->request->getParam('files', []); // прикрепленные файлы

        // проверка полученных данных
        $err = [];
        if (!$title) {
            $err[] = "Не указана тема конфликта";
        } elseif (mb_strlen($title) < 5) {
            $err[] = "Тема конфликта должна быть не менее 5 символов";
        }
        if (!$text) {
            $err[] = "Не указана суть конфликта";
        } elseif (mb_strlen($text) < 35) {
            $err[] = "Суть конфликта должна быть не менее 35 символов";
        }
        if (!$emoji_id) {
            $err[] = "Не указана эмоция конфликта";
        }
        if (!$user_conflict) {
            $err[] = "Не указан виновник конфликта";
        }
        if (!$director_id) {
            $err[] = "Не указан руководитель, решающий конфликта";
        }


        if (count($err) == 0) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $conflict = new WorkPlaceConflict();
                $conflict->title = $title;
                $conflict->text = $text;
                $conflict->chagrin = $emoji_id; // эмоция
                $conflict->user_conflict = $user_conflict;
                $conflict->head_conflict = $director_id;
                $conflict->view_user = json_encode($users_array);
                $conflict->save();

                if (count($files) > 0) {
                    foreach ($files as $file) {
                        $conflictFile = new WorkPlaceConflictFiles();
                        $conflictFile->conflict_id = $conflict->id;
                        $conflictFile->file_id = intval($file);
                        $conflictFile->save();
                    }
                }

                WorkPlaceActionArchive::addAction($conflict->id, "Конфликт создан", self::MODULE_NAME);

                //$text = "Инициировано новое разбирательство с " . $conflict->complain->getKadryFio() . ". Инициатор " . $conflict->user->getKadryFio();
                Notifiers::sendNotifier('WorkPlaceConflict', 'addNewConflict', $conflict->id, ['addUserId' => $conflict->userMakeConflict->id, 'addUserFio' => $conflict->userMakeConflict->getKadryFioShort(), 'members' => $conflict->memberRoleList]);
                $result['success'] = true;
                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['status'] = 400;
                $transaction->rollback();
            }
        } else {
            $result['error']['list'] = $err;
            $result['error']['text'] = "Ошибка заполнения полей";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function getUserDirectorList(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $user_id = Yii::app()->request->getParam('user_id', 85);
        $depth = Yii::app()->request->getParam('depth', 3);
        $user = User::model()->findByPk($user_id);
        if ($user) {
            if ($user->kadry) {
                $data = OkLsEmployees::makeBossArray($user->kadry, $depth);
                if (count($data) > 0) {
                    $result['data'] = $data;
                    $result['success'] = true;
                } else {
                    $result['error'] = "Список руководителей пуст";
                    $result['status'] = 400;
                }

            } else {
                $result['error'] = "Пользователь с Id [$user_id] не привязан к кадрам";
                $result['status'] = 400;
            }
        } else {
            $result['error'] = "Не найден пользователь по Id [$user_id]";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function sendMessageToConflictUsers(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $token = Yii::app()->request->getParam('token', 0);
        $conflict_id = Yii::app()->request->getParam('conflict_id', 0);
        $text = Yii::app()->request->getParam('text', null);

        $conflict = WorkPlaceConflict::model()->findByPk($conflict_id);
        if ($conflict) {
            $userList = [];
            $user = User::model()->findByPk(Yii::app()->user->id);
            if ($conflict->user_id != $user->id) {
                $userList[] = $conflict->user_id;
            }
            if ($conflict->head_conflict != $user->id) {
                $userList[] = $conflict->head_conflict;
            }
            if($conflict->conflict_view != 0){
                $userList[] = $conflict->user_conflict;
            }
            $users = User::getUsersListFromJson($conflict->view_user);
            if ($users && is_array($users) && count($users) > 0) {
                foreach ($users as $u) {
                    if ($u['id'] != $user->id) {
                        $userList[] = $u['id'];
                    }
                }
            }
            if (count($userList) > 0) {
                foreach ($userList as $uId) {
                    if ($uId != Yii::app()->user->id) {
                        Notice::sendToUserId($uId, $text, 'wp');
                    }
                }
                $result['success'] = true;
            }// else {
//                $result['error'] = 'Не кому отправлять!!!';
//                $result['status'] = 401;
//            }

        } else {
            $result['error'] = 'Не найден конфликт по id ' . $conflict_id;
            $result['status'] = 204;
        }
        return $result;
    }

    public static function moveConflictToColumn(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $oldColumn = Yii::app()->request->getParam('columnsOld');
        $newColumn = Yii::app()->request->getParam('columnsNew');
        $conflictIdFull = Yii::app()->request->getParam('conflictId');
        $conflictIndex = Yii::app()->request->getParam('conflictIndex');
        $conflictId = str_replace('conflict', '', strtolower($conflictIdFull));

        if (WorkPlaceColumns::moveElementToColumn(self::MODULE_NAME, $oldColumn, $newColumn, $conflictId, $conflictIndex)) {
            $result['success'] = true;
        } else {
            $result['status'] = 400;
            $result['error'] = 'При перемещении конфликта произошла ошибка';
        }
        return $result;
    }

    public static function addNewObserversToConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $user_array = Yii::app()->request->getParam('user_array');
        $conflict_id = Yii::app()->request->getParam('conflict_id');
        $conflict = WorkPlaceConflict::model()->findByPk($conflict_id);
        if ($conflict) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $errors = [];
                $i = 0;
                foreach ($user_array as $user_id) {
                    $res = Tools::PushUserToJsonField($conflict, 'view_user', 'user_id', $user_id);
                    if ($res['success']) {
                        $user = User::model()->findByPk($user_id);
                        $result['out'][$i] = User::getUserInfo($user);
                        WorkPlaceActionArchive::addAction($conflict_id, "В задачу добавлен пользователь " . $user->getKadryFioShort(), self::MODULE_NAME);
                        Notifiers::sendNotifier('WorkPlaceConflict', 'addUserToTask', $conflict->id, ['addUserId' => $user_id, 'addUserFio' => $user->getKadryFioShort(), 'members' => $conflict->memberRoleList]);
                    } else {
                        $errors[] = "Ошибка добавления пользователя в конфликт ";

                    }
                    $i++;
                }
                if (count($errors) == 0) {
                    $result['success'] = true;
                    $transaction->commit();
                } else {
                    $result['error'] = 'Возникли ошибки при добавлении пользователей в конфликт';
                    $result['errorList'] = $errors;
                    $result['status'] = 400;
                    $transaction->rollback();
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['status'] = 400;
                $transaction->rollback();
            }
        } else {
            $result['error'] = "Не найден конфликт по id [$conflict_id] ";
            $result['status'] = 400;
        }
        return $result;
    }


    public static function delObserversInConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0, 'out' => ['id' => 0, 'link' => '']];
        $conflict_id = Yii::app()->request->getParam('conflict_id');
        $user_id = Yii::app()->request->getParam('user_id');
      //  $transaction = Yii::app()->db->beginTransaction();
        try {
            WorkPlaceConflict::delObserversConflict($conflict_id, $user_id);
      //      $transaction->commit();
            $result['success'] = true;

        } catch (Exception $e) {
            $result['error'] = 'Ошибка выполнения операции' . $e->getMessage();
        //    $transaction->rollback();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function delObserversConflict($conflictId, $userId)
    {
        $result = false;
       // $conflict_id = WorkPlaceConflict::model()->findBySQl("SELECT * FROM workplace_conflict WHERE id = '$conflictId'");

            $conflict = WorkPlaceConflict::model()->find("id = '$conflictId'");
            // $conflict = WorkPlaceConflict::model()->find("id = '$conflictId'");
            if ($conflict) {
                $transaction = Yii::app()->db->beginTransaction();
                $result = Tools::DeleteUserFromJsonField($conflict, 'view_user', $userId);
                if ($result['success']) {
                    // создать сообщение о добавлении пользователя
                    $user = User::model()->findByPk($userId);
                    WorkPlaceActionArchive::addAction($conflictId, "Из конфликта удален обозреватель " . $user->getKadryFioShort(), self::MODULE_NAME);
                    $transaction->commit();
                    $result = true;
                } else {
                    $transaction->rollback();
                }
            }
        return $result;
    }

    public static function updateConflictColor(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $id = key_exists('conflict_id', $_POST) ? $_POST['conflict_id'] : 0;
        $color_id = key_exists('color_id', $_POST) ? $_POST['color_id'] : 0;
        if (!empty($id) && $id != 0) {
            $data = WorkPlaceConflict::model()->findByPk($id);
            if ($data) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $data->color_id = $color_id;
                    $data->save();
                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $result['error'] = $e->getMessage();
                    $result['status'] = 400;
                    $transaction->rollback();
                }
            } else {
                $result['error'] = 'Не найден конфликт по id ' . $id;
                $result['status'] = 204;
            }
        } else {
            $result['error'] = 'Не верное значение параметра id ' . $id;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function addNewColumnToConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $text = Yii::app()->request->getParam('text');
        if (WorkPlaceColumns::addNewColumn(self::MODULE_NAME, $text)) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Ошибка при создании каталога';
        }
        return $result;
    }

    public static function updateColumnToConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $text = Yii::app()->request->getParam('text');
        $columnId = Yii::app()->request->getParam('column_id');
        $res = WorkPlaceColumns::updateColumn(self::MODULE_NAME, $columnId, $text);
        if ($res === true) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Ошибка при создании каталога: ' . $res;
        }
        return $result;
    }

    public static function dellColumnInConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $columnId = Yii::app()->request->getParam('column_id');
        $res = WorkPlaceColumns::deleteColumn(self::MODULE_NAME, $columnId);
        if ($res === true) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Ошибка при создании каталога: ' . $res;
        }
        return $result;
    }

    public static function addCommentToConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => ['id' => 0, 'date' => '']];
        $conflict_id = Yii::app()->request->getParam('conflict_id');
        $message_id = Yii::app()->request->getParam('message_id');
        $message = Yii::app()->request->getParam('message');
        $quote = Yii::app()->request->getParam('quote');
        $system = Yii::app()->request->getParam('system');
        $user = User::model()->findByPk(Yii::app()->user->id);
        $w_conflict = WorkPlaceTasks::model()->findByPk($conflict_id);
        $file_id = Yii::app()->request->getParam('file_id');

        $transaction = Yii::app()->db->beginTransaction();
        if ($w_conflict) {

            if (!empty($message) || !empty($file_id)) {
                if ($message_id) {
                    $data = WorkPlaceConflictComments::model()->findByPk($message_id);
                    if ($data->user_id == $user->id) { // проверка, редактировать сообщение может только создатель
                        $data->comment = $message;
                        $data->change = 1;
                        $data-> updated_at = date('Y-m-d H:i:s');
                        $data->save();
                    $transaction->commit();
                        $result['out']['id'] = $data->id;
                        $result['out']['date'] = strtotime($data->updated_at);
                    } else {
                        $result['error'] = 'Попытка редактирования чужой записи!';
                        $result['status'] = 400;
                    }
                } else {
                    $data = new WorkPlaceConflictComments();
                    $data->conflict_id = $conflict_id;
                    $data->comment = $message;
                    $data->change = 0;
                    $data->quote = $quote;
                    $data->file_id = $file_id;
                    $data->system = ($system && $system == 1) ? 1 : 0;
                    $data-> created_at = date('Y-m-d H:i:s');
                    $data->save();
                    $transaction->commit();
                    $result['out']['id'] = $data->id;
                    $result['out']['date'] = strtotime($data->date);

                    if (!empty($w_conflict->view_user)) {
                        foreach (json_decode($w_conflict->view_user) as $user_id) {
                            if ($user_id == $user->id) continue;
                            WorkPlaceNotification::addRecord($user_id, $conflict_id, 'WorkPlaceConflict');
                        }
                    }

                    if ($user->id != $w_conflict->created_user) {
                        WorkPlaceNotification::addRecord($w_conflict->created_user, $conflict_id, 'WorkPlaceConflict');
                    }
                }

                $result['success'] = true;

            } else {
                $result['error'] = 'Текст сообщения отсутствует!';
                $result['status'] = 400;
            }
        } else {
            $result['error'] = 'Не найдена задача по id ' . $conflict_id;
            $result['status'] = 204;
        }
        return $result;
    }

    /**
     * Загрузка файлов в конфликт
     * @return array
     */
    public static function uploadConflictFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $_POST['category_id'] = 1;
        $_POST['category_status'] = 4;
        $res = WorkPlaceConflict::uploadMyFile(true);

        if ($res['success']) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                // добавляем комментарий
                $conflictId = Yii::app()->request->getParam('id');
                $data = new WorkPlaceConflictFiles();
                $data->conflict_id = $conflictId;
                $data->file_id = $res['out']['id'];
                $data->save();
                WorkPlaceActionArchive::addAction($conflictId, "В задачу добавлен файл ", self::MODULE_NAME);
                $result['out'] = $res['out'];
                $result['success'] = true;

                $result['out']['id'] = $data->id;

                ////////////////////
                $result['out']['date'] = $res['out']['date'];
               // $result['out']['size'] = $res['out']['size'];
                $result['out']['user'] = $res['out']['user'];

/////////////////////////////////////////////////////////////////////////
                $result['out']['file_id'] = $res['out']['id'];
                $result['out']['link'] = $res['out']['url'];
                $result['out']['name'] = $res['out']['name'];
                $result['success'] = true;
                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result = $res;
        }

        if ($res['success'] && $res['out']['id']) {
            $data = new WorkPlaceTaskFiles();
            $data->task_id = $data['task_id'];
            $data->file_id = $data['file_id'];
            $data->save();
            $result['out'] = $res['out'];
            $result['success'] = true;
        } else {
            $result['error'] = $res['error'];
            $result['status'] = $res['status'];
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
                $fileName = WorkPlaceMyFiles::makeFileServerName($file['name']);
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
                    $result['out']['url'] = "/static/uploads/wp/" . $f->link;
                    $result['out']['size'] = number_format( $f->size / 1048576, 2 );
                    $result['out']['date'] = strtotime($f->created_at);

                    $user = User::model()->findByPk($f->user_id);
                    $result['out']['user'] = User::getUserInfo($user);
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

    /**
     * Добавление текста в конфликт
     * после заполнения поля result_head_text конфликт переводится в архив (status = 1)
     * @return array
     */
    public static function addTextToConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $conflict_id = Yii::app()->request->getParam('conflict_id');
        $state = Yii::app()->request->getParam('state');
        $column = Yii::app()->request->getParam('column');
        $text = Yii::app()->request->getParam('text');
        $field = self::getFieldName($state, $column);
        $errors = [];
        if (!$text || $text == '') {
            $errors[] = "Не заполнен текст";
        }
        if (!$field) {
            $errors[] = "Не определено поле для записи";
        }
        $conflict = WorkPlaceConflict::model()->findByPk($conflict_id);
        if (count($errors) == 0) {
            if ($conflict) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $conflict->$field = $text;
                    WorkPlaceActionArchive::addAction($conflict->id, "Добавлен текст в поле [$field]", self::MODULE_NAME);

                        if ($field == 'result_head_text') {
                            $conflict->status = 1; // перевод в архив
                            WorkPlaceActionArchive::addAction($conflict->id, "Конфликт перемещен в архив", self::MODULE_NAME);
                        }
//                        if($field == 'result_head'){
//                            $conflict->status = 2;
//                            WorkPlaceActionArchive::addAction($conflict->id, "Конфликт перемещен в архив", self::MODULE_NAME);
//                        }

                    $conflict->save();
                    $transaction->commit();
                    $result['out']['tools'] = $conflict->makeTools();
                    $result['out']['textResolve'] = $conflict->makeTextResolve();
                    $result['out']['textSolution'] = $conflict->makeTextSolution();
                    $result['success'] = true;
                } catch (Exception $e) {
                    $result['error'] = $e->getMessage();
                    $result['status'] = 400;
                    $transaction->rollback();
                }
            } else {
                $result['error'] = 'Не найден конфликт по id ' . $conflict_id;
                $result['status'] = 204;
            }
        } else {
            $result['error']['text'] = "Обнаружена ошибка при записи";
            $result['error']['list'] = $errors;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function enableConflictedView(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $conflict_id = Yii::app()->request->getParam('conflict_id');
        if (!$conflict_id) $conflict_id = 101;
        $conflict = WorkPlaceConflict::model()->findByPk($conflict_id);

        if ($conflict) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $conflict->conflict_view = 1;
                $conflict->save();
                WorkPlaceActionArchive::addAction($conflict->id, "Конфликтеру разрешили видеть конфликт", self::MODULE_NAME);
                $transaction->commit();
                $result['success'] = true;
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['status'] = 400;
                $transaction->rollback();
            }
        } else {
            $result['error'] = 'Не найден конфликт по id ' . $conflict_id;
            $result['status'] = 204;
        }
        return $result;
    }

    /**
     * Формирование набора данных по задаче
     * @return array
     */
    public static function getDataForConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $id = Yii::app()->request->getParam('conflict_id', 0);
        if ($id == 0)
            $id = 101;

        $data = WorkPlaceConflict::model()->findByPk($id);
        if ($data) {
            $array = $data->makeConflictInfoFull();
            $result['success'] = true;
            $result['data'] = $array;
        } else {
            $result['error'] = 'Не найден чат по id ' . $id;
            $result['status'] = 204;
        }
        return $result;
    }

    //analitika

    /**
     *
     * @param int $userId Id пользавателя модель User
     * @return int Количество открытых конфликтов, в которых пользователь виновник
     */
    public static function getUserCoflictCount(int $userId): int
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("user_conflict = '$userId'");
        $criteria->addCondition("isNull(result_head_text) or result_head_text = '' ");
        return WorkPlaceConflict::model()->count($criteria);
    }

    /**
     *
     * @param array $userIds
     * @return int Количество открытых конфликтов, в которых пользователи виновники
     */
    public static function getUsersCoflictCount(array $userIds): int
    {
        if (count($userIds) == 0) return 0;
        $criteria = new CDbCriteria();
        $criteria->addInCondition("user_conflict", $userIds);
        $criteria->addCondition("isNull(result_head_text) or result_head_text = '' ");
        return WorkPlaceConflict::model()->count($criteria);
    }



    public static function uploadFileToChatConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $res = WorkPlaceMessageFiles::uploadMessageFileConflict(2);

        if ($res['success']) {
            try {
                // добавляем комментарий
                $conflictId = Yii::app()->request->getParam('id');
                $_POST['id'] = $conflictId;
                $_POST['file_id'] = $res['out']['id'];
                $res1 = WorkPlaceConflictComments::addCommentToChatConflict();

                if ($res1['success']) {
                    $message = new stdClass();
                    $message->id = $res1['out']['id'];
                    $message->date = $res1['out']['date'];
                    $message->my = $res1['out']['my'];
                    $message->quote = $res1['out']['quote'];
                    $message->system = $res1['out']['system'];
                    $message->text = $res1['out']['text'];
                    $message->updated = $res1['out']['updated'];
                    $message->url = $res['out']['url'];

                    $result['success']    = true;
                    $result['out']['status'] = true;
                    $result['out']['message'] = $message;
                } else {
                    $result['error'] = 'Ошибка создания собщения : ' . $res1['error'];
                    $result['status'] = 500;
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['status'] = 500;
            }
        } else {
            $result = $res;
        }
        return $result;
    }


    public static function setLinkUploadForConflict(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $file_id = Yii::app()->request->getParam('file_id');
        $file_id_in_conflict = Yii::app()->request->getParam('file_id_in_conflict');
        $conflict_id = Yii::app()->request->getParam('conflict_id');

        $conflict = WorkPlaceConflict::model()->findByPk($conflict_id);
        try {
            $transaction = Yii::app()->db->beginTransaction();

            if ($conflict){
                $file = WorkPlaceConflictFiles::model()->findByPk($file_id_in_conflict);
                if($file_id == $file->file_id && $conflict_id == $file->conflict_id){
 //                   if($file->upload_user != Yii::app()->user->id) {
                        $file->upload_user = Yii::app()->user->id;

                        $file->save();

                        $transaction->commit();
                        $result['success'] = true;
                        $result['status'] = 200;
//                    }else{
//                        $result['error'] = 'Вы уже скачивали этот файл';
//                        $transaction->rollback();
//                        $result['status'] = 500;
//                    }
                }
            }else{
                $result['error'] = 'Не найден конфликт ' . $conflict_id;
                $transaction->rollback();
                $result['status'] = 500;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }


    //   СТАРОЕ

//    public function add(array $data)
//    {
//        try {
//            if ($this->upload($data)) {
//                $result = true;
//
//            } else {
//                $result = $this->getErrors();
//            }
//        } catch (Exception $exception) {
//            $result = [$exception->getMessage()];
//        }
//        return $result;
//    }
//
//    public function upload(array $data)
//    {
//        $result = false;
//        $this->transaction = Yii::app()->db->beginTransaction();
//        $this->create($data);
//        if ($this->save()) {
//            if (!empty($data['file_list'])) {
//                $this->uploadFile($data['file_list']);
//            }
//            if ($this->addData($data)) $result = true;
//        }
//
//        return $result;
//    }
//
//    public function sendAllMemberInConflict()
//    {
//        VarDumper::dump($this->head);
//        Sms::model()->sendSMSUser($this->head, ' dfds sdfs dfds sd fsdf', 'wp');
//
//        if ($this->head) {
//            var_dump($this->head->id);
//            $this->sendMess($this->head); //send message in EIP
//        }
//
//        if (!empty($this->view_user)) {
//            $temp_data = (json_decode($this->view_user)) ?? null;
//
//            if (!empty($temp_data)) {
//                foreach ($temp_data as $item) {
//                    $temp_user = User::model()->findByPk($item);
//                    if (!empty($temp_user)) {
//                        $this->sendSms($temp_user); //sends a message to the head of the conflict
//                        $this->sendMess($temp_user); //send message in EIP
//                    }
//                }
//            }
//        }
//    }
//
//    public function sendNewChanges($text)
//    {
//        $chech = (!empty(Yii::app()->request->getParam('result_head_text'))) ? true : false;
//        if ($this->userMakeConflict) {
//            $this->sendMess($this->userMakeConflict, $text); //send message in EIP
//            if ($chech) $this->sendSms($this->userMakeConflict, 'Конфликт с ' . $this->сonflict->title . '. исчерпан.');
//
//        }
//
//        if ($this->head && $chech == false) {
//            $this->sendMess($this->head, $text); //send message in EIP
//        }
//
//        if ($this->conflict_view != 0 && $this->сonflict) {
//            $this->sendMess($this->сonflict, $text); //send message in EIP
//            if ($chech) $this->sendSms($this->сonflict, 'Конфликт с ' . $this->сonflict->title . '. исчерпан.');
//
//        }
//
//        if (!empty($this->view_user)) {
//            $temp_data = (json_decode($this->view_user)) ?? null;
//            if (!empty($temp_data)) {
//                foreach ($temp_data as $item) {
//                    $temp_user = User::model()->findByPk($item);
//                    if (!empty($temp_user)) {
//                        $this->sendMess($temp_user, $text); //send message in EIP
//                        if ($chech) $this->sendSms($temp_user, 'Конфликт с ' . $this->сonflict->title . '. исчерпан.');
//                    }
//                }
//            }
//        }
//    }
//
//    public function addData(array $data)
//    {
//        $result = '';
//        $toSave = false;
//        try {
//            $toSave = true; // пока пусть будет так
//            if ($toSave) {
//                $this->transaction->commit();
//                $result = true;
//            }
//        } catch (Exception $e) {
//            $this->transaction->rollback();
//            $result = $e->getMessage();
//        }
//        return $result;
//    }
//
//    public function updateField($field, $updateText, $data)
//    {
//        $text = '';
//        $result = false;
//        if (!empty($data[$field])) {
//            if ($field == 'text' || $field == 'title') $text = $updateText . $data[$field];
//            if ($field == 'user_conflict') $text = $updateText . $this->сonflict->title;
//            if ($field == 'file' && $data['file']['wp_doc']['size'] > 0) $text = $updateText . $data['file']['wp_doc']['name'];
//            if ($field == 'view_user') {
//                $text = $updateText;
//                foreach ($data[$field] as $user) {
//                    $u = User::model()->findByPk($user);
//                    $text .= $u->title . ' ';
//                }
//            }
//            if (!empty($text)) WorkPlaceActionArchive::AddAction($this->id, $text, 'conflict');
//            $result = true;
//        }
//        return $result;
//    }
//
//    public function uploadFile($data)
//    {
//        $result = false;
//        foreach ($data as $item) {
//            $upload = new WorkPlaceConflictFiles();
//            $upload->conflict_id = $this->id;
//            $upload->file_id = intval($item);
//            if ($upload->save()) $result = true;
//        }
//        return $result;
//    }
//
//    public function create(array $data)
//    {
//        $this->title = $data['title'];
//        $this->text = $data['text'];
//        $this->user_conflict = $data['user_conflict'];
//        $this->head_conflict = $data['head_conflict'];
//        $this->chagrin = $data['emotion'];
//        $this->view_user = (!empty($data['view_user'])) ? json_encode($data['view_user']) : null;
//    }
//
//    public static function makeFileServerName(string $name): string
//    {
//        $extension = pathinfo($name, PATHINFO_EXTENSION);
//        return str_replace('.', '', microtime(true)) . '.' . $extension;
//    }
//
//    public function sendMess($user, $text = '')
//    {
//        if (empty($text)) {
//            $text = $this->userMakeConflict->title . " инициировал(а) разбирательство с " . $this->сonflict->title . " на тему «" . $this->title . '»';
//        }
//        if (Notice::sendToUser($user, $text, 'wp')) return true;
//        return false;
//    }
//
//    public function sendSms($user, $text = '')
//    {
//        if (empty($text)) {
//            $text = $this->userMakeConflict->title . " инициировал(а) разбирательство с " . $this->сonflict->title;
//        }
//        if (Sms::model()->sendSMSUser($user, $text, 'wp')) return true;
//        return false;
//    }
//
//    public function AddField($field)
//    {
//        $this->$field = Yii::app()->request->getParam($field);
//        if ($this->save()) return true;
//        return false;
//    }

}