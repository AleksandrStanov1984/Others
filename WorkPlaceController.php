<?php

/**
 * Class TimesheetController
 *
 * @inheritdoc
 */
class WorkPlaceController extends ApiController
{

    public function init() {
        /*$token = Yii::app()->request->getParam('token', '0');
        $session_id = session_id();
        if ($token != $session_id) {
            $this->errorResponse('token error', 400);
            $this->sendResponse();
            Yii::app()->end();
        }*/
        parent::init();
    }

    /**
     * @var TimesheetUser
     */
    protected $user = null;

    /**
     * @return void
     */
    //protected function initFrontendIterator()
   // {
        //$this->setFrontendIterator(new FrontendIterator('api.timesheet'));
    //}

    // user

    public function getUserList(){
        $this->successResponse(User::getUserListForAPI());
    }

    public function getFirstData(){
        try {
            $data = WorkPlaceFiles::getMyCatalogFiles();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    // chat

    /**
     * API action
     * список чатов, к которым пользователь имеет отношение
     */
    public function getChatList(){
        try {
            $list = WorkPlaceChats::getChatList();
            if (count($list) > 0) {
                $this->successResponse($list);
            } else {
                $this->errorResponse($list, 204);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * API action
     * Данные конкретного чата
     */
    public function getDataForChat()
    {
        try {
            $data = WorkPlaceChats::getDataForChat();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateChatStatus() {
        try {
            $data = WorkPlaceChats::updateChatStatus();
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateChatColor() {
        try {
            $data = WorkPlaceChats::updateChatColor();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addNewChat() //done
    {
        try {
            $data = WorkPlaceChats::addNewChat();
            if ($data['success']) {
                $this->successResponse($data['id']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getUsersForNewChat()
    {
        try {
            $data = WorkPlaceChats::getUsersForNewChat();
            if (count($data) > 0) {
                $this->successResponse($data);
            } else {
                $this->errorResponse('Список пользователей пуст', 204);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addCommentToChat()
    {
        try {
            $data = WorkPlaceChats::addCommentToChat();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addUserToChat(){
        try {
            $data = WorkPlaceChats::addUserToChat();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteUserFromChat(){
        try {
            $data = WorkPlaceChats::deleteUserFromChat();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function sendMessageToChatUsers(){
        try {
            $data = WorkPlaceChats::sendMessageToChatUsers();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setChatColumns(){
        try {
            $data = WorkPlaceChats::setChatColumns();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadChatFile(){
        try {
            $data = WorkPlaceChats::uploadChatFile();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setChatName(){
        try {
            $data = WorkPlaceChats::setChatName();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function moveChatToColumn(){
        try {
            //$data = WorkPlaceChats::setChatName();
            $data = WorkPlaceChats::moveChatToColumn();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    //---- task -----

    public function updateBDToNewVersion() {
        $log = [];
        $log[] = "Начато выполнение";
        $tasks = WorkPlaceTasks::model()->findAll('updatedToNewVersion = 0');
        $log[] = "В обработке " . count($tasks) . " записей";
        if ($tasks) {
                foreach ($tasks as $task) {
                    $logStr = "Задача $task->id ";
                    $transaction = Yii::app()->db->beginTransaction();
                    try {
                        // обновление whom_id (меняем id из emploers на id из User)
                        $emploer = OkLsEmployees::model()->with('user')->findByPk($task->whom_id);
                        if ($emploer && $emploer->user) {
                            $task->whom_id = $emploer->user->id;
                        }

                        // обновление view_user (меняем id из emploers на id из User)
                        $observers = User::getUsersListFromJson($task->view_user);
                        $users     = [];
                        foreach ($observers as $observer) {
                            $emploer = OkLsEmployees::model()->with('user')->findByPk($observer);
                            if ($emploer && $emploer->user) {
                                array_push($users, $emploer->user->id);
                            }
                        }
                        $task->view_user           = json_encode($users);
                        $task->updatedToNewVersion = 1;
                        $task->save();
                        $logStr .= " обновление выполнено успешно ";
                        $transaction->commit();
                    } catch (Exception $e) {
                        $logStr .= " ошибка обновления " . $e->getMessage();
                        $transaction->rollback();
                    }
                    $log[] = $logStr;
                }
        }
        $log[] = "Завершено";
        $this->successResponse($log);
    }

    public function getTaskList(){
        try {
            $data = WorkPlaceTasks::getTaskList(false);
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);//
        }
    }

    public function getArchiveTaskList(){
        try {
            $data = WorkPlaceTasks::getTaskList(true);
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getDataForTask()
    {
        try {
            $data = WorkPlaceTasks::getDataForTask();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addCommentToTask()
    {
        try {
            $data = WorkPlaceTasks::addCommentToTask();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setColorBoxForTask()
    {
        try {
            $data = WorkPlaceTasks::setColorBoxForTask();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addNewTask(){ //done
        try {
            $data = WorkPlaceTasks::addNewTask();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addTaskControlPoint(){
        try {
            $data = WorkPlaceTaskControlPoints::addTaskControlPoint();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addTaskControlPointReport(){
        try {
            $data = WorkPlaceTaskControlPoints::addTaskControlPointReport();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addUserToTask(){
        try {
            $data = WorkPlaceTasks::addUserToTask();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteUserFromTask(){
        try {
            $data = WorkPlaceTasks::deleteUserFromTask();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadTaskFile(){
        try {
            $data = WorkPlaceTasks::uploadTaskFile();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadTaskCommentFile(){
        try {
            $data = WorkPlaceTasks::uploadTaskCommentFile();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateTaskStatus() {
        try {
            $data = WorkPlaceTasks::updateTaskStatus();
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setNewStatusForTask() {
        try {
            $data = WorkPlaceTasks::setNewStatusForTask();
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setApproveForTask() {
        try {
            $data = WorkPlaceTasks::setNoApproveForTask(true);
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setNoApproveForTask() {
        try {
            $data = WorkPlaceTasks::setNoApproveForTask(false);
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  Действие - делает не активной задачу для исполнителя после его отказа руководителем
     */
    public function setCloseTaskFromPeopleColumn(){
        try {
            $data = WorkPlaceTasks::setCloseTaskFromPeopleColumn();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setUserEndWorkForTask() {
        try {
            $data = WorkPlaceTasks::setUserEndWorkForTask();
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setChiefMoveTaskToArchive() { //done
        try {
            $data = WorkPlaceTasks::setChiefMoveTaskToArchive();
            if ($data['success']) {
                $this->successResponse('ok');
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    //----- conflict

    public function getConflictList() {
        try {
            $data = WorkPlaceConflict::getConflictList();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getArchiveConflictList() {
        try {
            $data = WorkPlaceConflict::getConflictList(1);
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addConflict() {
        try {
            $data = WorkPlaceConflict::addNewConflict();
            if ($data['success']) {
                $dataList = WorkPlaceConflict::getConflictList();
                if ($dataList['success']) {
                    $this->successResponse($dataList['data']);
                } else {
                    $this->errorResponse($dataList['error'], $dataList['status']);
                }
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getUserDirectorList() {
        try {
            $data = WorkPlaceConflict::getUserDirectorList();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function sendMessageToConflictUsers(){
        try {
            $data = WorkPlaceConflict::sendMessageToConflictUsers();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function moveConflictToColumn(){
        try {
            //$data = WorkPlaceChats::setChatName();
            $data = WorkPlaceConflict::moveConflictToColumn();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addNewObserversToConflict(){
        try {
            $data = WorkPlaceConflict::addNewObserversToConflict();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function delObserversInConflict(){
        try {
            $data = WorkPlaceConflict::delObserversInConflict();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateConflictColor() {
        try {
            $data = WorkPlaceConflict::updateConflictColor();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addNewColumnToConflict() {
        try {
            $data = WorkPlaceConflict::addNewColumnToConflict();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function updateColumnInConflict() {
        try {
            $data = WorkPlaceConflict::updateColumnToConflict();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function delColumnInConflict() {
        try {
            $data = WorkPlaceConflict::dellColumnInConflict();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadConflictFile(){
        try {
            $data = WorkPlaceConflict::uploadConflictFile();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addTextToConflict(){
        try {
            $data = WorkPlaceConflict::addTextToConflict();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Изминение даты в Конфликтах
     */
    public function getDataForConflict() //done
    {
        try {
            $data = WorkPlaceConflict::getDataForConflict();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function enableConflictedView() {
        try {
            $data = WorkPlaceConflict::enableConflictedView();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addCommentToConflict()
    {
        try {
            $data = WorkPlaceConflict::addCommentToConflict();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    //----- files

    public function getFileList(){
        try {
            $data = WorkPlaceFiles::getFileList();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getCatalogList(){
        try {
            $data = WorkPlaceFiles::getCatalogList();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getMyCatalogFiles(){
        try {
            $data = WorkPlaceFiles::getMyCatalogFilesOutTree();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Добавление моего файла в каталог
     */
    public function addMyFileCatalog(){
        try {
            $data = WorkPlaceMyFilesCategories::addMyFileCatalog();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Загрузка моего файла
     */
    public function uploadMyFile(){
        $moduleId = Yii::app()->request->getParam('module_id');
        try {
            switch ($moduleId) {
                case 0: // модуль не определен (переброска файлов)
                    $data = WorkPlaceMyFiles::uploadMyFile();
                    break;
                case 1: // задачник
                    $data = WorkPlaceTasks::uploadTaskFile();
                    break;
                case 2: // чат
                    $data = WorkPlaceChats::uploadChatFile();
                    break;
            }

            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Загрузка файла в чат конфликта
     */
    public function uploadFileToChatConflict(){// done
        try {
            $data = WorkPlaceConflict::uploadFileToChatConflict();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function sendFile(){
        try {
            $data = WorkPlaceFiles::sendFile();
            if ($data['success']) {
                $this->successResponse($data['data']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Добавление пользователя в каталог
     */
    public function addUserToCatalog(){
        try {
            $data = WorkPlaceMyFilesCategories::addUserToCatalog();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  Добавление файла в каталог
     */
    public function addFilesToCatalog(){
        try {
            $data = WorkPlaceMyFilesCategories::addFilesToCatalog();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  Удаления пользователя из каталога
     */
    public function deleteUserFromCatalog(){
        try {
            $data = WorkPlaceMyFilesCategories::deleteUserFromCatalog();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Переименование каталога
     */
    public function renameCatalog(){
        try {
            $data = WorkPlaceMyFilesCategories::renameCatalog();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *    Загрузка файла
     */
    public function downloadFile(){
        try {
            $data = WorkPlaceMyFiles::downloadFile();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteCatalogWithFile(){ //done
        try {
            $data = WorkPlaceMyFilesCategories::deleteCatalogFile();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * переименование файла
     */
    public function renameFolderWithFile(){ //done
        try {
            $data = WorkPlaceMyFilesCategories::renameFolderWithFile();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * переименование файла
     */
    public function renameFile(){ //done
        try {
            $data = WorkPlaceMyFiles::renamesFile();

            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * удаление папки с файлом
     */
    public function deleteFolderWithFile(){ // done
        try {
            $data = WorkPlaceMyFilesCategories::deleteFolderWithFile();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * удаление файла
     */
    public function deleteFile(){ //done
        try {
            $data = WorkPlaceMyFiles::deleteFile();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }


    //----- analitiks

    /**
     * структура предприятия с департаментами, подразделениями
     */
    public function getCompanyStructure(){ // done
        //$user = User::getUserUser();
        //$user = User::getUserUser(399); // Куроченко
        $user = User::getUserUser(84); // Игорь Олегович
       // $user = User::getUserUser(136); // Гливинский
      //  $user = User::model()->findByPk(Yii::app()->user->id);
        //$user = OkLsEmployees::model()->findByPk(43)->user; // гендир для примера

        try {
            Yii::import('application.components.workPlace.WorkPlaceAnalitics');
            $data = WorkPlaceAnaliticsData::getCompanyStructure($user);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function uploadProfileFile(){
        $employee_id = Yii::app()->request->getParam('employeeId', 0);
        $employee = OkLsEmployees::model()->findByPk($employee_id);
        try {
            $data = OkLoadeFileEmployee::uploadMyFile($employee);
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  возвращает список справочников
     */
    public function getDirectoryList(){
        $result = new stdClass();
        //$result->maritalStatusList = [];
        //$result->burnoutStageList = [];
        //$result->characteristicList = [];
        $result->ww = [];
        $result->maritalStatusList = OkPsyhMaritalStatusList::getList();
        $result->burnoutStageList = OkPsyhBurnoutStageList::getList();
        $result->characteristicList = OkPsyhCharacteristicList::getList();
        $this->successResponse($result);
    }

    /**
     * Сохранение характеристик психологом
     */
    public function setEmployeeCommonData(){
        try {
            $data = OkPsyhEmploers::setEmployeeCommonData();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *Установка ефективности психологом
     */
    public function setPsychologistEfficiency(){
        try {
            $data = OkPsyhEfficiency::setPsychologistEfficiency();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *Установка ефективности руководителем
     */
    public function setBossEfficiency(){
        try {
            $data = OkPsyhEfficiency::setBossEfficiency();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  данные по выбранному департаменту, структуре и месяцу для отображения интерфеса Аналитика
     */
    public function getAnalyticalData(){
        //$user = User::getUserUser();
        $user = User::getUserUser(399); // Куроченко
        //$user = User::getUserUser(84); // Игорь Олегович
        //$user = OkLsEmployees::model()->findByPk(43)->user; // гендир для примера
        try {
            $data = WorkPlaceAnaliticsData::getAnalyticalData($user);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  Взять профиль работника
     */
    public function getEmployeeProfile(){ // done
        $user = User::getUserUser();
        $emploerId = Yii::app()->request->getParam('employeeId', 97);
        $emploer = OkLsEmployees::model()->findByPk($emploerId);
        try {
            $data = WorkPlaceAnaliticsData::getEmployeeProfile($emploer);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function sendEmployeeNote(){
        $emploerId = Yii::app()->request->getParam('employeeId', 0);
        $text = Yii::app()->request->getParam('text', null);
        $type = Yii::app()->request->getParam('type', null);
        $emploer = OkLsEmployees::model()->findByPk($emploerId);
        try {
            $data = OkPsyhEmploers::sendEmployeeNote($emploer, $text, $type);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function sendDepartmentNote(){
        $departmentId = Yii::app()->request->getParam('departmentId', 0);
        $text = Yii::app()->request->getParam('text', null);
        try {
            $data = OkPsyhPdrNotes::sendDepartmentNote($departmentId, $text);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function sendMessageToEmployeeProfile (){
        $emploerId = Yii::app()->request->getParam('employeeId', 85);
        $message = Yii::app()->request->getParam('message', 'упс');
        $quoteId = Yii::app()->request->getParam('quoteId', 0);
        $messageId = Yii::app()->request->getParam('messageId', 0);
        $emploer = OkLsEmployees::model()->findByPk($emploerId);
        try {
            $system = 0;
            $data = OkPsyhComments::sendMessageToEmployeeProfile($emploer, $message, $quoteId, $messageId, $system);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }


    public function uploadProfileChatFile (){ //done
        $emploerId = Yii::app()->request->getParam('employeeId', 0);
        $emploer = OkLsEmployees::model()->findByPk($emploerId);
        try {
            $data = OkPsyhComments::uploadProfileChatFile($emploer);
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *  Взять все зписи по опредиленному условию для Событи и заметки
     */
    public function getNotesData(){ //done
        try {
            $data = WorkPlaceNotes::getNotesData();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addEmployeeAnalyticalFile(){ //done
        try {
            $data = WorkPlaceAnaliticFiles::addEmployeeAnalyticalFile();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function callUsersFromTask(){ //done
        try {
            $data = WorkPlaceTasks::callUsersFromTask();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Установка новой даты дэдлайна
     */
    public function setNewDateForTask(){ //done
        try {
            $data = WorkPlaceTasks::setNewDateForTask();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Удаление колонки в Приватный разговор
     */
    public function delColumnInChat() {//done
        try {
            $data = WorkPlaceChats::delColumnInChat();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function moveTaskInColumn(){ //done
        try {
            $data = WorkPlaceTasks::moveTaskInColumn();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setLinkUploadForTask(){ // done
        try {
            $data = WorkPlaceMyFiles::setLinkUploadForTask();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *     Добавление новой колонки в Приватный разговор
     */
    public function addNewColumn() {// done
        try {
            $data = WorkPlaceChats::addNewColumn();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *    Установка задачи в стадию "Ознакомления"
     */
    public function setTaskPreview () {// done
        try {
            $data = WorkPlaceTasks::setTaskPreview();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   отправка приглашения Психологу войти в чат с Начальником в профиле работника
     */
    public function sendInvitationToEmployeeProfile() { // done
        try {
            $data = OkPsyhEmploers::sendInvitationToEmployeeProfile();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Файлообменник - Доступы к директории
     */
    public function changeAccessToCatalog() { // done нужно переделать
        try {
            $data = WorkPlaceMyFilesCategoriesAccess::changeAccessToCatalog();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *    Для пуш уведомлений
     */
    public function GetPushModuleData() { // done
        try {
            $data = Notice::GetPushModuleData();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     *   Получаем все события и заметки за сегодняшний день с 00:00 по 23:59
     */
    public function getNotesAllToday() // done ????????????????????????? delete
    {
        try {
            $data = WorkPlaceNotes::getNotesAllToday();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function addNewNotes() // done
    {
        try {
            $data = WorkPlaceNotes::addNewNotes();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function deleteNotesFromId() // done
    {
        try {
            $data = WorkPlaceNotes::deleteNotesFromId();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setColorBoxForNotes() // done
    {
        try {
            $data = WorkPlaceNotes::setColorBoxForNotes();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setLinkUploadForConflict () // done
    {
        try {
            $data = WorkPlaceConflict::setLinkUploadForConflict ();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setLinkForTask () // Done
    {
        try {
            $data = WorkPlaceTasks::setLinkForTask ();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setLinkUploadForFileTransfer () // done
    {
        try {
            $data = WorkPlaceFiles::setLinkUploadForFileTransfer ();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function setLinkUploadForFileSend () // done
    {
        try {
            $data = WorkPlaceFiles::setLinkUploadForFileSend ();
            if ($data['success']) {
                $this->successResponse(true);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function ApiData() // done
    {
        try {
            $data = WorkPlaceCalendarView::ApiData ();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function ApiMonth() // done
    {
        try {
            $data = WorkPlaceCalendarView::ApiMonth ();
            if ($data['success']) {
                $this->successResponse($data);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getNotesList(){ //--------------------------
        try {
            $data = WorkPlaceNotes::getNotesList();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }

    public function getNotesDay(){ //done
        try {
            $data = WorkPlaceNotes::getNotesDay();
            if ($data['success']) {
                $this->successResponse($data['out']);
            } else {
                $this->errorResponse($data['error'], $data['status']);
            }
        } catch (Exception $e) {
            $this->errorResponse($e->getMessage(), 500);
        }
    }



}