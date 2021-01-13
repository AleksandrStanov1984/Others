<?

/**
 * Class WorkPlaceTasks
 */
class WorkPlaceTasks extends CActiveRecord
{
    protected $fields = [
        'title' => 'Тема задачи - ',
        'text' => 'Суть задачи - ',
        'date' => 'К сроку - ',
        'whom_id' => 'Для кого задача - ', //        'file' => 'Прикреплён файл - ',
        'users' => 'Кто еще видит - ',
        'status' => 'Состояние задачи 1- создана, 2-утверждена, 3-завершена исполнителем, 4-завершена руководителем',
        'stage' => 'Важность задачи 1-Простая, 2-обычная, 3-Важная,4-Срочная,5-За премию'];

    public static $statusList = [
        1 => 'Простая',
        2 => 'Обычная',
        3 => 'Важная',
        4 => 'Срочная',
        5 => 'За премию',
    ];

    public static $stageList = [
        -1 => 'Отклонена руководителем',
        1 => 'Создана',
        2 => 'Подтверждена руководителем',
        3 => 'Выполнена исполнителем',
        4 => 'Выполнена руководителем',
        5 => 'Предварительное рассмотрение',
        99 => 'Архив',
        100 => 'Удалена',
    ];

    const STAGE_APPROVAL_REJECTED = -1;
    const STAGE_CREATED = 1;
    const STAGE_APPROVAL = 2;
    const STAGE_ISPOLNITEL_COMPLETED = 3;
    const STAGE_BOSS_COMPLETED = 4;
    const STAGE_PREVIEW = 5;
    const STAGE_ARCHIVE = 99;
    const STAGE_DELETED = 100;

    const MODULE_NAME = 'task';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    protected function beforeSave()
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->created_user = Yii::app()->user->id;
            $this->stage = self::STAGE_CREATED;
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }

        return parent::beforeSave();
    }

    public function tableName()
    {
        return '{{workplace_tasks}}';
    }

    public function relations()
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'created_user'], // создатель
            'whom' => [self::BELONGS_TO, 'User', 'whom_id'], // на кого
            'head' => [self::BELONGS_TO, 'User', 'head_id'],
            'userSend' => [self::BELONGS_TO, 'User', 'whom_id'],
            'comments' => [self::HAS_MANY, 'WorkPlaceTaskComments', 'task_id'],
            'files' => [self::HAS_MANY, 'WorkPlaceTaskFiles', 'task_id'],
            'pdr' => [self::BELONGS_TO, 'OkSprPdr', ['pdr_id' => 'idPdr']],
            'points' => [self::HAS_MANY, 'WorkPlaceTaskControlPoints', 'task_id']
        ];
    }

    public $memberRoleList = [
        'boss' => 'Прямой руководитель',
        'creator' => 'Создатель',
        'ispolnitel' => 'Исполнитель',
        'obozrevatel' => 'Обозреватели',
    ];

    // ===== API ====
    // список пользователей, которых можно добавить в задачу (без создателя и участников)

    /**
     * список пользователей для добавления в обозреватели задачи
     * @return array
     */
    public function getUsersForAddToTask()
    {
        $result = [];
        $users = User::getUsersListFromJson($this->view_user);
        $userIds = [Yii::app()->user->id];
        foreach ($users as $u) {
            $userIds[] = $u['id'];
        }
        $criteria = new CDbCriteria;
        $criteria->join = "inner join ok_Employees c on t.extId = c.id ";
        $criteria->addCondition("t.isDell = '0' AND c.workend = '1970-01-01'");
        $criteria->addNotInCondition('t.id', $userIds);
        $criteria->order = 'c.last_name ASC, c.first_name ASC, c.patronymic_name ASC';
        $users = User::model()->with('kadry')->findAll($criteria);
        foreach ($users as $user) {
            array_push($result, [
                'value' => $user->id,
                'label' => $user->kadry->getFioShort(),
                'img' => $user->hasAvatarSource() ? $user->getAvatarSource() : $user->getPhotoSource(),
            ]);
        }
        return $result;
    }

    /**
     * формирование сокращенного массива данных по задаче
     * @return stdClass
     */
    public function makeTaskInfo()
    {
        $content = new stdClass();
        $content->id = $this->id;
        $content->stage = $this->stage;
        $content->userCreator = User::getUserInfo($this->user);
        $content->userApprove = User::getUserInfo($this->head);
        $content->userPerformer = User::getUserInfo($this->whom);
        $content->title = $this->title;
        $content->color = $this->color_id ?? 0;
        $content->text = $this->text;
        $content->headScore = $this->head_score;
        $content->status = $this->status;
        $content->my = $this->created_user = Yii::app()->user->id ? true : false;
        $content->approve = $this->head ? true : false;
        $content->dateCreate = strtotime($this->created_at);//----------------------
        $content->dateApprove = strtotime($this->head_date);//----------------------
        $content->dateFinish = strtotime($this->date);//-------------------------
        $content->viewed = $this->viewed ? true : false;
        $content->count = WorkPlaceNotification::getCount(Yii::app()->user->id, $this->id, 'WorkPlaceTasks');
        $content->done = $this->stage == self::STAGE_ISPOLNITEL_COMPLETED ? true : false;
        //  $content->done = $this->stage == self::STAGE_APPROVAL_REJECTED ? true : false;
        $content->observersArray = User::getUsersListFromJson($this->view_user);
        //if (!$this->date_colse_task || $this->date_colse_task == '1970-01-01 00:00:00') {

        $day = Tools::diffdate(time(), strtotime($this->date));
        $znak = "";
        if (time() > strtotime($this->date) && $day != 0) {
            $znak = "-";
        }
        $content->day = $znak . $day . " " . Tools::inflector($day, ['день', 'дня', 'дней']);
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->makeCpInfo();
        }

        $result = new stdClass();
        $result->id = 'item-' . $this->id;
        $result->content = $content;
        $result->points = $points;
        $result->search = $this->id . "|" . ($this->whom ? $this->whom->getKadryFio() : "") . "|" . $this->title . "|" . date('d.m.y', strtotime($this->date));
        return $result;
    }

    /**
     * Получение данных по пользователям в задаче
     * @param $typ
     * @return array
     */
    public function getMember($typ)
    {
        $result = [];
        switch ($typ) {
            case 'boss' : // прямой руководитель
                $result[] = OkSprPdr::findBoss($this->pdr_id, 'user');
                break;
            case 'creator' : // создатель
                $result[] = $this->created_user;
                break;
            case 'ispolnitel' : // исполнитель
                $result[] = $this->whom->id;
                break;
            case 'obozrevatel': // обозреватели
                foreach (User::getUsersListFromJson($this->view_user) as $u) {
                    $result[] = $u['id'];
                }
                break;
        }
        return $result;
    }

    /**
     * определение роли пользователя в задаче
     * @param false $user
     * @return stdClass
     */
    public function makeAccess($user = false)
    {
        if (!$user) {
            $user = User::model()->findByPk(Yii::app()->user->id);
        }
        // определяем пренадлежность пользователя к данной задаче
        $isCreator = $user->id == $this->created_user;
        $isBoss = $user->id == OkSprPdr::findBossId($this->pdr_id, 'user');
        $isIspolnitel = $user->id == $this->whom->id;
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
        $result->isIspolnitel = $isIspolnitel;
        return $result;
    }

    /**
     * формирование списка доступов пользователя к задаче
     * @param false $user
     * @return stdClass
     */
    public function makeTools($user = false)
    { // доступы
        $result = new stdClass();

        if (!$user) {
            $user = User::model()->findByPk(Yii::app()->user->id);
        }

        $access = $this->makeAccess($user);

        $result->role = new stdClass();
        $result->role->isBoss = $access->isBoss;
        $result->role->isCreator = $access->isCreator;
        $result->role->isObozrevatel = $access->isObozrevatel;
        $result->role->isIspolnitel = $access->isIspolnitel;

        // Изменить статус
        $result->status = new stdClass();
        $result->status->v = true;
        $result->status->on = $access->isBoss ? true : false;

        // Напомнить исполнителю
        $result->remember = new stdClass();
        $result->remember->v = true;
        $result->remember->on = ($access->isCreator || $access->isObozrevatel || $access->isBoss) ? true : false;
        $result->remember->date = strtotime($this->date_send_message);

        // Изменить время задачи (перенести дедлайн)
        $result->extend = new stdClass();
        $result->extend->v = true;
        $result->extend->on = $access->isBoss ? true : false;

        // Передать задачу (изменить исполнителя)
        $result->transfer = new stdClass();
        $result->transfer->v = true;
        $result->transfer->on = $access->isBoss ? true : false;

        if ($access->isBoss) {
            // Завершить   у исполнителя
            $result->finish = new stdClass();
            $result->finish->v = true;
            $result->finish->on = $this->stage == self::STAGE_APPROVAL ? true : false;

            // Завершить  у начальника
            $result->finishByBoss = new stdClass();
            $result->finishByBoss->v = true;
            $result->finishByBoss->on = $this->stage == self::STAGE_ISPOLNITEL_COMPLETED ? true : false;
        } elseif ($access->isIspolnitel) {
            // Завершить   у исполнителя
            $result->finish = new stdClass();
            $result->finish->v = true;
            $result->finish->on = $this->stage == self::STAGE_APPROVAL ? true : false;

            // Завершить  у начальника
            $result->finishByBoss = new stdClass();
            $result->finishByBoss->v = false;
            $result->finishByBoss->on = false;
        } else {
            // Завершить   у исполнителя
            $result->finish = new stdClass();
            $result->finish->v = false;
            $result->finish->on = false;

            // Завершить  у начальника
            $result->finishByBoss = new stdClass();
            $result->finishByBoss->v = false;
            $result->finishByBoss->on = false;
        }

        // test
        $result->status = new stdClass();
        $result->status->v = true;
        $result->status->on = true;

        // Напомнить исполнителю
        $result->remember = new stdClass();
        $result->remember->v = true;
        $result->remember->on = true;
        $result->remember->date = strtotime($this->date_send_message);

        // Изменить время задачи (перенести дедлайн)
        $result->extend = new stdClass();
        $result->extend->v = true;
        $result->extend->on = true;

        // Передать задачу (изменить исполнителя)
        $result->transfer = new stdClass();
        $result->transfer->v = true;
        $result->transfer->on = true;

        // Завершить   у исполнителя
        $result->finish = new stdClass();
        $result->finish->v = true;
        $result->finish->on = true;

        // Завершить  у начальника
        $result->finishByBoss = new stdClass();
        $result->finishByBoss->v = true;
        $result->finishByBoss->on = true;

        return $result;
    }

    /**
     * Расширенный набор данных по задаче
     * @return array
     */
    public function makeTaskInfoFull(): array
    {
        $user = User::model()->findByPk(Yii::app()->user->id);
        $comments = [];
        foreach ($this->comments as $comment) {
            $comments[] = [
                'id' => $comment->id,
                'text' => $comment->comment,
                'url' => $comment->file ? "/static/uploads/wp/" . $comment->file->link : null,
                'date' => strtotime($comment->updated_at),
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
                'size' => number_format( $file->file->size / 1048576, 2 ) ,///////////////////////////////////
                'name' => $file->file->name,
                'link' => "/static/uploads/wp/" . $file->file->link,
                'date' => strtotime($file->created_at),
                'start' => (/*($file->user_id == $this->created_user) &&*/
                ($file->created_at == $this->created_at)) ? 1 : 0, // файл создан при  создании задачи или нет
                'upload' => $file->upload_user == Yii::app()->user->id,
            ];
        }
        $points = [];
        foreach ($this->points as $point) {
            $points[] = $point->makeCpInfo();
        }
        $result = [
            //'users' => $this->getUsersForAddToTask(), //список пользователей, для добавления в чат
            'message' => $comments, // список сообщений
            'files' => $files, // список файлов
            'observers' => User::getUsersListFromJson($this->view_user),
            'points' => $points,
            'info' => [
                'id' => $this->id,
                'userCreate' => User::getUserInfo($this->user), // создатель
                'userPerformer' => User::getUserInfo($this->whom), // кому задача
                'userApprove' => User::getUserInfo($this->head), // кто утвердил
                'title' => $this->title,
                'text' => $this->text,
                'status' => $this->status,
                'stage' => $this->stage,
                'color' => ($this->color_id ?? 0),
                'my' => $this->created_user = Yii::app()->user->id ? true : false,
                'approve' => $this->head ? true : false, // утвержена
                'state' => $this->in_archive,
                'dateCreate' => strtotime($this->created_at), // дата создания
                'dateApprove' => strtotime($this->head_date), // дата сутверждения
                'dateFinish' => strtotime($this->date), // дата завершения
                'tools' => $this->makeTools()
            ],
        ];
        // чистим информацию о новых сообщениях
        if (Yii::app()->user->id == $this->whom_id) {
            WorkPlaceNotification::clearCount($user->id, $this->id, 'WorkPlaceTasks');
        }
        return $result;
    }

    /**
     * создание новой задачи
     * @return array
     */
    public static function addNewTask(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $error = [];

        $date = Yii::app()->request->getParam('startDate');

        if (!Yii::app()->request->getParam('title', null)) {
            $error[] = 'title';
        }
        if (!Yii::app()->request->getParam('text', null)) {
            $error[] = 'text';
        }
        if (!Yii::app()->request->getParam('status', null)) {
            $error[] = 'status';
        }
        if (!Yii::app()->request->getParam('userId', null)) {
            $error[] = 'userId';
        }
        if (!Yii::app()->request->getParam('startDate', null)) {
            $error[] = 'startDate';
        }

        if (count($error) == 0) {

            $transaction = Yii::app()->db->beginTransaction();
            try {
                //$whom_id = (!empty(Yii::app()->request->getParam('whom_id'))) ? Yii::app()->request->getParam('whom_id') : Yii::app()->request->getParam('s_whom_id');
                $whom_id = Yii::app()->request->getParam('userId');
                $temp = OkLsEmployees::model()->findByPk($whom_id);
                if ($temp) {
                    $pdr_id = $temp->pdrId;
                } else {
                    throw new CHttpException(403, "Ошибка определения подразделения исполнителя");
                }

                $task = new WorkPlaceTasks();
                $task->title = Yii::app()->request->getParam('title');
                $task->text = Yii::app()->request->getParam('text');
                $task->text_head = Yii::app()->request->getParam('text');
                $task->date = date('Y-m-d H:i:s', $date);
                //$task->self_add  = '0';
                $task->status = Yii::app()->request->getParam('status', 1);
                $task->whom_id = $whom_id;
                $task->pdr_id = $pdr_id;
                $task->view_user = json_encode(Yii::app()->request->getParam('userArray', []));

                if ($whom_id != Yii::app()->user->id) {// проверяет на себя
                    $pdr = OkSprPdr::model()->findByPk($pdr_id);
                    $boss = $pdr->getPdrBossNew($whom_id);
                    if ($boss && $boss->id == Yii::app()->user->kadry->id) {
                        // автоматически подтверждаем задачу
                        $task->head_active = 1;
                        $task->head_id = $boss->user->id;
                        $task->head_date = date('Y-m-d H:i:s');
                        $task->head_ = $boss->user->id;
                    } else {
                        $task->head_active = 0;
                    }
                }
                $task->save();

                if (!empty(Yii::app()->request->getParam('checkboxIdArray'))) {
                    foreach (Yii::app()->request->getParam('checkboxIdArray') as $item) {
                        $upload = new WorkPlaceTaskFiles();
                        $upload->task_id = $task->id;
                        $upload->file_id = intval($item);
                        $upload->save();
                    }
                }

                if ($task->head_active != '1') {
                    $pdr = OkSprPdr::model()->findByPk($pdr_id);
                    $boss = $pdr->getPdrBossNew();

                    if ($task->whom_id == $boss->user->id) {
                        $boss = $pdr->getPdrBossNew($task->whom_id);
                    }

                    if ($boss->user) {
                        $tempText = "Вашему подчиненному было создана задача № " . $task->id . " от " . Yii::app()->user->title;
                        Sms::model()->sendSMSUser($boss->user, $tempText, 'wp');// отправка смс
                       // Notice::sendToUser($boss->user, $tempText, 'wp');// отправка Notice
                        Notice::sendToUserChat($boss->user, $tempText, 'wp');// отправка Notice
                    }
                }
                if ($task->head_active == '1') {
                    if ($whom_id != Yii::app()->user->kadry->id) {//Если было создано руководителем
                        $tempText = "Для Вас задача № " . $task->id . " от " . Yii::app()->user->title;
                        Sms::model()->sendSMSUser($task->userSend, $tempText, 'wp');// отправка смс
                        Notice::sendToUser($task->userSend, $tempText, 'wp');
                    }
                    if (!empty($users)) {//Рассылаем сообщения в ЕИП учасникам
                        $text = 'Для ' . $task->userSend->title . ' поступила новая задача с темой «' . $task->title . '», Вы были отмечены как обозреватель.';
                        foreach ($users as $user_id) {
                            $user = User::model()->findByPk($user_id);
                            if (!empty($user)) {
                                Notice::sendToUser($user, $text, 'wp');
                            }
                        }
                    }
                }

                WorkPlaceActionArchive::addAction($task->id, "Задача создана", self::MODULE_NAME);

                $transaction->commit();
                $result['id'] = $task->id;
                $result['success'] = true;
                $result['out'] = $task->makeTaskInfo();
            } catch (Exception $e) {
                $transaction->rollback();
                $result['error'] = $e->getMessage();
                $result['status'] = 500;
            }
        } else {
            $result['error']['list'] = $error;
            $result['error']['text'] = "Ошибка заполнения полей";
            $result['status'] = 400;
        }
        return $result;
    }

    /**
     * установка цвета задачи
     * @return array
     */
    public static function setColorBoxForTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $id = Yii::app()->request->getParam('task_id', 0);
        $color_id = Yii::app()->request->getParam('color_id', 0);
        if (!empty($id) && $id != 0) {
            $data = WorkPlaceTasks::model()->findByPk($id);
            if ($data) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $data->color_id = $color_id;
                    $data->save();
                    $result['success'] = true;
                    WorkPlaceActionArchive::addAction($data->id, "Изменет цвет задачи на $color_id", self::MODULE_NAME);
                    $transaction->commit();
                } catch (Exception $e) {
                    $result['error'] = $e->getMessage();
                    $result['status'] = 400;
                    $transaction->rollback();
                }
            } else {
                $result['error'] = 'Не найдена задача по id ' . $id;
                $result['status'] = 204;
            }
        } else {
            $result['error'] = 'Не верное значение параметра id ' . $id;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function getTaskListForBoss($archive)
    {
        $user = User::getUserUser();
        $pdr_id = $user->kadry->pdrId;
        $tempText = "pdr_id = '$pdr_id'";

        $criteria = new CDbCriteria();
        $criteria->join = "LEFT JOIN workplace_tasks_order wto on wto.taskId = t.id AND wto.userId = $user->id ";

        if (strlen($user->kadry->pdrId) == 2) { // олег иванович
            if ($archive) {
                $criteria->addCondition("in_archive = 2");
            } else {
                $criteria->addCondition("in_archive <> 2");
            }
            $criteria->addCondition("CHAR_LENGTH(pdr_id) = 4");
            $criteria->addCondition("whom_id <> '$user->id'");
            $criteria->addCondition("pdr_id = $pdr_id OR pdr_id LIKE '$pdr_id%' ");
        } else {
            $childPdrIdsList = OkSprPdr::getPdrIdsForBoss(); // список ID подчиненных подразделений
            if ($childPdrIdsList) {
                if ($archive) {
                    $criteria->addCondition("in_archive = 2");
                } else {
                    $criteria->addCondition("in_archive <> 2");
                }
                $criteria->addCondition(" whom_id <> '$user->id' "); // задача не на меня
                $criteria->addCondition(" ( " .
                    " pdr_id = '$pdr_id'" .  // Получатель входит в мое подразделение
                    // получатель является прямым подчиненным и начальником своего подразделения
                    " OR  whom_id in (select idBoss from ok_SprPdr where idPdr in (" . implode(',', $childPdrIdsList) . ") AND isDelete = 0 ) " .
                    // получатель входит в прямое подчиненное подразделение, у которого нет начальника
                    " OR  whom_id in (select id from user where extId in (select id from ok_Employees where pdrId in ( select idPdr from ok_SprPdr where idPdr in (" . implode(',', $childPdrIdsList) . ") AND (isNull(idBoss) OR idBoss = '$user->extId' ) AND isDelete = 0 ) ) ) " .
                    " ) "
                );
            } else {
                if ($archive) {
                    $criteria->addCondition("in_archive = 2");
                } else {
                    $criteria->addCondition("in_archive <> 2");
                }
                $criteria->addCondition(" whom_id != '$user->id' AND ( $tempText OR  (whom_id in (select id from user where extId in (select idBoss from ok_SprPdr where parentId = '$pdr_id' ) AND pdr_id LIKE '" . $pdr_id . "__')))");
            }
        }

        $criteria->order = "wto.sort ASC";
        $taskList = WorkPlaceTasks::model()->findAll($criteria);
        $taskList = WorkPlaceTasksOrder::updateOrder($taskList, 2, $user->id);
        return $taskList;
    }

    public static function getTaskListForMe($archive)
    {
        $user = User::getUserUser();
        $criteria = new CDbCriteria();
        $criteria->join = "LEFT JOIN workplace_tasks_order wto on wto.taskId = t.id AND wto.userId = $user->id ";
        $criteria->addCondition("in_archive != 2 AND (whom_id ='$user->id' AND head_active = 1)");
        $criteria->order = "wto.sort ASC";
        $taskList = WorkPlaceTasks::model()->findAll($criteria);
        $taskList = WorkPlaceTasksOrder::updateOrder($taskList, 1, $user->id);
        return $taskList;
    }

    public static function getTaskListForColleagues($archive)
    {
        $user = User::getUserUser();
        $criteria = new CDbCriteria();
        $criteria->join = "LEFT JOIN workplace_tasks_order wto on wto.taskId = t.id AND wto.userId = $user->id ";
        if ($archive) {
            $criteria->addCondition("(in_archive = 2 OR (in_archive = 2 AND in_false = 1))");
        } else {
            $criteria->addCondition("(in_archive <> 2 OR (in_archive = 2 AND in_false = 1))");
        }
        $criteria->addCondition(" (head_active != 0 OR created_user = '$user->id') AND ((created_user ='$user->id' AND whom_id <> '$user->id')
                             OR (view_user LIKE '%$user->id%' AND whom_id <> '$user->id'))");
        $criteria->order = "wto.sort ASC";

        $taskList = WorkPlaceTasks::model()->findAll($criteria);
        $taskList = WorkPlaceTasksOrder::updateOrder($taskList, 3, $user->id);
        return $taskList;
    }

    /**
     * выборка задач
     * @return array
     */
    public static function getTaskList($archive = false)
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];

        $user = User::getUserUser();

        $data_wp['tasks'] = Yii::app()->session['tasks'] ?? '1';
        $pdr_id = $user->kadry->pdrId;

        $isBoss = Yii::app()->user->role == 'admin' || OkSprPdr::model()->find("idBoss ='" . $user->extId . "' AND isDelete = 0");
        $result['data']['isBoss'] = $isBoss ? true : false;

        $dataArchive = [];

        if ($isBoss) {
            $taskList = self::getTaskListForBoss($archive);
            $data2 = [];
            if ($taskList) foreach ($taskList as $tl) {
                $taskInfo = $tl->makeTaskInfo();
                if (!$archive) {
                    if ($taskInfo->content->stage == 1 || $taskInfo->content->stage == 2
                        || $taskInfo->content->stage == 3 || $taskInfo->content->stage == 5) {
                        $data2[] = $taskInfo;
                    }
                }
                if ($archive) {
                    if ($taskInfo->content->stage == 4 || $taskInfo->content->stage == -1
                        || $taskInfo->content->stage == 99 || $taskInfo->content->stage == 100) {
                        $dataArchive[] = $taskInfo;
                    }
                }
            }
            $result['data']['taskForPeopleColumn'] = $data2;
        } else {
            $result['data']['taskForPeopleColumn'] = [];
        }

        $taskList = self::getTaskListForMe($archive);
        $data1 = [];
        if ($taskList) foreach ($taskList as $tl) {
            $taskInfo = $tl->makeTaskInfo();
            if (!$archive) {
                if ($taskInfo->content->stage == 1 || $taskInfo->content->stage == 2
                    || $taskInfo->content->stage == 3 || $taskInfo->content->stage == 5) {
                    $data1[] = $taskInfo;
                }
            }
            if ($archive) {
                if ($taskInfo->content->stage == 4 || $taskInfo->content->stage == -1
                    || $taskInfo->content->stage == 99 || $taskInfo->content->stage == 100) {
                    $dataArchive[] = $taskInfo;
                }
            }
            //  $dataArchive[] = $taskInfo;
        }
        $result['data']['taskForMeColumn'] = $data1;

        $taskList = self::getTaskListForColleagues($archive);
        $data3 = [];
        if ($taskList) foreach ($taskList as $tl) {
            $taskInfo = $tl->makeTaskInfo();
            if (!$archive) {
                if ($taskInfo->content->stage == 1 || $taskInfo->content->stage == 2
                    || $taskInfo->content->stage == 3 || $taskInfo->content->stage == 5) {
                    $data3[] = $taskInfo;
                }
            }
            if ($archive) {
                if ($taskInfo->content->stage == 4 || $taskInfo->content->stage == -1
                    || $taskInfo->content->stage == 99 || $taskInfo->content->stage == 100) {
                    $dataArchive[] = $taskInfo;
                }
            }
        }
        $result['data']['taskForColleaguesColumn'] = $data3;

        if ($archive) {
            $result['data']['TaskAll'] = $dataArchive;
            unset($result['data']['taskForColleaguesColumn']);
            unset($result['data']['taskForMeColumn']);
            unset($result['data']['taskForPeopleColumn']);
        }

        $result['success'] = true;

        return $result;
    }

    /**
     * Формирование набора данных по задаче
     * @return array
     */
    public static function getDataForTask(): array
    {

        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $id = key_exists('id', $_POST) ? $_POST['id'] : 0;
        if ($id == 0) $id = 2;
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = WorkPlaceTasks::model()->with('user', 'whom')->findByPk($id);
        if ($data) {
            if (Yii::app()->user->id == $data->whom_id) {
                $data->viewed = 1;
                $data->save();
            }
            $array = $data->makeTaskInfoFull();
            $array['user'] = User::getUserInfo($user);
            $result['success'] = true;
            $result['data'] = $array;
        } else {
            $result['error'] = 'Не найден чат по id ' . $id;
            $result['status'] = 204;
        }
        return $result;
    }

    /**
     * создание системного комментария в задаче
     * @param $message
     * @return bool
     */
    public function addSystemCommentToTask($message)
    {
        $user = User::model()->findByPk(Yii::app()->user->id);
        $data = new WorkPlaceTaskComments();
        $data->task_id = $this->id;
        $data->comment = $message;
        $data->change = 0;
        $data->system = 1;
        $data->save();

        if (!empty($this->view_user)) {
            foreach (json_decode($this->view_user) as $user_id) {
                if ($user_id == $user->id) continue;
                WorkPlaceNotification::addRecord($user_id, $this->id, 'WorkPlaceTasks');
            }
        }

        if ($user->id != $this->created_user) {
            WorkPlaceNotification::addRecord($this->created_user, $this->id, 'WorkPlaceTasks');
        }
        return true;
    }

    /**
     * Добавление комментария в задачу
     * @return array
     */
    public static function addCommentToTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => ['id' => 0, 'date' => '']];
        $task_id = Yii::app()->request->getParam('task_id');
        $message_id = Yii::app()->request->getParam('message_id');
        $message = Yii::app()->request->getParam('message');
        $quote = Yii::app()->request->getParam('quote');
        $system = Yii::app()->request->getParam('system');
        $user = User::model()->findByPk(Yii::app()->user->id);
        $w_task = WorkPlaceTasks::model()->findByPk($task_id);
        $file_id = Yii::app()->request->getParam('file_id');
        if ($w_task) {
            if (!empty($message) || !empty($file_id)) {
                if ($message_id) {
                    $data = WorkPlaceTaskComments::model()->findByPk($message_id);
                    if ($data->user_id == $user->id) { // проверка, редактировать сообщение может только создатель
                        $data->message = $message;
                        $data->change = 1;
                        $data->save();
                        $result['out']['id'] = $data->id;
                        $result['out']['date'] = $data->created_at;
                    } else {
                        $result['error'] = 'Попытка редактирования чужой записи!';
                        $result['status'] = 400;
                    }
                } else {
                    $data = new WorkPlaceTaskComments();
                    $data->task_id = $task_id;
                    $data->comment = $message;
                    $data->change = 0;
                    $data->quote = $quote;
                    $data->file_id = $file_id;
                    $data->system = ($system && $system == 1) ? 1 : 0;
                    $data->save();
                    $result['out']['id'] = $data->id;
                    $result['out']['date'] = $data->created_at;

                    if (!empty($w_task->view_user)) {
                        foreach (json_decode($w_task->view_user) as $user_id) {
                            if ($user_id == $user->id) continue;
                            WorkPlaceNotification::addRecord($user_id, $task_id, 'WorkPlaceTasks');
                        }
                    }

                    if ($user->id != $w_task->created_user) {
                        WorkPlaceNotification::addRecord($w_task->created_user, $task_id, 'WorkPlaceTasks');
                    }
                }
                $result['success'] = true;

            } else {
                $result['error'] = 'Текст сообщения отсутствует!';
                $result['status'] = 400;
            }
        } else {
            $result['error'] = 'Не найдена задача по id ' . $task_id;
            $result['status'] = 204;
        }
        return $result;
    }

    /**
     * Добавление обозревателя в задачу
     * @return array
     */
    public static function addUserToTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $user_id = Yii::app()->request->getParam('user_id');
        $task_id = Yii::app()->request->getParam('task_id');
        $task = WorkPlaceTasks::model()->findByPk($task_id);
        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $result = Tools::PushUserToJsonField($task, 'view_user', 'created_user', $user_id);
                if ($result['success']) {
                    $user = User::model()->findByPk($user_id);
                    $result['out'] = User::getUserInfo($user);
                    // создать сообщение о добавлении пользователя
                    //$messege          = "Участник " . $user->getKadryFioShort() . " был добавлен в задачу.";
                    $messege = $user->getKadryFioShort() . " отмечен(а) новым обозревателем в задаче № $task->id";
                    $_POST['task_id'] = $task_id;
                    $_POST['message'] = $messege;
                    $_POST['system'] = 1;
                    $r = self::addCommentToTask();
                    if ($r['success']) {

                        WorkPlaceActionArchive::addAction($task_id, "В задачу добавлен пользователь " . $user->getKadryFioShort(), self::MODULE_NAME);

                        $result['out']['message']['id'] = $r['out']['id']; // id добавленной записи
                        $result['out']['message']['text'] = $messege;
                        $result['success'] = true;
                        //$task->makeSearchStr();
                        Notifiers::sendNotifier('WorkPlaceTasks', 'addUserToTask', $task->id, ['addUserId' => $user_id, 'addUserFio' => $user->getKadryFioShort(), 'members' => $task->memberRoleList]);
                        $transaction->commit();
                    } else {
                        $result['error'] = $r['error'];
                        $result['status'] = $r['status'];
                        $result['success'] = false;
                        $transaction->rollback();
                    }
                } else {
                    $transaction->rollback();
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['status'] = 400;
                $transaction->rollback();
            }
        } else {
            $result['error'] = "Не найден чат по id [$task_id] ";
            $result['status'] = 400;
        }
        return $result;
    }

    /**
     * удаление обозревателя из задачи
     * @return array
     */
    public static function deleteUserFromTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $user_id = Yii::app()->request->getParam('user_id');
        $task_id = Yii::app()->request->getParam('task_id');
        $task = WorkPlaceTasks::model()->findByPk($task_id);
        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $result = Tools::DeleteUserFromJsonField($task, 'view_user', $user_id);
                if ($result['success']) {
                    // создать сообщение о добавлении пользователя
                    $user = User::model()->findByPk($user_id);
                    $messege = "Участник " . $user->getKadryFioShort() . " был удален из задачи";
                    $_POST['task_id'] = $task_id;
                    $_POST['message'] = $messege;
                    $_POST['system'] = 1;
                    $r = self::addCommentToTask();
                    if ($r['success']) {
                        $result['out']['message']['id'] = $r['out']['id']; // id добавленной записи
                        $result['out']['message']['text'] = $messege;
                        WorkPlaceActionArchive::addAction($task_id, "Из задачи удален пользователь " . $user->getKadryFioShort(), self::MODULE_NAME);
                        Notifiers::sendNotifier('WorkPlaceTasks', 'deleteUserFromTask', $task->id, ['deletedUserId' => $user_id, 'deletedUserFio' => $user->getKadryFioShort(), 'members' => [$task->memberRoleList]]);
                        $transaction->commit();
                    } else {
                        $result['error'] = $r['error'];
                        $result['status'] = $r['status'];
                        $result['success'] = false;
                        $transaction->rollback();
                    }
                } else {
                    $transaction->rollback();
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $transaction->rollback();
            }
        } else {
            $result['error'] = "Не найдена задача по id [$task_id] ";
            $result['status'] = 400;
        }
        return $result;
    }

    /**
     * Загрузка файлов в задачу
     * @return array
     */
    public static function uploadTaskFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $res = WorkPlaceMessageFiles::uploadMessageFile(1);

        $id = Yii::app()->request->getParam('id');

        $transaction = Yii::app()->db->beginTransaction();
        if ($res['success'] && $res['out']['id']) {
            $data = new WorkPlaceTaskFiles();
            $data->task_id = $id;
            $data->file_id = $res['out']['id'];
            $data->save();
            $transaction->commit();
            WorkPlaceActionArchive::addAction($id, "В задачу добавлен файл ", self::MODULE_NAME);
            $result['out'] = $res['out'];
            $result['success'] = true;
        } else {
            $result['error'] = $res['error'];
            $result['status'] = $res['status'];
        }

        return $result;
    }

    /**
     * Загрузка файлов в чат задачи
     * @return array
     */
    public static function uploadTaskCommentFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $_POST['category_id'] = 1;
        $_POST['category_status'] = 3;
        $res = WorkPlaceMyFiles::uploadMyFile(true);
        if ($res['success']) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                // добавляем комментарий
                $taskId = Yii::app()->request->getParam('id');
                $_POST['system'] = 1;
                $_POST['task_id'] = $taskId;
                $_POST['file_id'] = $res['out']['id'];
                $res1 = WorkPlaceTasks::addCommentToTask();
                if ($res1['success']) {
                    $result['out']['id'] = $res1['out']['id'];
                    $result['out']['url'] = $res['out']['url'];
                    $result['success'] = true;
                    $transaction->commit();
                } else {
                    $result['error'] = 'Ошибка создания собщения : ' . $res1['error'];
                    $transaction->rollback();
                    $result['status'] = 500;
                }
                //$result = $res;
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

    /**
     * Изменеие статуса задачи
     * @param $newStatus
     * @return string
     */
    public function changeStatus($newStatus): string
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        $this->save();
        $newStatusText = self::$statusList[$newStatus];
        $oldStatusText = 'Не определен';
        if (key_exists($oldStatus, self::$statusList)) {
            $oldStatusText = self::$statusList[$oldStatus];
        }
        WorkPlaceActionArchive::addAction($this->id, "Изменен статус задачи с $oldStatusText на  $newStatusText ", self::MODULE_NAME);
        Notifiers::sendNotifier('WorkPlaceTasks', 'changeStatus', $this->id, ['oldStatusText' => $oldStatusText, 'newStatusText' => $newStatusText, 'members' => [$this->memberRoleList]]);
        return "Мною изменен статус задачи с $oldStatusText на $newStatusText";
    }

    /**
     * смена даты выполнения задачи
     * @param $newDate
     * @return string
     */
    public function changeDeadLine($newDate): string
    {
        $oldDate = $this->date;
        $this->date = date('Y-m-d H:i:s', $newDate);
        $this->save();
        $newDateText = date('d.m.Y H:i', $newDate);
        $oldDateText = date('d.m.Y H:i', $oldDate);
        WorkPlaceActionArchive::addAction($this->id, "Изменен срок выполнения задачи на с $oldDateText на $newDateText ", self::MODULE_NAME);
        Notifiers::sendNotifier('WorkPlaceTasks', 'changeDeadLine', $this->id, ['oldDateText' => $oldDateText, 'newDateText' => $newDateText, 'members' => [$this->memberRoleList]]);
        return "Мною продлены сроки выполнения задачи с $oldDateText на $newDateText";
    }

    public function changeDeadLineWithText($newDate, $text): string
    {
        $oldDate = $this->date;
        $this->date = date('Y-m-d H:i:s', $newDate);
        $this->save();

        $newDateText = date('d.m.Y H:i', $newDate);
        $oldDateText = $oldDate;
        WorkPlaceActionArchive::addAction($this->id, "Изменен срок выполнения задачи на с $oldDateText на $newDateText ", self::MODULE_NAME);
        Notifiers::sendNotifier('WorkPlaceTasks', 'changeDeadLine', $this->id, ['oldDateText' => $oldDateText, 'newDateText' => $newDateText, 'members' => [$this->memberRoleList]]);

        $msg = 'Изменен срок выполнения задачи: № ' . $this->id . '. Старый срок ' . date('d-m-Y H:i', strtotime($oldDateText)) . '. Новый срок ' . date('d-m-Y H:i', strtotime($newDateText)) . '. По причине: ' . $text;
        $this->addSystemCommentToTask($msg);


        return "Мною продлены сроки выполнения задачи с $oldDateText на $newDateText";
    }

    /**
     * смена исполнителя задачи
     * @param $newWhom
     * @return string
     */
    public function changeIspolnitel($newWhom)
    {
        $oldWhom = $this->whom_id;
        $this->whom_id = $newWhom;
        $this->save();
        $newUser = User::model()->findByPk($newWhom);
        $oldUser = User::model()->findByPk($oldWhom);
        $newWhomText = $newUser->getKadryFioShort();
        $oldWhomText = $oldUser->getKadryFioShort();
        WorkPlaceActionArchive::addAction($this->id, "Изменен исполнитель задачи на с $oldWhomText на $newWhomText ", self::MODULE_NAME);
        Notifiers::sendNotifier('WorkPlaceTasks', 'changeIspolnitel', $this->id, ['oldIspolnitelText' => $oldWhomText, 'newIspolnitelText' => $newWhomText, 'members' => [$this->memberRoleList]]);
        return "Мною изменен исполнитель задачи с $oldWhomText на $newWhomText уверен что этот сотрудник справится лучше";
    }

    /**
     * Изменени е темы задачи
     * @param $newTitle
     * @return string
     */
    public function changeTitle($newTitle)
    {
        $oldTitle = $this->title;
        $this->title = $newTitle;
        $this->save();
        WorkPlaceActionArchive::addAction($this->id, "Изменена тема задачи на с $oldTitle на $newTitle ", self::MODULE_NAME);
        Notifiers::sendNotifier('WorkPlaceTasks', 'changeTitle', $this->id, ['oldTitleText' => $oldTitle, 'newTitleText' => $newTitle, 'members' => [$this->memberRoleList]]);
        return "Мною изменено наименование задачи с $oldTitle на $newTitle";
    }

    /**
     * изменение статуса задачи
     * @return array
     */
    public static function setNewStatusForTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $task_id = Yii::app()->request->getParam('task_id', 0);
        $status_id = Yii::app()->request->getParam('status_id', null);
        $task = WorkPlaceTasks::model()->findByPk($task_id);
        if ($task) {
            if (key_exists($status_id, self::$statusList)) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $task->addSystemCommentToTask($task->changeStatus($status_id));
                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $result['error'] = $e->getMessage();
                    $result['status'] = 500;
                }
            } else {
                $result['error'] = 'Не допустимый статус';
                $result['status'] = 400;
            }

        } else {
            $result['error'] = 'Не найдена задача по ID ' . $task_id;
            $result['status'] = 400;
        }

        return $result;
    }

    /**
     * Отметка об исполнении задачи исполнителем
     * @return array
     */
    public static function setUserEndWorkForTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $task_id = Yii::app()->request->getParam('task_id', 0);

        $task = WorkPlaceTasks::model()->findByPk($task_id);
        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $task->in_archive = '1';
                $task->date_colse_task = date('Y-m-d H:i:s');
                $task->stage = self::STAGE_ISPOLNITEL_COMPLETED;
                //$task->text_end = $text;
                $task->save();
                WorkPlaceActionArchive::addAction($task->id, "Исполнитель завершил работу над задачей", self::MODULE_NAME);
                Notifiers::sendNotifier('WorkPlaceTasks', 'setfinishIspolnitel', $task->id, ['members' => [$task->memberRoleList]]);
                $task->addSystemCommentToTask('Исполнитель завершил работу над задачей');
                $result['success'] = true;
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $result['error'] = $e->getMessage();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = 'Не найдена задача по ID ' . $task_id;
            $result['status'] = 400;
        }
        return $result;
    }

    /**
     * Подтверждение выполнения задачи руководителем
     * @return array
     */
    public static function setChiefMoveTaskToArchive()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $task_id = Yii::app()->request->getParam('task_id', 0);
        $score = Yii::app()->request->getParam('status', 0);
        $text = Yii::app()->request->getParam('text', 0);

        $task = WorkPlaceTasks::model()->findByPk($task_id);
        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $task->head_comment = $text;
                $task->head_score = $score;
                $task->head_close_task = date('Y-m-d H:i:s');
                $task->stage = self::STAGE_BOSS_COMPLETED;
                $task->save();
                $task->addSystemCommentToTask('Руководитель подтвердил завершение задачи');
                WorkPlaceActionArchive::addAction($task->id, "Руководитель подтвердил завершение задачи", self::MODULE_NAME);
                Notifiers::sendNotifier('WorkPlaceTasks', 'setfinishIspolnitel', $task->id, ['members' => [$task->memberRoleList]]);
                $result['success'] = true;
                $transaction->commit();
            } catch (Exception $e) {
                $transaction->rollback();
                $result['error'] = $e->getMessage();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = 'Не найдена задача по ID ' . $task_id;
            $result['status'] = 400;
        }
        return $result;
    }

    /**
     * Обновление статуса задачи (устарела)
     * @return array
     */
    public static function updateTaskStatus()
    {
        /*
            status - Изменить статус (приоритет)
            extend - Изменить время задачи (дедлайн)
            remember  -  Напомнить исполнителю
            transfer - Передать задачу (изменить исполнителя)
            finish - Завершить   у исполнителя
            finishByBoss - Завершить  у начальника
         */
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $task_id = Yii::app()->request->getParam('task_id', 0);
        $tools = Yii::app()->request->getParam('tools', null);
        $task = WorkPlaceTasks::model()->findByPk($task_id);
        $_POST['system'] = 1;
        if ($task) {
            switch ($tools) {
                case 'status' : //изменение приоритета
                    $newStatus = Yii::app()->request->getParam('status', false);
                    if ($newStatus) {
                        if (key_exists($newStatus, self::$statusList)) {
                            $transaction = Yii::app()->db->beginTransaction();
                            try {
                                $task->addSystemCommentToTask($task->changeStatus($newStatus));
                                $result['success'] = true;
                                $transaction->commit();
                            } catch (Exception $e) {
                                $transaction->rollback();
                                $result['error'] = $e->getMessage();
                                $result['status'] = 500;
                            }
                        } else {
                            $result['error'] = 'Не допустимый статус';
                            $result['status'] = 401;
                        }
                    } else {
                        $result['error'] = 'Не определен статус';
                        $result['status'] = 401;
                    }
                    break;
                case 'extend' : // перенос срока
                    $newDate = Yii::app()->request->getParam('date', false);
                    if ($newDate) {
                        $transaction = Yii::app()->db->beginTransaction();
                        try {
                            $task->addSystemCommentToTask($task->changeDeadLine($newDate));
                            $result['success'] = true;
                            $transaction->commit();
                        } catch (Exception $e) {
                            $transaction->rollback();
                            $result['error'] = $e->getMessage();
                            $result['status'] = 500;
                        }
                    } else {
                        $result['error'] = 'Не определен статус';
                        $result['status'] = 401;
                    }
                    break;
                case 'remember' : // напомнить исполнителю
                    $users = Yii::app()->request->getParam('users', []);
                    $mes = "Какое то сообщение";
                    $userList = [
                        User::model()->findByPk($task->whom_id)
                    ];
                    if ($users && is_array($users) && count($users) > 0) {
                        foreach ($users as $user_id) {
                            $emp = OkLsEmployees::model()->findByPk($user_id);
                            if ($emp) {
                                $userList[] = $emp->user;
                            }
                        }
                    }
                    foreach ($userList as $user) {
                        Notice::sendToUserId($user->id, $mes);
                    }
                    Notifiers::sendNotifier('WorkPlaceTasks', 'rememberIspolnitel', $task->id, ['members' => [$task->memberRoleList]]);
                    $task->addSystemCommentToTask("Я напоминаю, эта задача еще не выполнена, давайте поторопимся");
                    break;
                case 'transfer' : // изменение исполнителя
                    $newDate = Yii::app()->request->getParam('date', false);
                    $newUser_id = Yii::app()->request->getParam('user_id', 0);
                    $newText = Yii::app()->request->getParam('text', 0);
                    if ($newDate) {
                        $transaction = Yii::app()->db->beginTransaction();
                        try {
                            $messages = [];
                            if ($task->whom_id != $newUser_id) {
                                $messages[] = $task->changeIspolnitel($newUser_id);
                            }
                            if (strtotime($task->date) != $newDate) {
                                $messages[] = $task->changeDeadLine($newDate);
                            }
                            if ($task->title != $newText) {
                                $messages[] = $task->changeTitle($newText);
                            }
                            $ok = true;
                            foreach ($messages as $message) {
                                $_POST['message'] = $message;
                                $_POST['system'] = 1;
                                $res1 = WorkPlaceTasks::addCommentToTask();
                                if (!$res1['success']) {
                                    $result['error'] = $res1['error'];
                                    $result['status'] = $res1['status'];
                                    $transaction->rollback();
                                    $ok = false;
                                    break;
                                }
                            }
                            if ($ok) {
                                $result['success'] = true;
                                $transaction->commit();
                            }
                        } catch (Exception $e) {
                            $transaction->rollback();
                            $result['error'] = $e->getMessage();
                            $result['status'] = 500;
                        }
                    } else {
                        $result['error'] = 'Не определен статус';
                        $result['status'] = 401;
                    }
                    break;
                case 'finish' :
                    $transaction = Yii::app()->db->beginTransaction();
                    try {
                        $task->addSystemCommentToTask($task->setfinishIspolnitel());
                        $result['success'] = true;
                        $transaction->commit();
                    } catch (Exception $e) {
                        $transaction->rollback();
                        $result['error'] = $e->getMessage();
                        $result['status'] = 500;
                    }
                    break;
                case 'finishByBoss' :
                    $transaction = Yii::app()->db->beginTransaction();
                    $text = Yii::app()->request->getParam('text', false);
                    $score = Yii::app()->request->getParam('score', false);
                    $error = '';
                    if (!$text) {
                        $error .= "Не указан комментарий завершения задачи! ";
                    }
                    if (!$score) {
                        $error .= "Не указана оценка выполнения задачи! ";
                    }
                    if (!$error) {
                        try {
                            $task->addSystemCommentToTask($task->setFinishBoss($text, $score));
                            $result['success'] = true;
                            $transaction->commit();
                        } catch (Exception $e) {
                            $transaction->rollback();
                            $result['error'] = $e->getMessage();
                            $result['status'] = 500;
                        }
                    } else {
                        $transaction->rollback();
                        $result['error'] = $error;
                        $result['status'] = 401;
                    }
                    break;
                default:
                    $result['error'] = 'Не определена функция [' . $tools . ']';
                    $result['status'] = 401;
            }
        } else {
            $result['error'] = 'Не определена задача';
            $result['status'] = 401;
        }
        return $result;
    }

    /**
     * Установка утверждения в задаче или отмена ее выполнения
     * @param $ok
     * @return array
     */
    public static function setNoApproveForTask($ok): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $task_id = Yii::app()->request->getParam('task_id', 0);
        $user_id = Yii::app()->request->getParam('user_id', 0);
        $date = Yii::app()->request->getParam('date', null);
        $text = Yii::app()->request->getParam('text', null);
        $task = WorkPlaceTasks::model()->findByPk($task_id);
        // $user = User::model()->findByPk($user_id);

        if ($task) {
            if ($ok) { // задача подтверждена
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $task->head_active = 1;
                    $task->head_id = Yii::app()->user->id;
                    $task->head_date = date('Y-m-d H:i:s');
                    $task->stage = self::STAGE_APPROVAL;
                    $task->whom_id = $user_id;
                    if (!empty($text)) {
                        $task->text_head = $text;
                    } else {
                        $task->text_head = $task->text;
                    }

                    if ($date != strtotime($task->date)) {
                        $temp = new WorkPlaceTaskComments();
                        $temp->task_id = $task->id;
                        $temp->comment = 'Изменен срок выполнения задачи. Старый срок ' . date('d-m-Y H:i', strtotime($task->date)) . ' Новый срок ' . date('d-m-Y H:i', $date);
                        $temp->save();
                        WorkPlaceActionArchive::addAction($task->id, 'Изменен срок выполнения задачи. Старый срок ' . date('d-m-Y H:i', strtotime($task->date)) . ' Новый срок ' . date('d-m-Y H:i', $date), self::MODULE_NAME);
                        $task->date = date('Y-m-d H:i', $date);
                    }
                    $task->save();
                    Notifiers::sendNotifier('WorkPlaceTasks', 'approveTrue', $task->id, ['members' => [$task->memberRoleList]]);
                    /*$text = "Для Вас задача № " . $task->id . " от " . $task->user->getKadryFioShort();
                    if (Yii::app()->user->id != $task->whom_id) {
                        Sms::model()->sendSMSUser($task->userSend, $text, 'wp');// отправка смс исполнителю
                        Notice::sendToUser($task->userSend, $text, 'wp');
                    }
                    if (!empty($data->view_user)) {//Рассылаем сообщения в ЕИП учасникам
                        $text = 'Для ' . $data->userSend->getKadryFioShort() . ' поступила новая задача с темой: «' . $task->title . '», Вы были отмечены как обозреватель.';
                        foreach (json_decode($task->view_user, true) as $user_id) {
                            $user = User::model()->findByPk($user_id);
                            if (!empty($user)) {
                                Sms::model()->sendSMSUser($user, $text, 'wp');// отправка смс учасникам
                                Notice::sendToUser($user, $text, 'wp');
                            }
                        }
                    }*/
                    WorkPlaceActionArchive::addAction($task->id, "Задача утверждена", self::MODULE_NAME);

                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $result['error'] = $e->getMessage();
                    $result['status'] = 500;
                }
            } else { // задача отменена
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $task->head_id = Yii::app()->user->id;
                    $task->head_date = date('Y-m-d H:i:s');
                    $task->in_archive = 1;
                    $task->in_false = 1;
                    $task->head_score = 0;
                    $task->head_comment = 'Задача забракована прямым руководителем.';
                    $task->date_colse_task = date('Y-m-d H:i:s', time());
                    $task->stage = self::STAGE_APPROVAL_REJECTED;
                    $task->save();

                    $comment = new WorkPlaceTaskComments();
                    $comment->task_id = $task->id;
                    $comment->comment = $text;
                    $comment->save();

                    /*$text = 'Задача № ' . $task->id . ' для ' . $task->userSend->getKadryFioShort() . '  забракована прямым руководителем';
                    Sms::model()->sendSMSUser($task->user, $text, 'wp');// отправка смс
                    Notice::sendToUser($task->user, $text, 'wp');*/
                    Notifiers::sendNotifier('WorkPlaceTasks', 'approveFalse', $task->id, ['members' => [$task->memberRoleList]]);

                    WorkPlaceActionArchive::addAction($task->id, "Задача забракована прямым руководителем", self::MODULE_NAME);

                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $transaction->rollback();
                    $result['error'] = $e->getMessage();
                    $result['status'] = 500;
                }
            }
        } else {
            $result['error'] = 'Не определена задача';
            $result['status'] = 401;
        }
        return $result;
    }

    // statistica

    /**
     * @param int $userId Id пользавателя модель User
     * @return int Количество задач, завершенных пользователем
     */
    public static function getUserClosedTaskCount(int $userId): int
    {
        $criteria = new CDbCriteria();
        $criteria->addCondition("whom_id = '$userId'");
        $criteria->addCondition("date_colse_task <> '00.00.0000 0:00:00'");
        return WorkPlaceTasks::model()->count($criteria);
    }

    /**
     * @param array $userId
     * @return int Количество задач, завершенных пользователеми
     */
    public static function getUsersClosedTaskCount(array $userIds): int
    {
        $criteria = new CDbCriteria();
        $criteria->addInCondition("whom_id", $userIds);
        $criteria->addCondition("date_colse_task <> '00.00.0000 0:00:00'");
        return WorkPlaceTasks::model()->count($criteria);
    }

    /**
     * @param int $userId Id пользавателя модель User
     * @return int Количество задач, завершенных пользователем
     */
    public static function getUserAverageClosedTaskCount(int $userId): int
    {
        $sql = "SELECT COALESCE(round(AVG(c), 1),0) as a  FROM   workplace_tasks, 
                (
                    SELECT  CONCAT(YEAR(wt.date_colse_task), '_', MONTH(wt.date_colse_task)) AS m, COUNT(wt.ID) AS c
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id = '$userId' AND wt.date_colse_task != '00.00.0000 0:00:00'
                    GROUP BY m
                ) AS qq";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }

    /**
     * @param array $userIds Id пользавателей модель User
     * @return int Количество задач, завершенных пользователями
     */
    public static function getUsersAverageClosedTaskCount(array $userIds): int
    {
        if (count($userIds) == 0) return 0;
        $sql = "SELECT COALESCE(round(AVG(c), 1), 0) as a  FROM   workplace_tasks, 
                (
                    SELECT  CONCAT(YEAR(wt.date_colse_task), '_', MONTH(wt.date_colse_task)) AS m, COUNT(wt.ID) AS c
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id in (" . implode(',', $userIds) . ") AND wt.date_colse_task != '00.00.0000 0:00:00'
                    GROUP BY m
                ) AS qq";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }

    /**
     * @param int $userId Id пользавателя модель User
     * @return int Средняя оценка выполненных задач
     */
    public static function getUserClosedTaskAverageMark(int $userId): int
    {
        $sql = "SELECT COALESCE(ROUND(AVG(wt.head_score + 1), 1), 0) AS a
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id = '$userId' AND wt.date_colse_task != '00.00.0000 0:00:00'";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }

    /**
     * @param array $userIds Id пользавателя модель User
     * @return int Средняя оценка выполненных задач
     */
    public static function getUsersClosedTaskAverageMark(array $userIds): int
    {
        if (count($userIds) == 0) return 0;
        // $sql = "SELECT COALESCE(ROUND(AVG(wt.head_score + 1), 1), 0) AS a
        $sql = "SELECT COALESCE(ROUND(AVG(wt.head_score), 1), 0) AS a
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id in (" . implode(',', $userIds) . ") AND wt.date_colse_task != '00.00.0000 0:00:00'";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }

    /**
     * @param int $userId Id пользавателя модель User
     * @return int среднее время выполнения одной задачи
     */
    public static function getUserClosedTaskAverageTime(int $userId): int
    {
        $sql = "SELECT COALESCE(AVG(date_colse_task - created_at), 0) AS a
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id = '$userId' AND wt.date_colse_task != '00.00.0000 0:00:00'";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }

    /**
     * @param array $userIds Id пользавателя модель User
     * @return int среднее время выполнения одной задачи
     */
    public static function getUsersClosedTaskAverageTime(array $userIds): int
    {
        if (count($userIds) == 0) return 0;
        $sql = "SELECT COALESCE(AVG(date_colse_task - created_at), 0) AS a
                    FROM workplace_tasks wt 
                    WHERE wt.whom_id in (" . implode(',', $userIds) . ") AND wt.date_colse_task != '00.00.0000 0:00:00'";
        $res = Yii::app()->db->createCommand($sql)->queryRow();
        return $res['a'];
    }
    // === API END ===

    /*    public function setfinishIspolnitel(){
            $this->status = 3;
            $this->save();
            Notifiers::sendNotifier('WorkPlaceTasks', 'setfinishIspolnitel', $this->id, ['members'=>[$this->memberRoleList]]);
            return "Я завершил(а) выполнение задачи, проверьте, пожалуйста, результат и если все устраивает переведите задачу в архив";
        }

        public function setfinishBoss($text, $score){
            $this->head_comment = $text;
            $this->head_score = $score;
            $this->status = 3;
            $this->save();
            Notifiers::sendNotifier('WorkPlaceTasks', 'setfinishBoss', $this->id, ['members'=>[$this->memberRoleList]]);
            return "Задача выполнена";
        }

        public function add(array $data)
        {
            $result         = false;
            $temp           = OkLsEmployees::model()->findByPk($data['whom_id']);
            $data['pdr_id'] = $temp->pdrId;
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
            $result            = false;
            $this->transaction = Yii::app()->db->beginTransaction();
            $this->create($data); //Заполняем поля модели
            if ($this->save()) {
                if (!empty($data['file_list'])) {
                    $this->uploadFile($data['file_list']);//Добавляем прикрепленные файлы
                }
                if ($this->addData($data)) $result = true;//Запись в архив
            }

            if ($result && $this->head_active != '1') {
                $head_id = OkSprPdr::findBossId($data['pdr_id']);
                if ($this->whom_id == $head_id->idBoss) {
                    $head_id = OkSprPdr::findBossId($head_id->parentId);
                }

                //$head = OkLsEmployees::model()->findByPk($head_id->idBoss);
                if ($head_id->boss->user) {
                    $tempText = "Вашему подчиненному было создана задача № " . $this->id . " от " . Yii::app()->user->title;
                    self::sendSmsForHead($head_id->boss->user, $tempText);// отправка смс
                    Notice::sendToUser($head_id->boss->user, $tempText, 'wp');// отправка Notice
                }
            }
            if ($result && $this->head_active == '1') {
                if ($data['whom_id'] != Yii::app()->user->kadry->id) {//Если было создано руководителем
                    $tempText = "Для Вас задача № " . $this->id . " от " . Yii::app()->user->title;
                    self::sendSmsForHead($this->userSend, $tempText);// отправка смс
                    Notice::sendToUser($this->userSend, $tempText, 'wp');
                }
                if (!empty($data['users'])) {//Рассылаем сообщения в ЕИП учасникам
                    $text = 'Для ' . $this->userSend->title . ' поступила новая задача с темой «' . $this->title . '», Вы были отмечены как обозреватель.';
                    foreach ($data['users'] as $user_id) {
                        $user = User::model()->findByPk($user_id);
                        if (!empty($user)) {
                            Notice::sendToUser($user, $text, 'wp');
                        }
                    }
                }
            }
            return $result;
        }

        public function addData(array $data)
        {
            $result = '';
            $toSave = false;
            try {
                foreach ($this->fields as $field => $updateText) {
                    if ($this->updateField($field, $updateText, $data)) $toSave = true;
                }
                if ($toSave) {
                    $this->transaction->commit();
                    $result = true;
                }
            } catch (Exception $e) {
                $this->transaction->rollback();
                $result = $e->getMessage();
            }
            return $result;
        }

        public function updateField($field, $updateText, $data) //Запись в архив
        {
            $text   = '';
            $result = false;
            if (!empty($data[$field])) {
                if ($field == 'text' || $field == 'title') $text = $updateText . $data[$field];
                if ($field == 'date') $text = $updateText . date('Y-m-d', strtotime($data[$field]));
                if ($field == 'whom_id') $text = $updateText . $this->userSend->title;
                //            if ($field == 'file' && $data['file']['wp_doc']['size'] > 0) $text = $updateText . $data['file']['wp_doc']['name'];
                if ($field == 'users') {
                    $text = $updateText;
                    foreach ($data[$field] as $user) {
                        $u    = User::model()->findByPk($user);
                        $text .= $u->title . ' ';
                    }
                }
                if (!empty($text)) WorkPlaceActionArchive::AddAction($this->id, $text, 'conflict');
                $result = true;
            }
            return $result;
        }

        public function uploadFile($data)
        {
            $result = false;
            foreach ($data as $item) {
                $upload          = new WorkPlaceTaskFiles();
                $upload->task_id = $this->id;
                $upload->file_id = intval($item);
                if ($upload->save()) $result = true;
            }
            return $result;
        }

        public function create(array $data)
        {
            $this->title     = $data['title'];
            $this->text      = $data['text'];
            $this->text_head = $data['text'];
            $date            = (date('Y-m-d H:i:s', strtotime($data['date']))) ?? date('Y-m-d H:i:s', time());
            $this->date      = $date;
            $this->whom_id   = $data['whom_id'];
            $this->pdr_id    = $data['pdr_id'];
            $this->status    = $data['status'];
            $this->view_user = (!empty($data['users'])) ? json_encode($data['users']) : null;
            if ($data['whom_id'] == Yii::app()->user->kadry->id) {// проверяет на себя
                $head_active      = 1;
                $data['self_add'] = '1'; //Если добавили себе сами
            } else {// проверяет начальник ли поставил
                $temp        = OkSprPdr::model()->find("(idPdr = '" . $data['pdr_id'] . "' OR (idPdr = '" . $data['pdr_id'] . "' AND parentId = '" . Yii::app()->user->kadry->pdrId . "')) AND idBoss = '" . Yii::app()->user->kadry->id . "'");
                $head_active = (!empty($temp)) ? '1' : 0;
                //            $head_active = (substr($data['pdr_id'], 0, -2) == Yii::app()->user->kadry->pdrId) ? '1' : 0;
            }
            $this->head_active = $head_active;// проверяет начальник ли поставил
        }

        public static function makeFileServerName(string $name): string
        {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            return str_replace('.', '', microtime(true)) . '.' . $extension;
        }



        public static function crutchForHeadPermissionsInTask($user, $task)
        {
            $check_head = false;

            if ($user && $task) {
                $tempHead = OkSprPdr::model()->find("idPdr = '" . $task->pdr_id . "'");
                if ($tempHead) {
                    // Проверяем на idBoss в OkSprPdr и что бы небыло задачи на нём тогда true
                    if ($user->extId == $tempHead->idBoss && $task->whom_id != $user->id) {
                        $check_head = true;
                    } else {
                        $tempHead = OkSprPdr::model()->find("idPdr = '" . $tempHead->parentId . "'");
                        if ($tempHead) {
                            if ($user->extId == $tempHead->idBoss) $check_head = true;
                        }
                    }
                }
            }
            return $check_head;
        }*/

    /*public static function sendSmsForHead($user, $text)
    {
        //        $user = User::model()->findByPk($id);
        if (Sms::model()->sendSMSUser($user, $text, 'wp')) return true;
        return false;
    }*/

    public static function callUsersFromTask()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $task_id = Yii::app()->request->getParam('task_id', null);
        $token = Yii::app()->request->getParam('token', null);
        $users[] = Yii::app()->request->getParam('users_id', null);

        $w_task = WorkPlaceTasks::model()->findByPk($task_id);
        $text = $w_task->text;
        if ($w_task) {
            $user = User::model()->findByPk(Yii::app()->user->id);
            if ($users && is_array($users) && count($users) > 0) {
                foreach ($users as $u) {
                    if ($u != $user->id) {
                        $userList[] = $u;
                    }
                }
            }
            if (count($users) > 0) {
                foreach ($users as $uId) {
                    Notice::sendToUser($uId, $text, 'wp');
                    // Notice::sendToUserNoMsg($uId, $w_task->text, 'wp');
                }
                $result['success'] = true;
            } else {
                $result['error'] = 'Не кому отправлять!!!';
                $result['status'] = 401;
            }
        } else {
            $result['error'] = 'Не найден таск по id ' . $task_id;
            $result['status'] = 204;
        }
        return $result;
    }

    public static function setNewDateForTask()// изменино на static
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $task_id = Yii::app()->request->getParam('task_id', 0);
        $token = Yii::app()->request->getParam('token', 0);
        $text = Yii::app()->request->getParam('text', 0);
        $newDate = Yii::app()->request->getParam('date', false);

        $task = WorkPlaceTasks::model()->findByPk($task_id);

        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                if ($newDate) {
                    try {
                        $task->changeDeadLineWithText($newDate, $text);
                        $result['success'] = true;
                        $transaction->commit();
                    } catch (Exception $e) {
                        $transaction->rollback();
                        $result['error'] = $e->getMessage();
                        $result['status'] = 500;
                    }
                } else {
                    $result['error'] = 'Ошибка изминения даты';
                    $result['status'] = 500;
                    $transaction->rollback();
                }
            } catch (Exception $e) {
                $result['error'] = 'Ошибка изминения даты ' . $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result['error'] = 'Не найден таск по id ' . $task_id;
            $result['status'] = 204;
        }
        return $result;
    }

    public static function moveTaskInColumn()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $oldColumn = Yii::app()->request->getParam('positionOld', null);
        $newColumnIndex = Yii::app()->request->getParam('positionNew', null);
        $taskIdFull = Yii::app()->request->getParam('task_id', null);
        $taskColumn = Yii::app()->request->getParam('task_column', null);
        $token = Yii::app()->request->getParam('token', null);

        $taskId = preg_replace("/[^0-9]/", '', $taskIdFull);

        if ($oldColumn !== null && $newColumnIndex !== null && $taskId !== null && $taskColumn !== null) {
            if (WorkPlaceTasksOrder::moveElement($oldColumn, $newColumnIndex, $taskId, $taskColumn)) {
                $result['success'] = true;
            } else {
                $result['status'] = 500;
                $result['error'] = 'При перемещении таска произошла ошибка';
            }
        } else {
            $result['success'] = false;
            $result['status'] = 500;
            $result['error'] = 'При перемещении таска произошла ошибка';//
        }
        return $result;
    }

    public static function setTaskColumns()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0];
        $columns = Yii::app()->request->getParam('columns');
        $res = WorkPlaceColumns::updateColumns(self::MODULE_NAME, $columns);
        //$res = UserSettings::setWorkPlaceColumns($columns);
        if ($res['success']) {
            $result['success'] = true;
        } else {
            $result['error'] = $res['error'];
            $result['status'] = 500;
        }
        return $result;
    }

    public function setTaskPreview()
    {
        $data = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0];
        $taskId = Yii::app()->request->getParam('taskId', 0);
        $userPerformerId = Yii::app()->request->getParam('userPerformerId', 0);

        $task = WorkPlaceTasks::model()->findByPk($taskId);
        $user = User::model()->findByPk($userPerformerId);

        try {
            if ($task) {
                $transaction = Yii::app()->db->beginTransaction();
                if ($task->stage == self::STAGE_CREATED || $task->stage == self::STAGE_PREVIEW) {
                    $task->stage = self::STAGE_PREVIEW;
                    $task->whom_id = $user->id;
                    $task->head_active = 1;
                    $task->save();
                    // $msg = User::getUserInfo($user->getFioShort()). ' , Ваш прямой руководитель перед утверждением новой Задачи №' . $task->id. ' просит Вас ознакомиться с ней';
                    $msg = ' , Ваш прямой руководитель перед утверждением новой Задачи №' . $task->id . ' просит Вас ознакомиться с ней';
                    (new Sms)->sendSMSUser($user, $msg, self::MODULE_NAME);

                    $data['out']['taskId'] = $task->id;
                    $data['out']['newStage'] = $task->stage;
                    $data['out']['userPerformer'] = User::getUserInfo($user);
                    $data['success'] = true;
                    $transaction->commit();
                } else {
                    $data['error'] = 'Задача не является неподтвержденной ' . $task->stage;
                    $data['status'] = 500;
                    $transaction->rollback();
                }
            } else {
                $data['error'] = 'Не удалось установить задачу в стадию Ознакомления';
                $data['status'] = 500;
            }
        } catch
        (Exception $e) {
            $data['error'] = 'Не удалось установить задачу в стадию Ознакомления ' . $e->getMessage();
            $data['status'] = 500;
            $transaction->rollback();
        }
        return $data;
    }


    public static function setCloseTaskFromPeopleColumn(): array
    {
        $data = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0];
        $task_id = Yii::app()->request->getParam('task_id', 0);
        $token = Yii::app()->request->getParam('token', null);
   //     $text = Yii::app()->request->getParam('text', 0);

        $task = WorkPlaceTasks::model()->findByPk($task_id);

        if ($task) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
              //  if($task->stage == self::STAGE_ISPOLNITEL_COMPLETED || $task->stage == self::STAGE_BOSS_COMPLETED){
                   // $task->head_comment = $text;

                    $task->head_active = 2;
                    $task->head_close_task = date('Y-m-d H:i:s');
                    $task->stage = self::STAGE_ARCHIVE;
                    $task->save();

                    $task->addSystemCommentToTask('Руководитель закрыл задачу');
                    WorkPlaceActionArchive::addAction($task->id, "Руководитель закрыл задачу", self::MODULE_NAME);
                    Notifiers::sendNotifier('WorkPlaceTasks', 'setCloseTaskFromPeopleColumn', $task->id, ['members' => [$task->memberRoleList]]);

                   // $data['out'] ['messege']= $mes;

                    $data['success'] = true;
                    $transaction->commit();

//                }else{
//                    $data['error'] = 'Не получилось закрыть задачу';
//                    $data['status'] = 500;
//                    $transaction->rollback();

//                }
            } catch (Exception $e) {
                $data['error'] = 'Произошла ошибка при закрытии задачи';
                $data['status'] = 500;
                $transaction->rollback();
            }
        } else {
            $data['error'] = 'Не найдена задача' . $task->id;
            $data['status'] = 400;
        }
        return $data;
    }

    public static function setLinkForTask(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];

        $file_id = Yii::app()->request->getParam('file_id');
        $file_id_in_task = Yii::app()->request->getParam('file_id_in_task');
        $task_id = Yii::app()->request->getParam('task_id');

        $task = WorkPlaceTasks::model()->findByPk($task_id);
        try {
            $transaction = Yii::app()->db->beginTransaction();

            if ($task){
                $file = WorkPlaceTaskFiles::model()->findByPk($file_id_in_task);
                if($file_id == $file->file_id && $task_id == $file->task_id){
                    if($file->upload_user != Yii::app()->user->id) {
                        $file->upload_user = Yii::app()->user->id;

                        $file->save();

                        $transaction->commit();
                        $result['success'] = true;
                        $result['status'] = 200;
                    }else{
                        $result['success'] = true;
                        $result['status'] = 200;
                    }
                }
            }else{
                $result['error'] = 'Не найдена задача ' . $task_id;
                $transaction->rollback();
                $result['status'] = 500;
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }

}