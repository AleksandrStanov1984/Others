<?

class CustomerController extends BackendController
{

    public function init()
    {
        parent::init();
        $this->checkAccess('customer');
        /* if(!Tools::checkAccess('CUSTOMER', 'customer', 'view')){
             throw new CHttpException(403);
         }*/
        //$this->menu['item']['active'] = true;
    }

    public function actionActions($id)
    {
        if (Yii::app()->user->role != 'admin') {
            throw new CHttpException(403);
        }
        $action = CustomerActions::model()->findAll('customer_id = ' . $id . ' order by id desc');
        $this->render('actions', array('actions' => $action));
    }

    public function actionIndex()
    {
        $this->title = 'Список заказчиков';

        $customers = Customer::model()->findAll();
        $data = array('customers' => $customers,
        );
        $this->render('index', $data);
    }

    public function actionGetCustomer($type)
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $q = Yii::app()->request->getParam("q");
        if ($q) {
            $criteria = new CDbCriteria();
            if ($type = 'zayavka') {
                $criteria->addSearchCondition('name', $q);
                $criteria->addSearchCondition('egrpou', $q, true, 'OR');
            } else {
                $criteria->addSearchCondition($type, $q);
            }

            // добавляем критерию по региону
            if (Yii::app()->user->regionId) {
                //Ограничение только своим регионом
                $userRegions = Yii::app()->user->getRegionIds();
                $criteria->addInCondition('regionId', $userRegions);
            }


            $criteria->order = 'egrpou DESC';
            //$criteria->group = $type;
            $customers = Customer::model()->findAll($criteria);
            if ($customers) {
                foreach ($customers as $o) {
                    if ($o->in_archive != 1) {
                        $criteria = new CDbCriteria();
                        $criteria->addCondition('customerId = ' . $o->id);
                        $criteria->addCondition('isDell = 0');
                        $criteria->order = "name";
                        $customer_contacts = CustomerContacts::model()->findAll($criteria);
                        $customer_contacts_options = '';
                        foreach ($customer_contacts as $cc) {
                            if ($cc->name != '') {
                                $optionsPhone = $cc->buildContactOptionsPhone();
                                $optionsMail = $cc->buildContactOptionsMail();
                                $customer_contacts_options .= "<option value = '$cc->id' class='d" . $cc->id . "'  data-phones='$optionsPhone' data-emails='$optionsMail' >" . htmlspecialchars($cc->name) . "</option>";
                            }
                        }
                        $contact = false;
                        foreach ($o->contacts as $c) {
                            $contact = $c;
                        }
                        if ($contact) {
                            $fio = $contact->name;
                        } else {
                            $fio = '';
                        }

                        echo $o->id . "|" . $o->name . "|" .
                            ($o->region ? $o->region : '') . "|" .
                            ($o->adres ? $o->adres : '') . "|" .
                            ($o->distance ? $o->distance : '') . "|" .
                            ($o->nameRus ? $o->nameRus : '') . "|" .
                            ($o->nameUkr ? $o->nameUkr : '') . "|" .
                            ($o->nameEn ? $o->nameEn : '') . "|" .
                            ($o->lawAdres ? $o->lawAdres : '') . "|" .
                            ($o->egrpou ? $o->egrpou : '') . "|" .
                            ($o->inn ? $o->inn : '') . "|" .
                            $customer_contacts_options . '|' .
                            $o->regionId .

//                        $o->customerId . "|" .
//                        $o->fio . "|" .
//                        ($o->position ? $o->position : '') . "|" .
//                        ($o->birthDate ? $o->birthDate : '') . "|" .
//                        ($o->phone ? $o->phone : '') . "|" .
//                        ($o->email ? $o->email : '') . "|" .
//                        ($o->notes ? $o->notes : '') . "|" .
                            "\n";
                    }
                }
            }
        }
    }

    public function actionGetFio()
    {
        $q = Yii::app()->request->getParam("q");

        $criteria = new CDbCriteria();
        $criteria->addSearchCondition('t.last_name', $q);
        $users = OkLsEmployees::model()->findAll($criteria);
        foreach ($users as $user) {
            $obAdres = OkObAdres::model()->find('idSot=' . $user->id);
            echo $user->id . "|" . $user->getFioShort() . "|" . $obAdres->phoneMobileKorp . "|" . $obAdres->email . "\n";
        }
    }

    public function actionEdit($id)
    {
        /*if(!Tools::checkAccess('CUSTOMER', 'customer', 'edit')){
            throw new CHttpException(403);
        }*/
        $customer = Customer::model()->findByPk($id);
        if (!$customer) {
            throw new CHttpException(404);
        }
        if (!$customer->accessToEdit()) {
            throw new CHttpException(403);
        }

        $this->title = 'Редактировать заказчика';
        $data = array(
            'customer' => $customer,
            'action' => "edit"
        );

        $this->render('personalCard', $data);
    }

    public function actionShow($id)
    {
        /*if(!Tools::checkAccess('CUSTOMER', 'customer', 'view')){
            throw new CHttpException(403);
        }*/
        $customer = Customer::model()->findByPk($id);
        if (!$customer) {
            throw new CHttpException(404);
        }

        if (!$customer->accessToEdit('view')) {
            throw new CHttpException(403);
        }

        $this->title = 'Личная карточка заказчика';
        $data = array(
            'customer' => $customer,
            'action' => 'show'
        );

        $this->render('personalCard', $data);
    }

    public function actionCreate()
    {
        $this->title = 'Создать заказчика';
        $data = array(
            'customer' => null,
            'action' => 'create'
        );
        $this->render('personalCard', $data);
    }

    public function actionContacts($id, $action)
    {
        if ($action == 'create') {
            $action = 'create';
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_contact', 'edit')) {
            $action = 'edit';
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_contact', 'view')) {
            $action = 'view';
        }
        $this->renderPartial('contact', array('id' => $id, 'action' => $action));
    }

    public function actionHistory($id, $action)
    {
        if (!Tools::checkAccess('CUSTOMER', 'customer_history', 'edit')) {
            throw new CHttpException(403);
        }
        $this->title = 'История обращений заказчика';
        $customer = Customer::model()->findByPk($id);
        if (!$customer) {
            throw new CHttpException(404);
        }

        if (Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'view') or Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'edit')) {
            $page = 1;
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_history_dogovor', 'view') or Tools::checkAccess('CUSTOMER', 'customer_history_dogovor', 'edit')) {
            $page = 2;
        } else {
//            throw new CHttpException(403);
            $page = 0;
        }

        if (Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'edit') and Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'view')) {
            $action = 'edit';
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'edit')) {
            $action = 'edit';
        } elseif (Tools::checkAccess('CUSTOMER', 'customer_history_proposal', 'view')) {
            $action = 'show';
        }

        if (isset($_GET['page'])) {
            $page = $_GET['page'];
        }
        $data = array(
            'customer' => $customer,
            'action' => $action,
            'page' => $page
        );

        $this->render('history', $data);
    }

    public function actionRequisites($id, $action)
    {
        $this->renderPartial('requisites', array('id' => $id, 'action' => $action));
    }

    /**
     * Сохранение сокращенной информации о заказчике (из КР и Заявок на договора)
     */
    public function actionSaveMini()
    {
        $response = array(
            'success' => false,
            'error' => 'Не предвиденная ошибка, обратитесь к администратору'
        );
        $log = new CustomerLog();
        $id = Yii::app()->request->getParam('id') !== null ? (int)Yii::app()->request->getParam('id') : 0;
        $errors = array();
        $info = array();
        $customer = false;

        if ($id != 0) {
            $customer = Customer::model()->findByPk($id);
            if (!$customer) {
                $response['error'] = 'Не найден заказчик по id ' . $id;
                echo json_encode($response);
                Yii::app()->end();
            }
            if (!$customer) {
                $info[] = "Не найден заказчик по ID $id";
            }
        }/* else {
            if (Yii::app()->request->getParam('name') !== null) { //пытаемся найти по названию
                $name = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('name')));
                $customer = Customer::findCustomerWithParams(array('name'=>$name));
                if ($customer) {
                    $info[] = "Найден заказчик по краткому наименованию $name";
                }
            }
            if (!$customer) {
                $customer = new Customer();
                $info[]   = "Создан новый заказчик";
            }
        }*/

        if ($customer) {

            if (Yii::app()->request->getParam('egrpou') !== null) {
                $egrpou = trim(Yii::app()->request->getParam('egrpou'));
                $duble = Customer::model()->find("egrpou = $egrpou AND id <> " . $customer->id);
                if ($duble) {
                    $errors[] = "Заказчик с кодом ЕГРПОУ [$egrpou] уже существует!";
                } else {
                    if ($customer->egrpou != $egrpou) {
                        $info[] = "Изменился код ЕГРПОУ заказчика [$customer->egrpou] - [$egrpou]";
                        $customer->egrpou = $egrpou;
                    }
                }
            }

            //----------Русское краткое наименование (обязательное, основное)--------
            if (Yii::app()->request->getParam('name') !== null) {
                $name = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('name')));
                $duble = Customer::model()->find("name = '$name' AND id <> " . $customer->id);
                if ($duble) {
                    $error = true;
                    $errors[] = "Заказчик с кратким наименованием [$name] уже существует!";
                } else {
                    if ($customer->name != $name) {
                        $info[] = "Изменилось краткое наименование заказчика [$customer->name] - [$name]";
                        $customer->name = $name;
                    }
                }
            }

            if (Yii::app()->request->getParam('nameEn') !== null) {
                $nameEn = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameEn')));
                if ($customer->nameEn != $nameEn) {
                    $info[] = "Изменился наименование заказчика (английский) [$customer->nameEn] - [$nameEn]";
                    $customer->nameEn = $nameEn;
                }
            }
            if (Yii::app()->request->getParam('nameRus') !== null) {
                $nameRus = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameRus')));
                if ($customer->nameRus != $nameRus) {
                    $info[] = "Изменился наименование заказчика (русский) [$customer->nameRus] - [$nameRus]";
                    $customer->nameRus = $nameRus;
                }
            }
            if (Yii::app()->request->getParam('nameUkr') !== null) {
                $nameUkr = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameUkr')));
                if ($customer->nameUkr != $nameUkr) {
                    $info[] = "Изменился наименование заказчика (Украинский) [$customer->nameUkr] - [$nameUkr]";
                    $customer->nameUkr = $nameUkr;
                }
            }

            //----------ФИО (обязательное, основное)--------
            if (Yii::app()->request->getParam('fio') !== null) {
                $fio = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('fio')));
                if ($customer->fio != $fio) {
                    $info[] = "Контактное лицо [$customer->fio] - [$fio]";
                    $customer->fio = $fio;
                }
            }
            if (Yii::app()->request->getParam('lawAdres') !== null) {
                $lawAdres = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('lawAdres')));
                if ($customer->lawAdres != $lawAdres) {
                    $info[] = "Изменился юридический адрес заказчика [$customer->lawAdres] - [$lawAdres]";
                    $customer->lawAdres = $lawAdres;
                }
            }
            if (Yii::app()->request->getParam('inn') !== null) {
                $inn = trim(Yii::app()->request->getParam('inn'));
                if ($customer->inn != $inn) {
                    $info[] = "Изменился код ИНН заказчика [$customer->inn] - [$inn]";
                    $customer->inn = $inn;
                }
            }
        } else {
            $errors[] = "Не найден заказчик по ID $id";
        }

        if (count($errors) == 0) {
            // проверяем заполненость обязательных полей
            if (!$customer->name) $errors[] = "Не заполнено наименование заказчика (краткое)";
            //if (!$customer->nameUkr) $errors[] = "Не заполнено наименование заказчика (украинский)";
            //if (!$customer->nameRus) $errors[] = "Не заполнено наименование заказчика (русский)";
            //if (!$customer->egrpou) $errors[] = "Не заполнен код ЕГРПОУ заказчика";
        }

        if (count($errors) == 0) {
            $customer->save();
            // обновляем поле в модели, если все заполнено
            if (
                Yii::app()->request->getParam('model') !== null &&
                Yii::app()->request->getParam('modelId') !== null &&
                Yii::app()->request->getParam('modelField') !== null
            ) {
                $m = trim(Yii::app()->request->getParam('model'));
                $mId = trim(Yii::app()->request->getParam('modelId'));
                $mField = trim(Yii::app()->request->getParam('modelField'));
                $mRecord = $m::model()->findByPk($mId);
                if ($mRecord) {
                    $mRecord->$mField = $customer->id;
                    $mRecord->save();
                }
            }

            $response['success'] = true;
            // сохраняем действия
            if (count($info) > 0) {
                foreach ($info as $inf) {
                    CustomerActions::AddAction($customer->id, 1, $inf);
                }
            }
        } else {
            $response['error'] = "Ошибки: <br>" . implode("<br>", $errors);
        }
        echo json_encode($response);
    }

    //ajax
    public function actionSave()
    {
        $idReturn = null;
        if (isset($_POST)) {
            $customer = null;
            //--------------EDIT---------------
            if (Yii::app()->request->getParam('id') != null) {
                try {
                    $customer = Customer::model()->findByPk($_POST['id']);
                    $info = array();

                    $idReturn = Yii::app()->request->getParam('id');
                    $status = true;
                    $errors = null;
                    $requestEdit = false;
                    $requestId = 0;

                    //----------Русское краткое наименование (обязательное, основное)--------
                    if (Yii::app()->request->getParam('name') !== null) {
                        $name = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('name')));
                        if ($customer->name != $name) {
//                            $checkName = Customer::model()->findByAttributes(array('name' => $name));
                            $checkName = Customer::model()->findBySQl("SELECT * FROM customer WHERE name = '$name' AND id <> $idReturn");
                            if ($checkName) {
                                $errors = "Такой заказчик уже существует!";
                                $status = false;
                            } else {
                                $info[] = CustomerActions::makeAction(1, 'Изменение русского краткого наименования', $customer->name, $name);
                                $customer->name = trim($_POST['name']);
                            }
                        }
                    } else {
                        $errors = "Утеряно наименование";
                        $status = false;
                    }

                    //-----------Английское полное наименование------------
                    if (Yii::app()->request->getParam('nameEn') !== null) {
                        $nameEn = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameEn')));
                        if ($customer->nameEn != $nameEn) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение английского наименования', $customer->nameEn, $nameEn);
                            $customer->nameEn = $nameEn;
                        }
                    } else {
                        $errors = "Утеряно английское наименование";
                        $status = false;
                    }

                    //-----------Украинское полное наименование------------
                    if (Yii::app()->request->getParam('nameUkr') !== null) {
                        $nameEn = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameUkr')));
                        if ($customer->nameUkr != $nameEn) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение украинского наименования', $customer->nameUkr, $nameEn);
                            $customer->nameUkr = $nameEn;
                        }
                    } else {
                        $errors = "Утеряно украинское наименование";
                        $status = false;
                    }

                    //----------Русское полное наименование----------
                    if (Yii::app()->request->getParam('nameRus') !== null) {
                        $nameRus = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('nameRus')));
                        if ($customer->nameRus != $nameRus) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение русского полного наименования', $customer->nameRus, $nameRus);
                            $customer->nameRus = $nameRus;
                        }
                    } else {
                        $errors = "Утеряно русское наименование";
                        $status = false;
                    }
                    //-----------------------------------------------

                    if (Yii::app()->request->getParam('region') !== null) {
                        $region = trim(Yii::app()->request->getParam('region'));
                        if ($customer->region != $region) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение региона', $customer->region, $region);
                            $customer->region = $region;
                        }
                    } else {
                        $errors = "Утерян регион";
                        $status = false;
                    }

                    if (Yii::app()->request->getParam('regionId') !== null) {
                        $regionId = trim(Yii::app()->request->getParam('regionId'));
                        if ($customer->regionId != $regionId) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение внутренний регион', $customer->regionId, $regionId);
                            $customer->regionId = $regionId;
                        }
                    }

                    if (Yii::app()->request->getParam('address') !== null) {
                        $address = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('address')));
                        if ($customer->adres != $address) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение адреса', $customer->adres, $address);
                            $customer->adres = $address;
                        }
                    } else {
                        $errors = "Утерян адресс";
                        $status = false;
                    }

                    if (Yii::app()->request->getParam('lawAdres') !== null) {
                        $lawAdres = Tools::KavichkiToSimvol(trim(Yii::app()->request->getParam('lawAdres')));
                        if ($customer->lawAdres != $lawAdres) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение юридического адреса', $customer->lawAdres, $lawAdres);
                            $customer->lawAdres = $lawAdres;
                        }
                    } else {
                        $errors = "Утерян юридический адрес";
                        $status = false;
                    }

                    $parentId = Yii::app()->request->getParam('parentId', null);
                    if ($parentId) {
                        $info[] = CustomerActions::makeAction(1, 'Изменено главное предприятие!', $customer->parentId, $parentId);
                        $customer->parentId = null;
                    } else if ($customer->isParent()) {
                        $customer->parentId = null;
                    }

                    //------ Запрет изминения главной при наличии дочерней ---------
//                    if ($customer->isParent()) {
//                        $parIdStr = 'parent';
//                        if ($parentId != $parIdStr) {
//                            if ($parentId !== null) {
//                                $errors = 'Компания является главной и имеет дочерние компании!!';
//                                $status = true; //false
//                                $requestEdit = true;// false
//                                $requestId = $idReturn;
//                                $requestCheckId = $customer->id;
//                            }
//                        }
//                    } else
                    if ($parentId !== null || $customer->parentId != $parentId) {
                        $info[] = CustomerActions::makeAction(1, 'Изменено главное предприятие!', $customer->parentId, $parentId);
                        $customer->parentId = $parentId;
                    }
                    if ($customer->parentId == 0) {
                        $customer->parentId = null;
                    }

                    if (Yii::app()->request->getParam('egrpou') !== null && trim(Yii::app()->request->getParam('egrpou')) != '') {
                        $customerParent = Customer::model()->findByPk($parentId); //null
                        $parentIdOld = Customer::model()->getCustomerParentId($customer->id); //Старый парент//null

                        $mainCustomer = Customer::model()->findByPk($parentIdOld);
                        $mainCustomer == null ? $mainCustomer = null : $mainCustomer = $mainCustomer->egrpou;

                        $egrpou = trim(Yii::app()->request->getParam('egrpou'));

                        if (($customerParent)) {
                            if ($customer->egrpou !== null) {
                                $info[] = CustomerActions::makeAction(1, 'Изменение кода ЕРГПОУ', $customer->egrpou, $customerParent->egrpou);
                                $customer->egrpou = null;
                            }
                        } else if ($customerParent === null && $mainCustomer != $egrpou) {
                            if ($customer->egrpou != $egrpou) {
                                $checkEgrpou = Customer::model()->findByAttributes(array('egrpou' => $egrpou));
                                if ($checkEgrpou) {
                                    $errors = 'Такой код ЕГРПОУ уже существует!';
                                    $status = false;
                                    $requestEdit = true;
                                    $requestId = $idReturn;
                                    $requestCheckId = $checkEgrpou->id;
                                } else {

                                    $info[] = CustomerActions::makeAction(1, 'Изменение кода ЕГРПОУ', $customer->egrpou, $egrpou);
                                    $customer->egrpou = $egrpou;
                                }
                            }
                        } else {
                            $info[] = CustomerActions::makeAction(1, 'Изменение кода ЕРГПОУ', $customer->egrpou, $parentIdOld);
                            $customer->egrpou = null;
                            $customer->parentId = $parentIdOld;
                        }
                    } else {
                        $errors = "Не указан код ЕГРПОУ";
                        $status = false;
                    }

                    if (Yii::app()->request->getParam('inn') !== null) {
                        $inn = trim(Yii::app()->request->getParam('inn'));
                        if ($customer->inn != $inn) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение кода ИНН', $customer->inn, $inn);
                            $customer->inn = $inn;
                        }
                    } else {
                        $errors = "Утерян ИНН";
                        $status = false;
                    }

                    if (Yii::app()->request->getParam('nds') !== null) {
                        $nds = trim(Yii::app()->request->getParam('nds'));
                        if ($customer->nds != $nds) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение НДС', $customer->nds, $nds);
                            $customer->nds = $nds;
                        }
                    } else {
                        $errors = "Утерян номер НДС";
                        $status = false;
                    }

                    if (Yii::app()->request->getParam('holding') !== null) {
                        if ($customer->holdingId != Yii::app()->request->getParam('holding')) {
                            $info[] = CustomerActions::makeAction(1, 'Изменение холдинга', $customer->holdingId, $_POST['holding']);
                            $customer->holdingId = Yii::app()->request->getParam('holding');
                        }
                    } else {
                        $errors = "Утерян Id холдинга";
                        $status = false;
                    }
                    if ($customer->isParent() && $customer->egrpou != $egrpou) {
                        if ($customer->isParent() && $checkEgrpou1 = Customer::model()->findByAttributes(array('egrpou' => $egrpou)) !== null) {
                            $parIdStr = 'parent';
                            if (($parentId != $parIdStr)) {
                                if ($parentId !== null) {
                                    $errors = 'Изменение ЕРГПОУ невозможно, т.к. уже существует компания с таким ЕГРПОУ и есть дочерние компании!';
                                    $status = false;// true
                                    $requestEdit = false;// true
                                    $requestId = $idReturn;
                                    $requestCheckId = $customer->id;
                                }
                            }
                        }
                    } else {

                        if (!$customer->save()) {
                            $status = false;
                            $errors = "Не удалось сохранить!";
                        } else {
                            CustomerActions::AddActions($customer->id, $info);
                        }

                        $temp = $status ? "OK" : "NO";

                        $respons = array(
                            "create" => false,
                            "requestEdit" => $requestEdit,
                            "requestId" => $requestId,
                            "requestCheckId" => isset($requestCheckId) ? $requestCheckId : false,
                            "status" => $temp,
                            "error" => $errors,
                            "id" => $idReturn
                        );
                        echo json_encode($respons);
                        Yii::app()->end();
                    }
                } catch
                (Exception $e) {
                    throw new Exception($e->getMessage());
                }

                //--------------CREATE---------------

            } else {
                $customer = new Customer();

                $status = true;
                $errors = null;
                $requestEdit = false;
                $requestId = 0;

                $contacts = trim($_POST['contacts']);
                $dContacts = json_decode($contacts);
                if (count($dContacts) <= 0) {
                    $status = false;
                    $errors = "Не указаны КОНТАКТЫ!";
                }

                $name = trim($_POST['name']);
                $nameRus = trim($_POST['nameRus']);
                $nameEn = trim($_POST['nameEn']);
                $nameUkr = trim($_POST['nameUkr']);
                $region = $_POST['region'];
                if (isset($_POST['regionId'])) {
                    $regionId = $_POST['regionId'];
                } else {
                    $regionId = Yii::app()->user->regionId;
                }
                $address = $_POST['address'];
//                $distance = $_POST['distance'];
//                $coord1 = $_POST['coord1'];
//                $coord2 = $_POST['coord2'];
                $lawAdres = $_POST['lawAdres'];
                $egrpou = trim($_POST['egrpou']);
                $inn = $_POST['inn'];
                $nds = $_POST['nds'];
                $holding = $_POST['holding'];
                $parentId = $_POST['parentId'];

                try {
                    //------------Русское название (обязательное)----------
                    if ($name != "") {
                        $ctmp = Customer::model()->findAll("name = '$name'");
                        if ($ctmp) {
                            $status = false;
                            $errors = "Такой заказчик уже существует!";
                        } else {
                            $customer->name = $name;
                        }
                    } else {
                        $status = false;
                        $errors = "Не указано имя!";
                    }
                    //-------------Английское наименование------------------
                    if ($nameEn != "") $customer->nameEn = $nameEn;
                    //-------------Русское полное наименование--------------
                    if ($nameRus != "") $customer->nameRus = $nameRus;
                    //-------------Украинское наименование------------------
                    if ($nameUkr != "") $customer->nameUkr = $nameUkr;
                    //------------------------------------------------------

                    if ($region != "") {
                        $customer->region = $region;
                    }

                    if ($regionId) {
                        $customer->regionId = $regionId;
                    }

                    if ($address != "") {
                        $customer->adres = $address;
                    }

                    if (Yii::app()->request->getParam('egrpou') !== null && trim(Yii::app()->request->getParam('egrpou')) != '') {
                        $customerParent = Customer::model()->findByPk($parentId);
                        $egrpou = trim(Yii::app()->request->getParam('egrpou'));
                        if ($customerParent) {
                            if ($customer->egrpou !== null) {
                                $info[] = CustomerActions::makeAction(1, 'Изменение кода ЕРГПОУ', $customer->egrpou, $customerParent->egrpou);
                                $customer->egrpou = null;
                            }
                        } else {
                            if ($customer->egrpou != $egrpou) {
                                $checkEgrpou = Customer::model()->findByAttributes(array('egrpou' => $egrpou));
                                if ($checkEgrpou) {
                                    $errors = 'Такой код ЕГРПОУ уже существует!';
                                    $status = false;
                                    $requestEdit = true;
                                    $requestId = $idReturn;
                                    $requestCheckId = $checkEgrpou->id;
                                } else {
                                    $info[] = CustomerActions::makeAction(1, 'Изменение кода ЕГРПОУ', $customer->egrpou, $egrpou);
                                    $customer->egrpou = $egrpou;
                                }
                            }
                        }
                    } else {
                        $errors = "Не указан код ЕГРПОУ";
                        $status = false;
                    }

                    if ($lawAdres != "") {
                        $customer->lawAdres = $lawAdres;
                    }

                    if ($inn != "") {
                        $customer->inn = $inn;
                    }

                    if ($nds != "") {
                        $customer->nds = $nds;
                    }

                    if ($holding != 0) {
                        $customer->holdingId = $holding;
                    }

                    if ($parentId !== "") {
                        $customer->parentId = null;//$parentId;
                    }

                    if ($status) {
                        $customer->save();
                        $idReturn = $customer->id;

                        CustomerActions::AddAction($customer->id, 1, 'Создан новый заказчик');

                        $addContacts = $this->saveContacts($customer);
                    }

                } catch (Exception $e) {
                    $status = false;
                }
            }
        } else {
            $status = false;
            $errors = "Не заполенены аргументы";
        }

        $temp = $status ? "OK" : "NO";

        $respons = array(
            "create" => true,
            "requestEdit" => $requestEdit,
            "requestId" => $requestId,
            "status" => $temp,
            "error" => $errors,
            "id" => $idReturn,
            // "addContacts" => $addContacts
        );

        echo json_encode($respons);
        Yii::app()->end();
    }

    public
    function saveContacts($customer)
    {
        $contacts = trim($_POST['contacts']);
        $dContacts = json_decode($contacts);

        $result = false;
        try {
            foreach ($dContacts as $contacts) {

                $cc = new CustomerContacts();
                $cc->customerId = $customer->id;

                $cc->name = $contacts->name;
                $cc->position = $contacts->position;
                $cc->birthDate = date('Y-m-d', strtotime($contacts->birthDate));

                $jsPhone = array();
                if (isset($contacts->phone)) {
                    if (strlen($contacts->phone) > 0) {
                        array_push($jsPhone, $contacts->phone);
                    }
                }
                if (!empty($jsPhone)) {
                    $contacts->phone = json_encode($jsPhone);
                }
                $cc->phone = $contacts->phone;

                $jsEmail = array();
                if (isset($contacts->email)) {
                    array_push($jsEmail, $contacts->email);
                }
                if (!empty($jsEmail)) {
                    $contacts->email = json_encode($jsEmail);
                }
                $cc->email = $contacts->email;

                $jsNotes = array();
                if (isset($contacts->notes)) {
                    if (strlen($contacts->notes) > 0) {
                        array_push($jsNotes, trim($contacts->notes));
                    }
                }
                $contacts->notes = json_encode($jsNotes);
                $cc->notes = $contacts->notes;

                $cc->save();
            }
            $result = true;
        } catch (Exception $e) {
            $result = $e->getMessage();
        }
        return $result;
    }


    public
    function actionSaveComment()
    {
        $status = true;
        $errors = "";
        $commentRes = "";
        if (isset($_POST)) {
            try {
                $comment = new CustomerComments();
                $comment->customerId = $_POST['idCustomer'];
                $comment->comment = $_POST['comment'];
                $comment->save();

                $foto = $comment->user->kadry->foto;
                if (!file_exists(Yii::getPathOfAlias('webroot') . '/static/uploads/ok/' . $foto)) $foto = 'nophoto.jpg';
                $commentRes = '<li><div class="time"><span class="right"><b>' . $comment->user->title . '<b>(' . date('d.m.Y H:m', strtotime($comment->data)) . ')</span></div>';
                $commentRes .= '<div style="clear: both;"></div><div class="avatar right"><div class="img-rounded"
                                        style="background: #f4f4f4 url(/static/uploads/ok/' . $foto . ');
                                                 background-repeat: no-repeat;
                                                 background-position: center center;
                                                 background-attachment: inherit;
                                                 background-size: cover;"></div></div>
                                        <div class="message left alert triangle-right" style="border-color: rgba(231,95,32,0.08) transparent; background-color: rgba(231,95,32,0.08);">' . $comment->comment . '</div></li>';
            } catch (Exception $e) {
                $status = false;
                $errors = $e->getMessage();
            }
        } else {
            $status = false;
            $errors = "Нет запроса";
        }

        $temp = $status ? "OK" : "NO";

        $respons = array(
            "status" => $temp,
            "error" => $errors,
            'comment' => $commentRes,
        );
        echo json_encode($respons);
    }

    public
    function actionDelContact($id)
    {
        $status = true;
        $errors = "";
        if (isset($_POST)) {
            try {
                $contact = CustomerContacts::model()->findByPk($id);
                if ($contact) {
                    $contact->isDell = 1;
                    $contact->save();
                }
            } catch (Exception $e) {
                $status = false;
                $errors = $e->getMessage();
            }
        } else {
            $status = false;
            $errors = "Нет запроса";
        }

        $temp = $status ? "OK" : "NO";

        $respons = array(
            "status" => $temp,
            "error" => $errors,
        );
        echo json_encode($respons);
    }


    public
    function actionSaveContact()
    {
        $result = array('success' => false, 'error' => '', 'conf' => '');

        $action = Yii::app()->request->getParam('action');
        $id = Yii::app()->request->getParam('customerId');// Если создание $id == id заказчика, если редактирование id == id контакта
        $fio = Yii::app()->request->getParam('fio5');
        $position = Yii::app()->request->getParam('position');
        $birthDate = Yii::app()->request->getParam('birthDate');

        try {

            $stopOperation = false;

            $jsNotes = array();
            if (isset($_POST['notes'])) {
                foreach ($_POST['notes'] as $index => $note) {
                    if (strlen($note) > 0) {
                        array_push($jsNotes, trim($note));
                    }
                }
            }
            $notes = json_encode($jsNotes);

            $jsEmail = array();
            if (isset($_POST['email'])) {
                foreach ($_POST['email'] as $index => $value) {
                    if (strlen($value) > 0) {
                        array_push($jsEmail, $value);
                    }
                }
            }
            if (!empty($jsEmail)) {
                $email = json_encode($jsEmail);
            }

            $jsPhone = array();
            if (isset($_POST['phone'])) {
                foreach ($_POST['phone'] as $index => $number) {
                    if (strlen($number) > 0) {
                        array_push($jsPhone, $number);
                    }
                }
            }
            if (!empty($jsPhone)) {
                $phone = json_encode($jsPhone);
            }
            if ($action == 'edit') {
                $contact = CustomerContacts::model()->findByPk($id);
                if (strlen($fio) > 0 && !empty(trim($fio))) {
                    $contact->name = $fio;
                } else {
                    $stopOperation = true;
                    $result['error'] = 'Заполните поле ФИО.';
                }

                if (strlen($position) >= 0) {
                    $contact->position = $position;
                }

                if (strlen($birthDate) > 0) {
                    $contact->birthDate = date('Y-m-d', strtotime($birthDate));
                }

                if (isset($email)) {
                    $contact->email = $email;
                } else {
                    $contact->email = false;
                }

                if (isset($phone)) {
                    $contact->phone = $phone;
                } else {
                    $contact->phone = false;
                }

                if (strlen($notes) >= 0) {
                    $decodeNotes = json_decode($notes, true);
                    $dbNotes = json_decode($contact->notes, true);
                    if (!$dbNotes) {
                        $dbNotes = array();
                    }
                    $mergeArray = array_merge($dbNotes, $decodeNotes);
                    $notes = json_encode($mergeArray);
                    $contact->notes = $notes;
                }

                $contact->uuser = Yii::app()->user->id;
                $contact->udate = date('Y-m-d H:i:s');

                if (!$stopOperation) {
                    if (!$contact->save()) {
                        $result['error'] = 'Ошибка сохранения.';
                    } else {
                        $result['success'] = true;
                        $result['conf'] = $this->renderPartial('components/contactForm', array('action' => 'edit', 'id' => $id), true);
                    }
                }
            } else { // Создание
                $contact = new CustomerContacts();

                if (strlen($fio) > 0 && !empty(trim($fio))) {

                    $contact->name = $fio;

                } else {

                    $stopOperation = true;

                    $result['error'] = 'Заполните поле ФИО.';

                }

                $contact->position = $position;
                if (strlen($birthDate) > 0) {
                    $contact->birthDate = date('Y-m-d', strtotime($birthDate));
                }
                $contact->customerId = $id;
                if (isset($phone)) {
                    $contact->phone = $phone;
                }
                if (isset($email)) {
                    $contact->email = $email;
                }
                $contact->notes = $notes;

                if (!$stopOperation) {

                    if (!$contact->save()) {
                        $result['error'] = 'Ошибка сохранения.';
                    } else {
                        $conId = Yii::app()->db->lastInsertID;
                        $result['success'] = true;
                        $result['conf'] = $this->renderPartial('components/contactForm', array('action' => 'edit', 'id' => $conId), true);
                    }

                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }

        echo CJSON::encode($result);
    }

    public
    function actionSaveContactForm()
    {
        $result = array('success' => false, 'error' => '', 'conf' => '');

        $action = Yii::app()->request->getParam('action');
        $id = 1;
        $fio = Yii::app()->request->getParam('fio5');
        $position = Yii::app()->request->getParam('position');
        $birthDate = Yii::app()->request->getParam('birthDate');

        try {
            $jsNotes = array();
            if (isset($_POST['notes'])) {
                foreach ($_POST['notes'] as $index => $note) {
                    if (strlen($note) > 0) {
                        array_push($jsNotes, trim($note));
                    }
                }
            }
            $notes = json_encode($jsNotes);

            $jsEmail = array();
            if (isset($_POST['email'])) {
                foreach ($_POST['email'] as $index => $value) {
                    if (strlen($value) > 0) {
                        array_push($jsEmail, $value);
                    }
                }
            }
            if (!empty($jsEmail)) {
                $email = json_encode($jsEmail);
            }

            $jsPhone = array();
            if (isset($_POST['phone'])) {
                foreach ($_POST['phone'] as $index => $number) {
                    if (strlen($number) > 0) {
                        array_push($jsPhone, $number);
                    }
                }
            }
            if (!empty($jsPhone)) {
                $phone = json_encode($jsPhone);
            }
            if ($action == 'create') {
                $contact = CustomerContacts::model()->findByPk($id == 1);
                if (strlen($fio) > 0 && !empty(trim($fio))) {
                    $contact->name = $fio;
                } else {
                    $stopOperation = true;
                    $result['error'] = 'Заполните поле ФИО.';
                }

                if (strlen($position) >= 0) {
                    $contact->position = $position;
                }

                if (strlen($birthDate) > 0) {
                    $contact->birthDate = date('Y-m-d', strtotime($birthDate));
                }

                if (isset($email)) {
                    $contact->email = $email;
                } else {
                    $contact->email = false;
                }

                if (isset($phone)) {
                    $contact->phone = $phone;
                } else {
                    $contact->phone = false;
                }

                if (strlen($notes) >= 0) {
                    $decodeNotes = json_decode($notes, true);
                    $dbNotes = json_decode($contact->notes, true);
                    if (!$dbNotes) {
                        $dbNotes = array();
                    }
                    $mergeArray = array_merge($dbNotes, $decodeNotes);
                    $notes = json_encode($mergeArray);
                }
            }
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }
        echo CJSON::encode($result);
    }

    public
    function actionDeleteContact()
    {
        $result = array('success' => false, 'error' => '');
        try {
            $stopOperation = false;
            $id = $_POST['id'];
            $contact = CustomerContacts::model()->findByPk($id);
            if ($contact->isDell == 0) {
                $contact->isDell = 1;
                $contact->ddate = date('Y-m-d H:i:s');
                $contact->duser = Yii::app()->user->id;
            } else {
                $stopOperation = true;
            }
            if ($stopOperation) {
                $result['error'] = 'Отказано, контакт не активен...';
            } else {
                if (!$contact->save()) {
                    $result['error'] = 'Ошибка удаления.';
                } else {
                    $result['success'] = true;
                }
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionDeleteNote()
    {
        $result = array('success' => false, 'error' => '');
        try {
            $id = Yii::app()->request->getParam('id');
            $text = trim(Yii::app()->request->getParam('text'));

            if (isset($_POST['type']) and $_POST['type'] == 'proposal') {

                $proposal = CustomerProposals::model()->findByPk($id);
                if ($proposal) {
                    $notesArray = json_decode($proposal->notes, true);
                    $key = array_search($text, $notesArray);
                    unset($notesArray[$key]);
                    $js = json_encode($notesArray);
                    $proposal->notes = $js;
                    if (!$proposal->update()) {
                        $result['error'] = 'Ошибка удаления.';
                    } else {
                        $result['success'] = true;
                    }
                }
            } else {
                $contact = CustomerContacts::model()->findByPk($id);
                if ($contact) {
                    $notesArray = json_decode($contact->notes, true);
                    $key = array_search($text, $notesArray);
                    unset($notesArray[$key]);
                    $js = json_encode($notesArray);
                    $contact->notes = $js;
                    if (!$contact->update()) {
                        $result['error'] = 'Ошибка удаления.';
                    } else {
                        $result['success'] = true;
                    }
                }
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionAppealKrTable($appealId, $regionId)
    {
        $responce = array('success' => false, 'result' => '', 'error' => 'Не найдены КР в обращении по ID=' . $appealId);
        $appeal = KrAppeals::model()->findByPk($appealId);
        if ($appeal) {
//            switch (Yii::app()->session['showstyle']) {
//                case 'work' : $krs = $appeal->kr_work; break; // на выполнении
//                case 'delete' : $krs = $appeal->kr_delete; break; // удаленные
//                case 'archive' : $krs = $appeal->kr_archive; break; // в архиве
//                default : $krs = $appeal->kr_active; // в работе
            $krs = Kr::model()->findAllByAttributes(array('appealId' => $appealId, 'parent_id' => 0));

            if ($krs) {
                $data = array('region_id' => $regionId, 'krs' => $krs, 'appealId' => $appealId,);
                $responce['result'] = $this->renderPartial('appealkrs', $data, true);
                $responce['error'] = '';
                $responce['success'] = true;
            } else {
                $responce['error'] = 'Не найдены КР в обращении № ' . $appeal->number;
            }
        } else {
            $responce['error'] = 'Ошибка поиска обращения № ' . $appeal->number;
        }
        echo json_encode($responce);
    }

    public
    function actionUirFancy($id)
    {
        $customer = Customer::model()->findByPk($id);
        $data = array(
            'customer' => $customer
        );
        $this->renderPartial('uirFancy', $data);
    }

    public
    function actionProposal($id, $action, $regionId)
    {
        switch ($action) {
            case 'create':
                $criteria = new CDbCriteria();
                $criteria->addCondition('customer_id = ' . $id);
                $criteria->addCondition('parent_id = 0', 'AND');
                if (Tools::checkAccess('CUSTOMER', 'region_access', 'edit') and Yii::app()->user->role != 'admin') {
                    $criteria->addCondition('regionId = ' . $regionId, 'AND');
                }
                $findKrs = Kr::model()->findAll($criteria);
                break;
            case 'edit' or 'view':
                $propKrs = CustomerProposalKrs::model()->findAll('proposalId = ' . $id);
                if ($propKrs) {
                    $findKrs = array();
                    foreach ($propKrs as $propKr) {
                        $criteria = new CDbCriteria();
                        $criteria->addCondition('id = ' . $propKr->krId);
//                        if(Tools::checkAccess('CUSTOMER','region_access','edit') AND Yii::app()->user->role != 'admin'){
//                            $criteria->addCondition('regionId = ' . $regionId,'AND');
//                        }
                        $findKr = Kr::model()->find($criteria);
                        if ($findKr) {
                            array_push($findKrs, $findKr);
                        }
                    }
                } else {
                    $findKrs = array();
                }

                break;
            default:
                $findKrs = array();
                break;
        }
        $this->renderPartial('proposalFancy', array('id' => $id, 'action' => $action, 'krs' => $findKrs, 'regionId' => $regionId));
    }

    public
    function actionSaveProposal()
    {
        try {
            $action = Yii::app()->request->getParam('action');
            $result = array('success' => false, 'error' => '', 'returnId' => '', 'conf' => '');

            switch ($action) {
                case 'create':
//                    var_dump($_POST);
                    $stopOperation = false;
//                    $customerId = $_POST['id'];
                    $customerId = Yii::app()->request->getParam('id');
                    $regionId = Yii::app()->request->getParam('regionId');
                    if (isset($_POST['krId'])) {
                        $krsId = $_POST['krId'];
                    } else {
                        $result['error'] = 'Вы не указали КР.';
                        echo json_encode($result);
                        Yii::app()->end();
                    }
                    $notes = $_POST['notes'];

                    $proposal = new CustomerProposals();
                    $proposal->customerId = $customerId;
                    $proposal->regionId = $regionId;
                    if (!empty($notes[0])) {
                        $proposal->notes = json_encode($notes);
                    }
                    if (!$proposal->save()) {
                        $result['error'] = 'Ошибка сохранения.';
                    } else {
                        foreach ($krsId as $krId) {
                            $proposalKr = new CustomerProposalKrs();
                            $proposalKr->proposalId = $proposal->id;
                            $proposalKr->krId = $krId;
                            if (!$proposalKr->save()) {
                                $result['error'] = 'Ошибка сохранения КРов.';
                                $stopOperation = true;
                                break;
                            }
                        }

                        if ($_FILES) {
                            foreach ($_FILES as $file) {
                                if ($file['error'][0] <= 0) {
                                    $extension = pathinfo($file['name'][0], PATHINFO_EXTENSION);
                                    $newName = str_replace('.', '', microtime(true)) . '.' . $extension;
                                    if (move_uploaded_file($file['tmp_name'][0], Yii::getPathOfAlias(Yii::app()->params['uploadDirCustomer']) . '/' . $newName)) {
                                        $doc = new CustomerProposalDoc;
                                        $doc->proposalId = $proposal->id;
                                        $doc->type = $file['type'][0];
                                        $doc->title = $file['name'][0];
                                        $doc->image = $newName;
                                        if (!$doc->save()) {
                                            $result['error'] = 'Ошибка сохранения документа.';
                                            $stopOperation = true;
                                            break;
                                        }
                                    };
                                }
                            }
                        }

                        if (!$stopOperation) {
                            $result['success'] = true;
                            $result['returnId'] = $proposal->id;

                            $propKrs = CustomerProposalKrs::model()->findAll('proposalId = ' . $proposal->id);
                            if ($propKrs) {
                                $findKrs = array();
                                foreach ($propKrs as $propKr) {
                                    $findKr = Kr::model()->findByPk($propKr->krId);
                                    array_push($findKrs, $findKr);
                                }
                            } else {
                                $findKrs = array();
                            }
                            $result['conf'] = $this->renderPartial('components/proposalForm', array('id' => $proposal->id, 'action' => 'edit', 'krs' => $findKrs), true);
                        }
                    }

                    break;

                //-----------------------------------------------------------------------------------

                case 'edit':
                    $stopOperation = false;
                    $proposalId = $_POST['id'];
                    $notes = $_POST['notes'];

                    $proposal = CustomerProposals::model()->findByPk($proposalId);
                    if (!empty($notes[0])) {
                        $jsNotes = json_decode($proposal->notes, true);
                        if ($jsNotes) {
                            $saveNotes = array_merge($jsNotes, $notes);
//                            VarDumper::dump($jsNotes);
//                            VarDumper::dump($notes);
//                            VarDumper::dump($saveNotes);
//                            die();
                            $proposal->notes = json_encode($saveNotes);
                        } else {
                            $proposal->notes = json_encode($notes);
                        }
                        $proposal->save();
                    }


                    //Save new KR
                    if (isset($_POST['availableKrId'])) {
                        foreach ($_POST['availableKrId'] as $aKrId) {
                            $proposalKr = new CustomerProposalKrs();
                            $proposalKr->proposalId = $proposalId;
                            $proposalKr->krId = $aKrId;
                            if (!$proposalKr->save()) {
                                $result['error'] = 'Ошибка сохранения.';
                                $stopOperation = true;
                                break;
                            }
                        }
                    }

                    //Delete KR
                    if (isset($_POST['selectedKrId'])) {
                        foreach ($_POST['selectedKrId'] as $sKrId) {
                            $proposalKr = CustomerProposalKrs::model()->findByAttributes(array('krId' => $sKrId, 'proposalId' => $proposalId));
                            if ($proposalKr) {
                                if (!$proposalKr->delete()) {
                                    $result['error'] = 'Ошибка сохранения.';
                                    $stopOperation = true;
                                    break;
                                }
                            }
                        }
                    }

                    //Save document
                    if ($_FILES) {
                        foreach ($_FILES as $file) {
                            if ($file['error'][0] <= 0) {
                                $extension = pathinfo($file['name'][0], PATHINFO_EXTENSION);
                                $newName = str_replace('.', '', microtime(true)) . '.' . $extension;
                                if (move_uploaded_file($file['tmp_name'][0], Yii::getPathOfAlias(Yii::app()->params['uploadDirCustomer']) . '/' . $newName)) {
                                    $doc = new CustomerProposalDoc;
                                    $doc->proposalId = $proposal->id;
                                    $doc->type = $file['type'][0];
                                    $doc->title = $file['name'][0];
                                    $doc->image = $newName;
                                    if (!$doc->save()) {
                                        $result['error'] = 'Ошибка сохранения документа.';
                                        $stopOperation = true;
                                        break;
                                    }
                                };
                            }
                        }
                    }

                    if (!$stopOperation) {
                        $result['success'] = true;
                        $result['returnId'] = $proposalId;
                        $propKrs = CustomerProposalKrs::model()->findAll('proposalId = ' . $proposalId);
                        if ($propKrs) {
                            $findKrs = array();
                            foreach ($propKrs as $propKr) {
                                $findKr = Kr::model()->findByPk($propKr->krId);
                                array_push($findKrs, $findKr);
                            }
                        } else {
                            $findKrs = array();
                        }
                        $result['conf'] = $this->renderPartial('components/proposalForm', array('id' => $proposalId, 'action' => $action, 'krs' => $findKrs), true);
                    }
                    break;
                default:
                    $result['error'] = 'Неизвестное действие.';
                    break;
            }
//            VarDumper::dump($result['conf']);
//            Yii::app()->end();
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionDeleteProposal()
    {
        try {
            $result = array('success' => false, 'error' => '');

            $id = Yii::app()->request->getParam('id');
            if ($id) {
                $proposal = CustomerProposals::model()->findByPk($id);
                $proposal->isDelete = 1;
                $proposal->ddate = date('Y-m-d H:i:s');
                $proposal->duser = Yii::app()->user->id;
                if (!$proposal->update()) {
                    $result['error'] = 'Ошибка удаления.';
                } else {
                    $result['success'] = true;
                }
            } else {
                $result['error'] = 'Такое предложение не найденно.';
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = 'Ошибка: ' . $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionDeleteDoc()
    {
        $result = array('success' => false, 'error' => '');
        try {
            $id = Yii::app()->request->getParam('id');
            $promt = Yii::app()->request->getParam('promt');

            $doc = CustomerProposalDoc::model()->findByPk($id);
            if ($doc) {
                $doc->isDelete = 1;
                $doc->reason = $promt;
                if (!$doc->save()) {
                    $result['error'] = 'Не удалось удалить документ.';
                } else {
                    $result['success'] = true;
                }
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = 'Ошибка удаления.' . $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionTest()
    {

    }

    public
    function actionAutocompleteCustomerList()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $q = Yii::app()->request->getParam("q");
        if ($q) {
            $criteria = new CDbCriteria();
            $criteria->addSearchCondition('name', $q);
            $criteria->addSearchCondition('egrpou', $q, true, 'OR');
            $criteria->order = "name";
            $Customers = Customer::model()->findAll($criteria);
            if ($Customers) {
                foreach ($Customers as $customer) {
                    if ($customer->in_archive != 1) {
                        $criteria = new CDbCriteria();
                        $criteria->addCondition('customerId = ' . $customer->id);
                        $criteria->addCondition('isDell = 0');
                        $criteria->order = "name";
                        $customer_contacts = CustomerContacts::model()->findAll($criteria);
                        $customer_contacts_options = '';
                        foreach ($customer_contacts as $cc) {
                            if ($cc->name != '') {
                                $optionsPhone = $cc->buildContactOptionsPhone();
                                $optionsMail = $cc->buildContactOptionsMail();
                                $customer_contacts_options .= "<option value = '$cc->id' class='d" . $cc->id . "'  data-phones='$optionsPhone' data-emails='$optionsMail' >" . htmlspecialchars($cc->name) . "</option>";
                            }
                        }

                        echo $customer->id . "|" . $customer->name . "|" . ($customer->region ? $customer->region : '') . "|" . ($customer->distance ? $customer->distance : '') . "|" . $customer_contacts_options . "|" . ($customer->egrpou ? $customer->egrpou : '') . "\n";
                    }
                }
            }
        }
    }

    public
    function actionAutocompleteCustomerContactList()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $q = Yii::app()->request->getParam("q");
        $customerId = Yii::app()->request->getParam("customer_id");
        if ($q) {
            $criteria = new CDbCriteria();
            $criteria->addSearchCondition('name', $q);
            $criteria->addCondition('customerId = ' . $customerId);
            $criteria->addCondition('isDell = 0');
            $criteria->order = "name";
            $Customers = CustomerContacts::model()->findAll($criteria);
            if ($Customers) {
                foreach ($Customers as $o) {
                    echo $o->id . "|" . $o->name . "|" . $o->phone . "\n";
                }
            }
        }
    }

    public
    function actionAddCustomerContact()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }
        $action = (Yii::app()->request->getParam("action") != null && Yii::app()->request->getParam("action") != '') ? Yii::app()->request->getParam("action") : false;

        $customerId = (Yii::app()->request->getParam("customer_id") != null && Yii::app()->request->getParam("customer_id") != '') ? Yii::app()->request->getParam("customer_id") : false;
        $fio = (Yii::app()->request->getParam("fio") != null && Yii::app()->request->getParam("fio") != '') ? Yii::app()->request->getParam("fio") : false;
        $phone = (Yii::app()->request->getParam("phone") != null && Yii::app()->request->getParam("phone") != '') ? Yii::app()->request->getParam("phone") : false;
        $email = (Yii::app()->request->getParam("email") != null && Yii::app()->request->getParam("email") != '') ? Yii::app()->request->getParam("email") : '';
        $new_customer_contact = new stdClass();
        $new_customer_contact->id = 0;

        $customer_contact = new CustomerContacts();
        $customer_contact->customerId = $customerId;
        if ($phone) {
            $customer_contact->phone = json_encode($phone);
        }
        $customer_contact->name = $fio;
        $customer_contact->zeoUserId = Yii::app()->request->getParam("zeoUserId");
        if ($email) {
            $customer_contact->email = json_encode($email);
        }
        if ($action == 'create') {
            $new_customer_contact = $customer_contact->save_new();
        } else {
            $new_customer_contact = $customer_contact->update_contact();
        }

        if (is_object($new_customer_contact)) {//Новый контакт из КРа
            $criteria = new CDbCriteria();
            $criteria->addCondition('customerId = ' . $customerId);
            $criteria->addCondition('isDell = 0');
            $criteria->order = "name";
            $Customer_contacts = CustomerContacts::model()->findAll($criteria);
            $options = '';
            if ($Customer_contacts) {
                foreach ($Customer_contacts as $cc) {//Формирование атрибутов data-... для ttl и value ля ссылки на редактирование
                    if ($cc->name != '') {
                        $ttlStr = "<div>
                                    <h4>Действующие номера:</h4>
                                    <hr>
                                    <table>";
                        if ($cc->phone) {
                            $ttlPhones = json_decode($cc->phone, true);
                            foreach ($ttlPhones as $tp) {
                                $ttlStr .= "<tr style=\"height: 20px\"><td><b>" . $tp . "</b></td></tr>";
                            }
                        } else {
                            $ttlStr .= "<tr><td><b>Номеров не найдено</b></td></tr>";
                        }
                        $ttlStr .= "</table></div>";

                        $ttlStrEmail = "<div>
                                        <h4>Электронные адреса:</h4>
                                        <hr>
                                        <table>";
                        if ($cc->email) {
                            $ttlEmail = json_decode($cc->email, true);
                            foreach ($ttlEmail as $tpe) {
                                $ttlStrEmail .= "<tr style=\"height: 20px\"><td><b>" . $tpe . "</b></td></tr>";
                            }
                        } else {
                            $ttlStrEmail .= "<tr><td><b>Нет данных</b></td></tr>";
                        }
                        $ttlStrEmail .= "</table></div>";

                        //$ttlStrContent = $ttlStr.'<br>'.$ttlStrEmail;

                        $options .= "<option class='d$cc->id' data-phones='$ttlStr' data-emails='$ttlStrEmail' value = '$cc->id' " . (($cc->id == $new_customer_contact->id) ? "selected='selected'" : "") . " >$cc->name</option>";
                    }
                }
            }
            echo $options;
        } else {//false из save_new, array из update_contact
            echo json_encode($new_customer_contact);
        }
    }

    public
    function actionKrContactFancy($id)
    {
        $contact = CustomerContacts::model()->findByPk($id);
        $data = array(
            'contact' => $contact,
//            'type' => $type
        );
        $this->renderPartial('/kr/component/krAddContactFancy', $data);
    }

    public
    function actionSaveRequisite()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '', 'stopOperation' => false);
        try {
            $id = (Yii::app()->request->getParam("id") != null && Yii::app()->request->getParam("id") != '') ? Yii::app()->request->getParam("id") : false;
            $reqText = trim((Yii::app()->request->getParam("text") != null && Yii::app()->request->getParam("text") != '') ? Yii::app()->request->getParam("text") : false);
            $action = (Yii::app()->request->getParam("action") != null && Yii::app()->request->getParam("action") != '') ? Yii::app()->request->getParam("action") : false;

            if ($id == false or $reqText == false or $action == false) {
                $result['stopOperation'] = true;
                $result['error'] = 'Утеряны входящие данные';
            }

            if (!$result['stopOperation']) {
                switch ($action) {
                    case 'create':
                        $check = CustomerRequisites::checkLastActiveReq($id);
                        $req = new CustomerRequisites();
                        $req->customerId = $id;
                        $req->text = $reqText;
                        if ($check) {
                            $req->last = 1;
                        }
                        if (!$req->save()) {
                            $result['error'] = 'Ошибка сохранения';
                        } else {
                            $result['success'] = true;
//            $result['conf'] = $this->renderPartial('requisites', array('id' => $id, 'action' => $action));;
                        }
                        break;
                    case 'edit':
                        $req = CustomerRequisites::model()->findByPk($id);
                        $req->text = $reqText;
                        if (!$req->update()) {
                            $result['error'] = 'Ошибка сохранения';
                        } else {
                            $result['success'] = true;
                            $result['conf'] = $reqText;
                        }
                        break;
                }
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionDeleteRequisite()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '', 'stopOperation' => false);
        try {
            $id = (Yii::app()->request->getParam("id") != null && Yii::app()->request->getParam("id") != '') ? Yii::app()->request->getParam("id") : false;

            $req = CustomerRequisites::model()->findByPk($id);

            $req->isDell = 1;
            $req->ddate = date('Y-m-d H:i:s');;
            $req->duser = Yii::app()->user->id;
            if (!$req->update()) {
                $result['error'] = 'Не удалось удалить';
            } else {
                $result['success'] = true;
            }

            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionSetActiveRequisite()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '', 'stopOperation' => false);
        try {
            $id = (Yii::app()->request->getParam("id") != null && Yii::app()->request->getParam("id") != '') ? Yii::app()->request->getParam("id") : false;

            $req = CustomerRequisites::model()->findByPk($id);
            $check = CustomerRequisites::checkLastActiveReq($req->customerId);

            $req->last = 1;
            if (!$req->update()) {
                $result['error'] = 'Не удалось завершить операцию';
            } else {
                $result['success'] = true;
//            $result['conf'] = $this->renderPartial('requisites', array('id' => $id, 'action' => $action));;
            }

            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionSearchAppealKr($regionId)
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $q = Yii::app()->request->getParam("q");
        if ($q) {
            $criteria = new CDbCriteria();
            $criteria->addSearchCondition('number', $q);
            if (Tools::checkAccess('CUSTOMER', 'region_access', 'edit') and Yii::app()->user->role != 'admin') {
                $criteria->addSearchCondition('regionId', $regionId);
            }
            $appeals = KrAppeals::model()->findAll($criteria);
            if ($appeals) {
                foreach ($appeals as $appeal) {
                    echo $appeal->number . "|" . $appeal->id . "\n";
                }
            }
        }
    }

    public
    function actionReloadChooseKrs()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }
        $result = array('success' => false, 'error' => '', 'conf' => '');
        try {
            $appealId = (Yii::app()->request->getParam("appealId") != null && Yii::app()->request->getParam("appealId") != '') ? Yii::app()->request->getParam("appealId") : false;
            $customerId = (Yii::app()->request->getParam("customerId") != null && Yii::app()->request->getParam("customerId") != '') ? Yii::app()->request->getParam("customerId") : false;
            $proposalId = (Yii::app()->request->getParam("proposalId") != null && Yii::app()->request->getParam("proposalId") != '') ? Yii::app()->request->getParam("proposalId") : false;
            $regionId = (Yii::app()->request->getParam("regionId") != null && Yii::app()->request->getParam("regionId") != '') ? Yii::app()->request->getParam("regionId") : false;

            if (!$proposalId) {//СОЗДАНИЕ КП
                $criteriaKr = new CDbCriteria();
                $criteriaKr->addCondition('customer_id = ' . $customerId);
                $criteriaKr->addCondition('parent_id = 0', 'AND');
                $criteriaKr->addCondition('appealId = ' . $appealId, 'AND');
                $findKrs = Kr::model()->findAll($criteriaKr);

                $result['success'] = true;
                $result['conf'] = $this->renderPartial('components/availableKr', array('krs' => $findKrs), true);
            } else {//РЕДАКТИРОВАНИЕ КП
                $criteriaProp = new CDbCriteria();
                $criteriaProp->addCondition('id = ' . $proposalId);
                if (Tools::checkAccess('CUSTOMER', 'region_access', 'edit') and Yii::app()->user->role != 'admin') {
                    $criteriaProp->addCondition('regionId = ' . $regionId, 'AND');
                }
                $proposal = CustomerProposals::model()->find($criteriaProp);

                $criteriaKr = new CDbCriteria();
                $criteriaKr->addCondition('customer_id = ' . $customerId);
                $criteriaKr->addCondition('parent_id = 0', 'AND');
                $criteriaKr->addCondition('appealId = ' . $appealId, 'AND');
                $findKrs = Kr::model()->findAll($criteriaKr);

                if ($findKrs) {
                    //Доступные заказчику КРы, которых нет в КП
                    $availableKrs = array();
                    foreach ($findKrs as $arrayKrs) {
                        array_push($availableKrs, $arrayKrs->id);
                    }

                    //Выбраные КРы внутри КП
                    $selectedKrs = array();
                    foreach ($proposal->krs as $kr) {
                        if ($kr->krId) {
                            array_push($selectedKrs, $kr->krId);
                        }
                    }

                    //Доступные для добавления КРы в КП
                    if ($availableKrs) {
                        $missingKrs = array_diff($availableKrs, $selectedKrs);
                        $editableKrs = Kr::model()->findAllByAttributes(array('id' => $missingKrs));
                    } else {
                        $missingKrs = array();
                        $editableKrs = array();
                    }
                    $result['success'] = true;
                    $result['conf'] = $this->renderPartial('components/editableKr', array('editableKrs' => $editableKrs), true);
                } else {
                    $editableKrs = array();
                    $result['error'] = 'КРы не найдены';
                }
            }
            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionMergeCustomer()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '');
        try {
            $masterId = (Yii::app()->request->getParam("mainId") != null && Yii::app()->request->getParam("mainId") != '') ? Yii::app()->request->getParam("mainId") : false;
            $wasteId = (Yii::app()->request->getParam("checkId") != null && Yii::app()->request->getParam("checkId") != '') ? Yii::app()->request->getParam("checkId") : false;
//            VarDumper::dump($masterId);
//            VarDumper::dump($wasteId);

            $mergeCustomer = Customer::mergeCustomer($masterId, $wasteId);

            if ($mergeCustomer['success']) {
                //
                $result['success'] = true;
            } else {
                $result['error'] = $mergeCustomer['error'];
            }

            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }


    public
    function actionHoldings()
    {
        if (!Tools::checkAccess('CUSTOMER', 'customer_holdings', 'view')) {
            throw new CHttpException(403);
        }

        $holdings = CustomerHoldings::model()->findAll('isDell = 0');
        $data = array(
            'holdings' => $holdings
        );
        $this->render('holdings', $data);
    }

    public
    function actionHoldingAction($action, $id)
    {
        $data = array();
        switch ($action) {
            case 'create':
                $data = array(
                    'action' => $action,
                    'holding' => array()
                );
                break;
            case 'view';
            case 'edit':
                $holding = CustomerHoldings::model()->findByPk($id);
                $data = array(
                    'action' => $action,
                    'holding' => $holding
                );
                break;
            default:
                throw new CHttpException(404);
                break;
        }
        $this->renderPartial('holdingFancy', $data);
    }

    public
    function actionSaveHolding()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '');
        try {
            $action = (Yii::app()->request->getParam("action") != null && Yii::app()->request->getParam("action") != '') ? Yii::app()->request->getParam("action") : false;

            switch ($action) {
                case 'create':
                    $name = Yii::app()->request->getParam("name");

                    $holding = new CustomerHoldings();
                    $holding->name = $name;
                    if (!$holding->save()) {
                        $result['error'] = 'Ошибка сохранения';
                    } else {
                        $result['success'] = true;
                        $result['conf'] = $this->renderPartial('components/holdingForm', array('action' => 'edit', 'holding' => $holding), true);
                    }
                    break;
                case 'edit':
                    $id = (Yii::app()->request->getParam("holdingId") != null && Yii::app()->request->getParam("holdingId") != '') ? Yii::app()->request->getParam("holdingId") : false;
                    $name = Yii::app()->request->getParam("name");

                    $holding = CustomerHoldings::model()->findByPk($id);
                    $holding->name = $name;
                    if (!$holding->save()) {
                        $result['error'] = 'Ошибка сохранения';
                    } else {
                        $result['success'] = true;
                        $result['conf'] = $this->renderPartial('components/holdingForm', array('action' => 'edit', 'holding' => $holding), true);
                    }

                    break;
                default:
                    throw new CHttpException(404);
                    break;
            }

            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionDeleteHolding()
    {
        if (!Yii::app()->request->getIsAjaxRequest()) {
            throw new CHttpException(404);
        }

        $result = array('success' => false, 'error' => '', 'conf' => '', 'stopOperation' => false);
        try {
            $id = (Yii::app()->request->getParam("id") != null && Yii::app()->request->getParam("id") != '') ? Yii::app()->request->getParam("id") : false;

            $holding = CustomerHoldings::model()->findByPk($id);

            $holding->isDell = 1;
            foreach ($holding->customers as $customer) {
                $customer->holdingId = 0;
                $customer->update();
            }
            $holding->ddate = date('Y-m-d H:i:s');;
            $holding->duser = Yii::app()->user->id;
            if (!$holding->update()) {
                $result['error'] = 'Не удалось удалить';
            } else {
                $result['success'] = true;
            }

            echo json_encode($result);
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            echo json_encode($result);
        }
    }

    public
    function actionSetRegion($id, $val)
    {
        $responce = array('success' => false, 'error' => 'Не предвиденная ошибка. Обратитесь к администратору!');
        $customer = Customer::model()->findByPk($id);
        if ($customer) {
            if ($customer->regionId != $val) {
                CustomerActions::model()->AddAction($id, 2, "Изменено значение региона [$customer->regionId] на [$val]");
                $customer->regionId = $val;
                $customer->save();
                $responce['success'] = true;
            } else {
                $responce['error'] = 'Полученое значение соответствует текущему. Обновление отменено. Обновите страницу!';
            }
        } else {
            $responce['error'] = 'Не найден заказчик по ID ' . $id;
        }
        echo json_encode($responce);
    }

    public
    function actionBirthDates()//$region = false
    {
//        if($region){
//            switch($region){
//                case 'all':
//                    $customers = Customer::model()->findAll();
//                break;
//                default:
//                    $customers = Customer::model()->findAll('regionId = ' . $region);
//                break;
//            }
        $contacts = CustomerContacts::getBirthDays();
        if ($contacts['quantity']) {
            $data = $contacts['data'];
        } else {
            $data = false;
        }
        $this->renderPartial('birthDatesFancy', array('contacts' => $data));
    }

    public
    function actionExportCustomers()
    {
        $format = (Yii::app()->request->getParam("format") != null && Yii::app()->request->getParam("format") != '') ? Yii::app()->request->getParam("format") : null;

        $regionId = (Yii::app()->request->getParam("region") != null && Yii::app()->request->getParam("region") != '') ? Yii::app()->request->getParam("region") : null;

        $regionName = 'все регионы';

        $criteria = new CDbCriteria();

        if ($regionId != 'all') {

            $criteria->addCondition('regionId = ' . $regionId);

            $region = UserRegion::model()->findByPk($regionId);

            if ($region) $regionName = $region->name;

        }

        $criteria->order = 'name ASC';

        $customers = new CActiveDataProvider('Customer', ['criteria' => $criteria]);

        $customers = new CDataProviderIterator($customers);

        switch ($format) {
            case 'xls':
                $this->renderPartial('print/customers', ['customers' => $customers, 'regionId' => $regionId, 'regionName' => $regionName]);
                break;
        }
    }

// ----------------- Поиск компаний  только "Главные" ---------------------
    public
    function actionAutocompleteCustomer($сustId)
    {
        if (!Yii::app()->request->getIsAjaxRequest())
            throw new CHttpException(404);

        $q = Yii::app()->request->getParam("q");
        if ($q) {
            $criteria = new CDbCriteria();

            $criteria->addCondition("name LIKE '%" . $q . "%'");
            $criteria->addCondition("id <> $сustId");
            $criteria->addCondition("parentId IS NULL");

            $customers = Customer::model()->findAll($criteria);
            if ($customers) {
                foreach ($customers as $customer) {
                    echo $customer->id . '|' . $customer->name . "\n";
                }
            }
        }
    }

// ----------- Главное/Дочерние предприятие ------------------
    public
    function actionShowModalChildCustomer($id)
    {
        $users = Yii::app()->user;
        $userRegions = UserRegionAccess::getUserRegionList($users);

        $id == 0 ? $customer = new Customer() : $customer = Customer::model()->findByPk($id);
        return $this->renderPartial('сustomerModal', ['customer' => $customer, 'userRegions' => $userRegions]);
    }

    // <------------Добавление в архив заказчика --------------->
    public
    function actionShowModalAddToArchiveCustomer($id)
    {
        $customer = Customer::model()->findByPk($id);
        if($customer->in_archive != 1)
            return $this->renderPartial('addToArchive', ['customer' => $customer]);
        else
            return $this->renderPartial('deleteToArchive', ['customer' => $customer]);
    }

    public function actionCustomerInArchive()
    {
        $this->title = 'Список заказчиков в архиве';

        $customers = Customer::model()->findAll();
        $data = array('customers' => $customers,);
        return $this->render('archive', $data);
    }

    public
    function actionSaveCustomerToArchive($id)
    {
        $status = false;
        $errors = '';

        if ($id <= 0 || $id === null) {
            $status = false;
            $errors = "What a fuck!";
        }

        try {
            if ($id > 0) {
                $customer = Customer::model()->findByPk($id);
                if ($customer) {
                    if ($customer->in_archive != 1) {
                        $customer->in_archive = 1;
                        $customer->user_add_to_archive = Yii::app()->user->id;
                        $customer->date_add_to_archive = date('Y-m-d H:i:s');

                        $customer->save();
                        $status = true;
                        CustomerActions::AddAction($customer->id, 1, 'Заказчик перенесен в архив');
                    } else {
                        $status = false;
                        $errors = "Закащик уже находится в архиве";
                    }
                } else {
                    $status = false;
                    $errors = "Не получилось перенести заказчика в архив";
                }
            } else {
                $status = false;
                $errors = "What a fuck-fuck-fuck!";
            }
        } catch (Exception $e) {
            $status = false;
        }

        $temp = $status ? "OK" : "NO";
        $respons = array(
            "status" => $temp,
            "error" => $errors,
        );
        echo json_encode($respons);
    }

    public
    function actionDeleteCustomerToArchive($id)
    {
        $status = false;
        $errors = '';

        if ($id <= 0 || $id === null) {
            $status = false;
            $errors = "What a fuck!";
        }

        try {
            if ($id > 0) {
                $customer = Customer::model()->findByPk($id);
                if ($customer) {
                    if ($customer->in_archive == 1) {
                        $customer->in_archive = null;
                        $customer->user_delete_to_archive = Yii::app()->user->id;
                        $customer->date_delete_to_archive = date('Y-m-d H:i:s');

                        $customer->save();
                        $status = true;
                        CustomerActions::AddAction($customer->id, 1, 'Заказчик перенесен из архива');
                    } else {
                        $status = false;
                        $errors = "Закащик не находится в архиве";
                    }
                } else {
                    $status = false;
                    $errors = "Не получилось перенести заказчика из архива";
                }
            } else {
                $status = false;
                $errors = "What a fuck-fuck-fuck!";
            }
        } catch (Exception $e) {
            $status = false;
        }

        $temp = $status ? "OK" : "NO";
        $respons = array(
            "status" => $temp,
            "error" => $errors,
        );
        echo json_encode($respons);
    }
}
