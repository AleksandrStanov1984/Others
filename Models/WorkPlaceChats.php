<?

class WorkPlaceChats extends CActiveRecord
{

    protected $transaction;

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName(): string
    {
        return '{{workplace_chat}}';
    }

    public function relations(): array
    {
        return [
            'user' => [self::BELONGS_TO, 'User', 'user_id'], 'message' => [self::HAS_MANY, 'WorkPlaceTaskComments', 'chat_id'],];
    }

    const MODULE_NAME = 'chat';

    protected function beforeSave(): bool
    {
        if (!$this->id) {
            $this->created_at = date('Y-m-d H:i:s');
            $this->updated_at = date('Y-m-d H:i:s');
            $this->user_id    = Yii::app()->user->id;
        } else {
            $this->updated_at = date('Y-m-d H:i:s');
        }
        return parent::beforeSave();
    }

    public static function sendNoticeForUser($user, $title): bool
    {
        if (Notice::sendToUser($user, Yii::app()->user->title . ' создал новый чат по теме «' . $title . '».', 'wp')) return true;
        return false;
    }

    public static function sendNoticeForUserChat($user, $title): bool
    {
        if (Notice::sendToUserChat($user, Yii::app()->user->title . ' создал новый чат по теме «' . $title . '».', 'wp')) return true;
        return false;
    }

    public static function sendSmsForUser($user): bool
    {
        if (Sms::model()->sendSMSUser($user, Yii::app()->user->title . ' пригласил(а) Вас в новый чат.', 'wp')) return true;
        return false;
    }

    public function makeSearchStr()
    {
        $arr = [
            $this->user->kadry->getFioShort(), $this->title, date('d.m.Y', strtotime($this->created_at))];
        foreach (User::getUsersListFromJson($this->user_view) as $u) {
            $arr[] = $u['name'];
        }
        $this->search_str = implode("|", $arr);
        $this->save();

    }

    public static function getChatList(): array
    {
        $status = Yii::app()->request->getParam('status', '');
        //if ($status == '') $status = false;
        $response  = [];
        $chatArray = [];
        $user      = User::model()->findByPk(Yii::app()->user->id);
        $userId    = $user->id;

        $criteria = new CDbCriteria();
        $criteria->addCondition("user_id = '$userId' OR user_view LIKE '%\"$userId\"%' ");
        $criteria->order = 'updated_at DESC';
//        $sql       = "user_id = '$userId' OR user_view LIKE '%\"$userId\"%' ";
//        $chats     = WorkPlaceChats::model()->with('user')->findAll($sql);
        $chats     = WorkPlaceChats::model()->with('user')->findAll($criteria);

        $chatIds   = [];
        $workInfo  = ['qqqqq'];
        WorkPlaceColumns::makeDefaultCol(self::MODULE_NAME, $user); // проверяем наличие дефолтных категорий
        $noDeleteIds = [];
        if ($chats) {
            foreach ($chats as $chat) {
                // информация по чату
                $chatId                  = 'chat' . $chat->id;
                $chatContent             = new stdClass();
                $chatContent->id         = $chat->id;
                $chatContent->title      = $chat->title;
                $chatContent->date       = strtotime($chat->created_at);
                $chatContent->status     = $chat->status;
                $chatContent->color_id   = $chat->color_id;
                $chatContent->count      = WorkPlaceNotification::getCount($userId, $chat->id, 'WorkPlaceChats');
                $chatContent->user       = User::getUserInfo($chat->user);
                $chatContent->view       = User::getUsersListFromJson($chat->user_view);
                $chatContent->my         = $chat->user->id == $userId ? true : false;
                $chatContent->searchStr  = $chat->search_str;
                $chatContent->updated_at = strtotime($chat->updated_at);
                $item                    = new stdClass();
                $item->id                = $chatId;
                $item->content           = $chatContent;

                if ($chat->status == 0) $chatIds[] = $chatId;
                if ($status !== '') {
                    if ($chat->status == $status) {
                        if (!isset($chatArray[$chat->status])) {
                            $chatArray[$chat->status] = new stdClass();
                        }
                        $chatArray[$chat->status]->$chatId = $item;
                    }
                } else {
                    if (!isset($chatArray[$chat->status])) {
                        $chatArray[$chat->status] = new stdClass();
                    }
                    $chatArray[$chat->status]->$chatId = $item;
                }
            }

            foreach ($chats as $chat) {
                if ($chat->status == 0) {
                    $noDeleteIds[] = $chat->id;
                    if (!WorkPlaceColumnLinks::checkLink(self::MODULE_NAME, $user, $chat->id)) {
                        WorkPlaceColumnLinks::addDefaultLinkModuleName(self::MODULE_NAME, $chat->id); // если конфликт не привязан ни к одной котагории, привязываем к дефолтной
                    }
                }
            }
        }

        // проверяем привязку конфликтов к каталогам
        WorkPlaceColumnLinks::deleteOldLinks(self::MODULE_NAME, $user, $noDeleteIds);
        $columnsOn                  = WorkPlaceColumns::makeColumnsList(self::MODULE_NAME, $user);
        $response[0]['columnOrder'] = $columnsOn['columnOrder'];
        $response[0]['columns']     = $columnsOn['columnsOn'];
        //$response[0]['columnsOld'] = UserSettings::getWorkPlaceColumns();;

        foreach ($chatArray as $key => $value) {
            $response[$key]['chats'] = $value;
        }

        $response[0]['workInfo'] = $workInfo;
        return $response;
    }

    // данные на один чат
    public static function getDataForChat(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'data' => []];
        $id     = key_exists('id', $_POST) ? $_POST['id'] : 0;
        $user   = User::model()->findByPk(Yii::app()->user->id);
        if (!empty($id) && $id != 0) {
            $array = [];
            $data  = WorkPlaceChats::model()->with('user')->findByPk($id);
            if ($data) {
                $array['user']  = User::getUserInfo(Yii::app()->user);
                $array['users'] = self::getUsersForNewChat();
                $array['info']  = [
                    'id' => $data->id, 'title' => $data->title, 'date' => strtotime($data->created_at), 'access' => $data->user_id == $user->id ? 1 : 0, 'user' => User::getUserInfo($data->user), 'view' => User::getUsersListFromJson($data->user_view), 'status' => $data->status, 'color_id' => $data->color_id,];

                $array['message'] = [];
                $message          = WorkPlaceChatComments::model()->with('user')->findAll(["condition" => "t.chat_id = '" . $data->id . "'", "order" => "t.id ASC"]);
                if ($message) {
                    foreach ($message as $item) {
                        $array['message'][] = [
                            'id'      => $item->id, 'text' => $item->message, 'url' => $item->file ? "/static/uploads/wpMessage/" . $item->file->link : null, 'date' => strtotime($item->updated_at), 'user' => User::getUserInfo($item->user), "my" => ($item->user_id == $user->id) ? true : false, // мое сообщение или нет
                            'system'  => ($item->system || $item->file) ? 1 : 0, // системное или файл - удаление, добавление пользователя, нельзя редактировать
                            'quote'   => ($item->quote > 0) ? $item->quote : false, // является сообщение ответом на другое сообщение или нет
                            'updated' => (strtotime($item->created_at) < strtotime($item->updated_at)) ? 1 : 0,];
                    }
                }
                $response = $array;

                // clearCount чистим список новых сообщений
                WorkPlaceNotification::clearCount($user->id, $id, 'WorkPlaceChats');
                $result['success'] = true;
                $result['data']    = $response;
            } else {
                $result['error']  = 'Не найден чат по id ' . $id;
                $result['status'] = 204;
            }
        } else {
            $result['error']  = 'Не верное значение параметра id ' . $id;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function addNewChat(): array
    {
        $result      = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0];
        $transaction = Yii::app()->db->beginTransaction();
        try {
            $chat = new WorkPlaceChats();
            $title = Yii::app()->request->getParam('title');
            $user_view = Yii::app()->request->getParam('users');

            if($title == '' || $title === null){
                $chat->title = 'TEST';
            }else {
                $chat->title = $title;
            }
            $chat->user_view = (!empty($user_view)) ? json_encode($user_view) : null;

            $chat->save();

            if (!empty($user_view)) {
                foreach ($user_view as $userId) {
                    $user_data = User::model()->findByPk($userId);
                    if (!empty($user_data)) {
                        self::sendSmsForUser($user_data);
                        self::sendNoticeForUserChat($user_data, $title);
                    }
                }
            }
            $chat->makeSearchStr();
            $transaction->commit();
            $result['success'] = true;
            $result['id']      = $chat->id;
        } catch (Exception $e) {
            $transaction->rollback();
            $result['error']  = $e->getMessage();
            $result['status'] = 500;
        }
        return $result;
    }

    public static function moveChatToColumn(): array
    {
        $result      = ['success' => false, 'error' => '', 'status' => 200];
        $oldColumn = Yii::app()->request->getParam('columnsOld');
        $newColumn = Yii::app()->request->getParam('columnsNew');
        $chatIdFull = Yii::app()->request->getParam('chatId');
        $chatIndex = Yii::app()->request->getParam('chatIndex');
        $chatId = str_replace('chat', '', strtolower($chatIdFull));

        if (WorkPlaceColumns::moveElementToColumn(self::MODULE_NAME, $oldColumn, $newColumn, $chatId, $chatIndex)){
            $result['success'] = true;
        } else {
            $result['status'] = 400;
            $result['error'] = 'При перемещении чата произошла ошибка';
        }
        return $result;
    }

    public static function updateChatStatus(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $id     = key_exists('id', $_POST) ? $_POST['id'] : 0;
        $status = key_exists('status', $_POST) ? $_POST['status'] : 0;
        if ($status == 0 or $status = 1) {
            if (!empty($id) && $id != 0) {
                $data = WorkPlaceChats::model()->findByPk($id);
                if ($data) {
                    $transaction = Yii::app()->db->beginTransaction();
                    try {
                        $data->status = $status;
                        $data->save();
                        $columns = Yii::app()->request->getParam('columns');
                        if ($columns) { //обновляем колонки, если получены
                            $res1 = self::setChatColumns();
                            if ($res1['success']) {
                                $result['success'] = true;
                                $transaction->commit();
                            } else {
                                $result['error']  = $res1['error'];
                                $result['status'] = $res1['status'];
                                $transaction->rollback();
                            }
                        } else {
                            $result['success'] = true;
                            $transaction->commit();
                        }
                    } catch (Exception $e) {
                        $result['error']  = $e->getMessage();
                        $result['status'] = 400;
                        $transaction->rollback();
                    }
                } else {
                    $result['error']  = 'Не найден чат по id ' . $id;
                    $result['status'] = 204;
                }
            } else {
                $result['error']  = 'Не верное значение параметра id ' . $id;
                $result['status'] = 400;
            }
        } else {
            $result['error']  = 'Не верное значение параметра status ' . $status;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function updateChatColor(): array
    {
        $result   = ['success' => false, 'error' => '', 'status' => 200];
        $id       = key_exists('id', $_POST) ? $_POST['id'] : 0;
        $color_id = key_exists('color_id', $_POST) ? $_POST['color_id'] : 0;
        if (!empty($id) && $id != 0) {
            $data = WorkPlaceChats::model()->findByPk($id);
            if ($data) {
                $transaction = Yii::app()->db->beginTransaction();
                try {
                    $data->color_id = $color_id;
                    $data->save();
                    $result['success'] = true;
                    $transaction->commit();
                } catch (Exception $e) {
                    $result['error']  = $e->getMessage();
                    $result['status'] = 400;
                    $transaction->rollback();
                }
            } else {
                $result['error']  = 'Не найден чат по id ' . $id;
                $result['status'] = 204;
            }
        } else {
            $result['error']  = 'Не верное значение параметра id ' . $id;
            $result['status'] = 400;
        }
        return $result;
    }

    public static function getUsersForNewChat(): array
    {
        $result         = [];
        $criteria       = new CDbCriteria;
        $criteria->join = "inner join ok_Employees c on t.extId = c.id ";
        $criteria->addCondition("t.isDell = '0' AND t.id != '" . Yii::app()->user->id . "' AND c.workend = '1970-01-01'");
        $criteria->order = 'c.last_name ASC, c.first_name ASC, c.patronymic_name ASC';
        $users           = User::model()->with('kadry')->findAll($criteria);
        foreach ($users as $user) {
            array_push($result, [
                'value' => $user->id,
                'label' => $user->kadry->getFioShort(),
                'img' => $user->hasAvatarSource() ? $user->getAvatarSource() : $user->getPhotoSource(),
                'isBoss' => OkSprPdr::isBoss($user),
                ]);
        }
        return $result;
    }

    public static function addCommentToChat(): array
    {
        $result     = ['success' => false, 'error' => '', 'status' => 200, 'out' => ['id' => 0, 'date' => '']];
        $chat_id    = Yii::app()->request->getParam('chat_id');
        $message_id = Yii::app()->request->getParam('message_id');
        $message    = Yii::app()->request->getParam('message');
        $quote      = Yii::app()->request->getParam('quote');
        $system     = Yii::app()->request->getParam('system');
        $user       = User::model()->findByPk(Yii::app()->user->id);
        $w_chat     = WorkPlaceChats::model()->findByPk($chat_id);
        $file_id    = Yii::app()->request->getParam('file_id');

        if ($w_chat) {
            if (!empty($message) || !empty($file_id)) {
                if ($message_id) {
                    $data = WorkPlaceChatComments::model()->findByPk($message_id);
                    //$data = WorkPlaceChatComments::model()->findByPk("chat_id = '$chat_id'");
                    if ($data->user_id == $user->id) { // проверка, редактировать сообщение может только создатель
                        $data->message = $message;
                        $data->save();
                        $result['out']['id']   = $data->id;
                        $result['out']['date'] = $data->updated_at;
                    } else {
                        $result['error']  = 'Попытка редактирования чужой записи!';
                        $result['status'] = 400;
                    }
                } else {
                    $data          = new WorkPlaceChatComments();
                    $data->chat_id = $chat_id;
                    $data->message = $message;
                    $data->quote   = $quote;
                    $data->file_id = $file_id;
                    $data->system  = ($system && $system == 1) ? 1 : 0;
                    $data->save();

                    $result['out']['id']   = $data->id;
                    $result['out']['date'] = $data->created_at;

                    if (!empty($w_chat->user_view)) {
                        foreach (json_decode($w_chat->user_view) as $user_id) {
                            if ($user_id == $user->id) continue;
                            WorkPlaceNotification::addRecord($user_id, $chat_id, 'WorkPlaceChats');
                        }
                    }

                    if ($user->id != $w_chat->user_id) {
                        WorkPlaceNotification::addRecord($w_chat->user_id, $chat_id, 'WorkPlaceChats');
                    }
                }
                $result['success'] = true;
            }
        } else {
            $result['error']  = 'Не найден чат по id ' . $chat_id;
            $result['status'] = 204;
        }
        return $result;
    }

    public static function addUserToChat(): array
    {
        $result  = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $user_id = Yii::app()->request->getParam('user_id');
        $chat_id = Yii::app()->request->getParam('chat_id');
        $chat    = WorkPlaceChats::model()->findByPk($chat_id);
        if ($chat) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $result = Tools::PushUserToJsonField($chat, 'user_view', 'user_id', $user_id);
                if ($result['success']) {
                    $user          = User::model()->findByPk($user_id);
                    $result['out'] = User::getUserInfo($user);
                    // создать сообщение о добавлении пользователя
                    $messege          = "Участник " . $user->getKadryFioShort() . " был добавлен в чат.";
                    $_POST['chat_id'] = $chat_id;
                    $_POST['message'] = $messege;
                    $_POST['system']  = 1;
                    $r                = self::addCommentToChat();
                    if ($r['success']) {
                        $result['out']['message']['id']   = $r['out']['id']; // id добавленной записи
                        $result['out']['message']['text'] = $messege;
                        $chat->makeSearchStr();
                        $transaction->commit();
                    } else {
                        $result['error']   = $r['error'];
                        $result['status']  = $r['status'];
                        $result['success'] = false;
                        $transaction->rollback();
                    }
                } else {
                    $transaction->rollback();
                }
            } catch (Exception $e) {
                $result['error']  = $e->getMessage();
                $result['status'] = 400;
                $transaction->rollback();
            }
        } else {
            $result['error']  = "Не найден чат по id [$chat_id] ";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function deleteUserFromChat(): array
    {
        $result  = ['success' => false, 'error' => '', 'status' => 200];
        $user_id = Yii::app()->request->getParam('user_id');
        $chat_id = Yii::app()->request->getParam('chat_id');
        $chat    = WorkPlaceChats::model()->findByPk($chat_id);
        if ($chat) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $result = Tools::DeleteUserFromJsonField($chat, 'user_view', $user_id);
                if ($result['success']) {
                    // создать сообщение о добавлении пользователя
                    $user             = User::model()->findByPk($user_id);
                    $messege          = "Участник " . $user->getKadryFioShort() . " был удален из чата";
                    $_POST['chat_id'] = $chat_id;
                    $_POST['message'] = $messege;
                    $_POST['system']  = 1;
                    $r                = self::addCommentToChat();
                    if ($r['success']) {
                        $result['out']['message']['id']   = $r['out']['id']; // id добавленной записи
                        $result['out']['message']['text'] = $messege;
                        $chat->makeSearchStr();
                        $transaction->commit();
                    } else {
                        $result['error']   = $r['error'];
                        $result['status']  = $r['status'];
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
            $result['error']  = "Не найден чат по id [$chat_id] ";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function setChatColumns(): array
    {
        $result  = ['success' => false, 'error' => '', 'status' => 200, 'id' => 0];
        $columns = Yii::app()->request->getParam('columns');
        $res     = WorkPlaceColumns::updateColumns(self::MODULE_NAME, $columns);
        //$res = UserSettings::setWorkPlaceColumns($columns);
        if ($res['success']) {
            $result['success'] = true;
        } else {
            $result['error']  = $res['error'];
            $result['status'] = 500;
        }
        return $result;
    }

    public static function setChatName(): array
    {
        $result   = ['success' => false, 'error' => '', 'status' => 200];
        $chat_id  = Yii::app()->request->getParam('id');
        $chatName = Yii::app()->request->getParam('title');
        $chat     = WorkPlaceChats::model()->findByPk($chat_id);
        if ($chat) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                $chat->title = $chatName;
                $chat->save();
                $chat->makeSearchStr();
                $result['success'] = true;
                $transaction->commit();
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $transaction->rollback();
            }
        } else {
            $result['error']  = "Не найден чат по id [$chat_id] ";
            $result['status'] = 400;
        }
        return $result;
    }

    public static function uploadChatFile(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $res    = WorkPlaceMessageFiles::uploadMessageFile(2);
        if ($res['success']) {
            $transaction = Yii::app()->db->beginTransaction();
            try {
                // добавляем комментарий
                $chatId           = Yii::app()->request->getParam('id');
                $_POST['chat_id'] = $chatId;
                $_POST['file_id'] = $res['out']['id'];
                $res1             = WorkPlaceChats::addCommentToChat();
                if ($res1['success']) {
                    $transaction->commit();
                    $result['id']  = $res1['out']['id'];
                    $result['out']['url'] = $res['out']['url'];
                    $result['success']    = true;
//                    $transaction->commit();
                } else {
                    $result['error'] = 'Ошибка создания собщения : ' . $res1['error'];
                    $transaction->rollback();
                    $result['status'] = 500;
                }
               // $result = $res;
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $transaction->rollback();
                $result['status'] = 500;
            }
        } else {
            $result = $res;
        }
        return $result;
    }

    public static function sendMessageToChatUsers(): array
    {
        $result  = ['success' => false, 'error' => '', 'status' => 200];
        $chat_id = Yii::app()->request->getParam('chat_id');
        $text    = Yii::app()->request->getParam('text');
        $w_chat  = WorkPlaceChats::model()->findByPk($chat_id);
        if ($w_chat) {
            $userList = [];
            $user     = User::model()->findByPk(Yii::app()->user->id);
            if ($w_chat->user_id != $user->id) {
                $userList[] = $w_chat->user_id;
            }
            $users = User::getUsersListFromJson($w_chat->user_view);
            if ($users && is_array($users) && count($users) > 0) {
                foreach ($users as $u) {
                    if ($u['id'] != $user->id) {
                        $userList[] = $u['id'];
                    }
                }
            }
            if (count($userList) > 0) {
                foreach ($userList as $uId) {
                    Notice::sendToUserId($uId, $text, 'wp');
                }
                $result['success'] = true;
            } else {
                $result['error']  = 'Не кому отправлять!!!';
                $result['status'] = 401;
            }

        } else {
            $result['error']  = 'Не найден чат по id ' . $chat_id;
            $result['status'] = 204;
        }
        return $result;
    }

    public static function delColumnInChat(): array
    {
        $result = ['success' => false, 'error' => '', 'status' => 200, 'out' => []];
        $columnId = Yii::app()->request->getParam('columnId', null);
        $res = WorkPlaceColumns::deleteColumn(self::MODULE_NAME, $columnId);
        if ($res === true) {
            $result['success'] = true;
        } else {
            $result['error'] = 'Ошибка при удалении колонки: ' . $res;
        }
        return $result;
    }

    public static function addNewColumn()
    {
        $result = ['success' => false, 'error' => '', 'status' => 200];
        $text = Yii::app()->request->getParam('title', 'TEST');
        if (WorkPlaceColumns::addNewColumn(self::MODULE_NAME, $text)) {

            $result['out']['title'] = $text;
            $result['success'] = true;
        } else {
            $result['error'] = 'Ошибка при создании каталога';
        }
        return $result;
    }


}