<?

class Customer extends CActiveRecord
{

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return '{{customer}}';
    }

    public function attributeLabels()
    {
        return array(
            'name' => 'Заказчик',
        );
    }

    public function rules()
    {
        return array(
            array('name', 'required'),
        );
    }

    public function relations()
    {
        return array(
            'createUser' => array(self::BELONGS_TO, 'User', 'userIdCreated'),
            'comments' => array(self::HAS_MANY, 'CustomerComments', 'customerId'),
            'contacts' => array(self::HAS_MANY, 'CustomerContacts', 'customerId', 'condition' => 'contacts.isDell = 0'),
            'proposals' => array(self::HAS_MANY, 'CustomerProposals', 'customerId'),
            'requisites' => array(self::HAS_MANY, 'CustomerRequisites', 'customerId', 'condition' => 'requisites.isDell = 0'),
            'holding' => array(self::BELONGS_TO, 'CustomerHoldings', 'holdingId',),
            'innerRegion' => array(self::BELONGS_TO, 'UserRegion', 'regionId',),
            'childes' => [self::HAS_MANY, 'Customer', 'parentId'],
            'parent' => [self::BELONGS_TO, 'Customer', 'parentId']
        );
    }

    protected function beforeSave()
    {
        if (!$this->id) {
            $this->timeCreated = time();
            $this->userIdCreated = Yii::app()->user->id;
            if (Yii::app()->user->regionId) {
                if($this->regionId === null || $this->regionId == 0) {
                    $this->regionId = Yii::app()->user->regionId; // устанавливаем регион заказчика, такой как у добавляющего пользователя
                }


            }
        }
        return parent::beforeSave();
    }

    public function save_new()
    {
        $old_customer = Customer::model()->find("name = '" . $this->name . "'");

        if ($old_customer) {
            $new_customer = $old_customer;
        } else {
            $this->save();
            $new_customer = $this;
        }
        return $new_customer;
    }

    public function checkCustomerInfo()
    {
        $result = true;
        if (!$this->id) {
            $result = false;
        }
        if (!$this->name) {
            $result = false;
        }
        if (!$this->nameUkr) {
            $result = false;
        }
        if (!$this->nameRus) {
            $result = false;
        }
        if (!$this->egrpou) {
            $result = false;
        }
        if (!$this->inn) {
            $result = false;
        }
        if (!$this->parentId) {
            $result = false;
        }
        return $result;
    }

    public function printCustomerName($lng = null)
    {
        $result = $this->name;
        if ($lng) {
            switch ($lng) {
                case 'UKR' :
                    if ($this->nameUkr) {
                        $result = $this->nameUkr;
                    }
                    break;
                case 'RUS' :
                    if ($this->nameRus) {
                        $result = $this->nameRus;
                    }
                    break;
                case 'EN' :
                    if ($this->nameEn) {
                        $result = $this->nameEn;
                    }
                    break;
            }
        }
        return $result;
    }

    public static function findCustomerWithParams($params, $createNew = false)
    {
        $result = false;
        $criteria = new CDbCriteria();
        if (is_array($params)) {
            if (isset($params['id']) && $params['id'] !== 0 && $params['id'] !== '') {
                $result = Customer::model()->findByPk($params['id']);
            } else {
                foreach ($params as $key => $value) {
                    if ($key != 'id') {
                        //$val = str_replace("'", '&#039;', str_replace('"', '&quot;', $value));
                        $val = Tools::KavichkiToSimvol($value);
                        $criteria->addCondition("replace(replace($key, '\"', '&quot;'), \"'\", '&#039;') = '$val'");
                    }
                }
                $result = Customer::model()->find($criteria);
            }
        }
        if (!$result && $createNew) {
            $result = new Customer();
            if (is_array($params)) {
                foreach ($params as $key => $value) {
                    if ($key != 'id') {
                        $result->$key = $value;
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Замена старого customer_id на новый во всех таблицах
     * @param $newId
     * @param $oldId
     * @return bool
     */
    public static function changeCustomer($newId, $oldId)
    {
        try {
            //$newCustomer = Customer::model()->findByPk($newId);
            //$oldCustomer = Customer::model()->findByPk($oldId);
            // изменяем заказчика в КР
            Kr::model()->updateAll(array('customer_id' => $newId), "customer_id=$oldId");
            KrAppeals::model()->updateAll(array('customer_id' => $newId), "customer_id=$oldId");
            Order::model()->updateAll(array('customerId' => $newId), "customerId=$oldId");
            DogZayavka::model()->updateAll(array('customerId' => $newId), "customerId=$oldId");
            Dogovors::model()->updateAll(array('customerId' => $newId), "customerId=$oldId");
            //CertZayavka::model()->updateAll(array('customerId'=>$newId), "customerId=$oldId");
            FundFlow::model()->updateAll(array('customer_id' => $newId), "customer_id=$oldId");
            //FundFlow::model()->updateAll(array('customer_id'=>$newId), "customer_id=$oldId");
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    public static function mergeCustomer($masterId, $wasteId)
    {
        $transaction = Yii::app()->db->beginTransaction();
        $result = array('success' => false, 'error' => '');
        $master = Customer::model()->findByPk($masterId);
        $waste = Customer::model()->findByPk($wasteId);

        try {
            //--------------------- ПЕРЕНОС КОНТАКТОВ ------------------------------
//            $transContact = CustomerContacts::getDbConnection()->beginTransaction();
            $contacts = CustomerContacts::model()->findAll('customerId = ' . $wasteId . ' AND isDell = 0');
            foreach ($contacts as $contact) {
                $contact->customerId = $masterId;
                $contact->update();
            }

            //--------------------- ПЕРЕНОС РЕКВИЗИТОВ -----------------------------
            $requisites = CustomerRequisites::model()->findAll('customerId = ' . $wasteId . ' AND isDell = 0');
            foreach ($requisites as $requisite) {
                $requisite->customerId = $masterId;
                $requisite->update();
            }

            //-------------------- ПЕРЕНОС КП -------------------------------------
            $proposals = CustomerProposals::model()->findAll('customerId = ' . $wasteId . ' AND isDelete = 0');
            foreach ($proposals as $proposal) {
                $proposal->customerId = $masterId;
                $proposal->update();
            }

            //-------------------- ПЕРЕНОС ЗАКАЗЧИКА ------------------------------
            $wasteAttr = $waste->getAttributes();
            $masterAttr = $master->getAttributes();

            $newAttr = array();
            foreach ($masterAttr as $key => $value) {
                if ($value) {
                    $newAttr[$key] = $value;
                } elseif ($wasteAttr[$key]) {
                    $newAttr[$key] = $wasteAttr[$key];
                }
            }

            $master->_attributes = $newAttr;
            if (!$master->update()) {
                $result['error'] = 'Не удалось объеденить заказчиков';
                $transaction->rollback();
            } else {
                if (!$waste->delete()) {
                    $result['error'] = 'Не удалось удалить заказчика';
                    $transaction->rollback();
                } else {
                    if (Customer::changeCustomer($master->id, $waste->id)) {
                        $masterActionText = CustomerActions::makeAction(1, 'Объеденение с заказчиком <a href="/customer/actions/id/' . $waste->id . '">' . $waste->name . '(' . $wasteId . ')</a>');
                        $wasteActionText = CustomerActions::makeAction(1, 'Объеденён с <a href="/customer/edit/id/' . $master->id . '">' . $master->name . '</a>');
                        CustomerActions::AddAction($masterId, $masterActionText['level'], $masterActionText['action']);
                        CustomerActions::AddAction($wasteId, $wasteActionText['level'], $wasteActionText['action']);
                        $result['success'] = true;
                        $transaction->commit();
                    } else {
                        $result['error'] = 'Не удалось изменить заказчика в базе данных';
                        $transaction->rollback();
                    }
                }
            }
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $transaction->rollback();
        }
        return $result;
    }

    public static function getInnerCustomer()
    {
        $customers = Customer::model()->findAll('inner_customer = 1');
        return $customers;
    }

    public function accessToEdit($type = 'edit')
    {
        $result = Tools::checkAccess('customer', 'customer', $type);
        if ($result && Yii::app()->user->regionId) {
            // тут будет проверка, когда у заказчиков пропишется регион
            $accessToAllRegions = Tools::checkAccess('customer', 'allRegions', 'edit');
            $accessToNoRegions = Tools::checkAccess('customer', 'noRegions', 'edit');

            $userRegions = [];
            $userRegionAccess = UserRegionAccess::model()->findAll("userId = " . Yii::app()->user->id);
            foreach ($userRegionAccess as $ura) {
                $userRegions[] = $ura->regionId;
            }
            if (!in_array(Yii::app()->user->regionId, $userRegions)) {
                $userRegions[] = Yii::app()->user->regionId;
            }

            if (!$accessToAllRegions) { // нет доступа ко всем регионам
                if ($this->regionId == 0) {
                    if (!$accessToNoRegions) {
                        $result = false; // если у заказчика регион не установлен и нет доступа к таким змказчикам
                    }
                } else {
                    /*if ($this->regionId != Yii::app()->user->regionId) {*/
                    if (!in_array($this->regionId, $userRegions)) {
                        $result = false;
                    }
                }
            }
        }
        return $result;
    }

    public static function getCustomerName($id)
    {
        $customer = Customer::model()->findByPk($id);
        return $customer->name;
    }

    public function getName($type = 'view')
    {
        $result = $this->printCustomerName(null);
        $id = $this->id;
        switch ($type) {
            case 'edit':
                if (Tools::checkAccess('CUSTOMER', 'customer', 'edit')) {
                    $result = "<a href = '/customer/save/id/$id' style='text-decoration: none;' target = '_blank' >$result</a >";
                }
                break;
            case 'view':
                if (Tools::checkAccess('CUSTOMER', 'customer', 'edit') || Tools::checkAccess('CUSTOMER', 'customer', 'view')) {
                    $result = "<a href = '/customer/show/id/$id' style='text-decoration: none;' target = '_blank' >$result</a >";
                }
                break;
        }
        return $result;
    }

    public static function getCustomerParentId($id)
    {
        $customer = Customer::model()->findByPk($id);
        return $customer->parentId;
    }

    public function isChild(): bool // Проверка являится Главным
    {
        return $this->parent ? true : false;
    }

    public function isParent(): bool // Проверка является Дочерним
    {
        return $this->childes ? true : false;
    }
}

